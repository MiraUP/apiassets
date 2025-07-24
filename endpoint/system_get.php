<?php
/**
 * Obtém dados gerais do sistema WordPress.
 *
 * @package MiraUP
 * @subpackage System
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response Resposta da API com os dados do sistema.
 */
function api_system_info_get(WP_REST_Request $request) {
    // Obtém o usuário atual
    $user = wp_get_current_user();
    
    // Verificar autenticação
    if ($error = Permissions::check_authentication($user)) {
        return $error;
    }
    
    // Verifica o status da conta do usuário
    if ($error = Permissions::check_account_status($user)) {
        return $error;
    }

    // Obtém informações básicas do WordPress
    $site_name = get_bloginfo('name');
    $admin_email = get_bloginfo('admin_email');
    $site_url = get_bloginfo('url');
    $wp_version = get_bloginfo('version');

    // 2. Obter 20 thumbnails aleatórios
    global $wpdb;
    
    // Query para buscar IDs de thumbnails aleatórios
    $thumbnail_ids = $wpdb->get_col(
        "SELECT meta_value FROM {$wpdb->postmeta} 
        WHERE meta_key = 'thumbnail' 
        AND meta_value != ''
        ORDER BY RAND()
        LIMIT 10"
    );
    
    $random_thumbnails = [];
    
    foreach ($thumbnail_ids as $attachment_id) {
        $thumbnail_url = wp_get_attachment_image_url($attachment_id, 'medium');
        if ($thumbnail_url) {
            $random_thumbnails[] = [
                'id' => $attachment_id,
                'url' => $thumbnail_url,
                'full_url' => wp_get_attachment_image_url($attachment_id, 'full')
            ];
        }
    }
    
    // Contagem de posts por status
    $post_counts = wp_count_posts();
    $total_posts = $post_counts->publish;
    
    // Contagem de usuários
    $user_count = count_users();
    $total_users = $user_count['total_users'];
    
    // Contagem de comentários
    $comment_counts = wp_count_comments();
    $total_comments = $comment_counts->approved;

    return rest_ensure_response([
        'success' => true,
        'message' => 'Dados do sistema obtidos com sucesso.',
        'data' => [
            'site_info' => [
                'name' => $site_name,
                'url' => $site_url,
                'admin_email' => $admin_email,
                'wp_version' => $wp_version,
                'timezone' => wp_timezone_string(),
                'language' => get_bloginfo('language'),
                'charset' => get_bloginfo('charset'),
            ],
            'content' => [
                'posts' => $total_posts,
                'pages' => wp_count_posts('page')->publish,
                'media' => wp_count_posts('attachment')->inherit,
                'comments' => $total_comments,
                'random_thumbnails' => $random_thumbnails
            ],
            'users' => [
                'total' => $total_users,
                'roles' => $user_count['avail_roles'],
            ],
        ],
    ]);
}

/**
 * Registra a rota da API para obter informações do sistema.
 */
function register_api_system_info_get() {
    register_rest_route('api/v1', '/system-info', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'api_system_info_get',
        'permission_callback' => function () {
            return is_user_logged_in(); // Apenas usuários autenticados podem acessar
        }
    ]);
}
add_action('rest_api_init', 'register_api_system_info_get');