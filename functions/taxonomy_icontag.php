<?php
/**
 * Configura a taxonomia de Tags dos Ícones dos Ativos.
 * 
 * @package MiraUP
 * @subpackage Taxonomy Icon Tags
 * @since 1.0.0
 * @version 1.0.0
 */
add_action( 'init', 'icon_tag_register_taxonomy' );
function icon_tag_register_taxonomy() {
	$args = [
		'label'  => esc_html__( 'Icon tags', 'icon_tag-list' ),
		'labels' => [
			'menu_name'                  => esc_html__( 'Icon Tags', 'icon_tag-list' ),
			'all_items'                  => esc_html__( 'All Icon Tags', 'icon_tag-list' ),
			'edit_item'                  => esc_html__( 'Edit Icon Tag', 'icon_tag-list' ),
			'view_item'                  => esc_html__( 'View Icon Tag', 'icon_tag-list' ),
			'update_item'                => esc_html__( 'Update Icon Tag', 'icon_tag-list' ),
			'add_new_item'               => esc_html__( 'Add new Icon Tag', 'icon_tag-list' ),
			'new_item'                   => esc_html__( 'New Icon Tag', 'icon_tag-list' ),
			'parent_item'                => esc_html__( 'Parent Icon Tag', 'icon_tag-list' ),
			'parent_item_colon'          => esc_html__( 'Parent Icon Tag', 'icon_tag-list' ),
			'search_items'               => esc_html__( 'Search Icon Tag', 'icon_tag-list' ),
			'popular_items'              => esc_html__( 'Popular Icon Tag', 'icon_tag-list' ),
			'separate_items_with_commas' => esc_html__( 'Separate Icon Tag with commas', 'icon_tag-list' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove Icon Tag', 'icon_tag-list' ),
			'choose_from_most_used'      => esc_html__( 'Choose most used Icon Tag', 'icon_tag-list' ),
			'not_found'                  => esc_html__( 'No Icon Tag found', 'icon_tag-list' ),
			'name'                       => esc_html__( 'Icon Tag', 'icon_tag-list' ),
			'singular_name'              => esc_html__( 'Icon Tag', 'icon_tag-list' ),
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
	register_taxonomy( 'icon_tag', [ 'attachment' ], $args );
}