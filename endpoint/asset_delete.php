<?php
/**
 * Deleta um Ativo.
 *
 * @package MiraUP
 * @subpackage Assets
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição.
 * @return WP_REST_Response|WP_Error Resposta da API.
 */
function api_asset_delete(WP_REST_Request $request) {
    global $wpdb;
    
    // Verifica o rate limiting
    if (is_rate_limit_exceeded('delete_asset')) {
      return new WP_Error('rate_limit_exceeded', 'Limite de requisições excedido.', ['status' => 429]);
    }

    // Obtém o ID do post e sanitiza
    $post_id = absint($request['id']);

    // Obtém o usuário atual
    $user = wp_get_current_user();
    $user_id = (int) $user->ID;

    // Verifica se o usuário está autenticado
    if ($user_id === 0) {
      return new WP_Error('unauthorized', 'Usuário não autenticado.', ['status' => 401]);
    }

    // Verifica o status da conta do usuário
    $status_account = get_user_meta($user_id, 'status_account', true);
    if ($status_account !== 'activated') {
      return new WP_Error('account_pending', 'Sua conta não está ativada.', ['status' => 403]);
    }

    // Verifica se o usuário tem permissão (administrator ou editor)
    if (!in_array($user->roles[0], ['administrator', 'editor'])) {
      return new WP_Error('forbidden', 'Você não tem permissão para acessar este endpoint.', ['status' => 403]);
    }

    // Obtém o post
    $post = get_post($post_id);
    if (!$post) {
        return new WP_Error('post_not_found', 'Post não encontrado.', ['status' => 404]);
    }

    // Verifica se o usuário é o autor do post ou um administrador/editor
    $author_id = (int) $post->post_author;
    if ($author_id !== $user_id && !in_array($user->roles[0], ['administrator', 'editor'])) {
        return new WP_Error('permission_denied', 'Você não tem permissão para deletar este ativo.', ['status' => 403]);
    }

    // Busca o ID da thumbnail e deleta a imagem
    $attachment_id = get_post_meta($post_id, 'thumbnail', true);
    if ($attachment_id) {
        wp_delete_attachment($attachment_id, true);
    }

    // Busca todos os IDs das imagens no custom field 'previews' e deleta cada uma
    $previews = get_post_meta($post_id, 'previews', false); // Retorna um array de valores
    foreach ($previews as $preview_id) {
        wp_delete_attachment($preview_id, true); // true = deleta o arquivo do servidor
    }

    // Busca e deleta eventuais registros de favoritos
    $wpdb->delete(
      'wp_favpost', // Nome da tabela
      ['_fav_id_post' => $post_id], // Condição (WHERE)
      ['%d'] // Formato da condição (inteiro)
    );


    // Deleta o post
    $deleted = wp_delete_post($post_id, true);
    if (!$deleted) {
        return new WP_Error('delete_failed', 'Erro ao deletar o ativo.', ['status' => 500]);
    }

    return rest_ensure_response([
        'success' => true,
        'message' => 'Ativo deletado com sucesso.',
        'data'    => $deleted,
    ]);
}

/**
 * Registra o endpoint da API para deletar ativos.
 */
function register_api_asset_delete() {
    register_rest_route('api', '/asset/(?P<id>[0-9]+)', [
        'methods' => WP_REST_Server::DELETABLE,
        'callback' => 'api_asset_delete',
        'permission_callback' => function () {
            return is_user_logged_in(); // Apenas usuários autenticados podem acessar
        },
    ]);
}

add_action('rest_api_init', 'register_api_asset_delete');