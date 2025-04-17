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
function api_new_code_email(WP_REST_Request $request) {  
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('statistics_get-' . $user_id, 100)) {
    return $error;
  }

  // Verifica se há um novo e-mail cadastrado
  $new_email = get_user_meta($user_id, 'new_email', true);
  //return(['data'=>$new_email]);
  
  if (!$new_email) {
    return new WP_Error('missing_email', 'Novo e-mail não encontrado.', ['status' => 400]);
  }

  $key_emailconfirm = wp_rand(10000000, 900000000);
  update_user_meta($user_id, 'email_confirm', $key_emailconfirm);
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