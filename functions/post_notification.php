<?php 
/**
 * Cria o tipo de post de Notificações.
 * 
 * @package MiraUP
 * @subpackage Notifications Posts
 * @since 1.0.0
 * @version 1.0.0
 */
function create_notification_post_type() {
  register_post_type('notification',
      array(
          'labels' => array(
              'name' => __('Notificações'),
              'singular_name' => __('Notificação'),
          ),
          'public' => true, // Não exibir publicamente
          'exclude_from_search' => false,
          'show_ui' => true, // Exibir no painel admin
          'supports' => array('title', 'custom-fields', 'editor'),
          'taxonomies' => array( 'notifications', 'notification' ),
          'menu_icon' => 'dashicons-flag',
      )
  );
}
add_action('init', 'create_notification_post_type');