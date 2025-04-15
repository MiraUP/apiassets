<?php
/**
 * Endpoint para listagem de mídias de ativos
 *
 * @package MiraUP
 * @subpackage Media
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição
 * @return WP_REST_Response|WP_Error
 */
function api_media_get(WP_REST_Request $request) {
  global $wpdb;
  
  // Verifica rate limiting
  if (is_rate_limit_exceeded('media_get')) {
    return new WP_Error('rate_limit_exceeded', 'Limite de requisições excedido', ['status' => 429]);
  }
  
  $user = wp_get_current_user();
  $user_id = $user->ID;
  
  // Verifica autenticação
  if ($user_id === 0) {
    return new WP_Error('unauthorized', 'Usuário não autenticado', ['status' => 401]);
  }
  
  // Verifica status da conta
  $status_account = get_user_meta($user_id, 'status_account', true);
  if ($status_account !== 'activated') {
    return new WP_Error('account_inactive', 'Conta não ativada', ['status' => 403]);
  }
  
  // Parâmetros sanitizados
  $post_type = sanitize_key($request->get_param('post-type'));
  $parent_id = absint($request->get_param('parent'));
  
  // Handler para diferentes tipos de post
    switch ($post_type) {
        case 'user':
            return handle_user_media($user, $parent_id);
            
        case 'asset':
            return handle_asset_media($request);
            
        default:
            return new WP_Error('invalid_type', 'Tipo inválido para busca', ['status' => 400]);
    }
}

/**
 * Manipula mídias de usuários
 */
function handle_user_media($current_user, $parent_id) {
    // Verifica permissões
    if (!in_array('administrator', $current_user->roles) && $parent_id !== $current_user->ID) {
        return new WP_Error('forbidden', 'Acesso não autorizado', ['status' => 403]);
    }
    
    
    $args = [
      'fields' => ['ID', 'user_login'],
      'include' => $parent_id ? [$parent_id] : []
    ];
    
    $users = get_users($args);
    
    $response = [];
    foreach ($users as $user) {
      $photo_id = get_user_meta($user->ID, 'photo', true);
      
      $response[] = [
        'id' => $user->ID,
        'title' => $user->user_login,
        'photo' => $photo_id ? wp_get_attachment_image_url($photo_id, 'full') : null
      ];
    }
    
    return rest_ensure_response([
      'success' => true,
      'message' => 'Usuario encontrado.',
      'data' => $response,
    ]);
}

/**
 * Manipula mídias de assets
 */
function handle_asset_media($request) {
  $post_id = absint($request->get_param('parent')); // ID do post (parent)
  
  $posts = get_post($post_id);

  // Verifica se o post existe
  $post_meta = get_post_meta($posts->ID);
  $thumbnail_url = wp_get_attachment_image_src($post_meta['thumbnail'][0], 'large')[0];
  
  // Busca os IDs das previews (custom field)
  $previews = [];
  if (!empty($post_meta['previews']) && is_array($post_meta['previews'])) {
    foreach ($post_meta['previews'] as $preview => $array) {
      $data = get_post($array);
      if ($data) {
        $previews[$preview] = [
            "id"            => $data->ID,
            "title"         => $data->post_title,
            "url"           => $data->guid,
            "icon_styles"   => get_the_terms($data->ID, 'icon_style'),
            "icon_tag"      => get_the_terms($data->ID, 'icon_tag'),
            "icon_category" => get_the_terms($data->ID, 'icon_category'),
            'mime_type'     => get_post_mime_type($data),
        ];
      }
    }
  }

  // Retorna os dados formatados
  return rest_ensure_response([
      'success' => true,
      'message' => 'Mídias do ativo encontradas.',
      'data' => [
          'id' => $post_id,
          'title' => $posts->post_title,
          'thumbnail' => [
              'id' => $post_meta['thumbnail'][0],
              'url' => $thumbnail_url,
              'mime_type' => $post_meta['thumbnail'][0] ? get_post_mime_type($post_meta['thumbnail'][0]) : null,
          ],
          'previews' => $previews,
          'icon_style' => get_the_terms($post_id, 'icon_style'),
          'icon_category' => get_the_terms($post_id, 'icon_category'),
          'icon_tag' => get_the_terms($post_id, 'icon_tag'),
      ],
  ]);
}

/**
 * Registra a rota da API
 */
function register_api_media_get() {
    register_rest_route('api', '/media', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'api_media_get',
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ]);
}

add_action('rest_api_init', 'register_api_media_get');