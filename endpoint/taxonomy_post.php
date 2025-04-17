<?php
/**
 * Cria uma nova taxonomia.
 *
 * @package MiraUP
 * @subpackage Taxonomy
 * @since 1.0.0
 * @version 1.0.0
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com o resultado da operação ou erro.
 */
function api_taxonomy_post(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;
  
  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('taxonomy_post-' . $user_id, 10)) {
    return $error;
  }
  
  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }
      
  // Restringe a ação do usuário por função
  if ($error = Permissions::check_user_roles($user, ['contributor', 'author', 'editor', 'administrator'])) {
    return $error;
  }

  // Sanitiza e valida os dados de entrada
  $taxonomy = sanitize_text_field($request['taxonomy']);
  $name = sanitize_text_field($request['name']);
  $slug = sanitize_text_field($request['slug']);
  $description = sanitize_text_field($request['description']);

  // Verifica se o tipo de taxonomia e o nome foram fornecidos
  if (empty($taxonomy)) {
    return new WP_Error('missing_taxonomy', 'Selecione o tipo de taxonomia que pretende cadastrar.', ['status' => 400]);
  }
  if (empty($name)) {
    return new WP_Error('missing_name', 'Informe o nome da taxonomia.', ['status' => 400]);
  }

  // Verifica se a taxonomia já existe
  $terms = get_terms([
    'taxonomy' => $taxonomy,
    'hide_empty' => false,
  ]);

  foreach ($terms as $term) {
    if ($term->name === $name) {
      return new WP_Error('taxonomy_exists', 'Taxonomia já cadastrada.', ['status' => 409]);
    }
  }

  // Cria a nova taxonomia
  $response = wp_insert_term($name, $taxonomy, [
    'description' => $description,
    'slug' => $slug,
  ]);

  if (is_wp_error($response)) {
    return new WP_Error('taxonomy_creation_failed', 'Erro ao criar a taxonomia.', ['status' => 500]);
  }

  return rest_ensure_response([
    'success' => true,
    'message' => 'Taxonomia criada com sucesso.',
    'data' => $response,
  ]);
}

/**
 * Registra a rota da API para criação de taxonomia.
 */
function register_api_taxonomy_post() {
  register_rest_route('api/v1', '/taxonomy', [
    'methods'             => WP_REST_Server::CREATABLE,
    'callback'            => 'api_taxonomy_post',
    'permission_callback' => function () {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}
add_action('rest_api_init', 'register_api_taxonomy_post');