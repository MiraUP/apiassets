<?php
/**
 * Conjunto de permissões de acesso ao sistema.
 * 
 * @package MiraUP
 * @subpackage Permissions
 * @since 1.0.0
 * @version 1.0.0
 */

class Permissions {
  /**
   * Verifica se o limite de requisições foi excedido
   * 
   * @param string $action Nome da ação para rate limiting
   * @return WP_Error|null Retorna erro se limite for excedido
   */
  public static function check_rate_limit($action, $amount = 100) {
    if (is_rate_limit_exceeded($action, $amount)) {
      return new WP_Error(
        'rate_limit_exceeded', 
        'Limite de requisições excedida.', 
        ['status' => 429]
      );
    }
    return null;
  }

  /**
   * Verifica se o usuário está autenticado
   * 
   * @param WP_User $user Objeto do usuário
   * @return WP_Error|null Retorna erro se não autenticado
   */
  public static function check_authentication($user) {
    if ($user->ID === 0) {
      return new WP_Error(
        'unauthorized', 
        'Usuário não autenticado.', 
        ['status' => 401]
      );
    }
    return null;
  }

  /**
   * Verifica se o usuário existe
   * 
   * @param WP_User $user Objeto do usuário
   * @return WP_Error|null Retorna erro se usuário não existir
   */
  public static function check_user_exists($user) {
    if (!$user->exists()) {
      return new WP_Error(
        'not_found', 
        'Usuário não encontrado.', 
        ['status' => 404]
      );
    }
    return null;
  }

  /**
   * Verifica se o usuário tem pelo menos uma das roles necessárias
   * 
   * @param WP_User $user Objeto do usuário
   * @param array $roles Lista de roles permitidas
   * @return WP_Error|null Retorna erro se não tiver permissão
   */
  public static function check_user_roles($user, $roles) {
    if (empty(array_intersect($user->roles, $roles))) {
      return new WP_Error(
        'forbidden', 
        'Você não tem permissão para esta ação.', 
        ['status' => 403]
      );
    }
    return null;
  }

  /**
   * Verifica se email já está em uso por outro usuário
   * 
   * @param WP_User $user Objeto do usuário
   * @param string $email Email a verificar
   * @return WP_Error|null Retorna erro se email já estiver em uso
   */
  public static function check_email_usage($user, $email) {
    if ($user->user_email !== $email && email_exists($email)) {
      return new WP_Error(
        'conflict', 
        'Email já cadastrado.', 
        ['status' => 409]
      );
    }
    return null;
  }

  /**
   * Verifica permissão para atualizar role e goal
   * 
   * @param WP_User $user Objeto do usuário
   * @param string $new_role Nova role a ser atribuída
   * @param string $new_goal Novo goal a ser atribuído
   * @return WP_Error|null Retorna erro se não tiver permissão
   */
  public static function check_role_goal_update($user, $new_role, $new_goal) {
    $is_admin = in_array('administrator', $user->roles, true);
    $current_role = $user->roles[0] ?? '';
    $current_goal = get_user_meta($user->ID, 'goal', true);

    if (!$is_admin && ($new_role !== $current_role || $new_goal !== $current_goal)) {
      return new WP_Error(
        'forbidden', 
        'Apenas administradores podem atualizar role e goal.', 
        ['status' => 403]
      );
    }
    return null;
  }

  /**
   * Verifica permissão para editar email
   * 
   * @param WP_User $current_user Usuário autenticado
   * @param int $target_user_id ID do usuário alvo
   * @return WP_Error|null Retorna erro se não tiver permissão
   */
  public static function check_email_edit_permission($current_user, $target_user_id) {
    $is_admin = in_array('administrator', $current_user->roles, true);
    if (!$is_admin && $current_user->ID !== $target_user_id) {
      return new WP_Error(
        'forbidden', 
        'Apenas administradores podem atualizar email de outros usuários.', 
        ['status' => 403]
      );
    }
    return null;
  }

  /**
   * Verifica status da conta do usuário
   * 
   * @param WP_User $user Objeto do usuário
   * @return WP_Error|null Retorna erro se conta tiver problemas
   */
  public static function check_account_status($user) {
    $status = get_user_meta($user->ID, 'status_account', true);

    if (empty($status) || $status === 'pending') {
      return new WP_Error(
        'account_status_error', 
        'Sua conta está com pendência.', 
        ['status' => 403]
      );
    }

    if ($status === 'disabled') {
      return new WP_Error(
        'account_status_error', 
        'Conta desativada. Entre em contato com o suporte.', 
        ['status' => 403]
      );
    }
    
    if ($status !== 'activated') {
    return new WP_Error(
      'account_status_error', 
      'Conta não ativa. Entre em contato com o suporte.', 
      ['status' => 403]
    );
    }

    return null;
  }

  /**
   * Verifica permissão para editar post
   * 
   * @param WP_User $user Objeto do usuário
   * @param int $post_id ID do post
   * @return WP_Error|null Retorna erro se não tiver permissão
   */
  public static function check_post_edit_permission($user, $post_id) {
    $post = get_post($post_id);
    if (!$post) {
      return new WP_Error(
        'not_found', 
        'Post não encontrado.', 
        ['status' => 404]
      );
    }

    $is_editor_or_admin = !empty(array_intersect($user->roles, ['editor', 'administrator']));
    if ($post->post_author != $user->ID && !$is_editor_or_admin) {
      return new WP_Error(
        'forbidden', 
        'Você não tem permissão para editar este post.', 
        ['status' => 403]
      );
    }
    return null;
  }

  /**
   * Verifica se credenciais estão corretas
   * 
   * @param WP_User $user Objeto do usuário
   * @param string $username Nome de usuário fornecido
   * @param string $password Senha fornecida
   * @return WP_Error|null Retorna erro se credenciais estiverem incorretas
   */
  /*public static function check_credentials($user, $username, $password) {
    if ($username !== $user->user_login) {
      return new WP_Error(
        'invalid_credentials', 
        'Credenciais incorretas.', 
        ['status' => 403]
      );
    }

    if (!wp_check_password($password, $user->user_pass, $user->ID)) {
      return new WP_Error(
        'invalid_credentials', 
        'Credenciais incorretas.', 
        ['status' => 403]
      );
    }
    return null;
  }*/
}