<?php
/**
 * Exclui um usuário e suas informações adicionais.
 *
 * @package MiraUP
 * @subpackage User
 * @since 1.0.0
 * @version 1.0.0
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com o resultado da exclusão ou erro.
 */
function api_user_delete(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('user_delete-' . $user_id, 6)) {
    return $error;
  }

  // Sanitiza e valida os dados de entrada
  $username = sanitize_text_field($request['username']);
  $password = sanitize_text_field($request['password']);
  $code = sanitize_text_field($request['code']);

  // Verifica se o nome de usuário e a senha foram fornecidos
  if (empty($username) || empty($password)) {
    return new WP_Error('missing_credentials', 'Informe o nome de usuário e a senha.', ['status' => 422]);
  }

  // Verifica se o nome de usuário fornecido corresponde ao usuário logado
  if ($username !== $user->user_login) {
    return new WP_Error('invalid_username', 'Nome de usuário incorreto.', ['status' => 403]);
  }

  // Verifica se a senha está correta
  if (!wp_check_password($password, $user->user_pass, $user_id)) {
    return new WP_Error('invalid_credentials', 'Usuário ou senha incorretos.', ['status' => 403]);
  }

  // Se o código de confirmação não foi fornecido, gera e envia um novo código
  if (empty($code)) {
    $key_delete = wp_generate_password(16, false);
    $update_result = update_user_meta($user_id, 'key_delete', $key_delete);

    if (!$update_result) {
      return new WP_Error('key_generation_failed', 'Falha ao gerar o código de segurança.', ['status' => 500]);
    }

    // Envia o código por email
    $subject = 'Código de confirmação para exclusão de conta';
    $message = "Foi iniciado um pedido de exclusão de conta. Esse é o seu código de confirmação para deletar suas informações: \r\n\r\n";
    $message .= $key_delete . "\r\n\r\n";
    $message .= 'Se você não iniciou esse processo, entre em contato com o administrador do site.';

    $email_sent = send_notification_email($user_id, 0, $subject, $message);

    if (!$email_sent) {
      delete_user_meta($user_id, 'key_delete');
      return new WP_Error('email_send_failed', 'Falha ao enviar o código de segurança.', ['status' => 500]);
    }

    return rest_ensure_response([
      'success' => true,
      'message' => 'Verifique o código de segurança no seu email.',
      'data'    => 'Código enviado para: ' . $user->user_email
    ]);
  }

  // Se o código de confirmação foi fornecido, verifica se é válido
  $generated_code = get_user_meta($user_id, 'key_delete', true);

  if (empty($code) || $code !== $generated_code) {
    delete_user_meta($user_id, 'key_delete');
    return new WP_Error('invalid_code', 'Código de confirmação inválido.', ['status' => 403]);
  }

  // Remove a foto de perfil do usuário (se existir)
  $attachment_id = get_user_meta($user_id, 'photo', true);
  if ($attachment_id > 0) {
    wp_delete_attachment($attachment_id, true);
  }

  // Remove todos os metadados do usuário
  $meta_keys = [
    'photo',
    'status_account',
    'goal',
    'notification_email',
    'notification_assets',
    'notification_personal',
    'notification_system',
    'appearance_system',
    'email_confirm',
    'key_delete',
  ];

  foreach ($meta_keys as $meta_key) {
    delete_user_meta($user_id, $meta_key);
  }

  // Exclui o usuário
  require_once(ABSPATH . 'wp-admin/includes/user.php');
  $delete_result = wp_delete_user($user_id);

  if (!$delete_result) {
    return new WP_Error('user_deletion_failed', 'Erro ao excluir o usuário.', ['status' => 500]);
  }

  return rest_ensure_response([
    'success' => true,
    'message' => 'Usuário excluído com sucesso.',
    'data'    => 'Usuário excluído com sucesso.'
  ]);
}

/**
 * Registra a rota da API para exclusão de usuário.
 */
function register_api_user_delete() {
  register_rest_route('api', '/user', [
    'methods'             => WP_REST_Server::DELETABLE,
    'callback'            => 'api_user_delete',
    'permission_callback' => function () {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}
add_action('rest_api_init', 'register_api_user_delete');
?>