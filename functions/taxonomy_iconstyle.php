<?php
/**
 * Configura a taxonomia de Estilos dos Ícones dos Ativos.
 * 
 * @package MiraUP
 * @subpackage Taxonomy Icon Styles
 * @since 1.0.0
 * @version 1.0.0
 */
add_action( 'init', 'icon_style_register_taxonomy' );
function icon_style_register_taxonomy() {
	$args = [
		'label'  => esc_html__( 'Icon Styles', 'icon_style-list' ),
		'labels' => [
			'menu_name'                  => esc_html__( 'Icon Styles', 'icon_style-list' ),
			'all_items'                  => esc_html__( 'All Icon Styles', 'icon_style-list' ),
			'edit_item'                  => esc_html__( 'Edit Icon Style', 'icon_style-list' ),
			'view_item'                  => esc_html__( 'View Icon Style', 'icon_style-list' ),
			'update_item'                => esc_html__( 'Update Icon Style', 'icon_style-list' ),
			'add_new_item'               => esc_html__( 'Add new Icon Style', 'icon_style-list' ),
			'new_item'                   => esc_html__( 'New Icon Style', 'icon_style-list' ),
			'parent_item'                => esc_html__( 'Parent Icon Style', 'icon_style-list' ),
			'parent_item_colon'          => esc_html__( 'Parent Icon Style', 'icon_style-list' ),
			'search_items'               => esc_html__( 'Search Icon Styles', 'icon_style-list' ),
			'popular_items'              => esc_html__( 'Popular Icon Styles', 'icon_style-list' ),
			'separate_items_with_commas' => esc_html__( 'Separate Icon Styles with commas', 'icon_style-list' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove Icon Styles', 'icon_style-list' ),
			'choose_from_most_used'      => esc_html__( 'Choose most used Icon Styles', 'icon_style-list' ),
			'not_found'                  => esc_html__( 'No Icon Styles found', 'icon_style-list' ),
			'name'                       => esc_html__( 'Icon Styles', 'icon_style-list' ),
			'singular_name'              => esc_html__( 'Icon Style', 'icon_style-list' ),
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
	register_taxonomy( 'icon_style', [ 'attachment' ], $args );
}