<?php
/**
 * Todas as funções da API.
 * 
 * @package MiraUP
 * @subpackage Functions API
 * @since 1.0.0
 * @version 1.0.0
 */

  /****** Permissions ******/
  include('functions/permission.php');

  $dirbase = get_template_directory();

  require_once $dirbase . '/endpoint/notification_get.php';
  require_once $dirbase . '/endpoint/notification_post.php';
  require_once $dirbase . '/endpoint/notification_add.php';
  require_once $dirbase . '/endpoint/notification_put.php';
  require_once $dirbase . '/endpoint/notification_error.php';
  require_once $dirbase . '/endpoint/notification_delete.php';
  
  require_once $dirbase . '/endpoint/user_search.php';
  require_once $dirbase . '/endpoint/user_post.php';
  require_once $dirbase . '/endpoint/user_get.php';
  require_once $dirbase . '/endpoint/user_put.php';
  require_once $dirbase . '/endpoint/user_delete.php';

  require_once $dirbase . '/endpoint/asset_search.php';
  require_once $dirbase . '/endpoint/asset_favorite.php';
  require_once $dirbase . '/endpoint/asset_post.php';
  require_once $dirbase . '/endpoint/asset_get.php';
  require_once $dirbase . '/endpoint/asset_put.php';
  require_once $dirbase . '/endpoint/asset_delete.php';
  require_once $dirbase . '/endpoint/new_code_email.php';

  require_once $dirbase . '/endpoint/media_search.php';
  require_once $dirbase . '/endpoint/media_post.php';
  require_once $dirbase . '/endpoint/media_get.php';
  require_once $dirbase . '/endpoint/media_put.php';
  require_once $dirbase . '/endpoint/media_delete.php';

  require_once $dirbase . '/endpoint/taxonomy_search.php';
  require_once $dirbase . '/endpoint/taxonomy_post.php';
  require_once $dirbase . '/endpoint/taxonomy_get.php';
  require_once $dirbase . '/endpoint/taxonomy_put.php';
  require_once $dirbase . '/endpoint/taxonomy_delete.php';

  require_once $dirbase . '/endpoint/comment_post.php';
  require_once $dirbase . '/endpoint/comment_get.php';
  require_once $dirbase . '/endpoint/comment_put.php';
  require_once $dirbase . '/endpoint/comment_delete.php';

  require_once $dirbase . '/endpoint/statistics_get.php';
  require_once $dirbase . '/endpoint/statistics_post.php';

  require_once $dirbase . '/endpoint/password.php';

  /****** Translation Dictionary ******/
  include('functions/translate_dictionary.php');

  /****** Rate Limiting ******/
  include('functions/rate_limiting.php');

  /****** Image Custom ******/
  include('functions/image_custom.php');

  /****** Prefix API ******/
  include('functions/prefix_api.php');

  /****** Time Token ******/
  include('functions/time_token.php');
  
  /****** SVG Support ******/
  include('functions/svg_support.php');

  /****** Add Taxonomys ******/
  include('functions/taxonomy_origin.php');
  include('functions/taxonomy_developer.php');
  include('functions/taxonomy_compatibility.php');
  include('functions/taxonomy_iconstyle.php');
  include('functions/taxonomy_icontag.php');
  include('functions/taxonomy_iconcategory.php');
  include('functions/taxonomy_notifications.php');

  /****** Post Notifications ******/
  include('functions/post_notification.php');

  /****** Create Term From Tags If Not Exists ******/
  include('functions/create_term_taxonomy.php');

  /****** Extract Valid Words Tags ******/
  include('functions/extract_words_tags.php');
?>