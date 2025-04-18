<?php
/**
 * Busca as taxonomias com base no tipo especificado ou retorna todas as taxonomias.
 *
 * @package MiraUP
 * @subpackage Taxonomy
 * @since 1.0.0
 * @version 1.0.0
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com as taxonomias ou erro.
 */
function api_taxonomy_get(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;
  
  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('taxonomy_get-' . $user_id, 100)) {
    return $error;
  }
  
  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }

  // Sanitiza e valida o parâmetro de taxonomia
  $taxonomy = sanitize_text_field($request->get_param('taxonomy'));

  // Define as taxonomias padrão
  $default_taxonomies = [
    'category',
    'post_tag',
    'developer',
    'origin',
    'compatibility',
    'icon_category',
    'icon_style',
    'icon_tag',
    'notification',
  ];

  // Busca as taxonomias
  if (empty($taxonomy)) {
    $terms = [];
    foreach ($default_taxonomies as $tax) {
      $terms = array_merge($terms, get_terms(['taxonomy' => $tax, 'hide_empty' => false]));
    }
  } else {
    if (!in_array($taxonomy, $default_taxonomies, true)) {
      return new WP_Error('invalid_taxonomy', 'Taxonomia inválida.', ['status' => 401]);
    }

    $terms = get_terms([
      'taxonomy' => $taxonomy,
      'hide_empty' => false,
    ]);
  }

  // Verifica se houve erro ao buscar as taxonomias
  if (is_wp_error($terms)) {
    return new WP_Error('taxonomy_error', 'Erro ao buscar as taxonomias.', ['status' => 500]);
  }

  return rest_ensure_response([
    'success' => true,
    'message' => 'Taxonomias listadas com sucesso.',
    'data' => $terms,
  ]);
}

/**
 * Registra a rota da API para buscar taxonomias.
 */
function register_api_taxonomy_get() {
  register_rest_route('api/v1', '/taxonomy', [
    'methods'             => WP_REST_Server::READABLE,
    'callback'            => 'api_taxonomy_get',
    'permission_callback' => function () {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}
add_action('rest_api_init', 'register_api_taxonomy_get');