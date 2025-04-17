<?php
/**
 * Atualiza uma taxonomia existente.
 *
 * @package MiraUP
 * @subpackage Taxonomy
 * @since 1.0.0
 * @version 1.0.0
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com o resultado da operação ou erro.
 */
function api_taxonomy_put(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;
  
  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('taxonomy_put-' . $user_id, 10)) {
    return $error;
  }
  
  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }
      
  // Restringe a ação do usuário por função
  if (Permissions::check_user_roles($user, ['author', 'editor', 'administrator'])) {
    $args['include'] = [$user_id];
  }

  // Sanitiza e valida os dados de entrada
  $term_id = sanitize_text_field($request['term_id']);
  $taxonomy = sanitize_text_field($request['taxonomy']);
  $name = sanitize_text_field($request['name']);
  $slug = sanitize_text_field($request['slug']);
  $description = sanitize_text_field($request['description']);

  // Verifica se os campos obrigatórios foram fornecidos
  if (empty($term_id)) {
    return new WP_Error('missing_term_id', 'O ID do termo é obrigatório.', ['status' => 400]);
  }
  if (empty($taxonomy)) {
    return new WP_Error('missing_taxonomy', 'Selecione o tipo de taxonomia.', ['status' => 400]);
  }
  if (empty($name)) {
    return new WP_Error('missing_name', 'Informe o nome da taxonomia.', ['status' => 400]);
  }

  // Atualiza a taxonomia
  $response = wp_update_term($term_id, $taxonomy, [
    'name'        => $name,
    'slug'        => $slug,
    'description' => $description,
  ]);

  // Verifica se houve erro ao atualizar a taxonomia
  if (is_wp_error($response)) {
    return new WP_Error('taxonomy_update_failed', 'Erro ao atualizar a taxonomia.', ['status' => 500]);
  }

  return rest_ensure_response([
    'success' => true,
    'message' => 'Taxonomia atualizada com sucesso.',
    'data' => $response,
  ]);
}

/**
 * Registra a rota da API para atualização de taxonomia.
 */
function register_api_taxonomy_put() {
  register_rest_route('api/v1', '/taxonomy', [
    'methods'             => WP_REST_Server::EDITABLE,
    'callback'            => 'api_taxonomy_put',
    'permission_callback' => function () {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}
add_action('rest_api_init', 'register_api_taxonomy_put');