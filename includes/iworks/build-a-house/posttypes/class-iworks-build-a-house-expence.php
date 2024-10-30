<?php
/*

Copyright 2020-2023 Marcin Pietrzak (marcin@iworks.pl)

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

if ( class_exists( 'iworks_build_a_house_posttypes_expence' ) ) {
	return;
}

require_once( dirname( dirname( __FILE__ ) ) . '/posttypes.php' );

class iworks_build_a_house_posttypes_expence extends iworks_build_a_house_posttypes {

	protected $post_type_name          = 'ibh_expence';
	protected $taxonomy_name_breakdown = 'iworks_build_a_house_breakdown';
	private $nonce_list                = 'iworks_build_a_house_expence_expences_list_nonce';
	private $users_list                = array();
	private $boats_list                = array();

	/**
	 * Semaphore helper for import option name
	 *
	 * @since 1.0.0
	 */
	private $import_breakdown_option_name = 'iworks_build_a_house_breakdowns_import';

	public function __construct() {
		parent::__construct();
		add_filter( 'the_content', array( $this, 'the_content' ) );
		/**
		 * change default columns
		 */
		add_filter( "manage_{$this->get_name()}_posts_columns", array( $this, 'add_columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'custom_columns' ), 10, 2 );
		/**
		 * apply default sort order
		 */
		add_action( 'pre_get_posts', array( $this, 'apply_default_sort_order' ) );
		/**
		 * AJAX list
		 */
		if ( is_a( $this->options, 'iworks_options' ) ) {
			$this->nonce_list = $this->options->get_option_name( 'expences_list_nonce' );
		}
		add_action( 'wp_ajax_iworks_build_a_house_expences_list', array( $this, 'get_select2_list' ) );
		add_action( 'wp_ajax_iworks_build_a_house_breakdowns_import', array( $this, 'ajax_import_breakdowns' ) );
		/**
		 * Taxonomy
		 */
		add_action( 'delete_' . $this->taxonomy_name_breakdown, array( $this, 'breakdown_maybe_allow_import' ) );
		/**
		 * admin enqueue scripts
		 */
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 117 );
		add_filter( 'wp_localize_script_build_a_house_admin', array( $this, 'add_nonce' ) );
		/**
		 * block
		 */
		add_action( 'init', array( $this, 'register_blocks' ) );
		/**
		 * fields
		 */
		$this->fields = array(
			'details' => array(
				'cost'       => array(
					'type'  => 'Number',
					'label' => __( 'Cost', 'build-a-house' ),
				),
				'contractor' => array(
					'type'  => 'select2',
					'label' => __( 'Contractor', 'kpir' ),
					'args'  => array(
						'data-source'       => 'contractor',
						'data-nonce-action' => 'get-contractors-list',
					),
				),
				'date_start' => array(
					'type'  => 'date',
					'label' => __( 'Date Start', 'build-a-house' ),
				),
				'date_end'   => array(
					'type'  => 'date',
					'label' => __( 'Date End', 'build-a-house' ),
				),
				'mobile'     => array( 'label' => __( 'Mobile', 'build-a-house' ) ),
				'email'      => array( 'label' => __( 'E-mail', 'build-a-house' ) ),
			),
		);
		/**
		 * add class to metaboxes
		 */
		foreach ( array_keys( $this->fields ) as $name ) {
			if ( 'basic' == $name ) {
				continue;
			}
			$key = sprintf( 'postbox_classes_%s_%s', $this->get_name(), $name );
			add_filter( $key, array( $this, 'add_defult_class_to_postbox' ) );
		}
	}

	/**
	 * Add default class to postbox,
	 */
	public function add_defult_class_to_postbox( $classes ) {
		$classes[] = 'iworks-type';
		return $classes;
	}

	public function register() {
		$parent = true;
		$labels = array(
			'name'                  => _x( 'Expences', 'expence General Name', 'build-a-house' ),
			'singular_name'         => _x( 'Expence', 'expence Singular Name', 'build-a-house' ),
			'menu_name'             => __( 'Build a house', 'build-a-house' ),
			'name_admin_bar'        => __( 'Expence', 'build-a-house' ),
			'archives'              => __( 'Expences', 'build-a-house' ),
			'attributes'            => __( 'Item Attributes', 'build-a-house' ),
			'all_items'             => __( 'Expences', 'build-a-house' ),
			'add_new_item'          => __( 'Add New expence', 'build-a-house' ),
			'add_new'               => __( 'Add New expence', 'build-a-house' ),
			'new_item'              => __( 'New expence', 'build-a-house' ),
			'edit_item'             => __( 'Edit expence', 'build-a-house' ),
			'update_item'           => __( 'Update expence', 'build-a-house' ),
			'view_item'             => __( 'View expence', 'build-a-house' ),
			'view_items'            => __( 'View expences', 'build-a-house' ),
			'search_items'          => __( 'Search expence', 'build-a-house' ),
			'not_found'             => __( 'Not found', 'build-a-house' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'build-a-house' ),
			'featured_image'        => __( 'Featured Image', 'build-a-house' ),
			'set_featured_image'    => __( 'Set featured image', 'build-a-house' ),
			'remove_featured_image' => __( 'Remove featured image', 'build-a-house' ),
			'use_featured_image'    => __( 'Use as featured image', 'build-a-house' ),
			'insert_into_item'      => __( 'Insert into item', 'build-a-house' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', 'build-a-house' ),
			'items_list'            => __( 'Items list', 'build-a-house' ),
			'items_list_navigation' => __( 'Items list navigation', 'build-a-house' ),
			'filter_items_list'     => __( 'Filter items list', 'build-a-house' ),
		);
		$args   = array(
			'label'                => __( 'Expence', 'build-a-house' ),
			'labels'               => $labels,
			'supports'             => array( 'title', 'editor', 'thumbnail', 'revision' ),
			'public'               => true,
			'show_ui'              => true,
			'show_in_menu'         => $parent,
			'show_in_admin_bar'    => true,
			'show_in_nav_menus'    => true,
			'can_export'           => true,
			'has_archive'          => _x( 'build_a_house_expences', 'slug for archive', 'build-a-house' ),
			'exclude_from_search'  => false,
			'publicly_queryable'   => true,
			'capability_type'      => 'page',
			'menu_icon'            => 'dashicons-admin-home',
			'register_meta_box_cb' => array( $this, 'register_meta_boxes' ),
		);
		$args   = apply_filters( 'build_a_house_register_expence_post_type_args', $args );
		register_post_type( $this->post_type_name, $args );
		/**
		 * expence hull breakdown Taxonomy.
		 */
		$labels = array(
			'name'                       => _x( 'Breakdowns', 'Breakdown General Name', 'build-a-house' ),
			'singular_name'              => _x( 'Breakdown', 'Breakdown Singular Name', 'build-a-house' ),
			'menu_name'                  => __( 'Breakdowns', 'build-a-house' ),
			'all_items'                  => __( 'Breakdowns', 'build-a-house' ),
			'new_item_name'              => __( 'New Breakdown Name', 'build-a-house' ),
			'add_new_item'               => __( 'Add New Breakdown', 'build-a-house' ),
			'edit_item'                  => __( 'Edit Breakdown', 'build-a-house' ),
			'update_item'                => __( 'Update Breakdown', 'build-a-house' ),
			'view_item'                  => __( 'View Breakdown', 'build-a-house' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'build-a-house' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'build-a-house' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'build-a-house' ),
			'popular_items'              => __( 'Popular Breakdowns', 'build-a-house' ),
			'search_items'               => __( 'Search Breakdowns', 'build-a-house' ),
			'not_found'                  => __( 'Not Found', 'build-a-house' ),
			'no_terms'                   => __( 'No items', 'build-a-house' ),
			'items_list'                 => __( 'Breakdowns list', 'build-a-house' ),
			'items_list_navigation'      => __( 'Breakdowns list navigation', 'build-a-house' ),
		);
		$args   = array(
			'labels'             => $labels,
			'hierarchical'       => true,
			'public'             => true,
			'show_admin_column'  => true,
			'show_in_nav_menus'  => true,
			'show_tagcloud'      => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_quick_edit' => true,
			'rewrite'            => array( 'slug' => 'build_a_house-breakdown' ),
		);
		$args   = apply_filters( 'build_a_house_register_expence_taxonomy_args', $args );
		register_taxonomy( $this->taxonomy_name_breakdown, array( $this->post_type_name ), $args );
	}

	public function save_post_meta( $post_id, $post, $update ) {
		$result = $this->save_post_meta_fields( $post_id, $post, $update, $this->fields );
	}

	public function register_meta_boxes( $post ) {
		add_meta_box( 'expenceal', __( 'Detailed data', 'build-a-house' ), array( $this, 'details' ), $this->post_type_name );
	}

	public function details( $post ) {
		$this->get_meta_box_content( $post, $this->fields, __FUNCTION__ );
	}

	/**
	 * Get custom column values.
	 *
	 * @since 1.0.0
	 *
	 * @param string $column Column name,
	 * @param integer $post_id Current post id (expence),
	 *
	 */
	public function custom_columns( $column, $post_id ) {
		$value = get_post_meta( $post_id, $this->options->get_option_name( $column ), true );
		switch ( $column ) {
			case 'details_contractor':
				$id = intval( $value );
				if ( empty( $id ) ) {
					echo '-';
				} else {
					$t = get_post_meta( $id, 'iworks_kpir_contractor_data_full_name', true );
					if ( empty( $t ) ) {
						$t = get_the_title( $id );
					}
					printf(
						'<a href="%s">%s</a>',
						add_query_arg(
							array(
								'contractor' => $id,
								'post_type'  => 'iworks_kpir_invoice',
							),
							admin_url( 'edit.php' )
						),
						$t
					);
				}
				break;

			case 'details_date_start':
				if ( empty( $value ) ) {
					echo '&ndash;';
				} else {
					echo date_i18n( get_option( 'date_format' ), $value );
				}
				break;

			default:
				echo get_post_meta( $post_id, $this->options->get_option_name( $column ), true );
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
		$columns['details_contractor'] = __( 'Contractor', 'build-a-house' );
		$columns['details_cost']       = __( 'Cost', 'build-a-house' );
		$columns['details_date_start'] = __( 'Date', 'build-a-house' );
		return $columns;
	}

	/**
	 * Add default sorting
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
		 * only main query
		 */
		if ( ! $query->is_main_query() ) {
			return $query;
		}
		/**
		 * do not change outsite th admin area
		 */
		$post_type = get_query_var( 'post_type' );
		if ( is_admin() ) {
			/**
			 * check get_current_screen()
			 */
			if ( function_exists( 'get_current_screen' ) ) {
				$screen = get_current_screen();
				if ( isset( $screen->post_type ) && $this->get_name() == $screen->post_type ) {
					$query->set( 'order', 'ASC' );
					$query->set( 'orderby', 'post_title' );
				}
			}
		} else {
			if ( ! empty( $post_type ) && $post_type === $this->post_type_name ) {
				$query->set( 'order', 'ASC' );
				$query->set( 'orderby', 'post_title' );
				return $query;
			}
		}
		return $query;
	}

	public function get_select2_list() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! isset( $_POST['user_id'] ) ) {
			wp_send_json_error();
		}
		$nonce = $_POST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, $this->nonce_list . $_POST['user_id'] ) ) {
			wp_send_json_error();
		}
		$data      = array();
		$args      = array(
			'nopaging'  => true,
			'post_type' => $this->get_name(),
			'orderby'   => 'post_title',
			'order'     => 'ASC',
		);
		$the_query = new WP_Query( $args );
		// The Loop
		if ( $the_query->have_posts() ) {
			foreach ( $the_query->posts as $post ) {
				$data[] = array(
					'id'   => $post->ID,
					'text' => $post->post_title,
				);
			}
			wp_send_json_success( $data );
		}
		wp_send_json_error();
	}

	public function add_nonce( $data ) {
		$data['nonces'][ $this->nonce_list ] = wp_create_nonce( $this->nonce_list . get_current_user_id() );
		return $data;
	}

	/**
	 *
	 * @since 1.0
	 */
	public function the_content( $content ) {
		if ( ! is_singular() ) {
			return $content;
		}
		$post_type = get_post_type();
		if ( $post_type != $this->post_type_name ) {
			return $content;
		}
		return $content;
	}

	public function admin_enqueue_scripts( $hook ) {
		if ( ! preg_match( '/^post(-new)?.php$/', $hook ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $this->post_type_name !== $screen->post_type ) {
			return;
		}
		wp_enqueue_style( 'jquery-ui-datepicker-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css' );
		wp_enqueue_script( $this->options->get_option_name( 'admin' ) );
		wp_enqueue_style( $this->options->get_option_name( 'admin' ) );
	}

	/**
	 * register blocs & blocs patterns
	 *
	 * @since 1.0.0
	 */
	public function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Block editor is not available.
			return;
		}
		register_block_type(
			'build-a-house/expences',
			array(
				'title'           => __( 'Expences', 'build-a-house' ),
				'category'        => 'build-a-house',
				'icon'            => 'money-alt',
				'description'     => __( 'Show expences from selected period.', 'build-a-house' ),
				'keywords'        => array(
					__( 'expences', 'build-a-house' ),
					__( 'table', 'build-a-house' ),
				),
				'textdomain'      => 'build-a-house',
				'attributes'      => array(
					'kind' => array(
						'type' => 'string',
						'enum' => array( 'all', 'this-month', 'this-year', 'last-7-days' ),
					),
				),
				'render_callback' => array( $this, 'render_callback_block_expences' ),
				'style'           => $this->options->get_option_name( 'blocks-expences' ),
				'editor_script'   => $this->options->get_option_name( 'admin-block-expences' ),
			)
		);
		register_block_pattern(
			'build-a-house/expences-pattern',
			array(
				'category'    => 'build-a-house',
				'title'       => __( 'Expences with header', 'build-a-house' ),
				'description' => _x( 'Show expences with header.', 'Block pattern description', 'build-a-house' ),
				'content'     => '<!-- wp:group --><div class="wp-block-group"><div class="wp-block-group__inner-container"><!-- wp:heading --><h2></h2><!-- /wp:heading --><!-- wp:build-a-house/expences --><div data-kind="all" class="wp-block-build-a-house-expences"></div><!-- /wp:build-a-house/expences --></div></div><!-- /wp:group -->',
			)
		);
	}

	public function render_callback_block_expences( $atts ) {
		$attr = wp_parse_args(
			$atts,
			array(
				'kind'     => 'all',
				'group_by' => 'no',
			)
		);
		$args = array(
			'post_type'  => $this->post_type_name,
			'nopaging'   => true,
			'orderby'    => 'meta_value_num',
			'order'      => 'DESC',
			'meta_query' => array(
				array(
					'key'     => $this->options->get_option_name( 'details_date_start' ),
					'compare' => 'EXISTS',
				),
			),
		);
		switch ( $attr['kind'] ) {
			case 'this-month':
				$args['meta_query'] = array(
					array(
						'key'     => $this->options->get_option_name( 'details_date_start' ),
						'compare' => '>=',
						'value'   => strtotime( date( 'Y-m-01 00:00:00' ) ),
					),
				);
				break;
			case 'this-year':
				$args['meta_query'] = array(
					array(
						'key'     => $this->options->get_option_name( 'details_date_start' ),
						'compare' => '>=',
						'value'   => strtotime( date( 'Y-01-01 00:00:00' ) ),
					),
				);
				break;
			case 'last-7-days':
				$args['meta_query'] = array(
					array(
						'key'     => $this->options->get_option_name( 'details_date_start' ),
						'compare' => '>=',
						'value'   => strtotime( date( 'Y-m-d 00:00:00' ) ) - 7 * DAY_IN_SECONDS,
					),
				);
				break;
		}
		ob_start();
		$the_query = new WP_Query( $args );
		if ( $the_query->have_posts() ) {
			if ( 'contractor' === $attr['group_by'] ) {
				$this->group_by_contractor( $the_query );
			} elseif ( 'breakdown' === $attr['group_by'] ) {
				$this->group_by_breakdown( $the_query );
			} else {
				$this->load_template( 'build-a-house/block/expences', 'table-header' );
				$i           = 1;
				$sum         = 0;
				$date_format = get_option( 'date_format' );
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$data = array(
						'i'          => $i++,
						'cost'       => intval( get_post_meta( get_the_ID(), $this->options->get_option_name( 'details_cost' ), true ) ),
						'date_start' => date_i18n( $date_format, get_post_meta( get_the_ID(), $this->options->get_option_name( 'details_date_start' ), true ) ),
						'date_end'   => date_i18n( $date_format, get_post_meta( get_the_ID(), $this->options->get_option_name( 'details_date_end' ), true ) ),
					);
					$sum += $data['cost'];
					$this->load_template( 'build-a-house/block/expences', 'table-body-row', $data );
				}
				$this->load_template( 'build-a-house/block/expences', 'table-footer', array( 'sum' => $sum ) );
			}
		}
		wp_reset_postdata();
		$content = ob_get_contents();
		ob_end_clean();
		return $content;

	}

	private function group_by_breakdown( $the_query ) {
		$date_format = get_option( 'date_format' );
		$entries     = array();
		/**
		 * get breakdowns
		 */
		foreach ( get_terms( $this->taxonomy_name_breakdown ) as $term ) {
			$entries[ $term->term_id ] = array(
				'ID'       => $term->term_id,
				'title'    => $term->name,
				'term'     => $term,
				'children' => array(),
				'sum'      => 0,
			);
		}
		while ( $the_query->have_posts() ) {
			$the_query->the_post();

			$cost  = intval( get_post_meta( get_the_ID(), $this->options->get_option_name( 'details_cost' ), true ) );
			$one   = array(
				'ID'         => get_the_ID(),
				'title'      => get_the_title(),
				'cost'       => $cost,
				'date_start' => date_i18n( $date_format, get_post_meta( get_the_ID(), $this->options->get_option_name( 'details_date_start' ), true ) ),
				'date_end'   => date_i18n( $date_format, get_post_meta( get_the_ID(), $this->options->get_option_name( 'details_date_end' ), true ) ),
			);
			$terms = wp_get_post_terms( get_the_ID(), $this->taxonomy_name_breakdown );
			foreach ( $terms as $term ) {
				$entries[ $term->term_id ]['children'][] = $one;
				$entries[ $term->term_id ]['sum']       += $cost;
			}
		}
		uasort( $entries, array( $this, 'sort_by_title' ) );
		$i    = 1;
		$root = 'build-a-house/block/expences-breakdown-table';
		foreach ( $entries as $term_id => $term_data ) {
			$this->load_template( $root, 'header', $term_data );
			foreach ( $term_data['children'] as $data ) {
				$data['i'] = $i++;
				$this->load_template( $root, 'body-row', $data );
			}
			$this->load_template( $root, 'footer', $term_data );
		}
	}

	private function group_by_contractor( $the_query ) {
		$date_format = get_option( 'date_format' );
		$entries     = array();
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			$contractor = get_post_meta( get_the_ID(), $this->options->get_option_name( 'details_contractor' ), true );
			if ( empty( $contractor ) ) {
				$contractor = 'empty';
			}
			if ( ! isset( $entries[ $contractor ] ) ) {
				$entries[ $contractor ] = array(
					'ID'       => $contractor,
					'title'    => get_the_title( $contractor ),
					'children' => array(),
					'sum'      => 0,
				);
			}
			$cost                                 = intval( get_post_meta( get_the_ID(), $this->options->get_option_name( 'details_cost' ), true ) );
			$entries[ $contractor ]['children'][] = array(
				'ID'         => get_the_ID(),
				'title'      => get_the_title(),
				'cost'       => $cost,
				'date_start' => date_i18n( $date_format, get_post_meta( get_the_ID(), $this->options->get_option_name( 'details_date_start' ), true ) ),
				'date_end'   => date_i18n( $date_format, get_post_meta( get_the_ID(), $this->options->get_option_name( 'details_date_end' ), true ) ),
			);
			$entries[ $contractor ]['sum']       += $cost;
		}
		uasort( $entries, array( $this, 'sort_by_title' ) );
		$i    = 1;
		$root = 'build-a-house/block/expences-section';
		foreach ( $entries as $contractor_id => $contractor_data ) {
			$this->load_template( $root, 'table-header', $contractor_data );
			foreach ( $contractor_data['children'] as $data ) {
				$data['i'] = $i++;
				$this->load_template( $root, 'table-body-row', $data );
			}
			$this->load_template( $root, 'table-footer', $contractor_data );
		}
	}

	private function sort_by_title( $a, $b ) {
		if ( $a['title'] === $b['title'] ) {
			return 0;
		}
		return $a['title'] > $b['title'] ? 1 : -1;
	}

	private function import_breakdown( $value, $data, $parent_ID ) {
		if ( preg_match( '/^\d+$/', $value ) && is_string( $data ) ) {
			$value = $data;
		}
		$term = get_term_by( 'name', $value, $this->taxonomy_name_breakdown );
		if ( is_a( $term, 'WP_Term' ) ) {
			$parent_ID = $term->term_id;
		} else {
			$retun = wp_insert_term( $value, $this->taxonomy_name_breakdown, array( 'parent' => $parent_ID ) );
			if ( is_wp_error( $retun ) ) {
				return;
			}
			$parent_ID = $retun['term_id'];
		}
		if ( ! is_array( $data ) ) {
			return;
		}
		foreach ( $data as $key => $one ) {
			$this->import_breakdown( $key, $one, $parent_ID );
		}

	}

	public function ajax_import_breakdowns() {
		$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
		if ( ! wp_verify_nonce( $nonce, 'iworks_build_a_house_breakdowns' ) ) {
			wp_send_json_error();
		}
		if ( 'imported' === get_option( $this->import_breakdown_option_name ) ) {
			wp_send_json_error();
		}
		$file = sprintf( '%s/assets/import/breakdowns.php', $this->base );
		if ( ! is_file( $file ) ) {
			wp_send_json_error();
		}
		require_once $file;
		$data = build_a_house_import_get_breakdowns();
		foreach ( $data as $key => $one ) {
			$this->import_breakdown( $key, $one, 0 );
		}
		add_option( $this->import_breakdown_option_name, 'imported', '', 'no' );
		wp_send_json_success();
	}

	public function breakdown_maybe_allow_import( $term_id ) {
		$num = wp_count_terms(
			$this->taxonomy_name_breakdown,
			array(
				'hide_empty' => false,
				'parent'     => 0,
			)
		);
		if ( 0 < $num ) {
			return;
		}
		delete_option( $this->import_breakdown_option_name );
	}

}

