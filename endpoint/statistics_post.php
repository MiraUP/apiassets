<?php
/**
 * Endpoint para registrar e gerenciar estatísticas
 * 
 * @package MiraUP
 * @subpackage Statistics
 * @since 1.0.0
 * @version 1.0.0
 */

/**
 * Cria a tabela de estatísticas caso ela não exista
 */
function create_stats_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'stats';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            post_id bigint(20) NOT NULL,
            action_type varchar(20) NOT NULL COMMENT 'download ou view',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_post_action (user_id, post_id, action_type)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
add_action('init', 'create_stats_table');

/**
 * Registra um evento de download
 *
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com o resultado da operação ou erro.
 */
function api_statistics_post(WP_REST_Request $request) {
    // Verifica rate limiting
    if (is_rate_limit_exceeded('post_stats')) {
      return new WP_Error(
          'rate_limit_exceeded', 
          'Limite de requisições excedido. Tente novamente mais tarde.', 
          ['status' => 429]
      );
  }

  // Obtém e valida usuário atual
  $current_user = wp_get_current_user();
  if (!$current_user->exists()) {
      return new WP_Error(
          'unauthorized',
          'Usuário não autenticado.',
          ['status' => 401]
      );
  }

  // Verifica status da conta
  $account_status = get_user_meta($current_user->ID, 'status_account', true);
  if ($account_status !== 'activated') {
      return new WP_Error(
          'account_not_activated',
          'Sua conta não está ativada ou não tem permissão.',
          ['status' => 403]
      );
  }

  // Valida e sanitiza os parâmetros
  $post_id = absint($request->get_param('post_id'));
  $action_type = sanitize_text_field($request->get_param('action_type'));

  // Validações adicionais
  if (empty($post_id)) {
      return new WP_Error(
          'missing_parameter',
          'O ID do ativo é obrigatório.',
          ['status' => 400]
      );
  }

  if (!in_array($action_type, ['download', 'view'])) {
      return new WP_Error(
          'invalid_action',
          'Tipo de ação inválido. Use "download" ou "view".',
          ['status' => 400]
      );
  }

  $post = get_post($post_id);
  if (!$post) {
      return new WP_Error(
          'invalid_post',
          'O post especificado não existe.',
          ['status' => 404]
      );
  }

  global $wpdb;
  $table_name = $wpdb->prefix . 'stats';

  //Define o fuso horário
  date_default_timezone_set('America/Sao_Paulo');

  // Verifica se a mesma ação já foi registrada recentemente
  $time_threshold = date('Y-m-d H:i:s', strtotime('-5 minutes')); 
  
  $existing = $wpdb->get_row($wpdb->prepare(
      "SELECT id FROM $table_name 
      WHERE user_id = %d 
      AND post_id = %d 
      AND action_type = %s 
      AND created_at > %s 
      LIMIT 1",
      $current_user->ID,
      $post_id,
      $action_type,
      $time_threshold
  ));

  if ($existing) {
      return new WP_Error(
          'duplicate_action',
          'Esta ação já foi registrada recentemente para este usuário.',
          ['status' => 429]
      );
  }
  
  // Registra a ação
  $result = $wpdb->insert(
      $table_name,
      [
          'user_id' => $current_user->ID,
          'post_id' => $post_id,
          'action_type' => $action_type
      ],
      ['%d', '%d', '%s']
  );

  if ($result === false) {
      throw new Exception('Falha ao registrar ação');
  }

  // Atualiza o cache de contagem
  $meta_key = $action_type . '_count';
  $count = get_post_meta($post_id, $meta_key, true);
  $count = $count ? (int) $count + 1 : 1;
  update_post_meta($post_id, $meta_key, $count);

  return rest_ensure_response([
      'success' => true,
      'data' => [
          'record_id' => $wpdb->insert_id,
          'user_id' => $current_user->ID,
          'post_id' => $post_id,
          'action_type' => $action_type,
          'count' => $count,
          'timestamp' => current_time('mysql')
      ]
  ]);
}

/**
 * Registra a rota da API para criar dados estatísticos.
 */
function register_api_statistics_post() {
    register_rest_route('api', '/statistics', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'api_statistics_post',
        'permission_callback' => function() {
            return is_user_logged_in();
        },
    ]);
}
add_action('rest_api_init', 'register_api_statistics_post');