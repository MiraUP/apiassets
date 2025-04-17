<?php
/**
 * Deleta uma notificação no WordPress.
 * 
 * @package MiraUP
 * @subpackage Notifications
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição.
 * @return WP_REST_Response|WP_Error Resposta da API.
 */
function api_notifications_delete(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('notification_delete-' . $user_id, 25)) {
    return $error;
  }

  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }

  // Restringe a ação do usuário por função
  if ($error = Permissions::check_user_roles($user, ['administrator'])) {
    return $error;
  }

  // Obtém o ID do post e sanitiza
  $post_id = absint($request['id']);

  // Obtém o post
  $post = get_post($post_id);
  if (!$post) {
    return new WP_Error('post_not_found', 'Post não encontrado.', ['status' => 404]);
  }

  // Deleta o post
  $deleted = wp_delete_post($post_id, true);
  if (!$deleted) {
    return new WP_Error('delete_failed', 'Erro ao deletar o ativo.', ['status' => 500]);
  }

  
  return rest_ensure_response([
    'success' => true,
    'message' => 'Notificação deletada com sucesso.',
    'data'    => $deleted,
  ]);
}

function register_api_notifications_delete() {
  register_rest_route('api/v1', '/notifications/(?P<id>\d+)', array(
    'methods'             => WP_REST_Server::DELETABLE,
    'callback'            => 'api_notifications_delete',
    'permission_callback' => function() {
        return is_user_logged_in(); // Apenas usuários autenticados podem acessar
      },
  ));
}
add_action('rest_api_init', 'register_api_notifications_delete');