<?php
/**
 * Deleta um Ativo.
 *
 * @package MiraUP
 * @subpackage Assets
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição.
 * @return WP_REST_Response|WP_Error Resposta da API.
 */
function api_asset_delete(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;
  
  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('assets_delete-' . $user_id, 20)) {
    return $error;
  }

  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }
      
  // Restringe a ação do usuário por função
  if ($error = Permissions::check_user_roles($user, ['editor', 'administrator'])) {
    return $error;
  }

  // Obtém o ID do post e sanitiza
  $post_id = absint($request['id']);

  // Obtém o post
  $post = get_post($post_id);
  if (!$post) {
    return new WP_Error('post_not_found', 'Post não encontrado.', ['status' => 404]);
  }

  // Verifica se o usuário é o autor do post ou um administrador
  if ($error = Permissions::check_post_edit_permission($user, $post_id)) {
    return $error;
  }

  // Busca o ID da thumbnail e deleta a imagem
  $attachment_id = get_post_meta($post_id, 'thumbnail', true);
  if ($attachment_id) {
    wp_delete_attachment($attachment_id, true);
  }

  // Busca todos os IDs das imagens no custom field 'previews' e deleta cada uma
  $previews = get_post_meta($post_id, 'previews', false); // Retorna um array de valores
  foreach ($previews as $preview_id) {
    wp_delete_attachment($preview_id, true); // true = deleta o arquivo do servidor
  }

  global $wpdb;

  // Busca e deleta eventuais registros de favoritos
  $wpdb->delete(
    'wp_favpost', // Nome da tabela
    ['_fav_id_post' => $post_id], // Condição (WHERE)
    ['%d'] // Formato da condição (inteiro)
  );


  // Deleta o post
  $deleted = wp_delete_post($post_id, true);
  if (!$deleted) {
    return new WP_Error('delete_failed', 'Erro ao deletar o ativo.', ['status' => 500]);
  }

  return rest_ensure_response([
    'success' => true,
    'message' => 'Ativo deletado com sucesso.',
    'data'    => $deleted,
  ]);
}

/**
 * Registra o endpoint da API para deletar ativos.
 */
function register_api_asset_delete() {
  register_rest_route('api/v1', '/asset/(?P<id>[0-9]+)', [
    'methods'             => WP_REST_Server::DELETABLE,
    'callback'            => 'api_asset_delete',
    'permission_callback' => function () {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}

add_action('rest_api_init', 'register_api_asset_delete');