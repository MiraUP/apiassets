<?php
/**
 * Função principal para buscar mídias com base no título e nas taxonomias
 *
 * @package MiraUP
 * @subpackage Media
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição REST.
 * @return WP_REST_Response Resposta formatada.
 */
function api_media_search(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('media_search-' . $user_id, 100)) {
    return $error;
  }

  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }    

  // Valida e sanitiza os parâmetros de entrada
  $search = sanitize_text_field($request->get_param('search'));
  $post_id = absint($request->get_param('post_id'));
  $icon_category = sanitize_text_field($request->get_param('icon_category'));
  $icon_style = sanitize_text_field($request->get_param('icon_style'));
  $page = absint($request->get_param('page')) ?: 1;
  $per_page = absint($request->get_param('per_page')) ?: 100; // Limite de resultados por página

  // Verifica se o ID do post foi informado
  if(empty($post_id)) {
    return new WP_Error('id_post_not_found', 'informe o ID do post onde será feita a pesquisa.', ['status' => 400]);
  }

  // Validação do post_id
  if (!$post_id || !get_post($post_id)) {
    return new WP_Error('invalid_post_id', 'ID do post inválido.', ['status' => 400]);
  }

  // Gera uma chave única para o cache com base nos parâmetros da requisição
  $cache_key = 'media_search_' . md5(serialize([
    'title' => $search,
    'post_id' => $post_id,
    'icon_category' => $icon_category,
    'icon_style' => $icon_style,
    'page' => $page,
    'per_page' => $per_page,
  ]));

  // Tenta obter os resultados do cache
  $cached_results = wp_cache_get($cache_key, 'media_search');
  if ($cached_results !== false) {
    return rest_ensure_response([
      'success' => true,
      'message' => 'Mídias encontradas com sucesso (cache).',
      'data' => $cached_results['data'],
      'pagination' => $cached_results['pagination'],
    ]);
  }

  // Busca os IDs dos previews
  $preview_ids = get_post_meta($post_id, 'previews', false);
  $preview_ids = array_map('absint', $preview_ids);
  $preview_ids = array_filter(array_unique($preview_ids));

  if (empty($preview_ids)) {
    return rest_ensure_response([
      'success' => true,
      'data' => [],
      'pagination' => [
        'page' => $page,
        'per_page' => $per_page,
        'total' => 0,
      ],
    ]);
  }

  global $wpdb;

  // Prepara o termo de busca para o título e taxonomias
  $search_query_like = '%' . $wpdb->esc_like($search) . '%';

  // Construção da query base
  $placeholders = implode(',', array_fill(0, count($preview_ids), '%d'));
  $sql = "SELECT DISTINCT p.ID, p.post_title, p.post_mime_type
          FROM {$wpdb->posts} p
          LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
          LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
          LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
          WHERE p.ID IN ($placeholders)
          AND (p.post_title LIKE %s OR t.name LIKE %s)";

  // Filtro por icon_category
  if (!empty($icon_category)) {
    $sql .= $wpdb->prepare(
      " AND EXISTS (
        SELECT 1 FROM {$wpdb->term_relationships} tr2
        JOIN {$wpdb->term_taxonomy} tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id
        JOIN {$wpdb->terms} t2 ON tt2.term_id = t2.term_id
        WHERE tr2.object_id = p.ID
        AND tt2.taxonomy = 'icon_category'
        AND t2.name = %s
      )",
      $icon_category
    );
  }

  // Filtro por icon_style
  if (!empty($icon_style)) {
    $sql .= $wpdb->prepare(
      " AND EXISTS (
        SELECT 1 FROM {$wpdb->term_relationships} tr3
        JOIN {$wpdb->term_taxonomy} tt3 ON tr3.term_taxonomy_id = tt3.term_taxonomy_id
        JOIN {$wpdb->terms} t3 ON tt3.term_id = t3.term_id
        WHERE tr3.object_id = p.ID
        AND tt3.taxonomy = 'icon_style'
        AND t3.name = %s
      )",
      $icon_style
    );
  }

  // Adiciona paginação
  $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, ($page - 1) * $per_page);

  // Executa a query
  $results = $wpdb->get_results(
    $wpdb->prepare($sql, array_merge($preview_ids, [$search_query_like, $search_query_like]))
  );

  // Verifica se há resultados
  if (empty($results)) {
    return rest_ensure_response([
      'success' => true,
      'message' => 'Nenhuma mídia encontrada.',
      'data' => [],
    ]);
  }

  // Formata os resultados
  $media_list = [];
  foreach ($results as $media) {
    $media_list[] = [
      'id' => $media->ID,
      'title' => $media->post_title,
      'icon_tags' => wp_get_post_terms($media->ID, 'icon_tag', ['fields' => 'names']),
      'icon_style' => wp_get_post_terms($media->ID, 'icon_style', ['fields' => 'names']),
      'icon_category' => wp_get_post_terms($media->ID, 'icon_category', ['fields' => 'names']),
      'url' => wp_get_attachment_url($media->ID),
      'mime_type' => $media->post_mime_type,
    ];
  }

  // Prepara os dados para o cache
  $cache_data = [
    'data' => $media_list,
    'pagination' => [
      'page' => $page,
      'per_page' => $per_page,
      'total' => count($results),
    ],
  ];

  // Armazena os resultados no cache por 1 hora
  wp_cache_set($cache_key, $cache_data, 'media_search', HOUR_IN_SECONDS);

  // Retorna a resposta
  return rest_ensure_response([
    'success' => true,
    'message' => 'Mídias encontradas com sucesso.',
    'data' => $media_list,
    'pagination' => [
      'page' => $page,
      'per_page' => $per_page,
      'total' => count($results),
    ],
  ]);
}

/**
 * Registra a rota da API.
 */
function register_api_media_search() {
  register_rest_route('api/v1', '/media-search', [
    'methods'             => WP_REST_Server::READABLE,
    'callback'            => 'api_media_search',
    'permission_callback' => function() {
      return is_user_logged_in();
    },
  ]);
}

add_action('rest_api_init', 'register_api_media_search');