<?php
function iworks_build_a_house_options() {
	$iworks_build_a_house_options = array();
	/**
	 * main settings
	 */
	$parent                                = add_query_arg( 'post_type', 'ibh_expence', 'edit.php' );
	$iworks_build_a_house_options['index'] = array(
		'version'    => '0.0',
		'use_tabs'   => true,
		'page_title' => __( 'Configuration', 'build-a-house' ),
		'menu'       => 'submenu',
		'parent'     => $parent,
		'options'    => array(
			array(
				'type'  => 'heading',
				'label' => __( 'Construction Site', 'build-a-house' ),
			),
			array(
				'name' => 'street',
				'th'   => __( 'Street', 'build-a-house' ),
			),
			array(
				'name' => 'zip',
				'th'   => __( 'Zip code', 'build-a-house' ),
			),
			array(
				'name' => 'city',
				'th'   => __( 'City', 'build-a-house' ),
			),
		),
	);
	/**
	 * add import once
	 */
	if ( 'imported' !== get_option( 'iworks_build_a_house_breakdowns_import' ) ) {
		$iworks_build_a_house_options['index']['options'][] = array(
			'type'  => 'heading',
			'label' => __( 'Import', 'build-a-house' ),
		);

		$iworks_build_a_house_options['index']['options'][] = array(
			'th'    => __( 'Import Breakdowns', 'build-a-house' ),
			'name'  => 'breakdowns',
			'type'  => 'button',
			'value' => __( 'Import Breakdowns', 'build-a-house' ),
		);
	}
	return $iworks_build_a_house_options;
}

