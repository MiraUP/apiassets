<?php
/**
 * Deleta um comentário existente
 *
 * @package MiraUP
 * @subpackage Comments
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição REST
 * @return WP_REST_Response|WP_Error Resposta da API
 */
function api_comment_delete(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;
  
  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('comment_delete-' . $user_id, 5)) {
    return $error;
  }
  
  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }

  // Valida e sanitiza o ID do comentário
  $comment_id = absint($request->get_param('id'));
  if (!$comment_id) {
    return new WP_Error( 'invalid_comment_id', 'ID do comentário inválido.', ['status' => 400] );
  }

  // Obtém o comentário existente
  $comment = get_comment($comment_id);
  if (!$comment) {
    return new WP_Error( 'comment_not_found', 'Comentário não encontrado.', ['status' => 404] );
  }

  // Verifica se o usuário é o autor do comentário ou um admin/editor
  $is_author = ($comment->user_id == $user->ID);
  $is_admin_or_editor = in_array('administrator', $user->roles);
  
  if (!$is_author && !$is_admin_or_editor) {
    return new WP_Error( 'permission_denied', 'Você não tem permissão para deletar este comentário.', ['status' => 403] );
  }

  // Deleta o comentário
  $deleted = wp_delete_comment($comment_id, true); // true para forçar deleção permanente

  if (!$deleted) {
    return new WP_Error( 'deletion_failed', 'Falha ao deletar o comentário.', ['status' => 500]
    );
  }
    
  return rest_ensure_response([
    'success' => true,
    'message' => 'Comentário deletado com sucesso.',
    'data'    => [
      'comment_id' => $comment_id,
      'deleted_at' => current_time('mysql'),
    ]
  ]);
  }

/**
 * Registra o endpoint REST para atualização de comentários
 */
function register_api_comment_delete() {
  register_rest_route('api/v1', '/comment/(?P<id>\d+)', [
    'methods'             => WP_REST_Server::DELETABLE,
    'callback'            => 'api_comment_delete',
    'permission_callback' => function() {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}
add_action('rest_api_init', 'register_api_comment_delete');