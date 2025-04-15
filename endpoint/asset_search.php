<?php
/**
 * Endpoint para pesquisar ativos digitais.
 *
 * @package MiraUP
 * @subpackage Assets
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição.
 * @return WP_REST_Response|WP_Error Resposta da API.
 */
function api_asset_search(WP_REST_Request $request) {
    global $wpdb;

    // Verifica o rate limiting
    if (is_rate_limit_exceeded('asset_search')) {
        return new WP_Error('rate_limit_exceeded', 'Limite de requisições excedido.', ['status' => 429]);
    }

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
        return new WP_Error('account_not_activated', 'Sua conta não está ativada.', ['status' => 403]);
    }

    // Obtém e sanitiza os parâmetros da requisição
    $search_query = sanitize_text_field($request->get_param('search'));
    $_total = (int) sanitize_text_field($request['total']) ?: 6;
    $_page = (int) sanitize_text_field($request['page']) ?: 1;
    $author_id = absint($request->get_param('author'));
    $category_id = absint($request->get_param('category'));
    $compatibility = sanitize_text_field($request->get_param('compatibility'));
    $developer = sanitize_text_field($request->get_param('developer'));
    $origin = sanitize_text_field($request->get_param('origin'));
    $favorite = filter_var($request->get_param('favorite'), FILTER_VALIDATE_BOOLEAN);

    // Prepara a chave de cache com base nos parâmetros da requisição
    $cache_key = 'asset_search_' . md5(serialize([
        'search' => $search_query,
        'author' => $author_id,
        'category' => $category_id,
        'compatibility' => $compatibility,
        'developer' => $developer,
        'origin' => $origin,
        'favorite' => $favorite,
        'total' => $_total,
        'page' => $_page,
    ]));

    // Tenta obter os resultados do cache
    $cached_results = wp_cache_get($cache_key, 'asset_search');
    if ($cached_results !== false) {
        return rest_ensure_response([
            'success' => true,
            'message' => 'Ativos encontrados com sucesso (cache).',
            'data' => $cached_results,
        ]);
    }

    // Prepara a busca direta no banco de dados
    $search_query_like = '%' . $wpdb->esc_like($search_query) . '%';

    // Query para buscar posts por título, subtítulo e tags
    $sql = $wpdb->prepare(
        "SELECT DISTINCT p.ID 
         FROM {$wpdb->posts} p
         LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'subtitle'
         LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
         LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
         LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
         WHERE p.post_type = 'post'
         AND p.post_status = 'publish'
         AND (
             p.post_title LIKE %s
             OR pm.meta_value LIKE %s
             OR t.name LIKE %s
         )",
        $search_query_like,
        $search_query_like,
        $search_query_like
    );

    // Filtro por autor
    if ($author_id > 0) {
        $sql .= $wpdb->prepare(" AND p.post_author = %d", $author_id);
    }

    // Filtro por categoria (taxonomia)
    if ($category_id > 0) {
        $sql .= $wpdb->prepare(" AND EXISTS (
            SELECT 1 FROM {$wpdb->term_relationships} tr2
            JOIN {$wpdb->term_taxonomy} tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id
            WHERE tr2.object_id = p.ID AND tt2.term_id = %d
        )", $category_id);
    }

    // Filtro por compatibilidade (taxonomia)
    if (!empty($compatibility)) {
        $sql .= $wpdb->prepare(" AND EXISTS (
            SELECT 1 FROM {$wpdb->term_relationships} tr3
            JOIN {$wpdb->term_taxonomy} tt3 ON tr3.term_taxonomy_id = tt3.term_taxonomy_id
            JOIN {$wpdb->terms} t3 ON tt3.term_id = t3.term_id
            WHERE tr3.object_id = p.ID AND t3.slug = %s
        )", $compatibility);
    }

    // Filtro por desenvolvedor (taxonomia)
    if (!empty($developer)) {
        $sql .= $wpdb->prepare(" AND EXISTS (
            SELECT 1 FROM {$wpdb->term_relationships} tr4
            JOIN {$wpdb->term_taxonomy} tt4 ON tr4.term_taxonomy_id = tt4.term_taxonomy_id
            JOIN {$wpdb->terms} t4 ON tt4.term_id = t4.term_id
            WHERE tr4.object_id = p.ID AND t4.slug = %s
        )", $developer);
    }

    // Filtro por origem (taxonomia)
    if (!empty($origin)) {
        $sql .= $wpdb->prepare(" AND EXISTS (
            SELECT 1 FROM {$wpdb->term_relationships} tr5
            JOIN {$wpdb->term_taxonomy} tt5 ON tr5.term_taxonomy_id = tt5.term_taxonomy_id
            JOIN {$wpdb->terms} t5 ON tt5.term_id = t5.term_id
            WHERE tr5.object_id = p.ID AND t5.slug = %s
        )", $origin);
    }

    // Filtro por favoritos (tabela wp_favpost)
    if ($favorite) {
        $favpost_table = $wpdb->prefix . 'favpost';
        $sql .= $wpdb->prepare(
            " AND EXISTS (
                SELECT 1 FROM {$favpost_table} f
                WHERE f.user_id = %d AND f._fav_id_post = p.ID AND f._fav_post = 1
            )",
            $user_id
        );
    }

    // Adiciona paginação
    $offset = ($_page - 1) * $_total;
    $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $_total, $offset);

    // Executa a query
    $post_ids = $wpdb->get_col($sql);

    // Verifica se há posts
    if (empty($post_ids)) {
        return rest_ensure_response([
            'success' => true,
            'message' => 'Nenhum ativo encontrado.',
            'data' => [],
        ]);
    }

    // Formata os resultados
    $assets = [];
    foreach ($post_ids as $post_id) {
        $post = get_post($post_id);
        $post_meta = get_post_meta($post_id);

        // Verifica se o post é favorito
        $favorite_query = $wpdb->prepare(
            "SELECT _fav_post FROM {$wpdb->prefix}favpost 
            WHERE user_id = %d AND _fav_id_post = %d",
            get_current_user_id(),
            $post_id
        );
        $favorite_result = $wpdb->get_var($favorite_query);
        $is_favorite = $favorite_result ? (bool) $favorite_result : false;

        $assets[] = [
            'id' => $post_id,
            'title' => $post->post_title,
            'subtitle' => !empty($post_meta['subtitle']) ? $post_meta['subtitle'][0] : '',
            'author' => get_the_author_meta('display_name', $post->post_author),
            'slug' => $post->post_name,
            'favorite' => $is_favorite,
            'date_create' => $post->post_date,
            'thumbnail' => !empty($post_meta['thumbnail']) ? wp_get_attachment_image_src($post_meta['thumbnail'][0], 'large')[0] : '',
            'developer' => get_the_terms($post_id, 'developer'),
            'origin' => get_the_terms($post_id, 'origin'),
            'compatibility' => get_the_terms($post_id, 'compatibility') ?: [],
            'download' => !empty($post_meta['download']) ? $post_meta['download'][0] : 'Sem link de download.',
        ];
    }

    // Armazena os resultados no cache por 1 hora
    wp_cache_set($cache_key, $assets, 'asset_search', HOUR_IN_SECONDS);

    return rest_ensure_response([
        'success' => true,
        'message' => 'Ativos encontrados com sucesso.',
        'data' => $assets,
        'total_pages' => ceil(count($post_ids) / $_total), // Total de páginas
        'current_page' => $_page,
    ]);
}

/**
 * Registra a rota da API para pesquisa de ativos.
 */
function register_api_asset_search() {
    register_rest_route('api', '/asset-search', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'api_asset_search',
        'permission_callback' => function () {
            return is_user_logged_in(); // Apenas usuários autenticados podem acessar
        },
    ]);
}
add_action('rest_api_init', 'register_api_asset_search');