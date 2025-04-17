<?php
/**
 * Atualiza o status de leitura e dados de uma notificação
 * 
 * @package MiraUP
 * @subpackage Notifications
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição REST
 * @return WP_REST_Response|WP_Error Resposta da API
 */
function api_notifications_put(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('notification_post-' . $user_id, 25)) {
    return $error;
  }

  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }

  // Valida e sanitiza os parâmetros
  $params = $request->get_json_params();
  $notification_id = isset($params['id']) ? absint($params['id']) : 0;
  $mark_as_read = wp_validate_boolean($params['change_read']);
    
  // Dados adicionais para atualização (apenas administradores)
  $data_add = [
    'marker' => isset($params['marker']) ? sanitize_text_field($params['marker']) : null,
    'title' => isset($params['title']) ? sanitize_text_field($params['title']) : null,
    'content' => isset($params['content']) ? sanitize_textarea_field($params['content']) : null,
    'category' => isset($params['category']) ? sanitize_text_field($params['category']) : null,
    'message' => isset($params['message']) ? sanitize_text_field($params['message']) : null,
    'post_id' => isset($params['post_id']) ? absint($params['post_id']) : 0,
    'sender' => isset($params['sender']) ? array_map('absint', explode(',', sanitize_text_field($params['sender']))) : null
  ];

  if (!$notification_id) {
    return new WP_Error(
      'invalid_data', 'ID da notificação inválido', ['status' => 400]
    );
  }

  global $wpdb;
  $notifications_table = $wpdb->prefix . 'notifications';

  // Verifica existência da notificação
  $notification_exists = $wpdb->get_row(
    $wpdb->prepare(
      "SELECT * FROM {$notifications_table} 
      WHERE notification_id = %d AND user_id = %d",
      $notification_id,
      $user_id
    ),
    ARRAY_A
  );

  if (!$notification_exists) {
    return new WP_Error(
      'notification_not_found', 'Notificação não encontrada', ['status' => 404]
    );
  }

  // Atualiza o status de leitura
  $update = $wpdb->update(
    $notifications_table,
    [
      'reader' => (int) $mark_as_read,
      'marker' => $data_add['marker']
    ],
    [
      'notification_id' => $notification_id,
      'user_id' => $user_id,
    ],
    ['%d', '%d'],
    ['%d', '%d']
  );

  if ($update === false) {
    return new WP_Error( 'update_notification', 'Falha ao atualizar a notificação', ['status' => 500] );
  }
  
  // Atualiza a tabela de notificações
  $update_data_table = array_filter([
    'marker' => $data_add['marker'],
  ]);

  if (!empty($update_data_table)) {
    $wpdb->update(
      $notifications_table,
      $update_data_table,
      ['notification_id' => $notification_id],
      array_fill(0, count($update_data_table), '%s'),
      ['%d']
    );
  }

  if(!current_user_can('administrator')) {
    return rest_ensure_response([
      'success' => true,
      'mesagem' => 'Notificação lida com sucesso.',
      'data' => [
        'notification_id' => $notification_id,
        'read' => (bool) $mark_as_read,
      ],
    ], 200);
  }
    
  // Se houver dados adicionais para atualizar, verifica permissões
  $update_data_add = !empty(array_filter($data_add));
  if ($update_data_add) {
    if ($error = Permissions::check_user_roles($user, ['administrator'])) {
      return $error;
    }  

    // Atualiza os dados do post de notificação
    $update_data_post = [
      'ID' => $notification_id,
      'post_title' => $data_add['title'] ?: get_the_title($notification_id),
      'post_content' => $data_add['content'] ?: get_post_field('post_content', $notification_id),
    ];

    $update = wp_update_post($update_data_post, true);
      
    if (is_wp_error($update)) {
      return $update;
    }

    // Atualiza metadados e taxonomia
    if ($data_add['category']) {
      $updated_category = wp_set_object_terms($notification_id, $data_add['category'], 'notification');
      if (is_wp_error($updated_category)) {
        return new WP_Error('updated_category_failed', 'Erro ao atualizar a categoria da notificação.', ['status' => 500]);
      }
    }

    if ($data_add['message']) {
      update_post_meta($notification_id, 'notification_message', $data_add['message']);
    }

    if ($data_add['message']) {
      update_post_meta($notification_id, 'related_post_id', $data_add['post_id']);
    }

    // Atualiza a tabela de notificações
    $update_data_table = array_filter([
      'post_id' => $data_add['post_id'],
    ]);

    if (!empty($update_data_table)) {
      $wpdb->update(
        $notifications_table,
        $update_data_table,
        ['notification_id' => $notification_id],
        array_fill(0, count($update_data_table), '%s'),
        ['%d']
      );
    }
  }

  // Processa novos usuários se o remetente foi especificado
  if (!empty($data_add['sender'])) {
    $users_send = $data_add['sender'];
    
    // Busca usuários que já receberam a notificação
    $users_received = $wpdb->get_col(
      $wpdb->prepare(
        "SELECT user_id FROM {$notifications_table} 
        WHERE notification_id = %d",
        $notification_id
      )
    );

    // Filtra apenas usuários que ainda não receberam
    $news_users = array_diff($users_send, $users_received);
    
    // Prepara dados do e-mail
    $post_notification = get_post($notification_id);
    $subject = $post_notification->post_title;
    $message = $post_notification->post_content;
    $post_id = $data_add['post_id'] ?: 0;
    
    // Envia e-mails para novos usuários
    $sent = [];
    $failure = [];
      
    foreach ($news_users as $user_id) {
      // Verifica se o usuário existe e está ativo
      $user = get_user_by('id', $user_id);
      $account_status = get_user_meta($user_id, 'status_account', true);
      
      
      if ($user && $account_status === 'activated') {
        // Adiciona notificação na tabela
        $wpdb->insert(
          $notifications_table,
          [
            'user_id' => $user_id,
            'notification_id' => $notification_id,
            'post_id' => $post_id,
            'marker' => $data_add['marker'] ?: 'default',
            'reader' => 0,
            'created_at' => current_time('mysql')
          ],
          ['%d', '%d', '%d', '%s', '%d', '%s']
        );

        // Envia e-mail
        $envoy = send_notification_email(
          $user_id,
          $post_id,
          $subject,
          $message,
        );

        if ($envoy) {
          $sent[] = $user_id;
        } else {
          $failure[] = $user_id;
        }
      }
    }

    // Adiciona info de envios na resposta
    $result_submissions = [
      'news_users' => count($news_users),
      'emails_sent' => count($sent),
      'users_failure' => count($failure),
      'ids_sent' => $sent,
      'ids_failure' => $failure
    ];
  }


  return rest_ensure_response([
    'success' => true,
    'mesagem' => 'Notificação atualizada com sucesso.',
    'data' => [
      'notification_id' => $notification_id,
      'read' => (bool) $mark_as_read,
      'data_updates' => $update_data_add ? $data_add : null,
    ],
  ], 200);
}

/**
* Registra a rota da API para atualização de notificações
*/
function register_api_notifications_put() {
  register_rest_route('api/v1', '/notifications', [
    'methods'             => WP_REST_Server::EDITABLE,
    'callback'            => 'api_notifications_put',
    'permission_callback' => function() {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}
add_action('rest_api_init', 'register_api_notifications_put');