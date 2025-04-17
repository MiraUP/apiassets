<?php
/**
 * Configura a taxonomia de Notificações.
 * 
 * @package MiraUP
 * @subpackage Taxonomy Notification
 * @since 1.0.0
 * @version 1.0.0
 */
add_action( 'init', 'notification_register_taxonomy' );
function notification_register_taxonomy() {
	$args = [
		'label'  => esc_html__( 'notification', 'notification-list' ),
		'labels' => [
			'menu_name'                  => esc_html__( 'Notification', 'notification-list' ),
			'all_items'                  => esc_html__( 'All notification', 'notification-list' ),
			'edit_item'                  => esc_html__( 'Edit notification', 'notification-list' ),
			'view_item'                  => esc_html__( 'View notification', 'notification-list' ),
			'update_item'                => esc_html__( 'Update notification', 'notification-list' ),
			'add_new_item'               => esc_html__( 'Add new notification', 'notification-list' ),
			'new_item'                   => esc_html__( 'New notification', 'notification-list' ),
			'parent_item'                => esc_html__( 'Parent notification', 'notification-list' ),
			'parent_item_colon'          => esc_html__( 'Parent notification', 'notification-list' ),
			'search_items'               => esc_html__( 'Search notification', 'notification-list' ),
			'popular_items'              => esc_html__( 'Popular notification', 'notification-list' ),
			'separate_items_with_commas' => esc_html__( 'Separate notification with commas', 'notification-list' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove notification', 'notification-list' ),
			'choose_from_most_used'      => esc_html__( 'Choose most used notification', 'notification-list' ),
			'not_found'                  => esc_html__( 'No notification found', 'notification-list' ),
			'name'                       => esc_html__( 'Notification', 'notification-list' ),
			'singular_name'              => esc_html__( 'Notification', 'notification-list' ),
		],
		'public'               => true,
		'show_ui'              => true,
		'show_in_menu'         => true,
		'show_in_nav_menus'    => true,
		'show_tagcloud'        => true,
		'show_in_quick_edit'   => true,
		'show_admin_column'    => false,
		'show_in_rest'         => true,
		'hierarchical'         => false,
		'query_var'            => true,
		'sort'                 => false,
		'rewrite_no_front'     => false,
		'rewrite_hierarchical' => false,
		'rewrite' => true
	];
	register_taxonomy( 'notification', [ 'post' ], $args );
}