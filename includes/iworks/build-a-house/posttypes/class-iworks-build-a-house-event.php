<?php
/*
Copyright 2021-2023 Marcin Pietrzak (marcin@iworks.pl)

this program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( class_exists( 'iworks_build_a_house_posttypes_event' ) ) {
	return;
}

require_once dirname( dirname( __FILE__ ) ) . '/posttypes.php';

class iworks_build_a_house_posttypes_event extends iworks_build_a_house_posttypes {

	protected $post_type_name = 'ibh_event'; // iworks_build_a_house_event (varchar(20))

	public function __construct() {
		parent::__construct();
		$this->fields                                 = array(
			'event_data' => array(
				'date_start' => array(
					'type'  => 'date',
					'label' => __( 'Date start', 'build-a-house' ),
				),
				'date_end'   => array(
					'type'  => 'date',
					'label' => __( 'Date start', 'build-a-house' ),
				),
			),
		);
		$this->post_type_objects[ $this->get_name() ] = $this;
		/**
		 * change default columns
		 */
		add_filter( "manage_{$this->get_name()}_posts_columns", array( $this, 'add_columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'custom_columns' ), 10, 2 );
		/**
		 * apply default sort order
		 */
		add_action( 'pre_get_posts', array( $this, 'apply_default_sort_order' ) );
	}

	public function register() {
		$labels = array(
			'name'                  => _x( 'Events', 'Event General Name', 'build-a-house' ),
			'singular_name'         => _x( 'Event', 'Event Singular Name', 'build-a-house' ),
			'menu_name'             => __( 'Events', 'build-a-house' ),
			'name_admin_bar'        => __( 'Event', 'build-a-house' ),
			'archives'              => __( 'Event Archives', 'build-a-house' ),
			'attributes'            => __( 'Event Attributes', 'build-a-house' ),
			'parent_item_colon'     => __( 'Parent Event:', 'build-a-house' ),
			'all_items'             => __( 'Events', 'build-a-house' ),
			'add_new_item'          => __( 'Add New Event', 'build-a-house' ),
			'add_new'               => __( 'Add New', 'build-a-house' ),
			'new_item'              => __( 'New Event', 'build-a-house' ),
			'edit_item'             => __( 'Edit Event', 'build-a-house' ),
			'update_item'           => __( 'Update Event', 'build-a-house' ),
			'view_item'             => __( 'View Event', 'build-a-house' ),
			'view_items'            => __( 'View Events', 'build-a-house' ),
			'search_items'          => __( 'Search Event', 'build-a-house' ),
			'not_found'             => __( 'Not found', 'build-a-house' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'build-a-house' ),
			'featured_image'        => __( 'Featured Image', 'build-a-house' ),
			'set_featured_image'    => __( 'Set featured image', 'build-a-house' ),
			'remove_featured_image' => __( 'Remove featured image', 'build-a-house' ),
			'use_featured_image'    => __( 'Use as featured image', 'build-a-house' ),
			'insert_into_item'      => __( 'Insert into contractor', 'build-a-house' ),
			'uploaded_to_this_item' => __( 'Uploaded to this contractor', 'build-a-house' ),
			'items_list'            => __( 'Events list', 'build-a-house' ),
			'items_list_navigation' => __( 'Events list navigation', 'build-a-house' ),
			'filter_items_list'     => __( 'Filter contractors list', 'build-a-house' ),
		);
		$args   = array(
			'label'                => __( 'Event', 'build-a-house' ),
			'description'          => __( 'Event Description', 'build-a-house' ),
			'labels'               => $labels,
			'supports'             => array( 'title', 'thumbnail', 'editor' ),
			'taxonomies'           => array(),
			'hierarchical'         => true,
			'public'               => true,
			'show_ui'              => true,
			'show_in_menu'         => add_query_arg( array( 'post_type' => 'ibh_expence' ), 'edit.php' ),
			'show_in_admin_bar'    => false,
			'show_in_nav_menus'    => false,
			'show_in_rest'         => true,
			'can_export'           => true,
			'has_archive'          => true,
			'exclude_from_search'  => true,
			'publicly_queryable'   => false,
			'capability_type'      => 'page',
			'register_meta_box_cb' => array( $this, 'register_meta_boxes' ),
		);
		register_post_type( $this->post_type_name, $args );
	}

	public function register_meta_boxes( $post ) {
		add_meta_box( 'event-data', __( 'Event Data', 'build-a-house' ), array( $this, 'event_data' ), $this->post_type_name );
	}

	public function event_data( $post ) {
		$this->get_meta_box_content( $post, $this->fields, __FUNCTION__ );
	}


	public function save_post_meta( $post_id, $post, $update ) {
		$this->save_post_meta_fields( $post_id, $post, $update, $this->fields );
	}


	/**
	 * Get custom column values.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $column Column name,
	 * @param integer $post_id Current post id (contractor),
	 */
	public function custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'date_start':
				echo get_post_meta( $post_id, $this->options->get_option_name( 'event_data_date_start' ), true );
				break;
			case 'date_end':
				echo get_post_meta( $post_id, $this->options->get_option_name( 'event_data_date_end' ), true );
				break;
		}
	}

	/**
	 * change default columns
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns list of columns.
	 * @return array $columns list of columns.
	 */
	public function add_columns( $columns ) {
		unset( $columns['date'] );
		$columns['date_start'] = __( 'Date Start', 'build-a-house' );
		$columns['date_end']   = __( 'Date End', 'build-a-house' );
		return $columns;
	}

	/**
	 * Add default sorting: post title
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query WP Query object.
	 */
	public function apply_default_sort_order( $query ) {
		/**
		 * do not change if it is already set by request
		 */
		if ( isset( $_REQUEST['orderby'] ) ) {
			return $query;
		}
		/**
		 * do not change outsite th admin area
		 */
		if ( ! is_admin() ) {
			return $query;
		}
		/**
		 * check get_current_screen()
		 */
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $query;
		}
		/**
		 * check screen post type
		 */
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $query;
		}
		$screen = get_current_screen();
		if ( isset( $screen->post_type ) && $this->get_name() == $screen->post_type ) {
			$query->set( 'orderby', 'title' );
			$query->set( 'order', 'ASC' );
		}
		return $query;
	}

}

