<?php 
/**
 * Obtém os comentários de um post específico
 *
 * @package MiraUP
 * @subpackage Comments
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição REST
 * @return WP_REST_Response|WP_Error Resposta da API
 */
function api_comment_get(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;
  
  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('comment_get-' . $user_id, 20)) {
    return $error;
  }
  
  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }

  // Valida e sanitiza o ID do post
  $post_id = absint($request->get_param('id'));
  if (!$post_id || !get_post($post_id)) {
    return new WP_Error( 'invalid_post', 'Post inválido ou não encontrado.', ['status' => 404] );
  }

  // Obtém os comentários com parâmetros otimizados
  $comments = get_comments([
    'post_id'       => $post_id,
    'order'         => 'ASC',
    'status'        => 'approve',
    'fields'        => 'all', // Ou 'ids' para melhor performance se necessário
  ]);

  // Formata os comentários para resposta
  $formatted_comments = array_map(function($comment) {

    // Obtém a foto do usuário
    $photo_id = get_user_meta($comment->user_id, 'photo', true);
    $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, '') : '';
    
    return [
      'id'            => $comment->comment_ID,
      'author'        => $comment->comment_author,
      'author_id'     => $comment->user_id,
      'author_photo'  => $photo_url,
      'content'       => wp_kses_post($comment->comment_content),
      'date'          => $comment->comment_date,
      'date_gmt'      => $comment->comment_date_gmt,
      'parent_id'     => $comment->comment_parent,
      'post_id'       => $comment->comment_post_ID,
      'status'        => $comment->comment_approved,
      'type'          => $comment->comment_type,
      'link'          => get_comment_link($comment),
    ];
  }, $comments);

  return rest_ensure_response([
    'success' => true,
    'data'    => $formatted_comments,
    'count'   => count($formatted_comments)
  ]);
}

/**
 * Registra o endpoint REST para obtenção de comentários
 */
function register_api_comment_get() {
  register_rest_route('api/v1', '/comment/(?P<id>[0-9]+)', [
    'methods'             => WP_REST_Server::READABLE,
    'callback'            => 'api_comment_get',
    'permission_callback' => function() {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}
add_action('rest_api_init', 'register_api_comment_get');
?>