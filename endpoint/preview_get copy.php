<?php
/**
 * Endpoint para listagem de previews organizados por categoria
 * 
 * @package MiraUP
 * @subpackage Assets
 * @since 1.0.0
 */

function register_api_previews_get() {
    register_rest_route('api/v1', '/previews/(?P<id>\d+)', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'api_previews_get',
        'permission_callback' => function() {
            return is_user_logged_in();
        },
        'args' => [
            'id' => [
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ],
            'page' => [
                'default' => 1,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ],
            'categories' => [
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            'styles' => [
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field'
            ]
        ]
    ]);
}
add_action('rest_api_init', 'register_api_previews_get');

function api_previews_get(WP_REST_Request $request) {
    $post_id = (int)$request['id'];
    $page = (int)$request['page'] ?: 1;
    $per_page = (int)$request['per_page'] ?: 60;
    
    // Processa filtros
    $filter_categories = array_filter(explode(',', $request['categories']));
    $filter_styles = array_filter(explode(',', $request['styles']));

    // Verifica se o post existe
    $post = get_post($post_id);
    if (!$post) {
        return new WP_Error('post_not_found', 'Post não encontrado', ['status' => 404]);
    }

    // 1. Busca TODOS os previews do post
    $all_previews_raw = get_post_meta($post_id, 'previews', false);
    
    $all_previews = [];
    foreach ($all_previews_raw as $preview_value) {
        $decoded = maybe_unserialize($preview_value);
        if (is_array($decoded)) {
            $all_previews = array_merge($all_previews, $decoded);
        } else {
            $all_previews[] = $decoded;
        }
    }
    
    // Garante que temos apenas IDs numéricos válidos
    $all_previews = array_filter(array_map('intval', $all_previews));
    $all_previews = array_unique($all_previews);

    // 2. Busca TODOS os attachments (para garantir que existem)
    $all_attachments = [];
    if (!empty($all_previews)) {
        $all_attachments = get_posts([
            'post_type' => 'attachment',
            'post__in' => $all_previews,
            'posts_per_page' => -1,
            'orderby' => 'post__in',
            'fields' => 'ids' // Apenas os IDs para melhor performance
        ]);
    }

    // 3. Busca TODAS categorias e estilos disponíveis (independente de filtros)
    $all_categories = [];
    $all_styles = [];
    $category_counts = [];
    $style_counts = [];
    
    if (!empty($all_attachments)) {
        // Primeiro busca todos os termos associados aos attachments
        // 3. Busca TODAS categorias e estilos disponíveis (independente de filtros)
    $all_categories = [];
    $all_styles = [];
    $category_counts = [];
    $style_counts = [];
    
    if (!empty($all_attachments)) {
        // Primeiro busca todos os termos associados aos attachments
        $all_terms = wp_get_object_terms($all_attachments, ['icon_category', 'icon_style']);
        
        // Organiza os termos por taxonomia
        foreach ($all_terms as $term) {
            if ($term->taxonomy === 'icon_category') {
                $all_categories[$term->term_id] = $term;
                // Inicializa contador se não existir
                if (!isset($category_counts[$term->term_id])) {
                    $category_counts[$term->term_id] = 0;
                }
                $category_counts[$term->term_id]++;
            } elseif ($term->taxonomy === 'icon_style') {
                $all_styles[$term->term_id] = $term;
                // Inicializa contador se não existir
                if (!isset($style_counts[$term->term_id])) {
                    $style_counts[$term->term_id] = 0;
                }
                $style_counts[$term->term_id]++;
            }
        }

        // Alternativa mais precisa para contar os termos
        // Conta ocorrências de cada categoria
        foreach ($all_attachments as $attachment_id) {
            $cats = get_the_terms($attachment_id, 'icon_category');
            if ($cats && !is_wp_error($cats)) {
                foreach ($cats as $cat) {
                    if (!isset($category_counts[$cat->term_id])) {
                        $category_counts[$cat->term_id] = 0;
                    }
                    $category_counts[$cat->term_id]++;
                }
            }
            
            $stls = get_the_terms($attachment_id, 'icon_style');
            if ($stls && !is_wp_error($stls)) {
                foreach ($stls as $stl) {
                    if (!isset($style_counts[$stl->term_id])) {
                        $style_counts[$stl->term_id] = 0;
                    }
                    $style_counts[$stl->term_id]++;
                }
            }
        }
    }

    }

    // 4. Busca os attachments com filtros aplicados
    $filtered_attachments = [];
    if (!empty($all_attachments)) {
        $query_args = [
            'post_type' => 'attachment',
            'post__in' => $all_attachments,
            'posts_per_page' => -1,
            'orderby' => 'post__in'
        ];
        
        // Aplica filtros via tax_query se necessário
        $tax_queries = [];
        
        if (!empty($filter_categories)) {
            $tax_queries[] = [
                'taxonomy' => 'icon_category',
                'field' => 'slug',
                'terms' => $filter_categories
            ];
        }
        
        if (!empty($filter_styles)) {
            $tax_queries[] = [
                'taxonomy' => 'icon_style',
                'field' => 'slug',
                'terms' => $filter_styles
            ];
        }
        
        if (!empty($tax_queries)) {
            $query_args['tax_query'] = $tax_queries;
            if (count($tax_queries) > 1) {
                $query_args['tax_query']['relation'] = 'AND';
            }
        }
        
        $filtered_attachments = get_posts($query_args);
    }

    // 5. Prepara os previews para resposta
    $processed_previews = [];
    foreach ($filtered_attachments as $attachment) {
        $processed_previews[] = [
            'id' => $attachment->ID,
            'url' => wp_get_attachment_url($attachment->ID),
            'title' => $attachment->post_title,
            'mime_type' => $attachment->post_mime_type,
            'categories' => get_the_terms($attachment->ID, 'icon_category') ?: [],
            'styles' => get_the_terms($attachment->ID, 'icon_style') ?: []
        ];
    }

    // 6. Aplica paginação
    $total_items = count($processed_previews);
    $total_pages = ceil($total_items / $per_page);
    $offset = ($page - 1) * $per_page;
    $paginated_previews = array_slice($processed_previews, $offset, $per_page);

    // 7. Prepara os filtros para resposta (com todas opções)
    $response_filters = [
        'categories' => array_values(array_map(function($cat) use ($category_counts) {
            return [
                'term_id' => $cat->term_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'count' => $category_counts[$cat->term_id] ?? 0
            ];
        }, $all_categories)),
        'styles' => array_values(array_map(function($style) use ($style_counts) {
            return [
                'term_id' => $style->term_id,
                'name' => $style->name,
                'slug' => $style->slug,
                'count' => $style_counts[$style->term_id] ?? 0
            ];
        }, $all_styles))
    ];

    return rest_ensure_response([
        'success' => true,
        'data' => [
            'previews' => $paginated_previews,
            'filters' => $response_filters,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_pages' => $total_pages,
                'total_items' => $total_items
            ]
        ]
    ]);
}