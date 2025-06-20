<?php
/**
 * Lista os ativos com base nos parâmetros fornecidos.
 *
 * @package MiraUP
 * @subpackage Assets
 * @since 1.0.0
 * @version 1.0.0 
 * @param WP_REST_Request $request Objeto de requisição da API.
 * @return WP_REST_Response|WP_Error Resposta da API com a lista de ativos ou erro.
 */
function api_assets_get(WP_REST_Request $request) {
  // Obtém o usuário atual
  $user = wp_get_current_user();
  $user_id = (int) $user->ID;
  
  // Verificar autenticação
  if ($error = Permissions::check_authentication($user)) {
    return $error;
  }

  // Verifica rate limiting
  if ($error = Permissions::check_rate_limit('assets_post-' . $user_id, 50)) {
    return $error;
  }
  
  // Verifica o status da conta do usuário
  if ($error = Permissions::check_account_status($user)) {
    return $error;
  }


  // Sanitiza e valida os parâmetros de consulta
  $_url = sanitize_text_field($request['url']) ?: '';
  $_total = (int) sanitize_text_field($request['total']) ?: 6;
  $_page = (int) sanitize_text_field($request['page']) ?: 1;
  $_user = sanitize_text_field($request['user']) ?: 0;
  $_date_created = sanitize_text_field($request['date_created']) ?: 'DESC';
  $_date_modified = sanitize_text_field($request['date_modified']) ?: 'DESC';
  $_category = sanitize_text_field($request['category']) ?: '';
  $_tags = sanitize_text_field($request['tags']) ?: '';
  $_compatibility = sanitize_text_field($request['compatibility']) ?: '';
  $_developer = sanitize_text_field($request['developer']) ?: '';
  $_origin = sanitize_text_field($request['origin']) ?: '';
  $_favorite = sanitize_text_field($request['favorite']) ?: '';
  $_new = filter_var($request['new'], FILTER_VALIDATE_BOOLEAN);
  

  if (current_user_can('administrator')) {
    $_status = 'publish,pending,draft,private'; 
  } else {
    $_status = 'publish';
    
    add_filter('posts_where', function($where) use ($user_id) {
      global $wpdb;
      $where .= $wpdb->prepare(
        " OR ({$wpdb->posts}.post_author = %d AND {$wpdb->posts}.post_status IN ('pending', 'draft'))",
        $user_id
      );
      return $where;
    });
  }

  // Busca o Ativo pela URL 
if(!empty($_url)) {
    // Remove a URL base e sanitiza
    $site_url = trailingslashit(get_site_url());
    $clean_url = str_replace($site_url, '', $_url);
    $clean_url = strtok($clean_url, '?#'); // Remove query strings e fragments
    
    // Primeiro tenta pelo ID se a URL contiver um número
    if (preg_match('/\d+/', $clean_url, $matches)) {
        $post_id = (int) $matches[0];
        $post = get_post($post_id);
        
        // Verifica se o post existe e tem status permitido
        if ($post && in_array($post->post_status, explode(',', $_status))) {
            // Verifica adicionalmente se o usuário tem permissão para ver posts não publicados
            if ($post->post_status !== 'publish' && !current_user_can('edit_post', $post->ID)) {
                return new WP_Error('post_not_found', 'Ativo não encontrado ou não disponível.', ['status' => 404]);
            }
            
            $asset = asset_data($post);
            return rest_ensure_response([
                'success' => true,
                'message' => 'Ativo encontrado por ID.',
                'data'    => $asset,
            ]);
        }
    }
    
    // Se não encontrar por ID, tenta pelo slug
    $post_slug = sanitize_title(basename($clean_url));
    if (!empty($post_slug)) {
        // Primeiro tenta encontrar diretamente pelo slug
        $post = get_page_by_path($post_slug, OBJECT, 'post');
        
        // Se não encontrou ou não tem permissão, faz uma query mais completa
        if (!$post || !in_array($post->post_status, explode(',', $_status)) || 
            ($post->post_status !== 'publish' && !current_user_can('edit_post', $post->ID))) {
            
            // Prepara argumentos para a query
            $query_args = [
                'name'           => $post_slug,
                'post_type'      => 'post',
                'post_status'    => explode(',', $_status),
                'posts_per_page' => 1,
            ];
            
            // Se não é admin, adiciona filtro para permitir ver seus próprios posts
            if (!current_user_can('administrator')) {
                add_filter('posts_where', function($where) use ($user_id) {
                    global $wpdb;
                    $where .= $wpdb->prepare(
                        " OR ({$wpdb->posts}.post_author = %d AND {$wpdb->posts}.post_status IN ('pending', 'draft'))",
                        $user_id
                    );
                    return $where;
                });
            }
            
            $query = new WP_Query($query_args);
            
            // Remove o filtro após a query
            if (!current_user_can('administrator')) {
                remove_filter('posts_where', 'custom_where_filter');
            }
            
            if ($query->have_posts()) {
                $post = $query->posts[0];
                $asset = asset_data($post);
                return rest_ensure_response([
                    'success' => true,
                    'message' => 'Ativo encontrado por slug.',
                    'data'    => $asset,
                ]);
            }
        } else {
            // Se encontrou diretamente pelo slug e tem permissão
            $asset = asset_data($post);
            return rest_ensure_response([
                'success' => true,
                'message' => 'Ativo encontrado por slug.',
                'data'    => $asset,
            ]);
        }
    }
    
    return new WP_Error('post_not_found', 'Ativo não encontrado.', ['status' => 404]);
}

  // Converte o autor de login para ID, se necessário
  if (!is_numeric($_user)) {
    $user = get_user_by('login', $_user);
    $_user = $user ? $user->ID : 0;
  }

  // Na função api_get_assets
  if ($_page < 1) {
    return new WP_Error('invalid_page', 'Número de página inválido', ['status' => 400]);
  }

  // Define os argumentos da consulta
  $args = [
    'post_type'      => 'post',
    'posts_per_page' => $_total,
    'paged'          => $_page,
    'orderby'        => 'date', // Ordenação padrão por data de criação
    'order'          => strtoupper($_date_created) === 'ASC' ? 'ASC' : 'DESC',
    'post_status'    => explode(',', $_status), // Aceita múltiplos status
  ];

  // Se o parâmetro "_new" for true, busca os 8 ativos mais recentes dos últimos 3 meses
  if ($_new) {
    $args['posts_per_page'] = 8; // Sobrescreve o _total
    $args['date_query'] = [
      [
        'after' => '1 months ago', // Filtra posts dos últimos 3 meses
      ]
    ];
    $args['orderby'] = 'date'; // Garante ordenação por data
    $args['order'] = 'DESC'; // Mais recentes primeiro
  }

  // Filtro por autor
  if ($_user) {
    $args['author'] = $_user;
  }

  // Filtro por categoria
  if ($_category) {
    $args['cat'] = $_category;
  }

  // Filtro por tags (uma ou mais palavras)
  if ($_tags) {
    $args['tag'] = $_tags;
  }

  // Filtro por compatibility (uma ou mais palavras)
  if ($_compatibility) {
    $args['tax_query'][] = [
      'taxonomy' => 'compatibility',
      'field'    => 'name',
      'terms'    => explode(',', $_compatibility),
      'operator' => 'AND',
    ];
  }

  // Filtro por developer (palavra associada ao name do developer)
  if ($_developer) {
    $args['tax_query'][] = [
      'taxonomy' => 'developer',
      'field'    => 'name',
      'terms'    => $_developer,
    ];
  }

  // Filtro por origin (palavra associada ao name da origin)
  if ($_origin) {
    $args['tax_query'][] = [
      'taxonomy' => 'origin',
      'field'    => 'name',
      'terms'    => $_origin,
    ];
  }

  // Filtro por data de modificação
  if (!empty($request['date_modified'])) {
    $args['orderby'] = 'modified';
    $args['order'] = strtoupper($_date_modified) === 'ASC' ? 'ASC' : 'DESC';
  }

  // Filtro por favoritos (true ou false)
  if ($_favorite === 'true') {
    $favorite_value = $_favorite === 'true' ? 1 : 0;

    // Adiciona filtro personalizado via hook
    global $wpdb;
    add_filter('posts_join', function($join) use ($wpdb) {
      $join .= " INNER JOIN {$wpdb->prefix}favpost AS fav ON {$wpdb->posts}.ID = fav._fav_id_post";
        return $join;
    });

    add_filter('posts_where', function($where) use ($wpdb, $user_id, $favorite_value) {
      $where .= $wpdb->prepare(
        " AND fav.user_id = %d AND fav._fav_post = %d",
        $user_id,
        $favorite_value
      );
      return $where;
    });
  }

  // Executa a consulta
  $query = new WP_Query($args);
  $posts = $query->posts;
  $assets = [];

  // Remove os filtros após a consulta
  if ($_favorite !== '') {
    remove_filter('posts_join', 'custom_join_filter');
    remove_filter('posts_where', 'custom_where_filter');
  }

  if ($posts) {
    foreach ($posts as $post) {
      $assets[] = asset_data($post);
    }
  }

  return rest_ensure_response([
    'success' => true,
    'message' => 'Busca de Ativos feita com sucesso.',
    'data'    => $assets,
    'total_pages' => $query->max_num_pages,
    'current_page' => $_page,
  ]);
}

/**
 * Registra as rotas da API para listar ativos ou busca dados de um ativo especifico.
 */
// Rota para busca lista de ativos com ou sem filtros
function register_api_assets_get() {
  register_rest_route('api/v1', '/asset', [
    'methods'             => WP_REST_Server::READABLE,
    'callback'            => 'api_assets_get',
    'permission_callback' => function () {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    }
  ]);
}
add_action('rest_api_init', 'register_api_assets_get');

// Rota para busca de ativo por URL
function register_api_asset_get() {
  register_rest_route('api/v1', '/asset/(?P<url>[\w\-\.\,\?\&\=\%]+)', [
    'methods'             => WP_REST_Server::READABLE,
    'callback'            => 'api_assets_get',
    'permission_callback' => function () {
      return is_user_logged_in(); // Apenas usuários autenticados podem acessar
    },
  ]);
}
add_action('rest_api_init', 'register_api_asset_get');


/**
 * Obtém os dados de um post.
 *
 * @param WP_Post $post Objeto do post.
 * @return array Dados do post.
 */
function asset_data($post) {
  $post_meta = get_post_meta($post->ID);
  $user = get_userdata($post->post_author);
  $user_data = wp_get_current_user();
  $user_id = (int) $user_data->ID;
  $total_comments = get_comments_number($post->ID);

  $thumbnail = !empty($post_meta['thumbnail']) ? wp_get_attachment_image_src($post_meta['thumbnail'][0], 'large')[0] : '';
  $previews = [];
  if (!empty($post_meta['previews']) && is_array($post_meta['previews'])) {
    foreach ($post_meta['previews'] as $preview => $array) {
      $data = get_post($array);
      if ($data) {
        $previews[$preview] = [
          "id"           => $data->ID,
          "title"        => $data->post_title,
          "url"          => $data->guid,
          "icon_styles"  => get_the_terms($data->ID, 'icon_style'),
          "icon_tag"     => get_the_terms($data->ID, 'icon_tag'),
          "icon_category" => get_the_terms($data->ID, 'icon_category'),
        ];
      }
    }
  }

  global $wpdb;
  $favorite = 0;
  $favorite_query = $wpdb->prepare(
    "SELECT _fav_post FROM {$wpdb->prefix}favpost 
    WHERE user_id = %d AND _fav_id_post = %d",
    get_current_user_id(),
    $post->ID
  );
  $favorite_result = $wpdb->get_var($favorite_query);
  $favorite = $favorite_result ? (bool) $favorite_result : false;

  $emphasis = [];
  $post_id = $post->ID;
  // Consulta direta ao banco de dados
  $results = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT meta_id, meta_value 
      FROM {$wpdb->postmeta} 
      WHERE meta_key = 'emphasis' AND post_id = %d",
      $post_id
    ),
    ARRAY_A
  );
  // Processa os resultados
  if (!empty($results)) {
    foreach ($results as $row) {
      $emphasis[] = [
        'id'    => $row['meta_id'], // meta_id
        'value' => $row['meta_value'] // meta_value
      ];
    }
  }

  $slug = $post->post_name;
  if (empty($slug)) {
    $slug = sanitize_title($post->post_title);
  }
  
  // Geração do permalink para rascunhos/pendentes
  $permalink = get_permalink($post);
  if (!$permalink) {
    $permalink = home_url('/?p=' . $post->ID);
  }

  return [
    'id'             => $post->ID,
    'slug'           => $slug,
    'permalink'      => $permalink,
    'status'         => $post->post_status,
    'author'         => $user->user_login,
    'title'          => $post->post_title,
    'date_create'    => $post->post_date,
    'thumbnail'      => $thumbnail,
    'post_content'   => $post->post_content,
    'subtitle'       => !empty($post_meta['subtitle']) ? $post_meta['subtitle'][0] : '',
    'previews'       => $previews,
    'category'       => get_the_terms($post->ID, 'category'),
    'post_tag'       => get_the_terms($post->ID, 'post_tag') ?: [],
    'compatibility'  => get_the_terms($post->ID, 'compatibility') ?: [],
    'emphasis'       => $emphasis,
    'developer'      => get_the_terms($post->ID, 'developer'),
    'origin'         => get_the_terms($post->ID, 'origin'),
    'version'        => !empty($post_meta['version']) ? $post_meta['version'][0] : '',
    'download'       => !empty($post_meta['download']) ? $post_meta['download'][0] : 'Sem link de download.',
    'font'           => !empty($post_meta['font']) ? $post_meta['font'][0] : 'Nenhuma fonte informada.',
    'size_file'      => !empty($post_meta['size_file']) ? $post_meta['size_file'][0] : 'Não foi informado o tamanho do arquivo.',
    'favorite'       => $favorite,
    'update'         => $post->post_modified,
    'total_comments' => $total_comments,
  ];
}

?>