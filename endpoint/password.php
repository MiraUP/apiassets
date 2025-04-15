<?php
/**
 * Gera um código de recuperação de senha e envia um email com o link de reset.
 * 
 * @package MiraUP
 * @subpackage Statistics
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com o resultado da operação ou erro.
 */
function api_password_lost(WP_REST_Request $request) {  
  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('user_password-' . $_SERVER["REMOTE_ADDR"], 10)) {
    return $error;
  }

    // Sanitiza e valida o login (email ou nome de usuário)
    $login = sanitize_text_field($request['login']);

    if (empty($login)) {
        return new WP_Error('missing_credentials', 'Informe o email ou o login.', ['status' => 400]);
    }

    // Busca o usuário pelo email ou nome de usuário
    $user = get_user_by('email', $login);
    if (empty($user)) {
        $user = get_user_by('login', $login);
    }

    if (empty($user)) {
        return new WP_Error('user_not_found', 'Usuário não encontrado.', ['status' => 404]);
    }

    // Gera a chave de recuperação de senha
    $user_login = $user->user_login;
    $key = get_password_reset_key($user);

    if (is_wp_error($key)) {
        return new WP_Error('key_generation_failed', 'Falha ao gerar a chave de recuperação.', ['status' => 500]);
    }

    // Monta o link de reset de senha
    $subject = 'Recuperação de Senha';
    $message = "Foi solicitado um pedido de recuperação de senha. Esse é o seu link para resetar a sua senha: \r\n\r\n";
    $message .= '<a href="' . esc_url_raw(get_bloginfo('url') . "/reset-password?key=$key&login=" . rawurlencode($user_login)) . '">' . esc_url_raw(get_bloginfo('url') . "/reset-password?key=$key&login=" . rawurlencode($user_login)) . '</a>';
    $message .= "\r\n\r\nSe você não iniciou esse processo, entre em contato com o administrador do site.";

    // Envia o email com o link de reset
    $email_sent = send_notification_email($user->ID, 0, $subject, $message);

    if (!$email_sent) {
        return new WP_Error('email_send_failed', 'Falha ao enviar o email de recuperação.', ['status' => 500]);
    }

    return rest_ensure_response([
      'success' => true,
      'message' => 'Email de recuperação enviado com sucesso.',
      'data'    => 'Email enviado para: ' . $user->user_email,
    ]);
}
  
/**
 * Registra a rota da API para recuperação de senha.
 */
function register_api_password_lost() {
  register_rest_route('api', '/password/lost', [
    'methods'             => WP_REST_Server::CREATABLE,
    'callback'            => 'api_password_lost',
    'permission_callback' => '__return_true', // Qualquer um pode acessar
  ]);
}
add_action('rest_api_init', 'register_api_password_lost');


/**
 * Reseta a senha do usuário com base na chave de recuperação.
 *
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com o resultado da operação ou erro.
 */
function api_password_reset(WP_REST_Request $request) {
  // Verifica o rate limiting
  if ($error = Permissions::check_rate_limit('user_password-' . $_SERVER["REMOTE_ADDR"], 10)) {
    return $error;
  }

  // Sanitiza e valida os dados de entrada
  $login = sanitize_text_field($request['login']);
  $password = sanitize_text_field($request['password']);
  $key = sanitize_text_field($request['key']);

  if (empty($login) || empty($password) || empty($key)) {
      return new WP_Error('missing_data', 'Informe o login, a senha e a chave de recuperação.', ['status' => 400]);
  }

  // Busca o usuário pelo login
  $user = get_user_by('login', $login);
  if (empty($user)) {
      return new WP_Error('user_not_found', 'Usuário não encontrado.', ['status' => 401]);
  }

  // Verifica a chave de recuperação
  $check_key = check_password_reset_key($key, $login);
  if (is_wp_error($check_key)) {
      return new WP_Error('invalid_key', 'Chave de recuperação inválida ou expirada.', ['status' => 401]);
  }

  // Reseta a senha
  reset_password($user, $password);

  return rest_ensure_response([
    'success' => true,
    'message' => 'Senha resetada com sucesso.',
    'data'    => 'Acesse sua conta com a nova senha.',
  ]);
}

/**
* Registra a rota da API para reset de senha.
*/
function register_api_password_reset() {
  register_rest_route('api', '/password/reset', [
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => 'api_password_reset',
      'permission_callback' => '__return_true', // Qualquer um pode acessar
  ]);
}
add_action('rest_api_init', 'register_api_password_reset');
?>