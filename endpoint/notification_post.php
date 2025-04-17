<?php
/**
 * Disparo manual de notificações via API
 * 
 * @package MiraUP
 * @subpackage Notifications
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição REST.
 * @return WP_REST_Response|WP_Error Resposta da API.
 */
function api_notifications_post(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('notification_post-' . $user_id, 25)) {
    return $error;
  }

  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }
      
  // Restringe a busca ao próprio usuário se não for administrador
  if ($error = Permissions::check_user_roles($user, ['administrator'])) {
    return $error;
  }      
      
  // Sanitize e valida os dados de entrada
  $notification_data = [
    'title'     => sanitize_text_field($request->get_param('title')),
    'message'   => sanitize_text_field($request->get_param('message')),
    'category'  => sanitize_text_field($request->get_param('category')),
    'content'   => sanitize_textarea_field($request->get_param('content')),
    'user_id'   => absint($request->get_param('user_id')),
  ];
      

  // Valida os campos obrigatórios
  $required_fields = ['title', 'message', 'category', 'content'];
  foreach ($required_fields as $field) {
    if (empty($notification_data[$field])) {
      return new WP_Error(
        'missing_field',
        'Campo obrigatório ausente.',
        ['status' => 400]
      );
    }
  }

  // Verifica se usuário existe
  if (!empty($notification_data['user_id'])) {
    $target_user = get_user_by('ID', $notification_data['user_id']);
      
    if (!$target_user) {
      return new WP_Error(
        'invalid_user',
        'O usuário especificado não existe.',
        ['status' => 404]
      );
    }

    if (!is_email($target_user->user_email)) {
      return new WP_Error(
        'invalid_user_email',
        'O remetente não tem endereço de e-mail válido.',
        ['status' => 400]
      );
    }
  }

  $post_id = !empty($request['post_id']) ? absint($request['post_id']) : null;

  // Cria notificação
  $notification_result = add_notification(
    $user_id,
    $notification_data['category'],
    $notification_data['message'],
    $notification_data['title'],
    $notification_data['content'],
    $post_id,
    $notification_data['user_id'] ?? 0
  );

  if (is_wp_error($notification_result)) {
    return $notification_result;
  }

  // Prepare response
  return rest_ensure_response([
    'success' => true,
    'message' => 'Notificação criada com sucesso.',
    'data'    => [
      'notification_id' => $notification_result['id'] ?? 0,
      'sent_to'         => $notification_data['user_id'] ?: 'Todos os usuários.',
    ]
  ]);
}

/**
 * Registra as rotas da API de notificação
 */
function register_api_notifications_post() {
  register_rest_route('api/v1', '/notifications', [
    'methods'             => WP_REST_Server::CREATABLE,
    'callback'            => 'api_notifications_post',
    'permission_callback' => function() {
        return is_user_logged_in();
    },
  ]);
}
add_action('rest_api_init', 'register_api_notifications_post');