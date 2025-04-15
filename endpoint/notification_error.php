<?php
/**
 * Endpoint para reportar erros do sistema e notificar administrator/autores
 * 
 * @package MiraUP
 * @subpackage Notifications
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição REST
 * @return WP_REST_Response|WP_Error response da API
 */
function api_notifications_error(WP_REST_Request $request) {
  // Verifica rate limiting
  if (is_rate_limit_exceeded('put_notification')) {
    return new WP_Error(
      'rate_limit_exceeded', 'Limite de requisições excedido. Tente novamente mais tarde', ['status' => 429]
    );
  }

    // Obtém e valida usuário atual
    $current_user = wp_get_current_user();
    if (!$current_user->exists()) {
        return new WP_Error(
            'unauthorized',
            'Usuário não autenticado.',
            ['status' => 401]
        );
    }

    // Verifica status da conta
    $account_status = get_user_meta($current_user->ID, 'status_account', true);
    if ($account_status === 'pending') {
        return new WP_Error(
            'conta_nao_ativada',
            'Sua conta não está ativada.',
            ['status' => 403]
        );
    }

    // Valida e sanitiza os parâmetros
    $params = $request->get_json_params();
    $error_type = isset($params['error_type']) ? sanitize_text_field($params['error_type']) : '';
    $title = isset($params['title']) ? sanitize_text_field($params['title']) : '';
    $message = isset($params['message']) ? sanitize_textarea_field($params['message']) : '';
    $page = isset($params['page']) ? absint($params['page']) : null;
    $details = isset($params['details']) ? sanitize_textarea_field($params['details']) : '';
    
    // Validação dos fields obrigatórios
    $required_fields = ['error_type', 'title', 'message'];
    foreach ($required_fields as $field) {
        if (empty($$field)) {
            return new WP_Error(
                'required_field',
                sprintf('field obrigatório faltando: %s', $field),
                ['status' => 400]
            );
        }
    }

    // Prepara o conteúdo da notificação
    $notification_content = "<p><strong>Mensagem de erro:</strong> {$message}</p>";
    if (!empty($details)) {
        $notification_content .= "<p><strong>Detalhes:</strong> {$details}</p>";
    }
    $notification_content .= "<p><strong>Reportado por:</strong> {$current_user->display_name} ({$current_user->user_email})</p>";

    // 1. Envia notificação para administradores
    $administrator = get_users([
        'role' => 'administrator',
        'fields' => 'ID',
        'meta_query' => [
            [
                'key' => 'status_account',
                'value' => 'activated',
                'compare' => '='
            ]
        ]
    ]);

    $notifications_sent = [];
    foreach ($administrator as $admin_id) {
        $result = add_notification(
            $current_user->ID,
            'error_report',
            $message,
            "[ERRO {$error_type}] {$title}",
            $notification_content,
            $page,
            $admin_id
        );

        if (!is_wp_error($result)) {
            $notifications_sent[] = [
                'user_id' => $admin_id,
                'type' => 'administrador',
                'notification_id' => $result['data']['notification_id'] ?? 0
            ];
            
            // Envia e-mail para o administrador
            $admin_subject = "Novo reporte de erro: [{$error_type}] {$title}";
            $admin_message = "Um novo erro foi reportado no sistema:\n\n" . 
                             strip_tags(str_replace('</p>', "\n", $notification_content)) .
                             "\n\Página: " . get_permalink($page);
            
            send_notification_email(
                $admin_id,
                $page,
                $admin_subject,
                $admin_message
            );
        }
    }
    

    // Se houver post relacionado, notifica o autor
    if ($page) {
        $post = get_post($page);
        if ($post && $post->post_author != $current_user->ID) {
            $autor_id = $post->post_author;
            $status_autor = get_user_meta($autor_id, 'status_account', true);

            if ($status_autor === 'activated') {
                $result = add_notification(
                    $current_user->ID,
                    'error_report',
                    $message,
                    "[ERRO {$error_type}] {$title}",
                    $notification_content,
                    $page,
                    $autor_id
                );

                if (!is_wp_error($result)) {
                    $notifications_sent[] = [
                        'user_id' => $autor_id,
                        'type' => 'autor',
                        'notification_id' => $result['data']['notification_id'] ?? 0
                    ];
                    
                    // Envia e-mail para o autor do post
                    $author_subject = "Erro reportado no seu conteúdo: {$post->post_title}";
                    $author_message = "Um erro foi reportado no seu conteúdo:\n\n" . 
                                      strip_tags(str_replace('</p>', "\n", $notification_content)) . 
                                      "\n\Página: " . get_permalink($page);
                    
                    send_notification_email(
                        $autor_id,
                        $page,
                        $author_subject,
                        $author_message
                    );
                }
            }
        }
    }

    // 3. Envia e-mail de confirmação para o usuário que reportou
    $subject_email = 'Seu reporte de erro foi recebido';
    $message_email = sprintf(
        'Obrigado por reportar o erro. Nossa equipe foi notificada e entrará em contato se necessário. Detalhe do reporte: %s',
        $message
    );

    $email_sent = send_notification_email(
        $current_user->ID,
        $page,
        $subject_email,
        $message_email
    );

    // Prepara resposta
    $response = [
        'success' => true,
        'message' => 'Erro reportado com sucesso.',
        'data' => [
            'error_type' => $error_type,
            'notifications_sent' => count($notifications_sent),
            'details_notifications' => $notifications_sent,
            'email_confirmation_sent' => $email_sent,
            'related_post' => $page
        ]
    ];

    return new WP_REST_Response($response, 200);
}

/**
 * Registra a rota da API para reporte de erros
 */
function register_api_notifications_error() {
    register_rest_route('api', '/notification-error', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'api_notifications_error',
        'permission_callback' => function() {
            return is_user_logged_in();
        },
    ]);
}
add_action('rest_api_init', 'register_api_notifications_error');