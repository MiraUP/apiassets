<?php
/**
 * Obtém as notificações do usuário autenticado
 * 
 * @package MiraUP
 * @subpackage Notifications
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição REST
 * @return WP_REST_Response|WP_Error Resposta da API
 */
function api_notifications_get(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('notification_get-' . $user_id, 20)) {
    return $error;
  }

  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }

  global $wpdb;
  $table_name = $wpdb->prefix . 'notifications';

  // Verifica se foi solicitada uma notificação específica
  $notification_id = $request->get_param('id');
  
  
  // Se um ID foi fornecido, busca apenas essa notificação
  if ($notification_id) {
    $notification_id = absint($notification_id);

    $query = $wpdb->prepare(
      "SELECT n.*, p.post_title, p.post_content, p.post_date, p.guid 
      FROM {$table_name} n
      INNER JOIN {$wpdb->posts} p ON n.notification_id = p.ID
      WHERE n.notification_id = %d AND n.user_id = %d AND p.post_type = 'notification'",
      $notification_id,
      $user->ID
    );

    $notification = $wpdb->get_row($query);

    // Busca todos os usuários que receberam esta notificação
    $recipients = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT 
        n.user_id, 
        u.user_email, 
        u.display_name, 
        n.reader as has_read,
        n.created_at as received_at
        FROM {$table_name} n
        INNER JOIN {$wpdb->users} u ON n.user_id = u.ID
        WHERE n.notification_id = %d",
        $notification_id
      )
    );

    if (!$notification) {
      return rest_ensure_response([
        'success' => false,
        'message' => 'Notificação não encontrada.',
        'data' => null,
      ]);
    }

    // Formata os dados da notificação específica
    $formatted_notification = format_notification_data($notification);

    return rest_ensure_response([
      'success' => true,
      'message' => 'Notificação encontrada com sucesso.',
      'data' => $formatted_notification,
      'recipients' => [
        'total_recipients' => count($recipients),
        'senders' => array_map(function($recipient) {
          return [
            'user_id' => (int) $recipient->user_id,
            'email' => sanitize_email($recipient->user_email),
            'name' => sanitize_text_field($recipient->display_name),
            'has_read' => (bool) $recipient->has_read,
            'received_at' => $recipient->received_at,
          ];
        }, $recipients),
      ],
    ]);
  }

  // Caso contrário, busca todas as notificações com paginação
  $params = $request->get_params();
  $per_page = isset($params['per_page']) ? absint($params['per_page']) : 10;
  $order = isset($params['order']) && in_array(strtoupper($params['order']), ['ASC', 'DESC']) 
          ? strtoupper(sanitize_text_field($params['order'])) 
          : 'DESC';
  $page = isset($params['page']) ? absint($params['page']) : 1;
  $read_status = isset($params['read']) ? filter_var($params['read'], FILTER_VALIDATE_BOOLEAN) : null;
  $offset = ($page - 1) * $per_page;

  // Base da query
  $query = "SELECT n.*, p.post_title, p.post_content, p.post_date, p.guid 
            FROM {$table_name} n
            INNER JOIN {$wpdb->posts} p ON n.notification_id = p.ID
            WHERE n.user_id = %d AND p.post_type = 'notification'";

  // Adiciona filtro por status de leitura se fornecido
  if ($read_status !== null) {
    $query .= " AND n.reader = %d";
    $query_args = [$user->ID, (int)$read_status];
  } else {
    $query_args = [$user->ID];
  }

  // Completa a query com ordenação e paginação
  $query .= " ORDER BY p.post_date " . $order . " LIMIT %d OFFSET %d";
  array_push($query_args, $per_page, $offset);

  // Prepara e executa a query
  $query = $wpdb->prepare($query, $query_args);
  $notifications = $wpdb->get_results($query);

  if (empty($notifications)) {
    return rest_ensure_response([
      'success' => true,
      'message' => 'Nenhuma notificação encontrada.',
      'data' => [],
    ]);
  }

  // Formata os dados de resposta
  $formatted_notifications = array_map('format_notification_data', $notifications);

  return rest_ensure_response([
    'success' => true,
    'message' => 'Notificações listadas com sucesso.',
    'data' => $formatted_notifications,
  ]);
}

/**
* Formata os dados de uma notificação para a resposta da API
*/
function format_notification_data($notification) {
  // Obtém metadados adicionais
  $post_meta = get_post_meta($notification->notification_id);
  $terms = get_the_terms($notification->notification_id, 'notification');

  // Obtém dados do post relacionado
  $post = get_post((int) $notification->post_id);
  
  // Obtém o nome do autor
  $author_id = isset($post_meta['notification_author'][0]) ? (int)$post_meta['notification_author'][0] : 0;
  $author_name = $author_id ? get_the_author_meta('display_name', $author_id) : 'Autor não disponível';

  if ($post) {
    $post->guid = get_permalink($post->ID); // Garante que o permalink seja obtido corretamente
  } else {
    $post = new stdClass();
    $post->guid = ''; // Define um valor padrão caso o post não exista
  }

  return [
    'id' => (int) $notification->notification_id,
    'title' => sanitize_text_field($notification->post_title),
    'post_id' => (int) $notification->post_id,
    'content' => wp_kses_post($notification->post_content),
    'category' => !is_wp_error($terms) ? $terms : [],
    'url_post' => esc_url($post->guid),
    'url_notification' => esc_url($notification->guid),
    'message' => isset($post_meta['notification_message'][0]) ? 
                sanitize_text_field($post_meta['notification_message'][0]) : 
                'Mensagem não disponível',
    'author' => $author_name,
    'read' => (bool) $notification->reader,
    'marker' => sanitize_text_field($notification->marker),
    'date' => $notification->post_date,
  ];
}

/**
 * Registra as rotas da API de notificação
 */
function register_api_notifications_get() {
  // Rota para listar todas as notificações ou uma específica
  register_rest_route('api/v1', '/notifications/(?P<id>[0-9]+)', [
    'methods' => WP_REST_Server::READABLE,
    'callback' => 'api_notifications_get',
    'permission_callback' => function() {
        return is_user_logged_in();
    },
  ], true);

  // Rota alternativa sem o ID para compatibilidade
  register_rest_route('api/v1', '/notifications', [
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => 'api_notifications_get',
      'permission_callback' => function() {
          return is_user_logged_in();
      },
  ]);
}
add_action('rest_api_init', 'register_api_notifications_get');