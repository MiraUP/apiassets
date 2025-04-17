<?php
/**
 * Cria um novo ativo.
 *
 * @package MiraUP
 * @subpackage Assets
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com o resultado da operação ou erro.
 */
function api_asset_post(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;
    
  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('assets_put-' . $user_id, 20)) {
    return $error;
  }
  
  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }
      
  // Restringe a busca ao próprio usuário se não for administrador
  if ($error = Permissions::check_user_roles($user, ['contributor', 'author', 'editor', 'administrator'])) {
    return $error;
  }

  // Sanitiza e valida os dados de entrada
  $title = sanitize_text_field($request['title']);
  $subtitle = sanitize_text_field($request['subtitle']);
  $content = sanitize_text_field($request['content']);
  $version = sanitize_text_field($request['version']);
  $category = json_decode(sanitize_text_field($request['category']));
  $developer = json_decode(sanitize_text_field($request['developer']));
  $origin = json_decode(sanitize_text_field($request['origin']));
  $tags_input = json_decode(sanitize_text_field($request['tag']));
  $compatibility = json_decode(sanitize_text_field($request['compatibility']));
  $download = sanitize_text_field($request['download']);
  $emphasis_array = json_decode(sanitize_text_field($request['emphasis']));
  $font = sanitize_text_field($request['font']);
  $size_file = sanitize_text_field($request['size_file']);
  $files = $request->get_file_params();

  // Verifica se os campos obrigatórios foram fornecidos
  $required_fields = [
    'title' => $title,
    'subtitle' => $subtitle,
    'content' => $content,
    'category' => $category,
    'tag' => $tags_input,
    'version' => $version,
    'compatibility' => $compatibility,
    'download' => $download,
    'files' => $files,
  ];

  foreach ($required_fields as $field => $value) {
    if (empty($value)) {
      return new WP_Error('missing_field', sprintf('Dados incompletos. Informe o "%s".', $field), ['status' => 400]);
    }
  }

  // Verifica se já existe um post com o mesmo título
  $existing_post = get_page_by_title($title, OBJECT, 'post');
  if ($existing_post) {
    return new WP_Error('duplicate_asset', 'Já existe um ativo com o mesmo título. Se Há alguma alteração, altere o Ativo existente.', ['status' => 409]);
  }

  // Verifica as extensões das imagens
  $allowed_types = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];

  // Valida a imagem de capa (thumbnail)
  if (!empty($files['thumbnail'])) {
    $file_info_thumbnail = wp_check_filetype($files['thumbnail']['name']);
    $file_extension_thumbnail = strtolower($file_info_thumbnail['ext']);

    if (!in_array($file_extension_thumbnail, $allowed_types)) {
      return new WP_Error(
        'invalid_file_type',
        'Apenas arquivos SVG, PNG, JPG, JPEG, WEBP e GIF são permitidos para a imagem de capa.',
        ['status' => 400]
      );
    }
  }

  // Valida as imagens de previews
  if (!empty($files['previews'])) {
    foreach ($files['previews'] as $preview) {
      $file_info_preview = wp_check_filetype($preview['name']);
      $file_extension_preview = strtolower($file_info_preview['ext']);

      if (!in_array($file_extension_preview, $allowed_types)) {
        return new WP_Error(
          'invalid_file_type',
          'Apenas arquivos SVG, PNG, JPG, JPEG, WEBP e GIF são permitidos para as imagens de preview.',
          ['status' => 400]
        );
      }
    }
  }

  // Prepara os dados do post
  $post_data = [
    'post_author' => $user_id,
    'post_type' => 'post',
    'post_status' => 'publish',
    'post_title' => $title,
    'post_content' => $content,
    'tax_input' => [
      'post_tag' => $tags_input,
      'compatibility' => $compatibility,
      'developer' => $developer,
      'origin' => $origin,
    ],
    'meta_input' => [
      'subtitle' => $subtitle,
      'version' => $version ? $version : '1.0',
      'download' => $download,
      'font' => $font,
      'size_file' => $size_file,
    ],
  ];

  // Insere o post
  $post_id = wp_insert_post(wp_slash($post_data), true); // wp_slash para preservar caracteres especiais

  if (is_wp_error($post_id)) {
    return new WP_Error('post_creation_failed', 'Erro ao criar o ativo: ' . $post_id->get_error_message(), ['status' => 500]);
  }

  // Verificação adicional do ID do post
  if (!is_numeric($post_id) || $post_id === 0) {
    return new WP_Error('post_creation_failed', 'Erro ao criar o ativo: ID inválido retornado', ['status' => 500]);
  }

  // Define as categorias
  wp_set_post_terms($post_id, $category, 'post_category');

  // Adiciona notificação
  $new_notification = add_notification(
    $user_id,
    'asset',
    'Novo Ativo Cadastrado',
    'Novo Ativo Cadastrado (' . $title . ')',
    'O Ativo Digital "' . $title . '" foi cadastrado. Confira no link abaixo.',
    $post_id
  );
  if (is_wp_error($new_notification)) {
    return new WP_Error('notification_failed', 'Erro fazer a notificação do novo Ativo.', ['status' => 500]);
  }

  // Adiciona ênfases
  foreach ($emphasis_array as $emphasis) {
    foreach ($emphasis as $emphas) {
      add_post_meta($post_id, 'emphasis', $emphas);
    }
  }

  // Faz o upload dos arquivos
  if ($files) {
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    // Upload da thumbnail (capa)
    $thumbnail_id = media_handle_upload('thumbnail', $post_id);
    if (!is_wp_error($thumbnail_id)) {
      set_post_thumbnail($post_id, $thumbnail_id);
      update_post_meta($post_id, 'thumbnail', $thumbnail_id);
      
      // Gera os metadados da imagem
      $attach_data = wp_generate_attachment_metadata($thumbnail_id, get_attached_file($thumbnail_id));
      wp_update_attachment_metadata($thumbnail_id, $attach_data);
    } else {
      return new WP_Error('upload_thumbnail_failed', 'Erro no upload da thumbnail.', ['status' => 500]);
    }

      // Upload das previews (apenas se não for categoria 'Ícones')
    if ($category != 'Ícones') {
      // Detecta o formato dos previews (array ou campos individuais)
      if (isset($files['previews']) && is_array($files['previews'])) {
        // Formato array multidimensional (previews[])
        foreach ($files['previews']['name'] as $index => $name) {
          if (!empty($files['previews']['name'][$index])) {
            $file = array(
              'name' => $files['previews']['name'][$index],
              'type' => $files['previews']['type'][$index],
              'tmp_name' => $files['previews']['tmp_name'][$index],
              'error' => $files['previews']['error'][$index],
              'size' => $files['previews']['size'][$index]
            );
            
            $preview_id = media_handle_sideload($file, $post_id);
            if (!is_wp_error($preview_id)) {
              add_post_meta($post_id, 'previews', $preview_id);
            }
          }
        }
      } else {
        // Formato campos individuais (previews-0, previews-1)
        foreach ($files as $key => $file) {
          if (strpos($key, 'previews-') === 0 && !empty($file['name'])) {
            $preview_id = media_handle_upload($key, $post_id);
            if (!is_wp_error($preview_id)) {
              add_post_meta($post_id, 'previews', $preview_id);
            }
          }
        }
      }
    }
  }

  return rest_ensure_response([
    'success' => true,
    'message' => 'Ativo criado com sucesso.',
    'data' => [
      'title' => $title,
    ],
  ]);
}

/**
 * Registra a rota da API para criar um ativo.
 */
function register_api_asset_post() {
  register_rest_route('api/v1', '/asset', [
    'methods'             => WP_REST_Server::CREATABLE,
    'callback'            => 'api_asset_post',
    'permission_callback' => function () {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}
add_action('rest_api_init', 'register_api_asset_post');