<?php
/**
 * Configura a taxonomia de Desenvolvedores dos Ativos.
 * 
 * @package MiraUP
 * @subpackage Taxonomy Developers
 * @since 1.0.0
 * @version 1.0.0
 */
add_action( 'init', 'developer_register_taxonomy' );
function developer_register_taxonomy() {
	$args = [
		'label'  => esc_html__( 'Developers', 'developer-list' ),
		'labels' => [
			'menu_name'                  => esc_html__( 'Developers', 'developer-list' ),
			'all_items'                  => esc_html__( 'All Developers', 'developer-list' ),
			'edit_item'                  => esc_html__( 'Edit Developer', 'developer-list' ),
			'view_item'                  => esc_html__( 'View Developer', 'developer-list' ),
			'update_item'                => esc_html__( 'Update Developer', 'developer-list' ),
			'add_new_item'               => esc_html__( 'Add new Developer', 'developer-list' ),
			'new_item'                   => esc_html__( 'New Developer', 'developer-list' ),
			'parent_item'                => esc_html__( 'Parent Developer', 'developer-list' ),
			'parent_item_colon'          => esc_html__( 'Parent Developer', 'developer-list' ),
			'search_items'               => esc_html__( 'Search Developers', 'developer-list' ),
			'popular_items'              => esc_html__( 'Popular Developers', 'developer-list' ),
			'separate_items_with_commas' => esc_html__( 'Separate Developers with commas', 'developer-list' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove Developers', 'developer-list' ),
			'choose_from_most_used'      => esc_html__( 'Choose most used Developers', 'developer-list' ),
			'not_found'                  => esc_html__( 'No Developers found', 'developer-list' ),
			'name'                       => esc_html__( 'Developers', 'developer-list' ),
			'singular_name'              => esc_html__( 'Developer', 'developer-list' ),
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
	register_taxonomy( 'developer', [ 'post' ], $args );
}