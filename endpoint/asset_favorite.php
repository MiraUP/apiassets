<?php
/**
 * Endpoint que gerencia a função de favoritos dos Ativos.
 *
 * @package MiraUP
 * @subpackage Assets
 * @since 1.0.0
 * @version 1.0.0 
 */

/**
 * Cria a tabela de favoritos no banco de dados.
 */
function create_favpost_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'favpost';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        _fav_id_post BIGINT(20) UNSIGNED NOT NULL,
        _fav_post TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        FOREIGN KEY (_fav_id_post) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('init', 'create_favpost_table');

/**
 * Função para lidar com a requisição de favoritos.
 *
 * @param WP_REST_Request $request Objeto da requisição.
 * @return WP_REST_Response|WP_Error Resposta da API.
 */
function api_favorite(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;
  
  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('assets_favorite-' . $user_id, 20)) {
    return $error;
  }
  
  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }

  // Obtém e sanitiza os dados da requisição
  $params = $request->get_json_params();
  $post_id = isset($params['post_id']) ? absint($params['post_id']) : 0;
  $favorite = isset($params['favorite']) ? (bool) $params['favorite'] : false;

  // Valida o ID do post
  if (!$post_id) {
    return new WP_Error('invalid_data', 'ID do post inválido.', ['status' => 400]);
  }

  global $wpdb;

  // Nome da tabela
  $table_name = $wpdb->prefix . 'favpost';

  // Verifica se já existe um registro para este post e usuário
  $existing_meta = $wpdb->get_row(
    $wpdb->prepare(
      "SELECT id, _fav_post FROM {$table_name} WHERE user_id = %d AND _fav_id_post = %d",
      $user_id,
      $post_id
    )
  );

  if ($existing_meta) {
    // Atualiza o registro existente
    $updated = $wpdb->update(
      $table_name,
      ['_fav_post' => $favorite],
      ['user_id' => $user_id, '_fav_id_post' => $post_id],
      ['%d'],
      ['%d', '%d']
    );

    if (false === $updated) {
      return new WP_Error('update_failed', 'Erro ao atualizar o favorito.', ['status' => 500]);
    }
  } else {
    // Insere um novo registro
    $inserted = $wpdb->insert(
      $table_name,
      [
        'user_id' => $user_id,
        '_fav_id_post' => $post_id,
        '_fav_post' => $favorite,
      ],
      ['%d', '%d', '%d']
    );

    if (false === $inserted) {
      return new WP_Error('insert_failed', 'Erro ao adicionar o favorito.', ['status' => 500]);
    }
  }

  return rest_ensure_response([
    'success' => true,
    'message' => 'Favorito atualizado com sucesso.',
    'data' => [
      'post_id' => $post_id,
      'favorite' => $favorite,
    ],
  ]);
}

/**
 * Registra a rota da API para favoritos.
 */
function register_api_favorite() {
  register_rest_route('api/v1', '/favorite', [
    'methods'             => WP_REST_Server::EDITABLE,
    'callback'            => 'api_favorite',
    'permission_callback' => function () {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}
add_action('rest_api_init', 'register_api_favorite');