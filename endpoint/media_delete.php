<?php
/**
 * Deleta um attachment de um ativo digital
 *
 * @package MiraUP
 * @subpackage Media
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição REST
 * @return WP_REST_Response|WP_Error Resposta da API
 */
function api_media_delete(WP_REST_Request $request) {

    // Obtém usuário atual
    $user = wp_get_current_user();
    $user_id = (int) $user->ID;

    // Verifica o rate limiting
    if (is_rate_limit_exceeded('update_asset')) {
        return new WP_Error('rate_limit_exceeded', 'Limite de requisições excedido.', ['status' => 429]);
    }

    // Verifica se o usuário tem permissão para acessar o endpoint
    if ($user_id === 0 || !in_array('administrator', $user->roles) && !in_array('editor', $user->roles)) {
        return new WP_Error('permission_denied', 'Sem permissão para acessar este recurso.', ['status' => 401]);
    }
    
    // Verifica o status da conta do usuário
    $status_account = get_user_meta($user_id, 'status_account', true);
    if ($status_account !== 'activated') {
        return new WP_Error('account_not_activated', 'Sua conta não está ativada.', ['status' => 403]);
    }

    // Valida e sanitiza os parâmetros
    $params = $request->get_params();
    $url = sanitize_text_field($request['post_slug']) ?: '';
    $media_id = absint($params['media_id'] ?? 0);

    // Extrai o slug da URL
    $post_slug = basename($url); // Obtém o último segmento da URL (slug)
    if (empty($post_slug)) {
        return new WP_Error('invalid_url', 'URL do post inválida.', ['status' => 400]);
    }
    
    // Busca o post pelo slug
    $asset = get_page_by_path($url, OBJECT, 'post');
    if (!$asset) {
        return new WP_Error('post_not_found', 'Post não encontrado.', ['status' => 404]);
    }

    $asset_id = $asset->ID;

    // Verifica os IDs
    if ($asset_id <= 0 || $media_id <= 0) {
        return new WP_Error( 'invalid_ids', 'IDs do ativo e/ou mídia são inválidos.', ['status' => 400] );
    }

    // Obtém e valida o ativo
    $asset = get_post($asset_id);

    if (!$asset || $asset->post_type !== 'post') {
        return new WP_Error( 'asset_not_found', 'Ativo não encontrado.', ['status' => 404] );
    }

    // Verifica permissões do usuário
    $is_author = ($asset->post_author === $user_id);
    $is_admin_or_editor = in_array('administrator', $user->roles) || in_array('editor', $user->roles);
    
    if (!$is_author && !$is_admin_or_editor) {
        return new WP_Error( 'permission_denied', 'Você não tem permissão para deletar esta mídia.', ['status' => 403] );
    }

    // Verifica se a mídia pertence ao ativo
    $media_meta = get_post_meta($asset_id, 'previews', false);
    if (!in_array($media_id, $media_meta)) {
        return new WP_Error( 'media_not_found', 'Mídia não encontrada ou não pertence ao ativo.', ['status' => 404] );
    }

    // Executa a deleção
    $attachment_deleted = wp_delete_attachment($media_id, true);
    $meta_deleted = delete_post_meta($asset_id, 'previews', $media_id);

    if (!$attachment_deleted || !$meta_deleted) {
        return new WP_Error( 'deletion_failed', 'Erro ao deletar a mídia.', ['status' => 500] );
    }

    return rest_ensure_response([
        'success' => true,
        'message' => 'Mídia deletada com sucesso.',
        'data' => [
            'asset_id' => $asset_id,
            'media_id' => $media_id
        ]
    ]);
}

function register_api_media_delete() {
  register_rest_route('api', '/media', [
      'methods' => WP_REST_Server::DELETABLE,
      'callback' => 'api_media_delete',
      'permission_callback' => function() {
        return is_user_logged_in();
        },
  ]);
}
add_action('rest_api_init', 'register_api_media_delete');
?>