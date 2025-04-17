<?php
/**
 * Função para criar a tabela de notificações e enviar emails de notificação
 * 
 * @package MiraUP
 * @subpackage Notifications
 * @since 1.0.0
 * @version 1.0.0 
 */

/**
 * Cria a tabela de notificações no banco de dados
 */
function create_notifications_table() {
  global $wpdb;

  $table_name = $wpdb->prefix . 'notifications';
  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) UNSIGNED NOT NULL,
    notification_id bigint(20) UNSIGNED NOT NULL,
    post_id bigint(20) UNSIGNED NOT NULL,
    reader tinyint(1) DEFAULT 0 NOT NULL,
    marker varchar(50) NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY notification_id (notification_id),
    FOREIGN KEY (notification_id) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE
  ) $charset_collate;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
  dbDelta($sql);
}
add_action('init', 'create_notifications_table');

/**
 * Envia email de notificação para um usuário
 * 
 * @param int $user_id ID do usuário destinatário
 * @param int $post_id ID do post relacionado
 * @param string $subject Assunto do email
 * @param string $message Corpo da mensagem
 * @param string|null $email Endereço de email alternativo (opcional)
 * @return bool True se o email foi enviado com sucesso
 */
function send_notification_email(int $user_id, int $post_id, string $subject, string $message, string $email = null): bool {
  // Verificar autenticação
  if ($error = Permissions::check_authentication((int) wp_get_current_user()->ID)) {
    return $error;
  }

  $user = get_user_by('ID', $user_id);
  if (!$user || !$user->user_email) {
    return false;
  }

  $headers = [
    'Content-Type: text/html; charset=UTF-8',
    'From: '.get_bloginfo('name').'  <'.get_bloginfo('admin_email').'>',
    'Reply-To: '.get_bloginfo('admin_email')
  ];

  $site_name = get_bloginfo('name');
  $site_url = home_url();
  $logo_url = get_theme_mod('custom_logo') ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : '';
  $post = get_post($post_id );

  if($post) {
    $post->guid = get_permalink($post->ID); // Garante que o permalink seja obtido corretamente
  } else {
    $post = new stdClass();
    $post->guid = false; // Define um valor padrão caso o post não exista
  }

  $url_post = $post->guid;

  $html = '<table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
    <!-- Cabeçalho -->
    <tr>
      <td align="center" bgcolor="#f8f8f8" style="padding: 20px; border-bottom: 1px solid #eaeaea;">
        '.($logo_url ? '<img src="'.$logo_url.'" alt="'.$site_name.'" width="200" height="60" style="display: block; max-width: 200px; height: auto;">' : '<h1 style="margin: 0; font-size: 24px;">'.$site_name.'</h1>').'
      </td>
    </tr>
    <tr>
      <td style="padding: 30px 20px; font-family: Arial, sans-serif; line-height: 1.6; color: #333333;">
        <h2 style="margin-top: 0; color: #222222;">'.$subject.'</h2>
        <p style="margin-bottom: 20px;">'.nl2br($message).'</p>
        
        '.($url_post ? '
        <table border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin: 25px auto;">
          <tr>
            <td align="center" bgcolor="#0073aa" style="border-radius: 3px;">
              <a href="'.$url_post.'" target="_blank" style="background-color: #0073aa; border: 1px solid #0073aa; color: #ffffff; text-decoration: none; padding: 10px 20px; display: inline-block; font-weight: bold;">Acesse a página</a>
            </td>
          </tr>
        </table>
        ' : '').'
      </td>
    </tr>
    <!-- Rodapé -->
    <tr>
      <td bgcolor="#f8f8f8" style="padding: 20px; text-align: center; font-size: 12px; color: #777777; border-top: 1px solid #eaeaea; font-family: Arial, sans-serif;">
        <p style="margin: 0 0 10px 0;">© '.date('Y').' '.$site_name.'. Todos os direitos reservados.</p>
        <p style="margin: 0;">
          <a href="'.$site_url.'" style="color: #0073aa; text-decoration: none;">Visite nosso site</a> | 
          <a href="'.home_url('/politica-de-privacidade').'" style="color: #0073aa; text-decoration: none;">Política de Privacidade</a>
        </p>
      </td>
    </tr>
  </table>';

  if (empty($email)) {
    $email = $user->user_email;
  } else {
    $email = sanitize_email($email);
  }
  
  return wp_mail($email, $subject, $html, $headers);
}

/**
 * Adiciona uma notificação na tabela relacional
 *
 * @param int $user_id ID do usuário
 * @param int $notification_id ID da postagem de notificação
 * @param int $post_id ID do post relacionado
 * @param string $marker Tipo de marcador (asset|personal|system)
 * @return int|false ID da notificação inserida ou false em caso de erro
 */
function add_notification_to_table(
  int $user_id,
  int $notification_id, 
  int $post_id,
  string $marker
) {
  // Verificar autenticação
  if ($error = Permissions::check_authentication((int) wp_get_current_user()->ID)) {
    return $error;
  }
  
  global $wpdb;

  return $wpdb->insert(
    $wpdb->prefix . 'notifications',
    [
      'user_id' => $user_id,
      'notification_id' => $notification_id,
      'post_id' => $post_id,
      'marker' => $marker
    ],
    ['%d', '%d', '%d', '%s']
  );
}

/**
 * Adiciona uma nova notificação e envia para os usuários relevantes
 *
 * @param int $author_id ID do autor da notificação
 * @param string $type Tipo de notificação (asset|personal|system)
 * @param string $message Mensagem curta da notificação
 * @param string $title Título da notificação
 * @param string $content Conteúdo detalhado da notificação
 * @param int|null $post_id ID do post relacionado (opcional)
 * @param int $target_user_id ID do usuário específico (0 para todos)
 * @return array|WP_Error
 */
function add_notification(
  int $author_id,
  string $type,
  string $message,
  string $title,
  string $content,
  $post_id = null,
  int $target_user_id = 0
) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }

  // Busca os termos da taxonomia 'notifications'
  $notification_terms = get_terms([
    'taxonomy' => 'notification',
    'hide_empty' => false, // Inclui termos mesmo sem posts associados
    'fields' => 'slugs' // Retorna apenas os slugs
  ]);

  // Verifica se a busca foi bem sucedida
  if (is_wp_error($notification_terms)) {
    return new WP_Error(
      'taxonomy_error', 
      'Erro ao buscar tipos de notificação.', 
      ['status' => 500]
    );
  }

  // Verifica se o tipo está entre os termos válidos
  if (!in_array($type, $notification_terms)) {
    return new WP_Error(
      'invalid_type', 
      'Tipo de notificação inválido. Tipos válidos: ' . implode(', ', $notification_terms), 
      ['status' => 400]
    );
  }

  // Converte post_id para inteiro (trata string vazia como 0)
  $post_id = $post_id !== null ? absint($post_id) : 0;

  // Cria o post da notificação
  $notification_post = [
    'post_title'   => sanitize_text_field($title),
    'post_content' => wp_kses_post($content),
    'post_type'    => 'notification',
    'post_status'  => 'publish',
    'post_author'  => $author_id,
  ];

  $notification_id = wp_insert_post($notification_post, true);
  
  if (is_wp_error($notification_id)) {
    return $notification_id;
  }

  // Adiciona metadados e taxonomia
  wp_set_object_terms($notification_id, $type, 'notification_type');
  update_post_meta($notification_id, 'notification_author', $author_id);
  update_post_meta($notification_id, 'notification_message', sanitize_text_field($message));
  update_post_meta($notification_id, 'related_post_id', $post_id);
  $updatedCategory = wp_set_post_terms($notification_id, $type, 'notification');
  if (is_wp_error($updatedCategory)) {
    return new WP_Error('updated_category_failed', 'Erro ao atualizar a categoria da notificação.', ['status' => 500]);
  }

  $affected_users = 0;

  // Dispara a notificação para usuário específico
  if ($target_user_id > 0) {
    $target_user = get_user_by('id', $target_user_id);
    
    if (!$target_user) {
      return new WP_Error('invalid_user', 'Usuário alvo não encontrado.', ['status' => 404]);
    }

    // Verifica status da conta do usuário alvo
    $user_account_status = get_user_meta($target_user_id, 'status_account', true);
    if ($user_account_status !== 'activated') {
      return new WP_Error('user_account_not_activated', 'Conta do usuário alvo não está ativada.', ['status' => 403]);
    }

    // Adiciona notificação na tabela
    $added = add_notification_to_table($target_user_id, $notification_id, $post_id, $type);
      
    if ($added > 0) {
      $affected_users++;
      
      // Verifica preferências de email do usuário
      $email_enabled = (bool) get_user_meta($target_user_id, 'notification_email', true);
      $type_enabled = get_user_meta($target_user_id, 'notification_' . $type, true);
      
      if ($email_enabled && $type_enabled) {
        send_notification_email($target_user_id, $post_id, $title, $content);
      }
    }
  } 
  // Dispara para todos os usuários ativados
  else {
    $users = get_users([
      'meta_key' => 'status_account',
      'meta_value' => 'activated',
      'fields' => 'ids',
    ]);

    foreach ($users as $user_id) {
      $user_notification_prefs = [
        'asset'    => (bool) get_user_meta($user_id, 'notification_asset', true),
        'personal' => (bool) get_user_meta($user_id, 'notification_personal', true),
        'system'   => (bool) get_user_meta($user_id, 'notification_system', true),
      ];

      if ($user_notification_prefs[$type]) {
        $added = add_notification_to_table($user_id, $notification_id, $post_id, $type);
        
        if ($added) {
          $affected_users++;
          
          if ((bool) get_user_meta($user_id, 'notification_email', true)) {
            send_notification_email($user_id, $post_id, $title, $content);
          }
        }
      }
    }
  }

  return [
    'success' => true,
    'message' => 'Notificação criada e enviada com sucesso.',
    'data' => [
      'notification_id' => $notification_id,
      'affected_users' => $affected_users,
      'target_type' => $target_user_id > 0 ? 'specific_user' : 'all_users',
    ],
  ];
}