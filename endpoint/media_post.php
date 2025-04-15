<?php
/**
 * Função para lidar com o upload de mídias com taxonomias.
 *
 * @package MiraUP
 * @subpackage Media
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição.
 * @return WP_REST_Response Resposta da API.
 */
function api_media_post(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  // Verifica o rate limiting
  if (is_rate_limit_exceeded('create_asset')) {
    return new WP_Error('rate_limit_exceeded', 'Limite de requisições excedido.', ['status' => 429]);
  }

  // Verifica se o usuário está logado
  if ($user_id === 0) {
    return new WP_Error('unauthorized', 'Usuário não autenticado.', ['status' => 401]);
  }

  // Verifica o status da conta do usuário
  $status_account = get_user_meta($user_id, 'status_account', true);
  if ($status_account === 'pending') {
    return new WP_Error('account_pending', 'Sua conta está pendente de aprovação.', ['status' => 403]);
  }

  // Verifica se o usuário tem um dos roles permitidos
  if (!$user_id || !in_array($user->roles[0], ['contributor', 'author', 'editor', 'administrator'])) {
    return new WP_Error('forbidden_roles', 'Você não tem permissão para acessar executar essa ação.', ['status' => 403]);
  }
  
  // Verifica se o ID do post foi fornecido
  $post_id = $request->get_param('post_id');
  if (empty($post_id) || !get_post($post_id)) {
    return new WP_Error('invalid_post_id', 'ID do post inválido.', ['status' => 400]);
  }

  // Verifica se o post existe
  $asset = get_post($post_id);
  if (!$asset) {
    return new WP_Error('post_not_found', 'Post não encontrado.', ['status' => 404]);
  }

  // Verifica se o usuário é autor do post ou administrador/editor
  $is_author = ($asset->post_author == $user_id);
  $is_admin_or_editor = in_array('administrator', $user->roles) || in_array('editor', $user->roles);

  if (!$is_author && !$is_admin_or_editor) {
    return new WP_Error('permission_denied', 'Você não tem permissão para alterar este ativo digital.', ['status' => 403]);
  }

  // Verifica se as imagens foram enviadas
  $files = $request->get_file_params();
  if (empty($files['preview'])) {
    return new WP_Error('no_media', 'Nenhuma mídia foi enviada.', ['status' => 400]);
  }
  
  // Verifica o número de imagens enviadas
  if (count($files['preview']['tmp_name']) > 100) {
    return new WP_Error('too_many_images', 'Você pode enviar no máximo 100 imagens por vez.', ['status' => 400]);
  }

  // Obtém os dados das taxonomias
  $icon_categories = $request->get_param('icon_category') ?? [];
  $icon_styles = $request->get_param('icon_style') ?? [];
  $icon_tags = $request->get_param('icon_tag') ?? [];

  // Verifica se a(s) categoria(s) foi(ram) informad(s)
  $files = $request->get_file_params();
  if (empty($icon_categories)) {
    return new WP_Error('not_found_icon_categories', 'Nenhuma categoria foi enviada.', ['status' => 400]);
  }
  
  // Prepara o array de nomes dos attachments
  $attachment_names = [];

  // Obtém as mídias já vinculadas ao post
  $existing_previews = get_post_meta($post_id, 'previews', false);

  // Prepara um array com os títulos das mídias já vinculadas ao post
  $existing_titles = [];
  if (!empty($existing_previews)) {
    foreach ($existing_previews as $preview_id) {
      $attachment = get_post($preview_id);
      if ($attachment) {
        $existing_titles[] = $attachment->post_title;
      }
    }
  }

  $duplicate_images = [];
  // Loop através das imagens enviadas
  foreach ($files['preview']['tmp_name'] as $index => $tmp_name) {
    $file_name = $files['preview']['name'][$index];
    $sanitized_file_name = sanitize_file_name($file_name);

    // Verifica se o nome da imagem já existe no post
    if (in_array($sanitized_file_name, $existing_titles)) {
      $duplicate_images[] = $file_name; // Adiciona o nome da imagem duplicada ao array
      continue; // Ignora a imagem duplicada
    }

    $file_type = $files['preview']['type'][$index];
    $file_size = $files['preview']['size'][$index];
    $file_ext = strtolower(wp_check_filetype($files['preview']['name'][$index])['ext']);

    $allowed_types = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];
    if (!in_array($file_ext, $allowed_types)) {
      return new WP_Error(
        'invalid_file_type',
        'Apenas arquivos SVG, PNG, JPG, JPEG, WEBP e GIF são permitidos.',
        ['status' => 400]
      );
    }
      
    // Verifica se o arquivo é uma imagem
    if (!str_contains($file_type, 'image')) {
      continue; // Ignora arquivos que não são imagens
    }
    
    // Extrai palavras válidas do nome do arquivo
    $valid_words = extract_valid_words($file_name); 
      
    // Prepara o array de arquivo para o wp_handle_upload
    $file = [
      'name'     => $file_name,
      'type'     => $file_type,
      'tmp_name' => $tmp_name,
      'error'    => $files['preview']['error'][$index],
      'size'     => $file_size,
    ];
      
    // Faz o upload da imagem
    $upload = wp_handle_upload($file, [
      'test_form' => false, // Desativa a verificação do formulário
    ]);
      
    if (isset($upload['file'])) {
      // Prepara os dados do attachment
      $attachment = [
        'post_mime_type' => $file_type,
        'post_title'     => sanitize_file_name($file_name),
        'post_content'   => '',
        'post_status'    => 'inherit',
        'post_parent'    => $post_id, // Associa o attachment ao post
      ];
        
      // Insere o attachment no banco de dados
      $attachment_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
      if (!is_wp_error($attachment_id)) {
        // Gera os metadados do attachment
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        // Reaciona o arquivo com o Ativo adicionando os metadados
        add_post_meta($post_id, 'previews', $attachment_id);
        
        // Adiciona os metadados das taxonomias ao attachment
        if (isset($icon_styles[$index])) {
          wp_set_post_terms($attachment_id, sanitize_text_field($icon_styles[$index]), 'icon_style', true);
        }
        if (isset($icon_categories[$index])) {
          wp_set_post_terms($attachment_id, sanitize_text_field($icon_categories[$index]), 'icon_category');
        }
          
        if (isset($icon_tags[$index])) { // Verifica se há tags para o índice atual
          $variations_tag = explode(',', $icon_tags[$index]);
          // Verifica se $variations_tag é um array
          if (is_array($variations_tag)) {
            $all_tags = [];
            foreach ($variations_tag as $tags) {
              // Verifica se $tags é uma string válida
              if (is_string($tags) && !empty(trim($tags))) {
                // Obtém as variações da tag
                $variations = translate_dictionary($tags);
                  
                // Verifica se $variations é um array e mescla com $all_tags
                if (is_array($variations)) {
                  $all_tags = array_merge($all_tags, $variations);
                }
              }
            }
              
            // Processa as variações das tags
            if (!empty($all_tags)) {
              foreach ($all_tags as $variation) {
                // Verifica se $variation é uma string válida
                if (is_string($variation) && !empty(trim($variation))) {
                  // Cria o termo se ele não existir
                  $term_id = create_term_taxonomy($variation, 'icon_tag');
                  if (!is_wp_error($term_id)) {
                    // Associa o termo ao attachment
                    $result = wp_set_post_terms($attachment_id, [$term_id], 'icon_tag', true);
                    if (is_wp_error($result)) {
                      return new WP_Error('term_association_failed', 'Falha ao associar o termo: ' . $result->get_error_message(), ['status' => 500]);
                    }
                  } else {
                    return $term_id; // Retorna o erro se a criação do termo falhar
                  }
                }
              }
            }
          } else {
            return new WP_Error('tags_string', 'Os valores da(s) tag(s) não é um array.', ['status' => 400]);
          }
        }
            
            
        // Processa as palavras válidas e suas variações
        $all_variations = [];
        foreach ($valid_words as $word) {
          // Obtém as variações da palavra
          $variations = translate_dictionary($word);
          if (is_array($variations)) {
            $all_variations = array_merge($all_variations, $variations);
          }
        }
          
      // Cadastra todas as variações como termos da taxonomia icon_tag
      if (!empty($all_variations)) {
        foreach ($all_variations as $variation_) {
          foreach ($variation_ as $variation) {
            // Verifica se a variação é uma string válida
            if (is_string($variation) && !empty(trim($variation))) {
              $term_id = create_term_taxonomy($variation, 'icon_tag');
              if (!is_wp_error($term_id)) {
                $result = wp_set_post_terms($attachment_id, [$term_id], 'icon_tag', true);
                if (is_wp_error($result)) {
                  return new WP_Error('term_association_failed', 'Falha ao associar o termo: ' . $result->get_error_message(), ['status' => 500]);
                }
              } else {
                return $term_id; // Retorna o erro se a criação do termo falhar
              }
            }
          }
        }
      }
        
      // Adiciona o ID do attachment ao array
      $attachment_names[] = $file_name;
    } else {
      return new WP_Error('error_attachment_create', 'Erro ao criar attachment: ' . $attachment_id->get_error_message(), ['status' => 500]);
    }
    } else {
      return new WP_Error('error_update_file', 'Erro ao fazer upload do arquivo: ' . print_r($upload, true), ['status' => 500]);
    }
  }

  // Retorna uma resposta de sucesso
  return rest_ensure_response([
    'success' => true,
    'message' => 'Mídias enviadas e registradas com sucesso.',
    'data'    => $attachment_names,
    'duplicate_files' => $duplicate_images,
  ]);
}

/**
 * Endpoint para upload de mídias de imagens e registro em um post.
 */
function register_api_media_post() {
    register_rest_route('api', '/media', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'handle_meapi_media_postdia_post',
        'permission_callback' => function() {
            return is_user_logged_in(); // Apenas usuários autenticados podem acessar
        },
    ]);
}
add_action('rest_api_init', 'register_api_media_post');