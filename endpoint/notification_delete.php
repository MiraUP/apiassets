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

  // Verifica o rate limiting
  if (is_rate_limit_exceeded('delete_asset')) {
    return new WP_Error('rate_limit_exceeded', 'Limite de requisições excedido.', ['status' => 429]);
  }

  // Obtém o ID do post e sanitiza
  $post_id = absint($request['id']);

  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  // Verifica se o usuário está autenticado
  if ($user_id === 0) {
    return new WP_Error('unauthorized', 'Usuário não autenticado.', ['status' => 401]);
  }

  // Verifica o status da conta do usuário
  $status_account = get_user_meta($user_id, 'status_account', true);
  if ($status_account !== 'activated') {
    return new WP_Error('account_pending', 'Sua conta não está ativada.', ['status' => 403]);
  }

  // Verifica se o usuário tem permissão (administrator ou editor)
  if (!in_array($user->roles[0], ['administrator'])) {
    return new WP_Error('forbidden', 'Você não tem permissão para acessar este endpoint.', ['status' => 403]);
  }

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
  register_rest_route('api', '/notifications/(?P<id>\d+)', array(
      'methods' => WP_REST_Server::DELETABLE,
      'callback' => 'api_notifications_delete',
      'permission_callback' => function() {
            return is_user_logged_in(); // Apenas usuários autenticados podem acessar
        },
  ));
}
add_action('rest_api_init', 'register_api_notifications_delete');