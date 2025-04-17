<?php
/**
 * Configura a taxonomia de Compatibilidades dos Ativos.
 * 
 * @package MiraUP
 * @subpackage Taxonomy Compatibility
 * @since 1.0.0
 * @version 1.0.0
 */
add_action( 'init', 'compatibility_register_taxonomy' );
function compatibility_register_taxonomy() {
	$args = [
		'label'  => esc_html__( 'Compatibility', 'compatibility-list' ),
		'labels' => [
			'menu_name'                  => esc_html__( 'Compatibility', 'compatibility-list' ),
			'all_items'                  => esc_html__( 'All Compatibility', 'compatibility-list' ),
			'edit_item'                  => esc_html__( 'Edit compatibility', 'compatibility-list' ),
			'view_item'                  => esc_html__( 'View compatibility', 'compatibility-list' ),
			'update_item'                => esc_html__( 'Update compatibility', 'compatibility-list' ),
			'add_new_item'               => esc_html__( 'Add new compatibility', 'compatibility-list' ),
			'new_item'                   => esc_html__( 'New compatibility', 'compatibility-list' ),
			'parent_item'                => esc_html__( 'Parent compatibility', 'compatibility-list' ),
			'parent_item_colon'          => esc_html__( 'Parent compatibility', 'compatibility-list' ),
			'search_items'               => esc_html__( 'Search Compatibility', 'compatibility-list' ),
			'popular_items'              => esc_html__( 'Popular Compatibility', 'compatibility-list' ),
			'separate_items_with_commas' => esc_html__( 'Separate Compatibility with commas', 'compatibility-list' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove Compatibility', 'compatibility-list' ),
			'choose_from_most_used'      => esc_html__( 'Choose most used Compatibility', 'compatibility-list' ),
			'not_found'                  => esc_html__( 'No Compatibility found', 'compatibility-list' ),
			'name'                       => esc_html__( 'Compatibility', 'compatibility-list' ),
			'singular_name'              => esc_html__( 'compatibility', 'compatibility-list' ),
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
	register_taxonomy( 'compatibility', [ 'post' ], $args );
}