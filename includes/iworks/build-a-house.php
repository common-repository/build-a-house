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

if ( class_exists( 'iworks_build_a_house' ) ) {
	return;
}

require_once dirname( dirname( __FILE__ ) ) . '/iworks.php';

class iworks_build_a_house extends iworks {

	private $capability;
	private $post_type_expence;
	protected $options;

	public function __construct() {
		parent::__construct();
		$this->options    = iworks_build_a_house_get_options_object();
		$this->base       = dirname( dirname( __FILE__ ) );
		$this->dir        = basename( dirname( $this->base ) );
		$this->version    = '1.0.7';
		$this->capability = apply_filters( 'iworks_build_a_house_capability', 'manage_options' );
		/**
		 * post_types
		 */
		$post_types = array( 'expence', 'contractor', 'event' );
		foreach ( $post_types as $post_type ) {
			include_once $this->base . '/iworks/build-a-house/posttypes/class-iworks-build-a-house-' . $post_type . '.php';
			$class        = sprintf( 'iworks_build_a_house_posttypes_%s', $post_type );
			$value        = sprintf( 'post_type_%s', $post_type );
			$this->$value = new $class();
		}
		/**
		 * admin init
		 */
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'init', array( $this, 'db_install' ) );
		add_action( 'init', array( $this, 'register_scripts' ), 0 );
		add_action( 'init', array( $this, 'register_block_pattern_category' ), 0 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 0 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		/**
		 * iWorks Rate integration - change logo for rate
		 *
		 * @since 1.0.4
		 */
		add_filter( 'iworks_rate_notice_logo_style', array( $this, 'filter_plugin_logo' ), 10, 2 );
	}


	public function admin_init() {
		iworks_build_a_house_options_init();
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_register' ), 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	public function get_post_type_name( $post_type ) {
		$value = sprintf( 'post_type_%s', $post_type );
		if ( isset( $this->$value ) ) {
			return $this->$value->get_name();
		}
		return new WP_Error( 'broke', __( 'Build a House do not have such post type!', 'build-a-house' ) );
	}

	public function admin_register() {
		/**
		 * datepicker
		 */
		$file = 'assets/externals/datepicker/css/jquery-ui-datepicker.css';
		$file = plugins_url( $file, $this->base );
		wp_register_style( 'jquery-ui-datepicker', $file, false, '1.12.1' );
		/**
		 * select2
		 */
		$file = 'assets/externals/select2/css/select2.min.css';
		$file = plugins_url( $file, $this->base );
		wp_register_style( 'select2', $file, false, '4.0.3' );
		/**
		 * Admin styles
		 */
		$file    = sprintf( '/assets/styles/admin%s.css', $this->dev );
		$version = $this->get_version( $file );
		$file    = plugins_url( $file, $this->base );
		wp_register_style( $this->options->get_option_name( 'admin' ), $file, array( 'jquery-ui-datepicker', 'select2' ), $version );
		/**
		 * Block: build-a-house-expences
		 */
		$handler = $this->options->get_option_name( 'admin-block-expences' );
		wp_register_script(
			$handler,
			plugins_url( 'assets/blocks/expences.js', $this->base ),
			array( 'wp-editor', 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor' ),
			$version
		);
		wp_set_script_translations(
			$handler,
			'build-a-house',
			dirname( $this->base ) . '/languages/'
		);
	}

	public function admin_enqueue() {
		$screen = get_current_screen();
		/**
		 * off on not build_a_house pages
		 */
		if ( ! preg_match( '/ibh/', $screen->id ) ) {
			return;
		}
		$handler = $this->options->get_option_name( 'admin' );
		wp_enqueue_script( $handler );
		wp_localize_script(
			$handler,
			'build-a-house',
			array(
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'messages' => array(
					'import' => __( 'Are you sure to import breakdowns?', 'build-a-house' ),
				),
			)
		);
		wp_enqueue_style( $handler );
	}

	public function register_scripts() {
		/**
		 * select2
		 */
		wp_register_script( 'select2', plugins_url( 'assets/externals/select2/js/select2.full.min.js', $this->base ), array(), '4.0.3' );
		/**
		 * Admin scripts
		 */
		$files = array(
			$this->options->get_option_name( 'admin' ) => sprintf( 'assets/scripts/admin%s.js', $this->dev ),
		);
		if ( 0 && '' == $this->dev ) {
			$files = array(
				'build_a_house-admin-select2'    => 'assets/scripts/admin/src/select2.js',
				'build_a_house-admin-datepicker' => 'assets/scripts/admin/src/datepicker.js',
				'build_a_house-admin-expence'    => 'assets/scripts/admin/src/expence.js',
				'build_a_house-admin'            => 'assets/scripts/admin/src/build_a_house.js',
			);
		}
		$deps = array(
			'jquery-ui-datepicker',
			'select2',
			'wp-block-editor',
			'wp-blocks',
			'wp-element',
			'wp-i18n',
		);
		foreach ( $files as $handle => $file ) {
			wp_register_script(
				$handle,
				plugins_url( $file, $this->base ),
				$deps,
				$this->get_version(),
				true
			);
		}
		/**
		 * JavaScript messages
		 *
		 * @since 1.0.0
		 */
		$data = array(
			'messages' => array(),
			'nonces'   => array(),
			'user_id'  => get_current_user_id(),
		);
		wp_localize_script(
			$this->options->get_option_name( 'admin' ),
			__CLASS__,
			apply_filters( 'wp_localize_script_build_a_house_admin', $data )
		);
		/**
		 * blocks: expences
		 */
		$file    = sprintf( 'assets/styles/frontend/blocks/expences%s.css', $this->dev );
		$version = $this->get_version( $file );
		$file    = plugins_url( $file, $this->base );
		wp_register_style( $this->options->get_option_name( 'blocks-expences' ), $file, array(), $version );
	}

	public function init() {
	}

	/**
	 * Plugin row data
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $this->dir . '/build_a_house.php' == $file ) {
			if ( ! is_multisite() && current_user_can( $this->capability ) ) {
				$links[] = '<a href="themes.php?page=' . $this->dir . '/admin/index.php">' . __( 'Settings' ) . '</a>';
			}
			$links[] = '<a href="http://iworks.pl/donate/build_a_house.php">' . __( 'Donate' ) . '</a>';
		}
		return $links;
	}

	public function get_list_by_post_type( $type ) {
		$args  = array(
			'post_type' => $this->{'post_type_' . $type}->get_name(),
			'nopaging'  => true,
		);
		$list  = array();
		$posts = get_posts( $args );
		foreach ( $posts as $post ) {
			$list[ $post->post_title ] = $post->ID;
		}
		return $list;
	}

	public function db_install() {
		global $wpdb;
		$version = intval( get_option( 'build_a_house_db_version' ) );
	}

	/**
	 * register styles
	 *
	 * @since 1.0.0
	 */
	public function register_assets() {
		wp_register_style(
			$this->options->get_option_name( 'frontend' ),
			sprintf( plugins_url( '/assets/styles/frontend%s.css', $this->base ), $this->dev ? '' : '.min' ),
			array(),
			$this->version
		);
		/**
		 * select2
		 */
		wp_register_script( 'select2', plugins_url( 'assets/externals/select2/js/select2.full.min.js', $this->base ), array(), '4.0.3' );
		/**
		 *
		 */
		/**
		 * Admin scripts
		 */
		$files = array(
			$this->options->get_option_name( 'admin' ) => sprintf( 'assets/scripts/admin%s.js', $this->dev ),
		);
		if ( 0 && '' == $this->dev ) {
			$files = array(
				'build-a-house-admin-datepicker' => 'assets/scripts/admin/src/datepicker.js',
				'build-a-house-admin-invoice'    => 'assets/scripts/admin/src/invoice.js',
				'build-a-house-admin-jpk'        => 'assets/scripts/admin/src/jpk.js',
				'build-a-house-admin-select2'    => 'assets/scripts/admin/src/select2.js',
			);
		}
		$deps = array(
			'jquery-ui-datepicker',
			'select2',
		);
		foreach ( $files as $handle => $file ) {
			wp_register_script(
				$handle,
				plugins_url( $file, $this->base ),
				$deps,
				$this->get_version(),
				true
			);
		}
	}

	/**
	 * Enquque styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		if ( $this->options->get_option( 'load_frontend_css' ) ) {
			wp_enqueue_style( $this->options->get_option_name( 'frontend' ) );
		}
	}

	/**
	 * Register block pattern category
	 *
	 * @since 1.0.3
	 */
	public function register_block_pattern_category() {
		register_block_pattern_category(
			'build-a-house',
			array( 'label' => __( 'Build a House', 'build-a-house' ) )
		);
	}

	/**
	 * Plugin logo for rate messages
	 *
	 * @since 1.0.4
	 *
	 * @param string $logo Logo, can be empty.
	 * @param object $plugin Plugin basic data.
	 */
	public function filter_plugin_logo( $logo, $plugin ) {
		if ( is_object( $plugin ) ) {
			$plugin = (array) $plugin;
		}
		if ( 'build-a-house' === $plugin['slug'] ) {
			return plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . '/assets/images/logo.svg';
		}
		return $logo;
	}

}
