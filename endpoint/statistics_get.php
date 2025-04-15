<?php
/**
 * Endpoint para obter estatísticas de usuários e posts
 * 
 * @package MiraUP
 * @subpackage Statistics
 * @since 1.0.0
 * @version 1.0.0
 * @param WP_REST_Request $request Objeto da requisição REST
 * @return WP_REST_Response|WP_Error Resposta da API
 */

function api_statistics_get(WP_REST_Request $request) {
  // Verifica rate limiting
  if (is_rate_limit_exceeded('get_statistics')) {
      return new WP_Error(
          'rate_limit_exceeded', 
          'Limite de requisições excedido. Tente novamente mais tarde.', 
          ['status' => 429]
      );
  }

  // Obtém e valida usuário atual
  $current_user = wp_get_current_user();
  if (!$current_user->exists()) {
      return new WP_Error(
          'unauthorized',
          'Usuário não autenticado.',
          ['status' => 401]
      );
  }

  // Verifica status da conta
  $account_status = get_user_meta($current_user->ID, 'status_account', true);
  if ($account_status !== 'activated') {
      return new WP_Error(
          'account_not_activated',
          'Sua conta não está ativada ou não tem permissão para acessar este recurso.',
          ['status' => 403]
      );
  }

  // Valida e sanitiza os parâmetros
  $target_user_id = $request->get_param('user_id');
  $target_user_id = !empty($target_user_id) ? absint($target_user_id) : 0;
  $post_id = $request->get_param('post_id');
  $post_id = !empty($post_id) ? absint($post_id) : 0;

  // Se post_id foi fornecido, busca estatísticas do post
  if ($post_id > 0) {
    return get_post_statistics($post_id, $current_user->roles);
  }

  // Determina qual usuário será consultado
  $user_id = $current_user->ID;
  $is_admin = current_user_can('administrator');
  
  if ($user_id != $target_user_id && !$is_admin) {
    return new WP_Error('permission_denied', 'Você não tem permissão para alterar este ativo.', ['status' => 403]);
  }
  
  // Se foi solicitado outro usuário, verifica permissões
  if (!empty($target_user_id)) {
    
    
    // Verifica se o usuário alvo existe
    $target_user = get_user_by('ID', $target_user_id);
    if (!$target_user) {
      return new WP_Error(
        'invalid_user',
        'O usuário solicitado não existe.',
        ['status' => 404]
      );
    }

    // Busca estatísticas do usuário (código anterior)
    return get_user_statistics($target_user->ID);
  }
}

/**
* Registra a rota da API para estatísticas
*/
function register_api_statistics_get() {
  register_rest_route('api', '/statistics', [
      'methods' => WP_REST_Server::READABLE,
      'callback' => 'api_statistics_get',
      'permission_callback' => function() {
          return is_user_logged_in();
      },
  ]);
}
add_action('rest_api_init', 'register_api_statistics_get');


/**
 * Busca estatísticas específicas de um post
 * 
 * @param int $post_id ID do post
 * @return WP_REST_Response
 */
function get_post_statistics($post_id, $user_roles) {
  // Verifica se o usuário tem permissão para acessar o endpoint
  if (!in_array('administrator', $user_roles)) {
    return new WP_Error('permission_denied', 'Sem permissão para acessar este recurso.', ['status' => 401]);
  }

  global $wpdb;

  // Verifica se o post existe
  $post = get_post($post_id);
  if (!$post) {
      return new WP_Error(
          'invalid_post',
          'O post solicitado não existe.',
          ['status' => 404]
      );
  }

  // Conta favoritos do post
  $favorites_count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) 
       FROM {$wpdb->prefix}favpost 
       WHERE _fav_id_post = %d 
       AND _fav_post = 1",
      $post_id
  ));

  // Conta visualizações do post
  $views_count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) 
       FROM {$wpdb->prefix}stats 
       WHERE post_id = %d 
       AND action_type = 'view'",
      $post_id
  ));

  // Conta downloads do post
  $downloads_count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) 
       FROM {$wpdb->prefix}stats 
       WHERE post_id = %d 
       AND action_type = 'download'",
      $post_id
  ));

  // Conta comentários do post
  $comments_count = get_comments_number($post_id);

  // Prepara resposta
  return rest_ensure_response([
      'success' => true,
      'data' => [
          'post' => [
              'id' => $post_id,
              'title' => get_the_title($post_id),
              'url' => get_permalink($post_id),
              'author' => get_the_author_meta('display_name', $post->post_author)
          ],
          'stats' => [
              'favorites_count' => (int) $favorites_count,
              'views_count' => (int) $views_count,
              'downloads_count' => (int) $downloads_count,
              'comments_count' => (int) $comments_count,
              'total_interactions' => (int) ($favorites_count + $views_count + $downloads_count + $comments_count)
          ]
      ]
  ]);
}


/**
 * Busca estatísticas do usuário
 */
function get_user_statistics($user_id) {
  
  global $wpdb;
  
  // Obtém informações básicas do usuário
  $user = get_userdata($user_id);
  
  // Conta os posts do autor
  $posts_count = count_user_posts(
    $user_id,
    'post',
    'publish'
  );
  //return (['data' => $posts_count]);

  // Conta os favoritos do usuário
  $favorites_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) 
     FROM {$wpdb->prefix}favpost 
     WHERE user_id = %d 
     AND _fav_post = 1",
    $user_id
  ));

  // Busca estatísticas agregadas
  $stats_query = $wpdb->get_results($wpdb->prepare(
    "SELECT 
        action_type, 
        COUNT(*) as count 
     FROM {$wpdb->prefix}stats 
     WHERE user_id = %d 
     GROUP BY action_type",
    $user_id
));

  // Inicializa contadores
  $views_count = 0;
  $downloads_count = 0;

  // Processa os resultados
  foreach ($stats_query as $stat) {
    if ($stat->action_type === 'view') {
      $views_count = (int) $stat->count;
    } elseif ($stat->action_type === 'download') {
      $downloads_count = (int) $stat->count;
    }
  }

  // Busca lista completa de posts visualizados
  $viewed_posts = $wpdb->get_results($wpdb->prepare(
    "SELECT 
      s.post_id,
      p.post_title,
      MAX(s.created_at) as last_viewed_at,
      COUNT(*) as view_count
     FROM {$wpdb->prefix}stats s
     INNER JOIN {$wpdb->posts} p ON s.post_id = p.ID
     WHERE s.user_id = %d AND s.action_type = 'view'
     GROUP BY s.post_id
     ORDER BY last_viewed_at DESC",
    $user_id
  ));

  // Busca lista completa de posts baixados
  $downloaded_posts = $wpdb->get_results($wpdb->prepare(
    "SELECT 
        s.post_id,
        p.post_title,
        MAX(s.created_at) as last_downloaded_at,
        COUNT(*) as download_count
     FROM {$wpdb->prefix}stats s
     INNER JOIN {$wpdb->posts} p ON s.post_id = p.ID
     WHERE s.user_id = %d AND s.action_type = 'download'
     GROUP BY s.post_id
     ORDER BY last_downloaded_at DESC",
    $user_id
  ));

  // Formata os dados dos posts
  $format_post_data = function($posts) {
    return array_map(function($post) {
        return [
            'post_id' => (int) $post->post_id,
            'title' => $post->post_title,
            'last_interaction' => $post->last_viewed_at ?? $post->last_downloaded_at,
            'count' => (int) ($post->view_count ?? $post->download_count),
            'url' => get_permalink($post->post_id)
        ];
    }, $posts);
  };

  // Prepara resposta
  $response = [
      'success' => true,
        'data' => [
          'user' => [
              'id' => $user->ID,
              'name' => $user->display_name,
              'email' => $user->user_email
          ],
          'stats' => [
              'posts_count' => $posts_count,
              'favorites_count' => (int) $favorites_count,
              'views_count' => $views_count,
              'downloads_count' => $downloads_count,
              'total_interactions' => $views_count + $downloads_count
          ],
          'viewed_posts' => $format_post_data($viewed_posts),
          'downloaded_posts' => $format_post_data($downloaded_posts)
      ]
  ];

  return rest_ensure_response($response);
}