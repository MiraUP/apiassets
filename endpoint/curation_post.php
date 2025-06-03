<?php
/**
 * Endpoint para notificação de curadoria
 * 
 * @package MiraUP
 * @subpackage Curation
 * @since 1.0.0
 * @version 1.0.4
 */
function api_curation_post(WP_REST_Request $request) {
    // Obtém o usuário atual
    $user = wp_get_current_user();
    $user_id = (int) $user->ID;
    
    // Verificar autenticação
    if ($error = Permissions::check_authentication($user)) {
        return $error;
    }

    // Verifica rate limiting
    if ($error = Permissions::check_rate_limit('curation_notification-' . $user_id, 30)) {
        return $error;
    }
    
    // Verifica o status da conta do usuário
    if ($error = Permissions::check_account_status($user)) {
        return $error;
    }

    // Sanitiza e valida os dados de entrada
    $params = $request->get_params();
    $status = sanitize_text_field($params['status'] ?? '');
    $message = sanitize_textarea_field($params['message'] ?? '');
    $post_id = absint($params['post_id'] ?? 0);

    // Validação dos campos obrigatórios
    if (empty($status) || empty($message) || empty($post_id)) {
        return new WP_Error(
            'missing_fields', 
            'Todos os campos são obrigatórios: status, message, post_id', 
            ['status' => 400]
        );
    }

    // Valida os status permitidos
    $valid_status = ['pending', 'publish', 'draft'];
    if (!in_array($status, $valid_status)) {
        return new WP_Error(
            'invalid_status', 
            'Status inválido. Valores permitidos: ' . implode(', ', $valid_status), 
            ['status' => 400]
        );
    }

    // Verifica se o post existe
    $post = get_post($post_id);
    if (!$post) {
        return new WP_Error(
            'post_not_found', 
            'Post não encontrado.', 
            ['status' => 404]
        );
    }

    // Obtém o autor do post
    $author = get_userdata($post->post_author);

    // Mapeia mensagens padrão para cada status
    $default_messages = [
        'draft' => 'Sua submissão foi rejeitada pela curadoria.',
        'pending' => 'Sua submissão requer revisões antes da aprovação.',
        'publish' => 'Seu conteúdo foi publicado com sucesso.'
    ];

    // Usa a mensagem padrão se não for fornecida
    $notification_message = !empty($message) ? $message : ($default_messages[$status] ?? '');

    // Cria a notificação
    $notification_result = add_notification(
        $user_id,                        // ID do curador
        'curation',                      // Tipo de notificação
        'Atualização na curadoria',      // Título curto
        'Status do Ativo atualizado: ' . $status, // Título da notificação
        $notification_message,           // Mensagem detalhada
        $post->ID,                       // ID do post relacionado
        $author->ID                      // ID do autor a ser notificado
    );

    if (is_wp_error($notification_result)) {
        error_log('Erro ao criar notificação de curadoria: ' . $notification_result->get_error_message());
        return $notification_result;
    }

    // Atualiza o status do post se necessário
    if (in_array($status, ['publish'])) {
        wp_update_post([
            'ID' => $post_id,
            'post_status' => 'publish'
        ]);
    } elseif ($status === 'pending') {
        wp_update_post([
            'ID' => $post_id,
            'post_status' => 'pending'
        ]);
    } elseif ($status === 'draft') {
      wp_update_post([
          'ID' => $post_id,
          'post_status' => 'draft'
      ]);
    }

    // Registra a ação no log de atividades
    do_action('curation_status_updated', $post_id, $status, $user_id);

    return rest_ensure_response([
        'success' => true,
        'data' => [
            'notification_id' => $notification_result['notification_id'] ?? null,
            'post_id' => $post_id,
            'new_status' => $status,
            'message' => 'Notificação enviada com sucesso.'
        ]
    ]);
}

/**
 * Registra o endpoint de curadoria
 */
function register_api_curation_post() {
    register_rest_route('api/v1', '/curation', [
        'methods'  => WP_REST_Server::CREATABLE,
        'callback' => 'api_curation_post',
        'permission_callback' => function() {
            return is_user_logged_in();
        },
        'args' => [
            'post_id' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
                'sanitize_callback' => 'absint'
            ],
            'status' => [
                'required' => true,
                'enum' => ['approved', 'rejected', 'pending_revision', 'published']
            ],
            'message' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field'
            ]
        ]
    ]);
}
add_action('rest_api_init', 'register_api_curation_post');