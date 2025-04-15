<?php
/**
 * Cria um novo comentário em um post
 *
 * @package MiraUP
 * @subpackage Comments
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição REST
 * @return WP_REST_Response|WP_Error Resposta da API
 */
function api_comment_post(WP_REST_Request $request) {
    // Verificação de rate limiting
    if (is_rate_limit_exceeded('create_comment')) {
      return new WP_Error('rate_limit_exceeded', 'Limite de requisições excedido.', ['status' => 429]);
    }
    // Obtém usuário atual e verifica autenticação
    $user = wp_get_current_user();
    if (!$user->exists()) {
      return new WP_Error( 'unauthorized', 'Usuário não autenticado.', ['status' => 401] );
    }
    
    // Verifica status da conta
    $account_status = get_user_meta($user->ID, 'status_account', true);
    if ($account_status === 'pending') {
      return new WP_Error( 'account_pending', 'Sua conta está pendente de aprovação.', ['status' => 403] );
    }

    // Valida e sanitiza os dados de entrada
    $post_id = absint($request->get_param('id'));
    $comment_content = sanitize_text_field($request->get_param('comment'));

    // Verifica se o conteúdo do comentário não está vazio
    if (empty($comment_content)) {
        return new WP_Error( 'invalid_data', 'O conteúdo do comentário não pode estar vazio.', ['status' => 400] );
    }

    if (!$post_id || !get_post($post_id)) {
        return new WP_Error( 'invalid_post', 'Post inválido ou não encontrado.', ['status' => 404] );
    }

    // Prepara os dados do comentário
    $comment_data = [
        'user_id'           => $user->ID,
        'comment_author'    => $user->user_login,
        'comment_content'   => $comment_content,
        'comment_post_ID'   => $post_id,
        'comment_approved'  => 1, // Auto-aprovação para usuários logados
        'comment_type'      => 'comment',
    ];

    // Insere o comentário
    $comment_id = wp_insert_comment(wp_slash($comment_data));

    if (is_wp_error($comment_id)) {
        return new WP_Error( 'comment_failed', 'Falha ao criar o comentário.', ['status' => 500] );
    }

    // Obtém o comentário completo para retorno
    $comment = get_comment($comment_id);

    return rest_ensure_response([
        'success' => true,
        'message' => 'Comentário enviado com sucesso!',
        'data'    => [
            'comment_id'      => $comment->comment_ID,
            'post_id'         => $comment->comment_post_ID,
            'author'          => $comment->comment_author,
            'content'         => $comment->comment_content,
            'date'            => $comment->comment_date,
        ]
    ]);
}

/**
 * Registra o endpoint REST para criação de comentários
 */
function register_api_comment_post() {
  register_rest_route('api', '/comment/(?P<id>\d+)', [
      'methods'  => WP_REST_Server::CREATABLE,
      'callback' => 'api_comment_post',
      'permission_callback' => function () {
            return is_user_logged_in(); // Apenas usuários autenticados podem acessar
        },
  ]);
}
add_action('rest_api_init', 'register_api_comment_post');