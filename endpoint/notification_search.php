<?php
/**
 * Endpoint para busca de notificações com SQL puro
 * 
 * @package MiraUP
 * @subpackage Notifications
 * @since 1.0.0
 * @version 1.0.3
 */
function api_notifications_search(WP_REST_Request $request) {
  global $wpdb;

  try {
    // Obtém o usuário atual
    $user = wp_get_current_user();
    $user_id = (int) $user->ID;
  
    // Verificar autenticação
    if ($error = Permissions::check_authentication($user)) {
      return $error;
    }

    // Verifica rate limiting
    if ($error = Permissions::check_rate_limit('assets_post-' . $user_id, 50)) {
      return $error;
    }
    
    // Verifica o status da conta do usuário
    if ($error = Permissions::check_account_status($user)) {
      return $error;
    }

    // Parâmetros sanitizados
    $params = [
      'title' => sanitize_text_field($request->get_param('title') ?? ''),
      'page' => absint($request->get_param('page') ?? 1),
      'per_page' => absint($request->get_param('per_page') ?? 10),
      'status' => sanitize_text_field($request->get_param('status') ?? ''),
      'orderby' => sanitize_text_field($request->get_param('orderby') ?? 'post_date'),
      'order' => sanitize_text_field($request->get_param('order') ?? 'DESC'),
      'marker' => sanitize_text_field($request->get_param('marker') ?? '')
    ];

    // Validação dos parâmetros
    if ($params['per_page'] > 100) {
      throw new Exception('O número máximo de itens por página é 100', 400);
    }

    // Ordenação segura
    $allowed_orderby = ['post_date', 'post_title', 'post_modified'];
    $params['orderby'] = in_array($params['orderby'], $allowed_orderby) ? $params['orderby'] : 'post_date';
    $params['order'] = strtoupper($params['order']) === 'ASC' ? 'ASC' : 'DESC';

    // Query principal
    $query = $wpdb->prepare(
      "SELECT 
        p.ID,
        p.post_title,
        p.post_content,
        p.post_date,
        pm_message.meta_value AS notification_message,
        pm_post.meta_value AS related_post_id,
        pm_author.meta_value AS notification_author,
        n.reader AS is_read,
        n.marker AS marker
      FROM {$wpdb->posts} p
      INNER JOIN {$wpdb->prefix}notifications n ON p.ID = n.notification_id
      LEFT JOIN {$wpdb->postmeta} pm_message ON p.ID = pm_message.post_id AND pm_message.meta_key = 'notification_message'
      LEFT JOIN {$wpdb->postmeta} pm_post ON p.ID = pm_post.post_id AND pm_post.meta_key = 'related_post_id'
      LEFT JOIN {$wpdb->postmeta} pm_author ON p.ID = pm_author.post_id AND pm_author.meta_key = 'notification_author'
      WHERE p.post_type = 'notification'
      AND p.post_status = 'publish'
      AND (n.user_id = %d OR n.user_id = 0)",
      $user->ID
    );

    // Filtro por título
    if (!empty($params['title'])) {
      $query .= $wpdb->prepare(" AND p.post_title LIKE %s", '%' . $wpdb->esc_like($params['title']) . '%');
    }

    // Filtro por status (read/unread)
    if (!empty($params['status']) && in_array($params['status'], ['read', 'unread'])) {
      $query .= $wpdb->prepare(" AND n.reader = %d", $params['status'] === 'read' ? 1 : 0);
    }

    // Filtro por marker
    if (!empty($params['marker'])) {
      $query .= $wpdb->prepare(" AND n.marker = %s", $params['marker']);
    }

    // Ordenação
    $query .= " ORDER BY p.{$params['orderby']} {$params['order']}";

    // Paginação
    $query .= $wpdb->prepare(" LIMIT %d, %d", 
      ($params['page'] - 1) * $params['per_page'], 
      $params['per_page']
    );

    $notifications = $wpdb->get_results($query);

    // Verifica erros na query
    if ($wpdb->last_error) {
      throw new Exception('Erro no banco de dados: ' . $wpdb->last_error, 500);
    }

    // Query para total de itens (sem LIMIT)
    $count_query = "SELECT COUNT(*)
      FROM {$wpdb->posts} p
      INNER JOIN {$wpdb->prefix}notifications n ON p.ID = n.notification_id
      WHERE p.post_type = 'notification'
      AND p.post_status = 'publish'
      AND (n.user_id = {$user->ID} OR n.user_id = 0)";

    if (!empty($params['title'])) {
      $count_query .= $wpdb->prepare(" AND p.post_title LIKE %s", '%' . $wpdb->esc_like($params['title']) . '%');
    }

    if (!empty($params['status']) && in_array($params['status'], ['read', 'unread'])) {
      $count_query .= $wpdb->prepare(" AND n.reader = %d", $params['status'] === 'read' ? 1 : 0);
    }

    if (!empty($params['marker'])) {
      $count_query .= $wpdb->prepare(" AND n.marker = %s", $params['marker']);
    }

    $total = $wpdb->get_var($count_query);

    // Formata a resposta
    $formatted = array_map(function($n) {
      $author = $n->notification_author ? get_user_by('id', $n->notification_author) : null;
      
      return [
        'id' => $n->ID,
        'title' => $n->post_title,
        'message' => $n->notification_message,
        'content' => $n->post_content,
        'created_at' => $n->post_date,
        'status' => $n->is_read ? 'read' : 'unread',
        'marker' => $n->marker,
        'related_post_id' => $n->related_post_id,
        'author' => $author ? [
          'id' => $author->ID,
          'name' => $author->display_name
        ] : null
      ];
    }, $notifications);

    // Resposta final
    $response = [
      'success' => true,
      'data' => [
        'notifications' => $formatted,
        'pagination' => [
          'total_items' => (int)$total,
          'total_pages' => ceil($total / $params['per_page']),
          'current_page' => $params['page'],
          'per_page' => $params['per_page']
        ],
        'search_params' => $params
      ]
    ];

    return rest_ensure_response($response);

  } catch (Exception $e) {
    $error_response = [
      'success' => false,
      'error' => [
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
        'details' => 'Ocorreu um erro ao processar sua solicitação'
      ]
    ];
    return new WP_REST_Response($error_response, $e->getCode() ?: 500);
  }
}

/**
 * Registra o endpoint de busca de notificações
 */
function register_api_notifications_search() {
  register_rest_route('api/v1', '/notifications-search', [
    'methods'  => WP_REST_Server::READABLE,
    'callback' => 'api_notifications_search',
    'permission_callback' => function () {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    }
  ]);
}
add_action('rest_api_init', 'register_api_notifications_search');