<?php
/**
 * Endpoint para listagem de previews com busca, filtros e paginação
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
      ],
      'search' => [
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field'
      ],
      // Novos parâmetros de ordenação
      'orderby' => [
        'default' => 'title',
        'validate_callback' => function($param) {
          return in_array($param, ['title', 'date', 'name', 'ID', 'modified']);
        }
      ],
      'order' => [
        'default' => 'ASC',
        'validate_callback' => function($param) {
          return in_array(strtoupper($param), ['ASC', 'DESC']);
        }
      ]
    ]
  ]);
}
add_action('rest_api_init', 'register_api_previews_get');

function api_previews_get(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;

  $post_id = (int)$request['id'];
  $page = (int)$request['page'] ?: 1;
  $per_page = (int)$request['per_page'] ?: 60;
  $search_term = sanitize_text_field($request['search']);
  $orderby = sanitize_text_field($request['orderby']) ?: 'title';
  $order = strtoupper(sanitize_text_field($request['order'])) ?: 'ASC';

  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }
  
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
      'orderby' => $orderby,
      'order' => $order,
      'fields' => 'ids'
    ]);
  }

  // 3. Busca TODAS taxonomias disponíveis (independente de filtros)
  $all_categories = [];
  $all_styles = [];
  $all_tags = [];
  $category_counts = [];
  $style_counts = [];
  $tag_counts = [];
  
  if (!empty($all_attachments)) {
    // Contagem manual para precisão
    foreach ($all_attachments as $attachment_id) {
      // Categorias
      $cats = get_the_terms($attachment_id, 'icon_category');
      if ($cats && !is_wp_error($cats)) {
        foreach ($cats as $cat) {
          $all_categories[$cat->term_id] = $cat;
          $category_counts[$cat->term_id] = ($category_counts[$cat->term_id] ?? 0) + 1;
        }
      }
        
      // Estilos
      $stls = get_the_terms($attachment_id, 'icon_style');
      if ($stls && !is_wp_error($stls)) {
        foreach ($stls as $stl) {
          $all_styles[$stl->term_id] = $stl;
          $style_counts[$stl->term_id] = ($style_counts[$stl->term_id] ?? 0) + 1;
        }
      }
        
      // Tags (para busca)
      $tags = get_the_terms($attachment_id, 'icon_tag');
      if ($tags && !is_wp_error($tags)) {
        foreach ($tags as $tag) {
          $all_tags[$tag->term_id] = $tag;
          $tag_counts[$tag->term_id] = ($tag_counts[$tag->term_id] ?? 0) + 1;
        }
      }
    }
  }

  // 4. Busca os attachments com filtros e busca aplicados
  $filtered_attachments = [];
  if (!empty($all_attachments)) {
    $query_args = [
      'post_type' => 'attachment',
      'post__in' => $all_attachments,
      'posts_per_page' => -1,
      'orderby' => $orderby,
      'order' => $order
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
      
      // Aplica busca se houver termo de pesquisa
      if (!empty($search_term)) {
        $query_args['s'] = $search_term;
          
        // Filtro para incluir tags na busca
        add_filter('posts_where', function($where, $wp_query) use ($search_term) {
          global $wpdb;
          
          if ($search_term) {
            // Busca no título
            $where .= " OR {$wpdb->posts}.post_title LIKE '%" . esc_sql($wpdb->esc_like($search_term)) . "%'";
            
            // Busca nas tags
            $tag_ids = get_terms([
              'taxonomy' => 'icon_tag',
              'name__like' => $search_term,
              'fields' => 'ids',
              'hide_empty' => false
            ]);
            
            if (!empty($tag_ids)) {
              $where .= " OR {$wpdb->posts}.ID IN (
                SELECT object_id FROM {$wpdb->term_relationships}
                WHERE term_taxonomy_id IN (" . implode(',', array_map('intval', $tag_ids)) . ")
              )";
            }
          }
            
          return $where;
        }, 10, 2);
      }
      
      $filtered_attachments = get_posts($query_args);
      
      // Remove o filtro após a query
      if (!empty($search_term)) {
        remove_filter('posts_where', 'icon_search_where_filter');
      }
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
      'styles' => get_the_terms($attachment->ID, 'icon_style') ?: [],
      'tags' => get_the_terms($attachment->ID, 'icon_tag') ?: []
    ];
  }

  // 6. Aplica paginação
  $total_items = count($processed_previews);
  $total_pages = ceil($total_items / $per_page);
  $offset = ($page - 1) * $per_page;
  $paginated_previews = array_slice($processed_previews, $offset, $per_page);

  // 7. Prepara os filtros para resposta
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
    }, $all_styles)),
    'tags' => array_values(array_map(function($tag) use ($tag_counts) {
      return [
        'term_id' => $tag->term_id,
        'name' => $tag->name,
        'slug' => $tag->slug,
        'count' => $tag_counts[$tag->term_id] ?? 0
      ];
    }, $all_tags))
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