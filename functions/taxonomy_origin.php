<?php
/**
 * Configura a taxonomia de Origens dos Ativos.
 * 
 * @package MiraUP
 * @subpackage Taxonomy Origins
 * @since 1.0.0
 * @version 1.0.0
 */
add_action( 'init', 'origin_register_taxonomy' );
function origin_register_taxonomy() {
	$args = [
		'label'  => esc_html__( 'Origins', 'origin-list' ),
		'labels' => [
			'menu_name'                  => esc_html__( 'Origins', 'origin-list' ),
			'all_items'                  => esc_html__( 'All Origins', 'origin-list' ),
			'edit_item'                  => esc_html__( 'Edit Origin', 'origin-list' ),
			'view_item'                  => esc_html__( 'View Origin', 'origin-list' ),
			'update_item'                => esc_html__( 'Update Origin', 'origin-list' ),
			'add_new_item'               => esc_html__( 'Add new Origin', 'origin-list' ),
			'new_item'                   => esc_html__( 'New Origin', 'origin-list' ),
			'parent_item'                => esc_html__( 'Parent Origin', 'origin-list' ),
			'parent_item_colon'          => esc_html__( 'Parent Origin', 'origin-list' ),
			'search_items'               => esc_html__( 'Search Origins', 'origin-list' ),
			'popular_items'              => esc_html__( 'Popular Origins', 'origin-list' ),
			'separate_items_with_commas' => esc_html__( 'Separate Origins with commas', 'origin-list' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove Origins', 'origin-list' ),
			'choose_from_most_used'      => esc_html__( 'Choose most used Origins', 'origin-list' ),
			'not_found'                  => esc_html__( 'No Origins found', 'origin-list' ),
			'name'                       => esc_html__( 'Origins', 'origin-list' ),
			'singular_name'              => esc_html__( 'Origin', 'origin-list' ),
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
	register_taxonomy( 'origin', [ 'post' ], $args );
}