<?php
/**
 * Deleta uma taxonomia existente.
 *
 * @package MiraUP
 * @subpackage Taxonomy
 * @since 1.0.0
 * @version 1.0.0
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com o resultado da operação ou erro.
 */
function api_taxonomy_delete(WP_REST_Request $request) {
    // Verifica o rate limiting
    if (is_rate_limit_exceeded('delete_taxonomy')) {
      return new WP_Error('rate_limit_exceeded', 'Limite de requisições excedido.', ['status' => 429]);
    }

    // Obtém o usuário atual
    $user = wp_get_current_user();
    $user_id = (int) $user->ID;

    // Verifica se o usuário está logado
    if ($user_id === 0) {
      return new WP_Error('unauthorized', 'Usuário não autenticado.', ['status' => 401]);
    }

    // Verifica se o usuário tem um dos roles permitidos
    $allowed_roles = ['administrator', 'editor'];
    $has_permission = false;

    foreach ($allowed_roles as $role) {
      if (in_array($role, $user->roles)) {
        $has_permission = true;
        break;
      }
    }

    if (!$has_permission) {
      return new WP_Error('forbidden', 'Você não tem permissão para deletar taxonomias.', ['status' => 403]);
    }

    // Verifica o status da conta do usuário
    $status_account = get_user_meta($user_id, 'status_account', true);
    if ($status_account === 'pending') {
      return new WP_Error('account_pending', 'Sua conta está pendente de aprovação.', ['status' => 403]);
    }

    // Sanitiza e valida os dados de entrada
    $term_id = (int) sanitize_text_field($request['term_id']);
    $taxonomy = sanitize_text_field($request['taxonomy']);

    // Verifica se os campos obrigatórios foram fornecidos
    if (empty($term_id)) {
      return new WP_Error('missing_term_id', 'O ID do termo é obrigatório.', ['status' => 400]);
    }
    if (empty($taxonomy)) {
      return new WP_Error('missing_taxonomy', 'Selecione o tipo de taxonomia.', ['status' => 400]);
    }

    // Verifica se a taxonomia existe
    $term_exists = term_exists($term_id, $taxonomy);
    if ($term_exists === 0 || $term_exists === null) {
      return new WP_Error('taxonomy_not_found', 'Taxonomia não encontrada.', ['status' => 404]);
    }

    // Deleta a taxonomia
    $deleted = wp_delete_term($term_id, $taxonomy);

    if (is_wp_error($deleted)) {
      return new WP_Error('taxonomy_deletion_failed', 'Falha ao deletar a taxonomia.', ['status' => 500]);
    }

    return rest_ensure_response([
      'success' => true,
      'message' => 'Taxonomia deletada com sucesso.',
    ]);
}

/**
 * Registra a rota da API para deletar taxonomia.
 */
function register_api_taxonomy_delete() {
  register_rest_route('api', '/taxonomy', [
    'methods'             => WP_REST_Server::DELETABLE,
    'callback'            => 'api_taxonomy_delete',
    'permission_callback' => function () {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}
add_action('rest_api_init', 'register_api_taxonomy_delete');