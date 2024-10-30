<?php
/*

Copyright 2017-2023 Marcin Pietrzak (marcin@iworks.pl)

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

if ( class_exists( 'iworks_build_a_house_posttypes' ) ) {
	return;
}

class iworks_build_a_house_posttypes {
	protected $post_type_name;
	protected $options;
	protected $fields;
	protected $base;
	protected $taxonomy_name_location = 'iworks_build_a_house_location';
	protected $root_url;

	/**
	 * Plugin version
	 */
	protected $version = '1.0.7.2023-01-23T06:36:45.036Z';

	/**
	 * Trophies Names
	 */
	protected $trophies_names = array();

	/**
	 * Debug
	 */

	protected $debug = false;

	public function __construct() {
		$this->options  = iworks_build_a_house_get_options_object();
		$this->base     = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );
		$this->root_url = plugins_url( basename( $this->base ) );
		$this->debug    = defined( 'WP_DEBUG' ) && WP_DEBUG;
		/**
		 * register
		 */
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 0 );
		add_action( 'init', array( $this, 'register_taxonomy_location' ), 9 );
		/**
		 * save post
		 */
		add_action( 'save_post', array( $this, 'save_post_meta' ), 10, 3 );
	}

	public function get_name() {
		return $this->post_type_name;
	}

	public function register_assets() {
		/**
		 * style
		 */
		wp_register_style(
			$this->options->get_option_name( 'admin' ),
			sprintf(
				'%s/assets/styles/admin%s.css',
				$this->root_url,
				$this->debug ? '' : '.min'
			),
			array( 'select2' ),
			$this->version
		);
	}

	protected function get_meta_box_content( $post, $fields, $group ) {
		$content  = '';
		$basename = $this->options->get_option_name( $group );
		foreach ( $fields[ $group ] as $key => $data ) {
			$args = isset( $data['args'] ) ? $data['args'] : array();
			/**
			 * ID
			 */
			$args['id'] = $this->options->get_option_name( $group . '_' . $key );
			/**
			 * name
			 */
			$name = sprintf( '%s[%s]', $basename, $key );
			/**
			 * sanitize type
			 */
			$type = isset( $data['type'] ) ? $data['type'] : 'text';
			/**
			 * get value
			 */
			$value = get_post_meta( $post->ID, $args['id'], true );
			/**
			 * Handle select2
			 */
			if ( ! empty( $value ) && 'select2' == $type ) {
				$value = array(
					'value' => $value,
					'label' => get_the_title( $value ),
				);
			}
			/**
			 * Handle date
			 */
			if ( ! empty( $value ) && 'date' == $type ) {
				$value = date_i18n( 'Y-m-d', $value );
			}
			/**
			 * build
			 */
			$content .= sprintf( '<div class="iworks-build_a_house-row iworks-build_a_house-row-%s">', esc_attr( $key ) );
			if ( isset( $data['label'] ) && ! empty( $data['label'] ) ) {
				$content .= sprintf( '<label for=%s">%s</label>', esc_attr( $args['id'] ), esc_html( $data['label'] ) );
			}
			$content .= $this->options->get_field_by_type( $type, $name, $value, $args );
			if ( isset( $data['description'] ) ) {
				$content .= sprintf( '<p class="description">%s</p>', $data['description'] );
			}
			$content .= '</div>';
		}
		echo $content;
	}

	/**
	 * Save post metadata when a post is saved.
	 *
	 * @param int $post_id The post ID.
	 * @param post $post The post object.
	 * @param bool $update Whether this is an existing post being updated or not.
	 */
	public function save_post_meta_fields( $post_id, $post, $update, $fields ) {
		/*
		 * In production code, $slug should be set only once in the plugin,
		 * preferably as a class property, rather than in each function that needs it.
		 */
		$post_type = get_post_type( $post_id );
		// If this isn't a Copyricorrect post, don't update it.
		if ( $this->post_type_name != $post_type ) {
			return false;
		}
		foreach ( $fields as $group => $group_data ) {
			$post_key = $this->options->get_option_name( $group );
			if ( isset( $_POST[ $post_key ] ) ) {
				foreach ( $group_data as $key => $data ) {
					$value = isset( $_POST[ $post_key ][ $key ] ) ? $_POST[ $post_key ][ $key ] : null;
					if ( is_string( $value ) ) {
						$value = trim( $value );
					} elseif ( is_array( $value ) ) {
						if (
							isset( $value['integer'] ) && 0 == $value['integer']
							&& isset( $value['fractional'] ) && 0 == $value['fractional']
						) {
							$value = null;
						}
					}
					$option_name = $this->options->get_option_name( $group . '_' . $key );
					if ( empty( $value ) ) {
						delete_post_meta( $post->ID, $option_name );
					} else {
						if ( isset( $data['type'] ) && 'date' == $data['type'] ) {
							$value = strtotime( $value );
						}
						/**
						 * filter
						 */
						$value  = apply_filters( 'iworks_build_a_house_meta_value', $value, $post->ID, $option_name );
						$result = add_post_meta( $post->ID, $option_name, $value, true );
						if ( ! $result ) {
							update_post_meta( $post->ID, $option_name, $value );
						}
						do_action( 'iworks_build_a_house_posttype_update_post_meta', $post->ID, $option_name, $value, $key, $data );
					}
				}
			}
		}
		return true;
	}

	/**
	 * Check post type
	 *
	 * @since 1.0.0
	 *
	 * @param integer $post_ID Post ID to check.
	 * @returns boolean is correct post type or not
	 */
	public function check_post_type_by_id( $post_ID ) {
		$post = get_post( $post_ID );
		if ( empty( $post ) ) {
			return false;
		}
		if ( $this->post_type_name == $post->post_type ) {
			return true;
		}
		return false;
	}

	/**
	 * Return counter of published posts by post type.
	 *
	 * @since 1.0.0
	 */
	public function count() {
		if ( empty( $this->post_type_name ) ) {
			return 0;
		}
		$counter = wp_count_posts( $this->post_type_name );
		if ( ! is_object( $counter ) ) {
			return 0;
		}
		if ( isset( $counter->publish ) ) {
			return $counter->publish;
		}
		return 0;
	}

	/**
	 * add where order to prev/next post links
	 *
	 * @since 1.0.0
	 */
	public function adjacent_post_where( $sql, $in_same_term, $excluded_terms, $taxonomy, $post ) {
		if ( $post->post_type === $this->post_type_name ) {
			global $wpdb;
			$post_title = $wpdb->_real_escape( $post->post_title );
			$sql        = preg_replace( '/p.post_date ([<> ]+) \'[^\']+\'/', "p.post_title $1 '{$post_title}'", $sql );
		}
		return $sql;
	}

	/**
	 * add sort order to prev/next post links
	 *
	 * @since 1.0.0
	 */
	public function adjacent_post_sort( $sql, $post, $order ) {
		if ( $post->post_type === $this->post_type_name ) {
			$sql = sprintf( 'ORDER BY p.post_title %s LIMIT 1', $order );
		}
		return $sql;
	}

	public function register_taxonomy_location() {
		/**
		 * Locations  Taxonomy.
		 */
		$labels = array(
			'name'                       => _x( 'Locations', 'Taxonomy General Name', 'build-a-house' ),
			'singular_name'              => _x( 'Locations', 'Taxonomy Singular Name', 'build-a-house' ),
			'menu_name'                  => __( 'Locations', 'build-a-house' ),
			'all_items'                  => __( 'All Locations', 'build-a-house' ),
			'new_item_name'              => __( 'New Locations Name', 'build-a-house' ),
			'add_new_item'               => __( 'Add New Locations', 'build-a-house' ),
			'edit_item'                  => __( 'Edit Locations', 'build-a-house' ),
			'update_item'                => __( 'Update Locations', 'build-a-house' ),
			'view_item'                  => __( 'View Locations', 'build-a-house' ),
			'separate_items_with_commas' => __( 'Separate Locations with commas', 'build-a-house' ),
			'add_or_remove_items'        => __( 'Add or remove Locations', 'build-a-house' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'build-a-house' ),
			'popular_items'              => __( 'Popular Locations', 'build-a-house' ),
			'search_items'               => __( 'Search Locations', 'build-a-house' ),
			'not_found'                  => __( 'Not Found', 'build-a-house' ),
			'no_terms'                   => __( 'No items', 'build-a-house' ),
			'items_list'                 => __( 'Locations list', 'build-a-house' ),
			'items_list_navigation'      => __( 'Locations list navigation', 'build-a-house' ),
		);
		$args   = array(
			'labels'             => $labels,
			'hierarchical'       => true,
			'public'             => true,
			'show_admin_column'  => true,
			'show_in_nav_menus'  => true,
			'show_tagcloud'      => true,
			'show_ui'            => true,
			'show_in_quick_edit' => true,
			'rewrite'            => array(
				'slug' => 'build_a_house-locations',
			),
		);
		register_taxonomy( $this->taxonomy_name_location, array( $this->post_type_name ), $args );
	}

	protected function get_cache_key( $data, $prefix = '' ) {
		$key = sprintf(
			'ibm-%s-%s',
			$prefix,
			md5( serialize( $data ) )
		);
		$key = substr( $key, 0, 172 );
		return $key;
	}

	protected function get_cache( $key ) {
		if ( $this->debug ) {
			return false;
		}
		$cache = get_transient( $key );
		return $cache;
	}

	protected function set_cache( $data, $key, $expiration = false ) {
		if ( empty( $expiration ) ) {
			$expiration = DAY_IN_SECONDS;
		}
		set_transient( $key, $data, $expiration );
	}

	/**
	 * TRIM
	 */
	protected function data_trim( $data ) {
		$data = preg_replace( '/ /', ' ', $data );
		$data = preg_replace( '/[ \t\r\n]+/', ' ', $data );
		return trim( $data );
	}
	protected function load_template( $slug, $name = null, $args = array() ) {
		$template = get_template_part( $slug, $name );
		if ( false === $template ) {
			$file = sprintf(
				'%s/assets/template-parts/%s%s%s.php',
				$this->base,
				preg_replace( '/build-a-house\//', '', $slug ),
				empty( $name ) ? '' : '-',
				empty( $name ) ? '' : $name
			);
			load_template( $file, false, $args );
		}
	}
}

