<?php
/**
 * Atualiza um ativo digital no WordPress.
 *
 * @package MiraUP
 * @subpackage Assets
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição.
 * @return WP_REST_Response|WP_Error Resposta da API.
 */
function api_asset_put(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $userId = $user->ID;

  // Verifica o rate limiting
  if (is_rate_limit_exceeded('update_asset')) {
    return new WP_Error('rate_limit_exceeded', 'Limite de requisições excedido.', ['status' => 429]);
  }
  
  // Verifica se o usuário está logado
  if ($userId === 0) {
    return new WP_Error('unauthorized', 'Usuário não autenticado.', ['status' => 401]);
  }
  
  // Verifica o status da conta do usuário
  $statusAccount = get_user_meta($userId, 'status_account', true);
  if ($statusAccount != 'activated') {
    return new WP_Error('account_pending', 'Sua conta está pendente de aprovação.', ['status' => 403]);
  }
  
  // Verifica se o usuário está autenticado e tem permissão
  if (!$userId || !in_array($user->roles[0], ['contributor', 'author', 'editor', 'administrator'])) {
    return new WP_Error('forbidden', 'Você não tem permissão para acessar este endpoint.', ['status' => 403]);
  }
  
  // Sanitiza e valida os dados da requisição
  $params = $request->get_json_params();
  $post_id = absint($request['post_id']);
  $status = sanitize_text_field($request['status']);
  $title = sanitize_text_field($request['title']);
  $subtitle = sanitize_text_field($request['subtitle']);
  $content = wp_kses_post($request['content']);
  $category = sanitize_text_field($request['category']);
  $origin = sanitize_text_field($request['origin']);
  $developer = sanitize_text_field($request['developer']);
  $version = sanitize_text_field($request['version']);
  $font = sanitize_text_field($request['font']);
  $size_file = sanitize_text_field($request['size_file']);
  $download = esc_url_raw($request['download']);
  $compatibility = $request['compatibility'];
  $post_tag = $request['post_tag'];
  $emphasis_array = json_decode($request['emphasis']);
  $deleteEmphasis = sanitize_text_field($request['delete_emphasis']);
  $files = $request->get_file_params();
    
    // Verifica se o usuário é o autor do post ou um administrador
    $asset = get_post($post_id);
    if (!$asset || ($asset->post_author != $userId && !in_array($user->roles[0], ['editor', 'administrator']))) {
      return new WP_Error('permission_denied', 'Você não tem permissão para alterar este ativo.', ['status' => 403]);
    }
    
    // Processa a exclusão de emphasis
    if ($deleteEmphasis) {
      $delete_emphasis = delete_post_meta($post_id, 'emphasis', $deleteEmphasis);
      if (is_wp_error($delete_emphasis)) {
        return new WP_Error('delete_failed', 'Erro ao deletar o destaque.', ['status' => 500]);
      } else {
        return rest_ensure_response([
          'success' => true,
          'message' => 'Ativo atualizado com sucesso.',
          'data' => $deleteEmphasis,
      ]);
      }
    }

    // Verifica se os campos obrigatórios foram fornecidos
    $required_fields = [
      'title' => $title,
      'subtitle' => $subtitle,
      'content' => $content,
      'category' => $category,
      'tag' => $post_tag,
      'version' => $version,
      'compatibility' => $compatibility,
      'download' => $download,
    ];

    foreach ($required_fields as $field => $value) {
      if (empty($value)) {
        return new WP_Error('missing_field', sprintf('Dados incompletos. Informe o "%s".', $field), ['status' => 400]);
      }
    }
    
    // Verifica se o título foi alterado e se já existe outro post com o mesmo título
    $currentPost = get_post($post_id);
    if (strtolower($currentPost->post_title) !== strtolower($title)) {
        $existingPost = get_page_by_title($title, OBJECT, 'post');
        if ($existingPost) {
            return new WP_Error('duplicate_asset', 'Já existe um ativo com o mesmo título. Se há alguma alteração, altere o ativo existente.', ['status' => 409]);
        }
    }

    // Atualiza os dados do post
    $postData = [
        'ID' => $post_id,
        'post_status' => !empty($status) ? $status : 'publish',
        'post_title' => $title,
        'post_content' => $content,
    ];

    $updatedpost_id = wp_update_post($postData, true);
    if (is_wp_error($updatedpost_id)) {
        return new WP_Error('update_failed', 'Erro ao atualizar o Ativo.', ['status' => 500]);
    }

    // Atualiza taxonomias
    $updatedCategory = wp_set_post_terms($post_id, $category, 'category');
    if (is_wp_error($updatedCategory)) {
        return new WP_Error('updated_category_failed', 'Erro ao atualizar a categoria do Ativo.', ['status' => 500]);
    }

    $updatedOrigin = wp_set_post_terms($post_id, $origin, 'origin');
    if (is_wp_error($updatedOrigin)) {
        return new WP_Error('updated_origin_failed', 'Erro ao atualizar a origem do Ativo.', ['status' => 500]);
    }

    $updatedDeveloper = wp_set_post_terms($post_id, $developer, 'developer');
    if (is_wp_error($updatedDeveloper)) {
        return new WP_Error('updated_developer_failed', 'Erro ao atualizar o desenvolvedor do Ativo.', ['status' => 500]);
    }

    $updatedCompatibility = wp_set_post_terms($post_id, $compatibility, 'compatibility');
    if (is_wp_error($updatedCompatibility)) {
        return new WP_Error('updated_compatibility_failed', 'Erro ao atualizar as compatibilidades do Ativo.', ['status' => 500]);
    }

    $updatedpost_tag = wp_set_post_terms($post_id, $post_tag, 'post_tag');
    if (is_wp_error($updatedpost_tag)) {
        return new WP_Error('updated_post_tag_failed', 'Erro ao atualizar as tags do Ativo.', ['status' => 500]);
    }

    // Atualiza metadados
    $updatedSubtitle = update_post_meta($post_id, 'subtitle', $subtitle);
    if (is_wp_error($updatedSubtitle)) {
        return new WP_Error('updated_subtitle_failed', 'Erro ao atualizar o subtítulo do Ativo.', ['status' => 500]);
    }

    $updatedVersion = update_post_meta($post_id, 'version', $version);
    if (is_wp_error($updatedVersion)) {
        return new WP_Error('updated_version_failed', 'Erro ao atualizar a versão do Ativo.', ['status' => 500]);
    }

    $updatedsize_file = update_post_meta($post_id, 'size_file', $size_file);
    if (is_wp_error($updatedsize_file)) {
        return new WP_Error('updated_size_file_failed', 'Erro ao atualizar o tamanho do arquivo desse Ativo.', ['status' => 500]);
    }

    $updatedFont = update_post_meta($post_id, 'font', $font);
    if (is_wp_error($updatedFont)) {
        return new WP_Error('updated_font_failed', 'Erro ao atualizar a fonte do Ativo.', ['status' => 500]);
    }

    $updatedDownload = update_post_meta($post_id, 'download', $download);
    if (is_wp_error($updatedDownload)) {
        return new WP_Error('updated_download_failed', 'Erro ao atualizar o link de download do Ativo.', ['status' => 500]);
    }

  // Processa os arquivos de upload
  if ($files != []) {
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    // Verifica as extensões das imagens
    $allowed_types = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];
    if (!in_array($file_extension_thumbnail, $allowed_types)) {
      return new WP_Error(
        'invalid_file_type',
        'Apenas arquivos SVG, PNG, JPG, JPEG, WEBP e GIF são permitidos para a imagem de capa.',
        ['status' => 401]
      );
    }

    if (!empty($files['thumbnail'])) {
      $old_thumbnail_id = get_post_meta($post_id, 'thumbnail', true); // Busca o ID da thumbnail antiga
      
      // Deleta a thumbnail antiga, se existir
      if ($old_thumbnail_id) {
        wp_delete_attachment($old_thumbnail_id, true); // true = deleta o arquivo do servidor
      }
      
      $attachment_data = [
        'post_parent' => $post_id, // Associa o attachment ao post
      ];

      $new_thumbnail_id = media_handle_upload('thumbnail', $post_id); // Faz o upload da nova thumbnail
      wp_update_attachment_metadata($new_thumbnail_id, $attachment_data);
      
      if (is_wp_error($new_thumbnail_id)) {
        $response = new WP_Error('thumbnail_update_failed', 'Erro ao fazer upload da thumbnail.', ['status' => 500]);
        return rest_ensure_response($response);
      }
      // Atualiza o custom field com o ID da nova thumbnail
      update_post_meta($post_id, 'thumbnail', $new_thumbnail_id);
    }
    
    // Envia os arquivos de imagem de preview
    foreach ($files as $file => $array) {
      $asset_id = media_handle_upload( $file, $post_id );
      wp_update_attachment_metadata($asset_id, $attachment_data);
      if(is_numeric($asset_id)) {
        add_post_meta($post_id, 'previews', $asset_id);
      } else {
        $response = new WP_Error('previews_update_failed', 'Erro ao fazer upload dos previews.', ['status' => 500]);
        return rest_ensure_response($response);
      }
    }
  }
  // Processa os emphasis
  if (is_array($emphasis_array)) {
    global $wpdb;
    $table_name = $wpdb->postmeta;
      
    foreach ($emphasis_array as $emphasis) {
      $meta_id = isset($emphasis->meta_id) ? (int) $emphasis->meta_id : 0;
        $meta_value = isset($emphasis->meta_value) ? sanitize_text_field($emphasis->meta_value) : '';

        $existing_meta = $wpdb->get_row(
          $wpdb->prepare(
            "SELECT meta_id, meta_value FROM {$table_name} WHERE post_id = %d AND meta_key = 'emphasis' AND meta_id = %s",
            $post_id,
            $meta_id
          )
        );

        // Verifica se o meta_value já existe para o mesmo post (evita duplicidade)
        $existing_meta_value = $wpdb->get_row(
          $wpdb->prepare(
            "SELECT meta_id FROM {$table_name} WHERE post_id = %d AND meta_key = 'emphasis' AND meta_value = %s",
            $post_id,
            $meta_value
          )
        );

        if ($existing_meta_value && $existing_meta_value->meta_id != $meta_id) {
          return new WP_Error('duplicate_meta_value', 'Já existe um emphasis com o mesmo valor para este post.', ['status' => 409]);
        }
        
      if ($existing_meta) {
        
        $wpdb->update(
          $table_name,
          ['meta_value' => $meta_value],
          ['meta_id' => $existing_meta->meta_id],
          ['%s'],
          ['%d']
        );
      } else {
        $wpdb->insert(
          $table_name,
          [
            'post_id' => $post_id,
            'meta_key' => 'emphasis',
            'meta_value' => $meta_value,
          ],
          ['%d', '%s', '%s']
        );
      }
    }
  }
        
  return rest_ensure_response([
    'success' => true,
    'message' => 'Ativo atualizado com sucesso.',
    'data' => [
      'post_id' => $post_id,
      'emphasis' => $emphasis_array,
      'tag' => $post_tag,
      'compatibility' => $compatibility,
      'files' => $files,
    ],
  ]);
}

/**
 * Registra o endpoint da API para atualização de ativos.
 */
function register_api_asset_put() {
  register_rest_route('api', '/asset-put', [
    'methods' => WP_REST_Server::CREATABLE,
    'callback' => 'api_asset_put',
    'permission_callback' => function () {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}

add_action('rest_api_init', 'register_api_asset_put');