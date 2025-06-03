  <?php
  /**
   * Endpoint para listagem de curadorias relacionadas a posts
   * 
   * @package MiraUP
   * @subpackage Curation
   * @since 1.0.0
   * @version 1.0.0
   */

  /**
   * Lista curadorias baseado no post_id
   */
  function api_curation_get(WP_REST_Request $request) {
      global $wpdb;
      
      // Obtém o usuário atual
      $current_user = wp_get_current_user();
      $current_user_id = (int) $current_user->ID;
      
      // Verificar autenticação
      if ($error = Permissions::check_authentication($current_user)) {
          return $error;
      }

      // Verifica rate limiting
      if ($error = Permissions::check_rate_limit('curation_list-' . $current_user_id, 60)) {
          return $error;
      }
      
      // Verifica o status da conta do usuário
      if ($error = Permissions::check_account_status($current_user)) {
          return $error;
      }

      // Parâmetros da requisição
      $params = $request->get_params();
      $post_id = absint($params['post_id'] ?? 0);
      $slug = sanitize_text_field($params['slug'] ?? '');
      $page = absint($params['page'] ?? 1);
      $per_page = absint($params['per_page'] ?? 10);
      
      // Validação do post_id
      if (empty($post_id) && empty($slug)) {
          return new WP_Error(
              'missing_post_id', 
              'É necessário fornecer um post_id ou slug para buscar curadorias.', 
              ['status' => 400]
          );
      }
      
      // Se slug foi fornecido, busca o post_id correspondente
      if (!empty($slug)) {
          $post = get_page_by_path($slug, OBJECT, 'post');
          if (!$post) {
              return new WP_Error(
                  'post_not_found', 
                  'Nenhum post encontrado com o slug fornecido.', 
                  ['status' => 404]
              );
          }
          $post_id = $post->ID;
      }
      
      // Verifica se o post existe
      $post = get_post($post_id);
      if (!$post) {
          return new WP_Error(
              'post_not_found', 
              'Post não encontrado.', 
              ['status' => 404]
          );
      }

      if (!check_curation_permissions($current_user_id, $post->post_author)) {
          return new WP_Error(
              'permission_denied', 
              'Você não tem permissão para acessar as curadorias deste post.', 
              ['status' => 403]
          );
      }

      // Query base
      $query = $wpdb->prepare("
          SELECT 
          n.id,
          n.notification_id,
          n.post_id,
          n.marker as status,
          n.created_at,
          p.post_title,
          p.post_name as slug,
          p.post_author as post_author_id,
          pm_author.meta_value as curator_id,
          curator.user_login as curator_name,
          author.user_login as post_author_name,
          pm_message.meta_value as short_message,
          cp.post_content as content
      FROM {$wpdb->prefix}notifications n
      INNER JOIN {$wpdb->posts} p ON n.post_id = p.ID
      INNER JOIN {$wpdb->users} author ON p.post_author = author.ID
      LEFT JOIN {$wpdb->postmeta} pm_author ON n.notification_id = pm_author.post_id AND pm_author.meta_key = 'notification_author'
      LEFT JOIN {$wpdb->users} curator ON pm_author.meta_value = curator.ID
      LEFT JOIN {$wpdb->postmeta} pm_message ON n.notification_id = pm_message.post_id AND pm_message.meta_key = 'notification_message'
      LEFT JOIN {$wpdb->posts} cp ON n.notification_id = cp.ID
      WHERE n.marker = 'curation'
      AND n.post_id = %d
      ", $post_id);
      
      // Ordenação
      $query .= " ORDER BY n.created_at DESC";
      
      // Modo padrão (última curadoria)
      if (empty($page) || $page == 1 && $per_page == 1) {
          $query .= " LIMIT 1";
          $item = $wpdb->get_row($query);
          
          if (!$item) {
              return new WP_Error(
                  'no_curations', 
                  'Nenhuma curadoria encontrada para este post.', 
                  ['status' => 404]
              );
          }
          
          return rest_ensure_response([
              'success' => true,
              'data' => format_curation_data($item, false)
          ]);
      }
      
      // Paginação para listagem completa
      $offset = ($page - 1) * $per_page;
      $query .= $wpdb->prepare(" LIMIT %d, %d", $offset, $per_page);
      
      $results = $wpdb->get_results($query);
      
      // Contagem total para paginação
      $count_query = $wpdb->prepare("
          SELECT COUNT(*)
          FROM {$wpdb->prefix}notifications
          WHERE marker = 'curation'
          AND post_id = %d
      ", $post_id);
      
      $total = $wpdb->get_var($count_query);
      
      // Formata os resultados
      $formatted = [];
      foreach ($results as $item) {
          $formatted[] = format_curation_data($item, true);
      }
      
      return rest_ensure_response([
          'success' => true,
          'data' => $formatted,
          'pagination' => [
              'current_page' => $page,
              'per_page' => $per_page,
              'total_items' => (int)$total,
              'total_pages' => ceil($total / $per_page)
          ],
          'post' => [
              'id' => $post->ID,
              'title' => $post->post_title,
              'author_id' => $post->post_author
          ]
      ]);
  }

  /**
   * Formata os dados da curadoria
   */
  function format_curation_data($item, $full_details) {
      $base_data = [
          'id' => $item->notification_id,
          'notification_id' => $item->notification_id,
          'post_id' => $item->post_id,
          'status' => $item->status,
          'created_at' => $item->created_at,
          'post_title' => $item->post_title,
          'slug' => $item->slug,
      ];
      
      if ($full_details) {
          $base_data['curator'] = [
              'id' => $item->curator_id,
              'name' => $item->curator_name ?? 'Usuário não encontrado'
          ];
          $base_data['post_author'] = [
              'id' => $item->post_author_id,
              'name' => $item->post_author_name
          ];
          $base_data['short_message'] = $item->short_message ?? null;
          $base_data['content'] = $item->content ?? null;
      }
      
      return $base_data;
  }

  /**
   * Registra os endpoints de curadoria
  */
  function register_api_curation_latest_get() {
    // Obtém a última curadoria de um post específico
    register_rest_route('api/v1', '/curations/latest', [
      'methods'  => WP_REST_Server::READABLE,
      'callback' => function($request) {
        $request->set_param('per_page', 1);
        $request->set_param('page', 1);
        return api_curation_get($request);
        },
      'permission_callback' => function() {
        return is_user_logged_in();
      },
    ]);
  }
  add_action('rest_api_init', 'register_api_curation_latest_get');

  function register_api_curation_list_get() {
    // Obtém a última curadoria de um post específico
    register_rest_route('api/v1', '/curations', [
        'methods'  => WP_REST_Server::READABLE,
        'callback' => 'api_curation_get',
        'permission_callback' => function() {
            return is_user_logged_in();
        },
    ]);
  }
  add_action('rest_api_init', 'register_api_curation_list_get');

  function register_api_curation_get() {
    // Obtém a última curadoria de um post específico
    register_rest_route('api/v1', '/curations/(?P<slug>[\w\-]+)', [
        'methods'  => WP_REST_Server::READABLE,
        'callback' => function($request) {
        $request->set_param('per_page', 1);
        $request->set_param('page', 1);
        return api_curation_get($request);
        },
        'permission_callback' => function() {
            return is_user_logged_in();
        },
    ]);
  }
  add_action('rest_api_init', 'register_api_curation_get');

  
/**
 * Função auxiliar para verificar permissões de curadoria
 */
function check_curation_permissions($user_id, $post_author_id) {
    // Administradores têm acesso completo
    if (current_user_can('administrator')) {
        return true;
    }
    
    // Autor do post principal tem acesso
    if ($user_id == $post_author_id) {
        return true;
    }
    
    // Outros casos negam acesso
    return false;
}