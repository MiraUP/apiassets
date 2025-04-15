<?php
/**
 * Endpoint para pesquisar taxonomias.
 *
 * @package MiraUP
 * @subpackage Taxonomy
 * @since 1.0.0
 * @version 1.0.0
 * @param WP_REST_Request $request Objeto da requisição.
 * @return WP_REST_Response|WP_Error Resposta da API.
 */
function api_taxonomy_search(WP_REST_Request $request) {
  global $wpdb;

  // Verifica o rate limiting
  if (is_rate_limit_exceeded('taxonomy_search')) {
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
  $taxonomy = sanitize_text_field($request->get_param('taxonomy'));
  $page = absint($request->get_param('page')) ?: 1;
  $per_page = 20; // Limite de resultados por página
  $search_name = filter_var($request->get_param('name'), FILTER_VALIDATE_BOOLEAN);
  $search_slug = filter_var($request->get_param('slug'), FILTER_VALIDATE_BOOLEAN);

  // Verifica se o termo de pesquisa foi informado
  if (empty($search_query)) {
    return new WP_Error('search_term_not_found', 'Informe algum termo para pesquisar uma taxonomia.', ['status' => 400]);
  }
  
  // Verifica se a taxonomia foi informada
  if (empty($search_query)) {
    return new WP_Error('taxonomy_not_found', 'Informe a categoria de taxonomia que deve ser pesquisada.', ['status' => 400]);
  }

  // Prepara a busca direta no banco de dados
  $search_query_like = '%' . $wpdb->esc_like($search_query) . '%';
  $offset = ($page - 1) * $per_page;

  // Query base
  $sql = "SELECT t.term_id AS id, tt.taxonomy, t.name, t.slug, tt.description 
          FROM {$wpdb->terms} t
          INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
          WHERE 1=1";

  // Filtro por taxonomia
  if (!empty($taxonomy)) {
    $sql .= $wpdb->prepare(" AND tt.taxonomy = %s", $taxonomy);
  }

  // Adiciona condições de busca
  $conditions = [];
  if ($search_name || empty($request->get_params())) {
    $conditions[] = $wpdb->prepare("t.name LIKE %s", $search_query_like);
  }
  if ($search_slug || empty($request->get_params())) {
    $conditions[] = $wpdb->prepare("t.slug LIKE %s", $search_query_like);
  }

  // Combina as condições com OR
  if (!empty($conditions)) {
    $sql .= " AND (" . implode(" OR ", $conditions) . ")";
  }

  // Adiciona paginação
  $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $offset);

  // Executa a query
  $taxonomies = $wpdb->get_results($sql);

  // Verifica se há taxonomias
  if (empty($taxonomies)) {
    return rest_ensure_response([
      'success' => true,
      'message' => 'Nenhuma taxonomia encontrada.',
      'data' => [],
    ]);
  }

  // Formata os resultados
  $formatted_taxonomies = [];
  foreach ($taxonomies as $taxonomy) {
    $formatted_taxonomies[] = [
      'id' => $taxonomy->id,
      'taxonomy' => $taxonomy->taxonomy,
      'name' => $taxonomy->name,
      'slug' => $taxonomy->slug,
      'description' => $taxonomy->description,
    ];
  }

  return rest_ensure_response([
    'success' => true,
    'message' => 'Taxonomias encontradas com sucesso.',
    'data' => $formatted_taxonomies,
    'total_pages' => ceil(count($taxonomies) / $per_page), // Total de páginas
    'current_page' => $page,
  ]);
}

/**
 * Registra a rota da API para pesquisa de taxonomias.
 */
function register_api_taxonomy_search() {
    register_rest_route('api', '/taxonomy-search', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'api_taxonomy_search',
        'permission_callback' => function () {
            return is_user_logged_in(); // Apenas usuários autenticados podem acessar
        },
    ]);
}
add_action('rest_api_init', 'register_api_taxonomy_search');