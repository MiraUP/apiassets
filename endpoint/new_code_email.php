<?php
/**
 * Gera um novo código de confirmação de email e envia o email cadastrado.
 * 
 * @package MiraUP
 * @subpackage New Code Email
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com o resultado da operação ou erro.
 */

// Define constantes para o tempo de expiração do código de confirmação
define('CONFIRMATION_CODE_EXPIRATION', 5 * MINUTE_IN_SECONDS); // 15 minutos

function api_new_code_email(WP_REST_Request $request) {  
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;
  $email = $user->user_email;

  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }
  
  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('statistics_get-' . $user_id, 100)) {
    return $error;
  }
  
  $key_emailconfirm = wp_generate_password(9, false);
  $confirmation_expiration = time() + CONFIRMATION_CODE_EXPIRATION;

  update_user_meta($user_id, 'email_confirm', $key_emailconfirm);
  update_user_meta($user_id, 'email_confirm_expiration', $confirmation_expiration);
  update_user_meta($user_id, 'status_account', 'pending');
  
  $subject = 'Novo código de confirmação de Email';
  $message = "Olá, \r\n\r\n";
  $message = "Utilize o código abaixo para confirmar o seu email: \r\n\r\n";
  $message_footer = "\r\n\r\nSe você não iniciou esse processo, entre em contato com o administrador do site.";
  $body = $message . $key_emailconfirm . $message_footer;
  
  $email_sent = send_notification_email($user_id, 0, $subject, $body, $email);
  
  if (!$email_sent) {
    delete_user_meta($id, 'email_confirm');
    return new WP_Error('send_email_failed', 'Falha ao enviar o código de confirmação de email.', ['status' => 400]);
  }
  
  return rest_ensure_response([
    'success' => true,
    'message' => 'Código enviado com sucesso.',
    'data'    => "Código de confirmação enviado para o e-mail cadastrado.",
  ]);
}
  
/**
 * Registra a rota da API para recuperação de senha.
 */
function register_api_new_code_email() {
  register_rest_route('api/v1', '/user/new-code', [
    'methods'             => WP_REST_Server::CREATABLE,
    'callback'            => 'api_new_code_email',
    'permission_callback' => function() {
      return is_user_logged_in();
    },
  ]);
}
add_action('rest_api_init', 'register_api_new_code_email');