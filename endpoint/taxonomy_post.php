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
    // Verifica o rate limiting
    if (is_rate_limit_exceeded('create_taxonomy')) {
        return new WP_Error('rate_limit_exceeded', 'Limite de requisições excedido.', ['status' => 429]);
    }

    // Obtém o usuário atual
    $user = wp_get_current_user();
    $user_id = $user->ID;

    // Verifica se o usuário está logado
    if ($user_id === 0) {
        return new WP_Error('unauthorized', 'Usuário não autenticado.', ['status' => 401]);
    }

    // Verifica se o usuário tem permissão para criar taxonomias
    $allowed_roles = ['administrator', 'editor', 'author', 'contributor'];
    $has_permission = false;

    foreach ($allowed_roles as $role) {
        if (in_array($role, $user->roles)) {
            $has_permission = true;
            break;
        }
    }

    if (!$has_permission) {
        return new WP_Error('forbidden', 'Você não tem permissão para criar taxonomias.', ['status' => 403]);
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
    register_rest_route('api', '/taxonomy', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'api_taxonomy_post',
        'permission_callback' => function () {
            return is_user_logged_in(); // Apenas usuários autenticados podem acessar
        },
    ]);
}
add_action('rest_api_init', 'register_api_taxonomy_post');