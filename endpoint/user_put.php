<?php
/**
 * Atualiza os dados de um usuário.
 *
 * @package MiraUP
 * @subpackage User
 * @since 1.0.0
 * @version 1.0.0
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com o resultado da atualização ou erro.
 */
function api_user_put(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('user_put-' . $user_id, 300)) {
    return $error;
  }

  // Sanitiza e valida os dados de entrada
  $id = intval($request['id']);
  $email = sanitize_email($request['email']);
  $code_email = sanitize_text_field($request['code_email']);
  $password = sanitize_text_field($request['password']);
  $password_new = sanitize_text_field($request['password_new']);
  $password_confirm = sanitize_text_field($request['password_confirm']);
  $name = sanitize_text_field($request['name']);
  $role = sanitize_text_field($request['role']);
  $goal = sanitize_text_field($request['goal']);
  $appearance_system = sanitize_text_field($request['appearance_system']);
  $notification_asset = sanitize_text_field($request['notification_asset']);
  $notification_email = sanitize_text_field($request['notification_email']);
  $notification_personal = sanitize_text_field($request['notification_personal']);
  $notification_system = sanitize_text_field($request['notification_system']);

  // Verifica se o usuário existe
  if ($error = Permissions::check_user_exists($user)) {
    return $error;
  }

  // Limita a atualização do role e goal apenas para administradores
  if ($error = Permissions::check_role_goal_update( $user, $role, $goal )) {
    return $error;
  }
  
  // Limita a atualização do email apenas para administradores ou o próprio usuário
  if ($error = Permissions::check_email_edit_permission($user, $id)) {
    return $error;
  }

  // Verifica se o role é válido
  $valid_roles = ['subscriber', 'contributor', 'author', 'editor', 'administrator'];
  if (!in_array($role, $valid_roles, true)) {
    return new WP_Error('invalid_role', 'Role inválido.', ['status' => 400]);
  }

  // Verifica se o email é diferente do cadastro e envia o código de confirmação
  if ($user->user_email !== $email) {
    // Verifica se o email já está em uso por outro usuário
    if ($error = Permissions::check_email_usage($user, $email)) {
      return $error;
    }

    $key_emailconfirm = wp_generate_password(9, false);
    update_user_meta($id, 'email_confirm', $key_emailconfirm);
    update_user_meta($id, 'status_account', 'pending');
    update_user_meta($id, 'new_email', $email);

    $subject = 'Confirmação de Email';
    $message = "Olá, \r\n\r\n";
    $message = "Utilize o código abaixo para confirmar o seu email: \r\n\r\n";
    $message_footer = "\r\n\r\nSe você não iniciou esse processo, entre em contato com o administrador do site.";
    $body = $message . $key_emailconfirm . $message_footer;

    $email_sent = send_notification_email($id, 0, $subject, $body, $email);

    if (!$email_sent) {
      delete_user_meta($id, 'email_confirm');
      return new WP_Error('send_email_failed', 'Falha ao enviar o código de confirmação de email.', ['status' => 400]);
    }
  }

  // Atualização da senha
  if (!empty($password) && !empty($password_new) && !empty($password_confirm)) {
    if ($password_new !== $password_confirm) {
      return new WP_Error('password_mismatch', 'As senhas não coincidem.', ['status' => 400]);
    }

    // Verifica se a senha atual está correta
    if (!wp_check_password($password, $user->user_pass, $user_id)) {
      return new WP_Error('invalid_password', 'Senha atual inválida.', ['status' => 400]);
    }

    // Atualiza a senha
    wp_set_password($password_new, $id);
  }

  // Atualiza os dados do usuário
  $update_data = [
    'ID'           => $id,
    'display_name' => $name,
    'role'         => $role,
  ];

  $update_result = wp_update_user($update_data);

  // Verifica se a atualização foi bem-sucedida
  if (is_wp_error($update_result)) {
    return $update_result;
  }

  // Atualiza status da conta com a confirmação do código enviado por email
  if (!empty($code_email)) {
    $generated_code = get_user_meta($user_id, 'email_confirm', true);
    $expiration = (int) get_user_meta($user->ID, 'email_confirm_expiration', true);

    if (time() > $expiration) {
      return new WP_Error('expired_code', 'Código expirado. Por favor, solicite um novo.', ['status' => 400]);
    }

    if ($code_email === $generated_code) {
      
      $new_email = get_user_meta($user_id, 'new_email', true);
      if (!empty($new_email)) {
        wp_update_user(['ID' => $id, 'user_email' => $new_email]);
      }

      update_user_meta($user_id, 'status_account', 'activated');
      delete_user_meta($user_id, 'email_confirm');
      delete_user_meta($user_id, 'new_email');
    } else {
      return new WP_Error('invalid_code', 'Código de confirmação inválido.', ['status' => 400]);
    }
  }

  // Atualiza os metadados do usuário
  update_user_meta($id, 'goal', $goal);
  update_user_meta($id, 'appearance_system', $appearance_system);
  update_user_meta($id, 'notification_asset', $notification_asset);
  update_user_meta($id, 'notification_email', $notification_email);
  update_user_meta($id, 'notification_personal', $notification_personal);
  update_user_meta($id, 'notification_system', $notification_system);
  update_user_meta($id, 'notification_curation', 'true');
  update_user_meta($id, 'notification_error_report', 'true');

  return rest_ensure_response([
    'success' => true,
    'message' => 'Dados do usuário atualizados com sucesso.',
    'data'    => [
      'user_id' => $id,
    ],
  ]);
}

/**
 * Registra a rota da API para atualização de dados do usuário.
 */
function register_api_user_put() {
  register_rest_route('api/v1', '/user', [
      'methods'             => WP_REST_Server::EDITABLE,
      'callback'            => 'api_user_put',
      'permission_callback' => function () {
        return is_user_logged_in(); // Apenas usuários autenticados podem acessar
      },
  ]);
}
add_action('rest_api_init', 'register_api_user_put');



/**
 * Atualiza a foto de perfil do usuário.
 *
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com o resultado da atualização ou erro.
 */
function api_user_photo_put(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('user_photo_put-' . $user_id, 5)) {
    return $error;
  }

  // Verifica se o arquivo de foto foi enviado
  $photo = $request->get_file_params();
  if (empty($photo)) {
      return new WP_Error('invalid_data', 'Nenhuma foto foi enviada.', ['status' => 400]);
  }

  // Verifica a extensão da foto
  $allowed_types = ['png', 'jpg', 'jpeg', 'gif'];
  $file_info = wp_check_filetype($photo['photo']['name']);
  $file_extension = strtolower($file_info['ext']);

  if (!in_array($file_extension, $allowed_types, true)) {
    return new WP_Error('invalid_file_type', 'Apenas arquivos PNG, JPG, JPEG e GIF são permitidos.', ['status' => 400]);
  }

  // Remove a foto antiga (se existir)
  $old_photo_id = get_user_meta($user_id, 'photo', true);
  if ($old_photo_id > 0) {
    wp_delete_attachment($old_photo_id, true);
  }

  // Faz o upload da nova foto
  require_once ABSPATH . 'wp-admin/includes/image.php';
  require_once ABSPATH . 'wp-admin/includes/file.php';
  require_once ABSPATH . 'wp-admin/includes/media.php';

  $new_photo_id = media_handle_upload('photo', $user_id);
  if (is_wp_error($new_photo_id)) {
    return $new_photo_id;
  }

  // Atualiza o metadado da foto do usuário
  update_user_meta($user_id, 'photo', $new_photo_id);

  return rest_ensure_response([
      'success' => true,
      'message' => 'Foto de perfil atualizada com sucesso.',
      'data'    => [
        'photo_id' => $new_photo_id,
      ],
  ]);
}

/**
 * Registra a rota da API para atualização da foto de perfil do usuário.
 */
function register_api_user_photo_put() {
  register_rest_route('api/v1', '/user/photo', [
    'methods'             => WP_REST_Server::CREATABLE,
    'callback'            => 'api_user_photo_put',
    'permission_callback' => function () {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}
add_action('rest_api_init', 'register_api_user_photo_put');



/**
 * Atualiza a foto de perfil do usuário.
 *
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com o resultado da atualização ou erro.
 */
function api_user_code_put(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('user_code_put-' . $user_id, 500)) {
    return $error;
  }

  $code_email = sanitize_text_field($request['code_email']);

  if(!empty($code_email)) {
    $generated_code = get_user_meta($user_id, 'email_confirm', true);
    $expiration = (int) get_user_meta($user->ID, 'email_confirm_expiration', true);

    if (time() > $expiration) {
      return new WP_Error('expired_code', 'Código expirado. Por favor, solicite um novo.', ['status' => 400]);
    }

    if ($code_email === $generated_code) {
      update_user_meta($user_id, 'status_account', 'activated');
      delete_user_meta($user_id, 'email_confirm');
      delete_user_meta($user_id, 'email_confirm_expiration');
      delete_user_meta($user_id, 'new_email');
    } else {
      return new WP_Error('invalid_code', 'Código de confirmação inválido.', ['status' => 400]);
    }
  } else {
    return new WP_Error('missing_code', 'Código não informado.', ['status' => 400]);
  }

  return rest_ensure_response([
    'success' => true,
    'message' => 'Conta Ativada com sucesso.',
    'data'    => [
      'user_id' => $id,
    ],
  ]);
}

/**
 * Registra a rota da API para confirmação do código de e-mail do usuário.
 */
function register_api_user_code_put() {
  register_rest_route('api/v1', '/user/code', [
    'methods'             => WP_REST_Server::EDITABLE,
    'callback'            => 'api_user_code_put',
    'permission_callback' => function () {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}
add_action('rest_api_init', 'register_api_user_code_put');