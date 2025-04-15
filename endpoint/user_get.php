<?php
/**
 * Obtém informações do usuário atual.
 * 
 * @package MiraUP
 * @subpackage User
 * @since 1.0.0
 * @version 1.0.0
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com os dados do usuário ou erro de permissão.
 */
function api_user_get($request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = $user->ID;

  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
      return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('user_get-' . $user_id, 100)) {
    return $error;
  }

  // Obtém a foto do usuário
  $photo_id = get_user_meta($user_id, 'photo', true);
  $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, '') : '';

  //verifica há código de confirmação de email
  $code_email = get_user_meta($user_id, 'email_confirm', true);
  if (!empty($code_email)) {
    $email_confirm = true;
  } else {
    $email_confirm = false;
  }
    
  //verifica há código de confirmação de exclusão de usuário
  $key_delete_user = get_user_meta($user_id, 'key_delete', true);
  if (!empty($key_delete_user)) {
    $key_delete = true;
  } else {
    $key_delete = false;
  }

  // Monta a resposta com os dados do usuário
  $response = [
    'id' => $user_id,
    'username' => $user->user_login,
    'name' => $user->display_name,
    'email' => $user->user_email,
    'roles' => $user->roles,
    'photo' => $photo_url,
    'status_account' => get_user_meta($user_id, 'status_account', true),
    'goal' => get_user_meta($user_id, 'goal', true),
    'notification_email' => get_user_meta($user_id, 'notification_email', true),
    'notification_asset' => get_user_meta($user_id, 'notification_asset', true),
    'notification_personal' => get_user_meta($user_id, 'notification_personal', true),
    'notification_system' => get_user_meta($user_id, 'notification_system', true),
    'appearance_system' => get_user_meta($user_id, 'appearance_system', true),
    'email_confirm' => $email_confirm,
    'key_delete' => $key_delete,
  ];

  return rest_ensure_response([
    'success' => true,
    'message' => 'Busca do usuário feita com sucesso.',
    'data' => $response,
  ]);
}

/**
 * Registra a rota da API para obter informações do usuário atual.
 */
function register_api_user_get() {
  register_rest_route('api', '/user', [
    'methods' => WP_REST_Server::READABLE,
    'callback' => 'api_user_get',
    'permission_callback' => function() {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },    
  ]);
}
add_action('rest_api_init', 'register_api_user_get');




/**
 * Obtém informações de múltiplos usuários.
 *
 * @package MiraUP
 * @subpackage user
 * @since 1.0.0
 * @version 1.0.0
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com a lista de usuários ou erro de permissão.
 */
function api_users_get($request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = $user->ID;

  // Verifica se o usuário está logado
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('user_get-' . $user_id, 100)) {
    return $error;
  }

  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }

  // Sanitiza e valida o parâmetro 'id-user'
  $user_ids = [];
  if (!empty($request->get_param('id-user'))) {
    $user_ids = array_map('intval', explode(',', $request->get_param('id-user')));
  }

  // Define os argumentos para a busca de usuários
  $args = [];
  if (!empty($user_ids)) {
    $args['include'] = $user_ids;
  }
    
  // Restringe a busca ao próprio usuário se não for administrador
  if (Permissions::check_user_roles($user, ['administrator'])) {
    $args['include'] = [$user_id];
  }

  // Obtém a lista de usuários
  $users = get_users($args);
  $user_list = [];

  foreach ($users as $user) {
    $photo_id = get_user_meta($user->ID, 'photo', true);
    $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, '') : '';

    $user_list[] = [
      'id' => $user->ID,
      'username' => $user->user_login,
      'name' => $user->display_name,
      'roles' => $user->roles,
      'email' => $user->user_email,
      'photo' => $photo_url,
      'status_account' => get_user_meta($user->ID, 'status_account', true),
      'goal' => get_user_meta($user->ID, 'goal', true),
      'notification_email' => get_user_meta($user->ID, 'notification_email', true),
      'notification_asset' => get_user_meta($user->ID, 'notification_asset', true),
      'notification_personal' => get_user_meta($user->ID, 'notification_personal', true),
      'notification_system' => get_user_meta($user->ID, 'notification_system', true),
      'appearance_system' => get_user_meta($user->ID, 'appearance_system', true),
    ];
  }

  return rest_ensure_response([
    'success' => true,
    'message' => 'Busca de usuários feita com sucesso.',
    'data' => $user_list,
  ]);
}

/**
 * Registra a rota da API para obter informações de múltiplos usuários.
 */
function register_api_users_get() {
  register_rest_route('api', '/users', [
    'methods' => WP_REST_Server::READABLE,
    'callback' => 'api_users_get',
    'permission_callback' => function() {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    }, 
  ]);
}
add_action('rest_api_init', 'register_api_users_get');