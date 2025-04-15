<?php
/**
 * Atualiza um comentário existente
 *
 * @package MiraUP
 * @subpackage Comments
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição REST
 * @return WP_REST_Response|WP_Error Resposta da API
 */
function api_comment_put(WP_REST_Request $request) {
    // Verificação de rate limiting
    if (is_rate_limit_exceeded('update_comment')) {
        return new WP_Error('rate_limit_exceeded', 'Limite de requisições excedido.', ['status' => 429]);
      }

    // Obtém usuário atual e verifica autenticação
    $user = wp_get_current_user();
    if (!$user->exists()) {
        return new WP_Error( 'unauthorized', 'Usuário não autenticado.', ['status' => 401]
        );
    }

    // Verifica status da conta
    $account_status = get_user_meta($user->ID, 'status_account', true);
    if ($account_status === 'pending') {
        return new WP_Error( 'account_not_activated', 'Sua conta não está ativada.', ['status' => 403] );
    }

    // Valida e sanitiza os dados de entrada
    $comment_id = absint($request->get_param('id'));
    $comment_content = wp_kses_post($request->get_param('content'));

    // Validações básicas
    if (empty($comment_content)) {
        return new WP_Error( 'invalid_content', 'O conteúdo do comentário não pode estar vazio.', ['status' => 400] );
    }

    // Obtém o comentário existente
    $comment = get_comment($comment_id);
    if (!$comment) {
        return new WP_Error( 'comment_not_found', 'Comentário não encontrado.', ['status' => 404] );
    }

    // Verifica se o usuário é o autor do comentário ou um admin/editor
    $is_author = ($comment->user_id == $user->ID);
    $is_admin_or_editor = in_array('administrator', $user->roles) || in_array('editor', $user->roles);
    
    if (!$is_author && !$is_admin_or_editor) {
        return new WP_Error( 'permission_denied', 'Você não tem permissão para editar este comentário.', ['status' => 403] );
    }

    // Prepara dados para atualização
    $comment_data = [
        'comment_ID'      => $comment_id,
        'comment_content' => $comment_content,
    ];

    // Atualiza o comentário
    $updated = wp_update_comment(wp_slash($comment_data));

    if (is_wp_error($updated) || false === $updated) {
        return new WP_Error( 'update_failed', 'Falha ao atualizar o comentário.', ['status' => 500] );
    }

    // Obtém o comentário atualizado para retornar
    $updated_comment = get_comment($comment_id);

    return rest_ensure_response([
        'success' => true,
        'message' => 'Comentário atualizado com sucesso.',
        'data'    => [
            'comment_id' => $updated_comment->comment_ID,
            'content'    => $updated_comment->comment_content,
            'author'    => $updated_comment->comment_author,
            'date'      => $updated_comment->comment_date,
        ]
    ]);
}

/**
 * Registra o endpoint REST para atualização de comentários
 */
function register_api_comment_put() {
    register_rest_route('api', '/comment/(?P<id>\d+)', [
        'methods'  => WP_REST_Server::EDITABLE,
        'callback' => 'api_comment_put',
        'permission_callback' => function() {
            return is_user_logged_in(); // Apenas usuários autenticados podem acessar
        },
    ]);
}
add_action('rest_api_init', 'register_api_comment_put');