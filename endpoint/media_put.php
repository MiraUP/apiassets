<?php
/**
 * Função para atualizar as mídias de ícones dos Ativos.
 *
 * @package MiraUP
 * @subpackage Media
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto da requisição.
 * @return WP_REST_Response Resposta da API.
 */
function api_media_put(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('media_put-' . $user_id, 200)) {
    return $error;
  }

  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }
    
  // Obtém os dados enviados na requisição
  $params = $request->get_json_params();
  $url = sanitize_text_field($request['post_slug']) ?: '';
  $icon_id = absint($request['icon_id']) ?: '';
  $category = isset($request['post_category']) ? $params['post_category'] : [];
  $style = isset($request['post_style']) ? $params['post_style'] : [];
  $tags = isset($request['post_tag']) ? $params['post_tag'] : [];
  $delete_tag = sanitize_text_field($request['delete_tag']) ?: '';


  // Verificar se o ID do ícone é válido
  if (empty($icon_id)) {
    return new WP_Error('missing_icon_id', 'O ID do ícone é obrigatório.', ['status' => 400]);
  }

  // return rest_ensure_response([
  //   'success' => true,
  //   'message' => $icon_id,
  //   'data' => $delete_tag
  // ]);
    
  // Verifica se houve uma ação de exclusão de relação de tag com a mídia
  if (!empty($request['delete_tag'])) {
    $delete_tag = absint($request['delete_tag']); // Converte para inteiro seguro
    
    if (!$delete_tag) {
        return new WP_Error(
            'invalid_tag_id', 
            'ID da tag inválido.', 
            ['status' => 400]
        );
    }

    // Verifica se o termo existe na taxonomia
    if (!term_exists($delete_tag, 'icon_tag')) {
        return new WP_Error(
            'tag_not_found',
            'Tag não encontrada na taxonomia.',
            ['status' => 404]
        );
    }

    // Verifica se o termo está associado à mídia
    $current_terms = wp_get_object_terms($icon_id, 'icon_tag', ['fields' => 'ids']);
    if (!in_array($delete_tag, $current_terms)) {
        return new WP_Error(
            'tag_not_associated',
            'Esta tag não está associada à mídia.',
            ['status' => 400]
        );
    }

    $result = wp_remove_object_terms($icon_id, $delete_tag, 'icon_tag');
    
    if (is_wp_error($result)) {
        error_log('Falha ao remover tag: ' . $result->get_error_message());
        return new WP_Error(
            'removal_failed',
            'Falha ao remover a tag: ' . $result->get_error_message(),
            ['status' => 500]
        );
    }

    return rest_ensure_response([
        'success' => true,
        'message' => 'Tag removida com sucesso.',
        'data' => [
            'removed_tag_id' => $delete_tag,
            'remaining_tags' => array_diff($current_terms, [$delete_tag])
        ]
    ]);
  }
    
  // Extrai o slug da URL
  $post_slug = basename($url); // Obtém o último segmento da URL (slug)
  if (empty($post_slug)) {
    return new WP_Error('invalid_url', 'URL do post inválida.', ['status' => 400]);
  }
    
  // Busca o post pelo slug
  $asset = get_page_by_path($url, OBJECT, 'post');
  if (!$asset) {
    return new WP_Error('post_not_found', 'Post não encontrado.', ['status' => 404]);
  }

  $post_id = $asset->ID;

  // Verifica se o usuário é o autor do post ou um administrador
  if ($error = Permissions::check_post_edit_permission($user, $post_id)) {
    return $error;
  }

  // Processa o registro de novas tags
  if (!empty($tags)) {
    // Garante que $tags seja um array (caso venha como string)
    if (!is_array($tags)) {
      $tags = [$tags];
    }

    $all_term_ids = []; // Armazenará todos os IDs de termos a serem associados

    foreach ($tags as $tag_input) {
      // Sanitiza o input da tag
      $tag_input = sanitize_text_field($tag_input);
      if (empty($tag_input)) {
        continue;
      }

      // Extrai palavras válidas (mantendo a função original)
      $extracted_words = extract_valid_words($tag_input, true);
      
      // Para cada palavra extraída, obtém variações (incluindo traduções)
      foreach ($extracted_words as $word) {
        $variations = translate_dictionary($word);
        
        if (is_wp_error($variations)) {
          error_log('Erro ao traduzir palavra: ' . $variations->get_error_message());
          continue;
        }

        // Processa cada variação (incluindo a palavra original)
        foreach ($variations as $variation_group) {
          foreach ($variation_group as $variation) {
            $variation = sanitize_text_field($variation);
            if (empty($variation)) continue;

            // Verifica se o termo já existe ou cria um novo
            $term = term_exists($variation, 'icon_tag');
            if (!$term) {
              $term = wp_insert_term($variation, 'icon_tag');
              if (is_wp_error($term)) {
                error_log('Falha ao criar tag: ' . $term->get_error_message());
                continue;
              }
              $term_id = $term['term_id'];
            } else {
              $term_id = $term['term_id'];
            }

            $all_term_ids[] = (int)$term_id;
          }
        }
      }
    }

    // Remove duplicatas (caso a mesma tag apareça em múltiplas variações)
    $all_term_ids = array_unique($all_term_ids);

    // Associa todas as tags ao attachment
    if (!empty($all_term_ids)) {
      $result = wp_set_post_terms($icon_id, $all_term_ids, 'icon_tag', false); // Substitui as tags existentes
      if (is_wp_error($result)) {
        return new WP_Error('term_association_failed', 'Falha ao associar as tags: ' . $result->get_error_message(), ['status' => 500]);
      }
    }
  }

  // Inicia o processo de alteração de categoria
  if (!empty($category)) {
    // Verifica se $category é um array
    if (!is_array($category)) {
      $category = [$category]; // Converte para array se for string
    }

    $term_ids = []; // Armazenará os IDs dos termos
    
    foreach ($category as $icon_category) {
      // Sanitiza o nome da categoria
      $icon_category = sanitize_text_field($icon_category);
      
      // Verifica se o termo existe
      $term = term_exists($icon_category, 'icon_category');
      if (!$term) {
        return new WP_Error(
          'category_not_found', 
          'Essa categoria não existe',
          ['status' => 500]
        );
      }

      $term_ids[] = (int)$term['term_id'];
    };

    // Atualiza as categorias do attachment
    $updated_category = wp_set_post_terms(
      $icon_id, 
      $term_ids, 
      'icon_category', 
      false // Substitui as categorias existentes
    );
      
    if (is_wp_error($updated_category)) {
      return new WP_Error(
        'updated_category_failed', 
        'Erro ao atualizar a categoria do ícone.',
        ['status' => 500,]
      );
    }
  }

  

  if (!empty($style)) {
    // Verifica se $style é um array
    if (!is_array($style)) {
      $style = [$style]; // Converte para array se for string
    }

    $term_ids = []; // Armazenará os IDs dos termos
    
    foreach ($style as $icon_style) {
      // Sanitiza o nome do estilo
      $icon_style = sanitize_text_field($icon_style);
      
      // Verifica se o termo existe
      $term = term_exists($icon_style, 'icon_style');
      if (!$term) {
        return new WP_Error(
          'style_not_found', 
          'Esse estilo não existe: ' . $icon_style,
          ['status' => 404]
        );
      }

      $term_ids[] = (int)$term['term_id'];
    };

    // Atualiza os estilos do attachment
    $updated_style = wp_set_post_terms(
      $icon_id, 
      $term_ids, 
      'icon_style', 
      false // Substitui os estilos existentes
    );

    if (is_wp_error($updated_style)) {
      return new WP_Error(
        'updated_style_failed', 
        'Erro ao atualizar o estilo do ícone.',
        ['status' => 500]
      );
    }
  }


  // Retorna uma resposta de sucesso
  return rest_ensure_response([
    'success' => true,
    'message' => 'Dados atualizados com sucesso!',
    'data' => $url
  ]);
}

/**
 * Deleta a relação de tag com a mídia.
 *
 * @param int $attachment_id ID da mídia
 * @param int $term_id ID do termo que será removido
 * @return bool|WP_Error True foi bem sucedido, WP_Error houve uma falha
 */
function delete_tag($attachment_id, $term_id) {
  // Validação básica dos parâmetros
  if (empty($attachment_id)) {
    return new WP_Error(
      'missing_attachment_id',
      'O ID do attachment é obrigatório.',
      ['status' => 400]
    );
  }
  
  if (empty($term_id)) {
    return new WP_Error(
      'missing_term_id',
      'O ID do termo da taxonomia é obrigatório.',
      ['status' => 400]
    );
  }
  
  // Verifica se o attachment existe
  $attachment = get_post($attachment_id);
  if (!$attachment || 'attachment' !== $attachment->post_type) {
    return new WP_Error(
      'invalid_attachment',
      'O attachment especificado não existe.',
      ['status' => 404]
    );
  }
  
  // Remove a relação
  $result = wp_remove_object_terms($attachment_id, $term_id, 'icon_tag');

  if (is_wp_error($result)) {
    return new WP_Error( 'removal_failed', 'Falha ao remover a relação da taxonomia.', ['status' => 500] );
  }
  
  return($result);
}


/**
 * Função para registrar o endpoint de atualização de mídias de preview.
 */
function register_api_media_put() {
  register_rest_route('api/v1', '/media', [
    'methods'             => WP_REST_Server::EDITABLE,
    'callback'            => 'api_media_put',
    'permission_callback' => function() {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}

add_action('rest_api_init', 'register_api_media_put');