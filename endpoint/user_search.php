<?php
 /**
 * Endpoint para pesquisar usuários.
 * 
 * @package MiraUP
 * @subpackage User
 * @since 1.0.0
 * @version 1.0.0
 * @param WP_REST_Request $request Objeto da requisição REST
 * @return WP_REST_Response|WP_Error Resposta da API
 */
function api_user_search(WP_REST_Request $request) {
    // Obtém o usuário atual
    $user = wp_get_current_user();
    $user_id = (int) $user->ID;
  
    // Verifica se o usuário está logado
    if ($error = Permissions::check_authentication($user)) {
      return $error;
    }

    // Verifica rate limiting
    if ($error = Permissions::check_rate_limit('user_search-' . $user_id, 200)) {
      return $error;
    }

    // Verifica o status da conta do usuário
    if ($error = Permissions::check_account_status($user)) {
      return $error;
    }
    
    // Restringe a busca ao próprio usuário se não for administrador
    if ($error = Permissions::check_user_roles($user, ['administrator'])) {
      return $error;
    }

    global $wpdb;

    // Obtém e sanitiza os parâmetros da requisição
    $search_query = sanitize_text_field($request->get_param('search'));
    $page = absint($request->get_param('page')) ?: 1;
    $per_page = 20; // Limite de resultados por página
    $search_email = filter_var($request->get_param('email'), FILTER_VALIDATE_BOOLEAN);
    $search_username = filter_var($request->get_param('username'), FILTER_VALIDATE_BOOLEAN);
    $search_display_name = filter_var($request->get_param('display_name'), FILTER_VALIDATE_BOOLEAN);
    $search_first_name = filter_var($request->get_param('first_name'), FILTER_VALIDATE_BOOLEAN);
    $search_last_name = filter_var($request->get_param('last_name'), FILTER_VALIDATE_BOOLEAN);

    // Verifica se o termo de pesquisa foi informado
    if (empty($search_query)) {
        return new WP_Error('search_term_not_found', 'Informe algum termo para pesquisar um usuário.', ['status' => 400]);
    }

    // Prepara a busca direta no banco de dados
    $search_query_like = '%' . $wpdb->esc_like($search_query) . '%';
    $offset = ($page - 1) * $per_page;

    // Query base
    $sql = "SELECT u.ID, u.user_email, u.user_nicename, u.display_name, 
                   um1.meta_value AS first_name, um2.meta_value AS last_name, 
                   um3.meta_value AS photo, um4.meta_value AS status_account, 
                   um5.meta_value AS wp_capabilities 
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
            LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
            LEFT JOIN {$wpdb->usermeta} um3 ON u.ID = um3.user_id AND um3.meta_key = 'photo'
            LEFT JOIN {$wpdb->usermeta} um4 ON u.ID = um4.user_id AND um4.meta_key = 'status_account'
            LEFT JOIN {$wpdb->usermeta} um5 ON u.ID = um5.user_id AND um5.meta_key = 'wp_capabilities'
            WHERE 1=1";

    // Adiciona condições de busca
    $conditions = [];
    if ($search_email || empty($request->get_params())) {
        $conditions[] = $wpdb->prepare("u.user_email LIKE %s", $search_query_like);
    }
    if ($search_username || empty($request->get_params())) {
        $conditions[] = $wpdb->prepare("u.user_nicename LIKE %s", $search_query_like);
    }
    if ($search_display_name) {
        $conditions[] = $wpdb->prepare("u.display_name LIKE %s", $search_query_like);
    }
    if ($search_first_name) {
        $conditions[] = $wpdb->prepare("um1.meta_value LIKE %s", $search_query_like);
    }
    if ($search_last_name) {
        $conditions[] = $wpdb->prepare("um2.meta_value LIKE %s", $search_query_like);
    }

    // Combina as condições com OR
    if (!empty($conditions)) {
        $sql .= " AND (" . implode(" OR ", $conditions) . ")";
    } else {
        $conditions[] = $wpdb->prepare("u.user_email LIKE %s", $search_query_like);
        $conditions[] = $wpdb->prepare("u.user_nicename LIKE %s", $search_query_like);
        $conditions[] = $wpdb->prepare("u.display_name LIKE %s", $search_query_like);

        $sql .= " AND (" . implode(" OR ", $conditions) . ")";
    }

    // Adiciona paginação
    $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, $offset);

    // Executa a query
    $users = $wpdb->get_results($sql);

    // Verifica se há usuários
    if (empty($users)) {
        return rest_ensure_response([
            'success' => true,
            'message' => 'Nenhum usuário encontrado.',
            'data' => [],
        ]);
    }

    // Formata os resultados
    $formatted_users = [];
    foreach ($users as $user) {
        $formatted_users[] = [
            'id' => $user->ID,
            'email' => $user->user_email,
            'username' => $user->user_nicename,
            'display_name' => $user->display_name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'photo' => !empty($user->photo) ? wp_get_attachment_image_src($user->photo, 'large')[0] : '',
            'status_account' => $user->status_account,
            'role' => get_user_role_from_serialized($user->wp_capabilities),
        ];
    }

    return rest_ensure_response([
        'success' => true,
        'message' => 'Usuários encontrados com sucesso.',
        'data' => $formatted_users,
        'total_pages' => ceil(count($users) / $per_page), // Total de páginas
        'current_page' => $page,
    ]);
}

/**
 * Registra a rota da API para pesquisa de usuários.
 */
function register_api_user_search() {
    register_rest_route('api', '/user-search', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'api_user_search',
        'permission_callback' => function () {
            return is_user_logged_in(); // Apenas usuários autenticados podem acessar
        },
    ]);
}
add_action('rest_api_init', 'register_api_user_search');

/**
 * Extrai o role do usuário a partir do valor serializado.
 *
 * @param string $serialized_roles Valor serializado dos roles.
 * @return string|null Retorna o primeiro role encontrado ou null se não houver.
 */
function get_user_role_from_serialized($serialized_roles) {
  // Desserializa o valor
  $roles = maybe_unserialize($serialized_roles);

  // Verifica se é um array e retorna o primeiro role
  if (is_array($roles) && !empty($roles)) {
      return array_key_first($roles); // Retorna o primeiro role
  }

  return null; // Retorna null se não houver roles
}
