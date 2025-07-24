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
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;
  
  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }
  
  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }
  
  // Obtém e sanitiza os parâmetros da requisição
  $search_query = sanitize_text_field($request->get_param('search'));
  $_total = (int) sanitize_text_field($request['total']) ?: 9;
  $_page = (int) sanitize_text_field($request['page']) ?: 1;
  function parse_param($param) {
    // Se for nulo ou vazio, retorna array vazio
    if (empty($param)) {
      return [];
    }
    
    // Se já for array, retorna como está
    if (is_array($param)) {
      return $param;
    }
    
    // Se for string com vírgulas, divide em array
    if (strpos($param, ',') !== false) {
      return explode(',', $param);
    }
    
    // Caso contrário, retorna como array de um elemento
    return [$param];
  }

  $author_ids = array_filter(array_map('absint', parse_param($request->get_param('author'))));
  $category_ids = array_filter(array_map('absint', parse_param($request->get_param('category'))));
  $compatibilities = array_filter(array_map('sanitize_text_field', parse_param($request->get_param('compatibility'))));
  $developers = array_filter(array_map('sanitize_text_field', parse_param($request->get_param('developer'))));
  $origins = array_filter(array_map('sanitize_text_field', parse_param($request->get_param('origin'))));
  $favorite = filter_var($request->get_param('favorite'), FILTER_VALIDATE_BOOLEAN);
  
  // Prepara a chave de cache com base nos parâmetros da requisição
  $cache_key = 'asset_search_' . md5(serialize([
    'search' => $search_query,
    'authors' => $author_ids,
    'categories' => $category_ids,
    'compatibilities' => $compatibilities,
    'developers' => $developers,
    'origins' => $origins,
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
  
  global $wpdb;
    
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

  // Query para contar o total de itens
  $count_sql = $wpdb->prepare(
    "SELECT COUNT(DISTINCT p.ID) 
     FROM {$wpdb->posts} p
     WHERE p.post_type = 'post'
     AND p.post_status = 'publish'"
  );

  // Filtro por autor
  if (!empty($author_ids)) {
    $author_ids_placeholders = implode(',', array_fill(0, count($author_ids), '%d'));
    $sql .= $wpdb->prepare(" AND p.post_author IN ($author_ids_placeholders)", $author_ids);
  }

  // Filtro por categoria (taxonomia)
  if (!empty($category_ids)) {
    $category_placeholders = implode(',', array_fill(0, count($category_ids), '%d'));
    $sql .= $wpdb->prepare(" AND EXISTS (
      SELECT 1 FROM {$wpdb->term_relationships} tr2
      JOIN {$wpdb->term_taxonomy} tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id
      WHERE tr2.object_id = p.ID AND tt2.term_id IN ($category_placeholders)
    )", $category_ids);
  }

  // Filtro por compatibilidade (taxonomia) - Corrigido
if (!empty($compatibilities)) {
    $compatibility_placeholders = implode(',', array_fill(0, count($compatibilities), '%s'));
    $sql .= $wpdb->prepare(" AND EXISTS (
      SELECT 1 FROM {$wpdb->term_relationships} tr_comp
      JOIN {$wpdb->term_taxonomy} tt_comp ON tr_comp.term_taxonomy_id = tt_comp.term_taxonomy_id
      JOIN {$wpdb->terms} t_comp ON tt_comp.term_id = t_comp.term_id
      WHERE tr_comp.object_id = p.ID 
      AND tt_comp.taxonomy = 'compatibility'
      AND t_comp.term_id IN ($compatibility_placeholders)
    )", $compatibilities);
}

// Filtro por desenvolvedor (taxonomia) - Corrigido
if (!empty($developers)) {
    $developers_placeholders = implode(',', array_fill(0, count($developers), '%s'));
    $sql .= $wpdb->prepare(" AND EXISTS (
      SELECT 1 FROM {$wpdb->term_relationships} tr_dev
      JOIN {$wpdb->term_taxonomy} tt_dev ON tr_dev.term_taxonomy_id = tt_dev.term_taxonomy_id
      JOIN {$wpdb->terms} t_dev ON tt_dev.term_id = t_dev.term_id
      WHERE tr_dev.object_id = p.ID 
      AND tt_dev.taxonomy = 'developer'
      AND t_dev.term_id IN ($developers_placeholders)
    )", $developers);
}

// Filtro por origem (taxonomia) - Corrigido
if (!empty($origins)) {
    $origins_placeholders = implode(',', array_fill(0, count($origins), '%s'));
    $sql .= $wpdb->prepare(" AND EXISTS (
      SELECT 1 FROM {$wpdb->term_relationships} tr_orig
      JOIN {$wpdb->term_taxonomy} tt_orig ON tr_orig.term_taxonomy_id = tt_orig.term_taxonomy_id
      JOIN {$wpdb->terms} t_orig ON tt_orig.term_id = t_orig.term_id
      WHERE tr_orig.object_id = p.ID 
      AND tt_orig.taxonomy = 'origin'
      AND t_orig.term_id IN ($origins_placeholders)
    )", $origins);
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
  
  // Adiciona ordenação
  $sql .= " ORDER BY p.post_date DESC";

  // Query para contar o total de itens (sem paginação)
  $total_items = $wpdb->get_var($count_sql);

  // Adiciona paginação
  $offset = ($_page - 1) * $_total;
  $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $_total, $offset);

  // Executa a query principal
  $post_ids = $wpdb->get_col($sql);

  // Verifica se há posts
  if (empty($post_ids)) {
    return rest_ensure_response([
      'success' => true,
      'message' => 'Nenhum ativo encontrado.',
      'data' => [],
      'total_pages' => 0,
      'current_page' => $_page,
      'total_items' => 0
    ]);
  }

  // Formata os resultados
  $assets = [];
  foreach ($post_ids as $post_id) {
    $post = get_post($post_id);
    $post_meta = get_post_meta($post_id);

    // Obter a categoria principal (primeira categoria)
    $categories = get_the_terms($post_id, 'category');
    $main_category = !empty($categories) ? $categories[0]->name : '';

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
      'permalink' => get_permalink($post_id),
      'category' => $main_category, 
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
    'total_pages' => ceil($total_items / $_total),
    'current_page' => $_page,
    'total_items' => $total_items
  ]);
}

/**
 * Registra a rota da API para pesquisa de ativos.
 */
function register_api_asset_search() {
  register_rest_route('api/v1', '/asset-search', [
    'methods'             => WP_REST_Server::READABLE,
    'callback'            => 'api_asset_search',
    'permission_callback' => function () {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}
add_action('rest_api_init', 'register_api_asset_search');