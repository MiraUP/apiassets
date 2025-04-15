<?php
/**
 * Conjunto de permissões de acesso ao sistema.
 * 
 * @package MiraUP
 * @subpackage Permissions
 * @since 1.0.0
 * @version 1.0.0
 */

/* Verifica o rate limiting */
function permition_is_rate_limit_exceeded($action) {
  if (is_rate_limit_exceeded($action)) {
    return new WP_Error('rate_limit_exceeded', 'Limite de requisições excedido.', ['status' => 429]);
  }
}

/* Verifica se o usuário está logado */
function permition_user_logged($current_user) {
  if ($current_user->ID === 0) {
    return new WP_Error('unauthorized', 'Usuário não autenticado.', ['status' => 401]);
  }
}

/* Verifica se o usuário existe */
function permition_user_exists($current_user) {
  if (!$current_user->exists()) {
    return new WP_Error('not_found', 'Usuário não encontrado.', ['status' => 404]);
  }
}

/* Verifica roles do usuários é válido */
function permition_user_role($current_user, $roles) {
  $has_permission = false;

  foreach ($roles as $role) {
    if (in_array($role, $user->roles)) {
      $has_permission = true;
      break;
    }
  }

  if (!$has_permission) {
    return new WP_Error('forbidden', 'Você não tem permissão para fazer essa ação.', ['status' => 403]);
  }
}

/* Verifica se o email já está em uso por outro usuário */
function permition_email_exists($current_user, $email) {
  $user = get_userdata($current_user->ID);
  if ($user->user_email !== $email && email_exists($email)) {
    return new WP_Error('conflict', 'Email já cadastrado.', ['status' => 409]);
  }
}

/* Limita a atualização do role e goal apenas para administradores */
function permition_role_goal_update($current_user, $role, $goal) {
  $is_admin = in_array('administrator', $current_user->roles, true);
  if (!$is_admin && ($role !== $current_user->roles[0] || $goal !== get_user_meta($current_user->ID, 'goal', true))) {
    return new WP_Error('forbidden', 'Apenas administradores podem atualizar o role e o goal.', ['status' => 403]);
  }
}

/* Limita a atualização do email apenas para administradores ou o próprio usuário */
function permition_email_edit($current_user, $user_id) {
  $is_admin = in_array('administrator', $current_user->roles, true);
  if (!$is_admin && $current_user->ID !== $user_id) {
    return new WP_Error('forbidden', 'Apenas administradores podem atualizar o email.', ['status' => 403]);
  }
}

/* Verifica se o role é válido */
function permition_role_valid($role) {
  $valid_roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];
  if (!in_array($role, $valid_roles, true)) {
    return new WP_Error('invalid_role', 'Função inválida.', ['status' => 400]);
  }
}

/* Verifica o status da conta do usuário */
function permition_account_status($current_user) {
  $status_account = get_user_meta($current_user->ID, 'status_account', true);
  if (empty($status_account) || $status_account === 'pending') {
    return new WP_Error('account_status_error', 'Sua conta tem está com uma pendencia.', ['status' => 403]);
  }

  if (empty($status_account) || $status_account === 'disabled') {
    return new WP_Error('account_status_error', 'Conta desativada. Entre em contato com os administradores do sistema.', ['status' => 403]);
  }
}

/* Verifica se o usuário é o autor do Ativo, adminstrador ou editor do sistema */
function permition_post_author($current_user, $post_id) {
  $asset = get_post($post_id);
  if (!$asset || ($asset->post_author != $userId && !in_array($user->roles[0], ['editor', 'administrator']))) {
    return new WP_Error('forbidden', 'Você não tem permissão para editar esse post.', ['status' => 403]);
  }
}

/* Verifica se o nome de usuário fornecido corresponde ao usuário logado */
function permition_username_match($current_user, $username) {
  if ($username !== $current_user->user_login) {
    return new WP_Error('invalid_username', 'Nome de usuário incorreto.', ['status' => 403]);
  }
}

/* Verifica se a senha está correta */
function permition_password_correct($current_user, $password) {
  if (!wp_check_password($password, $current_user->user_pass, $current_user->ID)) {
    return new WP_Error('invalid_credentials', 'Usuário ou senha incorretos.', ['status' => 403]);
  }
}