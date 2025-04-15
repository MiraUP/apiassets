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
    // Verifica se o usuário está logado
    $user = wp_get_current_user();
    $user_id = $user->ID;
    
    // Verifica o rate limiting
    if (is_rate_limit_exceeded('update_asset')) {
        return new WP_Error('rate_limit_exceeded', 'Limite de requisições excedido.', ['status' => 429]);
    }

    // Verifica se o usuário tem permissão para acessar o endpoint
    if ($user_id === 0 || !in_array('administrator', $user->roles) && !in_array('editor', $user->roles)) {
        return new WP_Error('permission_denied', 'Sem permissão para acessar este recurso.', ['status' => 401]);
    }
    
    // Verifica o status da conta do usuário
    $status_account = get_user_meta($user_id, 'status_account', true);
    if ($status_account === 'pending') {
        return new WP_Error('account_pending', 'Sua conta está pendente de aprovação.', ['status' => 403]);
    }
    
    // Obtém os dados enviados na requisição
    $params = $request->get_json_params();
    $url = sanitize_text_field($request['post_slug']) ?: '';
    $icon_id = absint($request['icon_id']) ?: '';
    $category = isset($request['post_category']) ? $params['post_category'] : [];
    $style = isset($request['post_style']) ? $params['post_style'] : [];
    $tags = sanitize_text_field($request['post_tag']) ? $params['post_tag'] : '';
    $delete_tag = sanitize_text_field($request['delete_tag']) ?: '';

    // Verificar se o ID do ícone é válido
    if (empty($icon_id)) {
        return new WP_Error('missing_icon_id', 'O ID do ícone é obrigatório.', ['status' => 400]);
    }
    
    // Verifica se houve uma ação de exclusão de relação de tag com a mídia
    if (!empty($delete_tag)) {
        $result = delete_tag($icon_id, $delete_tag);
        return rest_ensure_response([
            'success' => true,
            'message' => 'Tag removed successfully.', 'digital-assets',
            'data'=>$result
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

    // Verifica se o usuário é autor do post ou administrador/editor
    $is_author = ($asset->post_author == $user_id);
    $is_admin_or_editor = in_array('administrator', $user->roles) || in_array('editor', $user->roles);
    
    if (!$is_author && !$is_admin_or_editor) {
        return new WP_Error('permission_denied', 'Você não tem permissão para alterar este ativo digital.', ['status' => 403]);
    }

    // Processa o registro de novas tags
    if (!empty($tags)) {
        $extract_words = extract_valid_words($tags, true);
        
        $all_tags = [];
        foreach ($extract_words as $tags) {
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

        if (!empty($all_tags)) {
            foreach ($all_tags as $variations) {
                foreach ($variations as $variation) {
                    // Verifica se $variation é uma string válida
                    if (is_string($variation) && !empty(trim($variation))) {
                        // Cria o termo se ele não existir
                        $term_id = create_term_taxonomy($variation, 'icon_tag');
                        
                        if (!is_wp_error($term_id)) {
                            // Associa o termo ao attachment
                            $result = wp_set_post_terms($icon_id, [$term_id], 'icon_tag', true);
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

        if (is_wp_error($result)) {
            return $result;
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
                    ['status' => 500]);
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
        
        $icon_style = sanitize_text_field($style);
        $term = term_exists($icon_style, 'icon_style');
        if (!$term) {
            return new WP_Error(
                'category_not_found', 
                'Essa categoria não existe',
                ['status' => 404]);
        }
        $term_name = $icon_style;

        // Atualiza o estilo do attachment
        $updated_style = wp_set_post_terms(
            $icon_id, 
            $term_name, 
            'icon_style', 
            false // Substitui os estilos existentes
        );

        if (is_wp_error($updated_style)) {
            return new WP_Error(
                'updated_style_failed', 
                'Erro ao atualizar o estilo do ícone.' ,
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
    register_rest_route('api', '/media', [
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => 'api_media_put',
        'permission_callback' => function() {
            return is_user_logged_in(); // Apenas usuários autenticados podem acessar
        },
    ]);
}

add_action('rest_api_init', 'register_api_media_put');