<?php
/**
 * Cadastra um novo usuário.
 *
 * @package MiraUP
 * @subpackage User
 * @since 1.0.0
 * @version 1.0.0
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com o ID do usuário cadastrado ou erro.
 */
function api_user_post($request) {
  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('user_post-' . $_SERVER["REMOTE_ADDR"], 2)) {
    return $error;
  }

  // Obtém o usuário atual, se houver algum logado
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  if($user_id > 0) {
    
    // Verifica o status da conta do usuário
    if ($error = Permissions::check_account_status($user)) {
      return $error;
    }
  }

  // Sanitiza e valida os dados de entrada
  $email = sanitize_email($request['email']);
  $display_name = sanitize_text_field($request['displayname']);
  $username = sanitize_text_field($request['username']);
  $password = $request['password']; // A senha será sanitizada pelo WordPress
  $role = sanitize_text_field($request['role']);
  $photo = $request->get_file_params();

  // Verifica se o nome de usuário ou email já existe
  if (username_exists($username) || email_exists($email)) {
    return new WP_Error('conflict', 'Usuário ou email já cadastrado.', ['status' => 409]);
  }

  // Verifica se todos os campos obrigatórios foram fornecidos
  if (empty($display_name) || empty($email) || empty($username) || empty($password) || empty($role)) {
    return new WP_Error('invalid_data', 'Dados incompletos.', ['status' => 400]);
  }

  // Verifica a extensão da imagem de perfil (se fornecida)
  if (!empty($photo)) {
    $allowed_types = ['png', 'jpg', 'jpeg', 'gif'];
    $file_info = wp_check_filetype($photo['photo']['name']);
    $file_extension = strtolower($file_info['ext']);

    if (!in_array($file_extension, $allowed_types)) {
      return new WP_Error(
        'invalid_file_type',
        'Apenas arquivos PNG, JPG, JPEG e GIF são permitidos.',
        ['status' => 400]
      );
    }
  }

  // Cria o usuário
  $user_id = wp_insert_user([
    'display_name' => $display_name,
    'user_login' => $username,
    'user_email' => $email,
    'user_pass' => $password,
    'role' => $role,
  ]);

  // Verifica se o usuário foi criado com sucesso
  if (is_wp_error($user_id)) {
    return $user_id;
  }

  // Adiciona metadados do usuário
  add_user_meta($user_id, 'status_account', 'pending');
  add_user_meta($user_id, 'goal', 100);
  add_user_meta($user_id, 'notification_email', 'true');
  add_user_meta($user_id, 'notification_asset', 'true');
  add_user_meta($user_id, 'notification_personal', 'true');
  add_user_meta($user_id, 'notification_system', 'true');
  add_user_meta($user_id, 'appearance_system', 'false');

  // Cria o código de confirmação registra e enviar por email
  $key_status_account = wp_generate_password(16, false);
  $email_confirm = add_user_meta($user_id, 'email_confirm', $key_status_account);
  
  if (!$email_confirm) {
    return new WP_Error('register_failed', 'Falha ao registrar o código de confirmação de email.', ['status' => 500]);
  }

  // Envia o código por email
  $subject = "Nova conta no MiraUP Banco de Ativos";
  $message = "Olá, ".$display_name.", receba nossas boas vindas camarada.\r\n\r\nUse o código abaixo para ativar sua conta: \r\n\r\n";
  $message .= $key_status_account . "\r\n\r\n";
  $message .= 'Se você não iniciou esse processo, entre em contato com o administrador do site.';

  $email_sent = send_notification_email($user_id, 0, $subject, $message);

  if(!$email_sent) {
    return new WP_Error('send_email_failed', 'Falha ao enviar o de código de confirmação de email.', ['status' => 500]);
  }
  
  // Faz o upload da foto do usuário (se fornecida)
  if (!empty($photo)) {
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $attachment_id = media_handle_upload('photo', $user_id);
    if (!is_wp_error($attachment_id)) {
      add_user_meta($user_id, 'photo', $attachment_id);
    }
  }

  // Retorna o ID do usuário cadastrado
  return rest_ensure_response([
    'success' => true,
    'message' => 'Usuário cadastrado com sucesso. Verifique seu email para ativar sua conta.',
    'data' => [
      'display_name' => $display_name,
    ],
  ]);
}

/**
 * Registra a rota da API para cadastro de usuários.
 */
function register_api_user_post() {
  register_rest_route('api/v1', '/user', [
    'methods'             => WP_REST_Server::CREATABLE,
    'callback'            => 'api_user_post',
    'permission_callback' => '__return_true', // Qualquer um pode acessar
  ]);
}
add_action('rest_api_init', 'register_api_user_post');