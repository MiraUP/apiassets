<?php
/**
 * Configura a taxonomia de Categorias dos Ícones dos Ativos.
 * 
 * @package MiraUP
 * @subpackage Taxonomy Icon Categorys
 * @since 1.0.0
 * @version 1.0.0
 */
add_action( 'init', 'icon_category_register_taxonomy' );
function icon_category_register_taxonomy() {
	$args = [
		'label'  => esc_html__( 'Icon Categorys', 'icon_category-list' ),
		'labels' => [
			'menu_name'                  => esc_html__( 'Icon Categorys', 'icon_category-list' ),
			'all_items'                  => esc_html__( 'All Icon Categorys', 'icon_category-list' ),
			'edit_item'                  => esc_html__( 'Edit Icon Category', 'icon_category-list' ),
			'view_item'                  => esc_html__( 'View Icon Category', 'icon_category-list' ),
			'update_item'                => esc_html__( 'Update Icon Category', 'icon_category-list' ),
			'add_new_item'               => esc_html__( 'Add new Icon Category', 'icon_category-list' ),
			'new_item'                   => esc_html__( 'New Icon Category', 'icon_category-list' ),
			'parent_item'                => esc_html__( 'Parent Icon Category', 'icon_category-list' ),
			'parent_item_colon'          => esc_html__( 'Parent Icon Category', 'icon_category-list' ),
			'search_items'               => esc_html__( 'Search Icon Category', 'icon_category-list' ),
			'popular_items'              => esc_html__( 'Popular Icon Category', 'icon_category-list' ),
			'separate_items_with_commas' => esc_html__( 'Separate Icon Category with commas', 'icon_category-list' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove Icon Category', 'icon_category-list' ),
			'choose_from_most_used'      => esc_html__( 'Choose most used Icon Category', 'icon_category-list' ),
			'not_found'                  => esc_html__( 'No Icon Category found', 'icon_category-list' ),
			'name'                       => esc_html__( 'Icon Category', 'icon_category-list' ),
			'singular_name'              => esc_html__( 'Icon Category', 'icon_category-list' ),
		],
		'public'               => true,
		'show_ui'              => true,
		'show_in_menu'         => true,
		'show_in_nav_menus'    => true,
		'show_categorycloud'        => true,
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
	register_taxonomy( 'icon_category', [ 'attachment' ], $args );
}