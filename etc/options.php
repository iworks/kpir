<?php

function iworks_kpir_options() {

	$iworks_kpir_options = array();

	/**
	 * main settings
	 */
	$iworks_kpir_options['index'] = array(
		'use_tabs' => true,
		'version'  => '0.0',
		'page_title' => __( 'kpir Pro! configuration', 'kpir' ),
		'menu_title' => __( 'kpir Pro!', 'kpir' ),
		'menu' => 'kpir',
		'enqueue_scripts' => array(
			'kpir-admin-js',
		),
		'enqueue_styles' => array(
			'kpir-admin',
			'kpir',
		),
		'options'  => array(
			array(
				'name'              => 'last_used_tab',
				'type'              => 'hidden',
				'dynamic'           => true,
				'autoload'          => false,
				'default'           => 0,
			),
			array(
				'name'              => 'configuration',
				'type'              => 'special',
				'default'           => 'simple',
				'sanitize_callback' => 'iworks_kpir_sanitize_callback_configuration',
			),
			/**
			 * Appearance: simple
			 */
			array(
				'type'              => 'heading',
				'label'             => __( 'Appearance', 'kpir' ),
				'configuration'     => 'simple',
			),
			array(
				'name'              => 'layout',
				'type'              => 'serialize',
				'th'                => __( 'Layout', 'kpir' ),
				'default'           => 'simple',
				'callback'          => 'iworks_kpir_callback_layout',
			),
			/**
			 * Position: simple
			 */
			array(
				'type'              => 'heading',
				'label'             => __( 'Position', 'kpir' ),
				'configuration'     => 'simple',
			),
			array(
				'name'              => 'position',
				'type'              => 'radio',
				'th'                => __( 'Position', 'kpir' ),
				'default'           => 'right',
				'radio'             => array(
					'right'         => array( 'label' => __( 'bottom right', 'kpir' ) ),
					'left'          => array( 'label' => __( 'bottom left',  'kpir' ) ),
					'bottom'        => array( 'label' => __( 'bottom',       'kpir' ), 'need_pro' => true ),
					'right-top'     => array( 'label' => __( 'top right',    'kpir' ), 'need_pro' => true ),
					'top'           => array( 'label' => __( 'top',          'kpir' ), 'need_pro' => true ),
					'left-top'      => array( 'label' => __( 'top left',     'kpir' ), 'need_pro' => true ),
					'right-middle'  => array( 'label' => __( 'middle right', 'kpir' ), 'need_pro' => true ),
					'left-middle'   => array( 'label' => __( 'middle left',  'kpir' ), 'need_pro' => true ),
				),
				'configuration'     => 'both',
				'sanitize_callback' => 'esc_html',
			),
			/**
			 * Appearance: advance
			 */
			array(
				'type'              => 'heading',
				'label'             => __( 'Appearance', 'kpir' ),
				'configuration'     => 'advance',
			),
			array(
				'name'              => 'animation',
				'type'              => 'radio',
				'th'                => __( 'Animation style', 'kpir' ),
				'default'           => 'flyout',
				'radio'             => array(
					'flyout' => array( 'label' => __( 'flyout', 'kpir' ) ),
					'fade'   => array( 'label' => __( 'fade in/out', 'kpir' ) ),
				),
				'sanitize_callback' => 'esc_html',
			),
			array(
				'name'              => 'position',
				'type'              => 'radio',
				'th'                => __( 'Position', 'kpir' ),
				'default'           => 'right',
				'radio'             => array(
					'right'         => array( 'label' => __( 'bottom right', 'kpir' ) ),
					'left'          => array( 'label' => __( 'bottom left',  'kpir' ) ),
					'bottom'        => array( 'label' => __( 'bottom',       'kpir' ), 'need_pro' => true ),
					'right-top'     => array( 'label' => __( 'top right',    'kpir' ), 'need_pro' => true ),
					'top'           => array( 'label' => __( 'top',          'kpir' ), 'need_pro' => true ),
					'left-top'      => array( 'label' => __( 'top left',     'kpir' ), 'need_pro' => true ),
					'right-middle'  => array( 'label' => __( 'middle right', 'kpir' ), 'need_pro' => true ),
					'left-middle'   => array( 'label' => __( 'middle left',  'kpir' ), 'need_pro' => true ),
				),
				'configuration'     => 'both',
				'sanitize_callback' => 'esc_html',
			),
			array(
				'name'              => 'css_bottom',
				'type'              => 'number',
				'class'             => 'small-text',
				'th'                => __( 'Margin bottom', 'kpir' ),
				'label'             => __( 'px', 'kpir' ),
				'default'           => 5,
				'sanitize_callback' => 'absint',
			),
			array(
				'name'              => 'css_side',
				'type'              => 'number',
				'class'             => 'small-text',
				'th'                => __( 'Margin side', 'kpir' ),
				'label'             => __( 'px', 'kpir' ),
				'description'       => __( 'Left or right depending on position.', 'kpir' ),
				'default'           => 5,
				'sanitize_callback' => 'absint',
			),
			array(
				'name'              => 'css_width',
				'type'              => 'number',
				'class'             => 'small-text',
				'th'                => __( 'Box width', 'kpir' ),
				'label'             => __( 'px', 'kpir' ),
				'default'           => 360,
				'sanitize_callback' => 'absint',
			),
			array(
				'name'              => 'offset_percent',
				'type'              => 'number',
				'class'             => 'small-text',
				'th'                => __( 'Offset', 'kpir' ),
				'label'             => __( '%', 'kpir' ),
				'description'       => __( 'Percentage of the page required to be scrolled to display a box.', 'kpir' ),
				'default'           => 75,
				'sanitize_callback' => 'iworks_kpir_sanitize_callback_offset_percent',
			),
			array(
				'name'              => 'offset_element',
				'type'              => 'text',
				'class'             => 'regular-text',
				'label'             => __( 'Before HTML element.', 'kpir' ),
				'description'       => __( 'If empty, all page length is taken for calculation. If not empty, make sure to use the ID or class of an existing element. Put # "hash" before the ID, or . "dot" before a class name.', 'kpir' ),
				'default'           => '#comments',
				'sanitize_callback' => 'esc_html',
			),
			array(
				'name'              => 'header_show',
				'type'              => 'checkbox',
				'th'                => __( 'Box header', 'kpir' ),
				'label'             => __( 'Show box header.', 'kpir' ),
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			array(
				'name'              => 'header_text',
				'type'              => 'text',
				'class'             => 'regular-text',
				'label'             => __( 'Header text.', 'kpir' ),
				'description'       => __( 'Leave blank to allow plugin set the heading text.', 'kpir' ),
				'default'           => false,
				'sanitize_callback' => 'esc_html',
			),
			array(
				'name'              => 'close_button_show',
				'type'              => 'checkbox',
				'th'                => __( 'Close button', 'kpir' ),
				'label'             => __( 'Show close button.', 'kpir' ),
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			/**
			 * Colors: both
			 */
			array(
				'type'              => 'heading',
				'label'             => __( 'Colors', 'kpir' ),
				'configuration'     => 'both',
			),
			array(
				'name'              => 'color_set',
				'type'              => 'checkbox',
				'th'                => __( 'Set custom colors', 'kpir' ),
				'label'             => __( 'Turn on custom colors.', 'kpir' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'need_pro'          => true,
			),
			array(
				'name'              => 'color',
				'type'              => 'wpColorPicker',
				'class'             => 'short-text',
				'th'                => __( 'Text', 'kpir' ),
				'default'           => '#000',
				'sanitize_callback' => 'esc_html',
				'use_name_as_id'    => true,
				'need_pro'          => true,
			),
			array(
				'name'              => 'color_background',
				'type'              => 'wpColorPicker',
				'class'             => 'short-text',
				'th'                => __( 'Background', 'kpir' ),
				'default'           => '#fff',
				'sanitize_callback' => 'esc_html',
				'use_name_as_id'    => true,
				'need_pro'          => true,
			),
			array(
				'name'              => 'color_link',
				'type'              => 'wpColorPicker',
				'class'             => 'short-text',
				'th'                => __( 'Links', 'kpir' ),
				'sanitize_callback' => 'esc_html',
				'default'           => '#000',
				'use_name_as_id'    => true,
				'need_pro'          => true,
			),
			array(
				'name'              => 'color_border',
				'type'              => 'wpColorPicker',
				'class'             => 'short-text',
				'th'                => __( 'Border', 'kpir' ),
				'sanitize_callback' => 'esc_html',
				'default'           => '#000',
				'use_name_as_id'    => true,
				'need_pro'          => true,
			),
			/**
			 * Content: advance
			 */
			array(
				'type'              => 'heading',
				'label'             => __( 'Content', 'kpir' ),
				'configuration'     => 'advance',
			),
			array(
				'name'              => 'number_of_posts',
				'type'              => 'number',
				'class'             => 'small-text',
				'th'                => __( 'Number of posts to show', 'kpir' ),
				'description'       => __( 'Not affected if using YARPP as choose method.', 'kpir' ),
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			array(
				'name'              => 'remove_all_filters',
				'type'              => 'checkbox',
				'th'                => __( 'Content filters', 'kpir' ),
				'label'             => __( 'Remove all filters.', 'kpir' ),
				'description'       => __( 'Untick this if you have some strange things in kpir box, but unticked have a lot of chances breaks your layout.' , 'kpir' ),
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			array(
				'name'              => 'compare',
				'type'              => 'radio',
				'th'                => __( 'Previous entry choose method', 'kpir' ),
				'default'           => 'simple',
				'radio'             => array(
					'simple'   => array( 'label' => __( 'Just previous.',        'kpir' ) ),
					'category' => array( 'label' => __( 'Previous in category.', 'kpir' ) ),
					'tag'      => array( 'label' => __( 'Previous in tag.',      'kpir' ) ),
					'random'   => array( 'label' => __( 'Random entry.',         'kpir' ) ),
				),
				'sanitize_callback' => 'esc_html',
				'extra_options'    => 'iworks_kpir_get_compare_option',
			),
			array(
				'name'              => 'taxonomy_limit',
				'type'              => 'number',
				'class'             => 'small-text',
				'th'                => __( 'Taxonomy limit', 'kpir' ),
				'label'             => __( 'Number of taxonomies (tags or categories) to show.', 'kpir' ),
				'description'       => __( 'Default value: 0 (no limit).', 'kpir' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			array(
				'name'              => 'match_post_type',
				'type'              => 'checkbox',
				'th'                => __( 'Match post type', 'kpir' ),
				'label'             => __( 'Display only for selected post types.', 'kpir' ),
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			array(
				'name'              => 'post_type',
				'type'              => 'checkbox_group',
				'th'                => __( 'Select post types', 'kpir' ),
				'label'             => __( 'Show posts.', 'kpir' ),
				'description'       => __( 'If not any, then default value is "post".', 'kpir' ),
				'default'           => array( 'post' => 'post' ),
				'options'           => array(
					'post' => __( 'Posts.',                                'kpir' ),
					'page' => __( 'Pages.',                                'kpir' ),
					'any'  => __( 'Any post type (include custom post types).', 'kpir' ),
				),
				'extra_options'    => 'iworks_kpir_get_post_types',
			),
			/**
			 * ignore sticky posts to avoid two post loop
			 */
			array(
				'name'              => 'ignore_sticky_posts',
				'type'              => 'checkbox',
				'th'                => __( 'Sticky posts', 'kpir' ),
				'label'             => __( 'Ignore sticky posts.', 'kpir' ),
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			/**
			 * excerpt
			 */
			array(
				'name'              => 'excerpt_show',
				'type'              => 'checkbox',
				'th'                => __( 'Excerpt', 'kpir' ),
				'label'             => __( 'Show excerpt.', 'kpir' ),
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			array(
				'name'              => 'excerpt_length',
				'type'              => 'number',
				'class'             => 'small-text',
				'default'           => 20,
				'label'             => __( 'Number of words to show.', 'kpir' ),
				'sanitize_callback' => 'absint',
			),
			/**
			 * Featured image
			 */
			array(
				'name'              => 'show_thumb',
				'type'              => 'checkbox',
				'th'                => __( 'Featured image', 'kpir' ),
				'label'             => __( 'Show featured image.', 'kpir' ),
				'sanitize_callback' => 'absint',
				'default'           => 1,
				'check_supports'    => array( 'post-thumbnails' ),
			),
			array(
				'name'              => 'thumb_width',
				'type'              => 'number',
				'class'             => 'small-text',
				'label'             => __( 'Featured image width.', 'kpir' ),
				'default'           => 96,
				'sanitize_callback' => 'absint',
				'check_supports'    => array( 'post-thumbnails' ),
			),
			/**
			 * Links: advance
			 */
			array(
				'type'              => 'heading',
				'label'             => __( 'Links', 'kpir' ),
				'configuration'     => 'advance',
			),
			array(
				'name'              => 'url_prefix',
				'type'              => 'text',
				'th'                => __( 'URL prefix', 'kpir' ),
				'class'             => 'regular-text',
				'description'       => __( 'Will be added before link.', 'kpir' ),
				'default'           => '',
				'sanitize_callback' => 'esc_html',
			),
			array(
				'name'              => 'url_suffix',
				'type'              => 'text',
				'th'                => __( 'URL suffix', 'kpir' ),
				'class'             => 'regular-text',
				'description'       => __( 'Will be added after link.', 'kpir' ),
				'default'           => '',
				'sanitize_callback' => 'esc_html',
			),
			array(
				'name'              => 'url_new_window',
				'type'              => 'checkbox',
				'th'                => __( 'Open link', 'kpir' ),
				'label'             => __( 'Open link in new window.', 'kpir' ),
				'description'       => __( 'Not recommended!', 'kpir' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			array(
				'name'              => 'ga_status',
				'type'              => 'checkbox',
				'th'                => __( 'Google Analytics', 'kpir' ),
				'label'             => __( 'I don\'t have GA tracking on site.', 'kpir' ),
				'description'       => __( 'Turn it on if you don\'t use any other GA tracking plugin.', 'kpir' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			array(
				'name'              => 'ga_account',
				'type'              => 'text',
				'label'             => __( 'Google Analytics Account', 'kpir' ),
				'description'       => __( 'Replace UA-XXXXX-X with your web property ID.', 'kpir' ),
				'class'             => 'regular-text',
				'default'           => 'UA-XXXXX-X',
				'sanitize_callback' => 'iworks_kpir_sanitize_callback_ga_account',
				'related_to'        => 'ga_status',
			),
			array(
				'name'              => 'ga_track_views',
				'type'              => 'checkbox',
				'label'             => __( 'Track views', 'kpir' ),
				'description'       => __( 'Track showing of kpir box.', 'kpir' ),
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			array(
				'name'              => 'ga_track_clicks',
				'type'              => 'checkbox',
				'label'             => __( 'Track clicks', 'kpir' ),
				'description'       => __( 'Turn it on if you don\'t use any other GA tracking plugin.', 'kpir' ),
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			array(
				'name'              => 'ga_opt_noninteraction',
				'type'              => 'checkbox',
				'label'             => __( 'Prevent bounce-rate', 'kpir' ),
				'description'       => __( 'Turn it on to indicate that the event hit will not be used in bounce-rate calculation.', 'kpir' ),
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			/**
			 * Advance: css, mobile devices
			 */
			array(
				'type'              => 'heading',
				'label'             => __( 'Other', 'kpir' ),
				'configuration'     => 'advance',
			),
			/**
			 * Advance: mobile devices
			 */
			array(
				'type'              => 'subheading',
				'label'             => __( 'Mobile devices', 'kpir' ),
			),
			array(
				'name'              => 'mobile_hide',
				'type'              => 'checkbox',
				'th'                => __( 'Mobile devices', 'kpir' ),
				'label'             => __( 'Hide for mobile devices.', 'kpir' ),
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			array(
				'name'              => 'mobile_tablets',
				'type'              => 'checkbox',
				'th'                => __( 'Tablets', 'kpir' ),
				'label'             => __( 'Hide for tablets too.', 'kpir' ),
				'description'       => __( 'Works only when hiding for mobile devices is turn on.', 'kpir' ),
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			/**
			 * Advance: css
			 */
			array(
				'type'              => 'subheading',
				'label'             => __( 'Custom CSS', 'kpir' ),
			),
			array(
				'name'              => 'css',
				'type'              => 'textarea',
				'class'             => 'large-text code',
				'th'                => __( 'Custom CSS', 'kpir' ),
				'sanitize_callback' => 'esc_html',
				'rows'              => 10,
			),
			/**
			 * Excludes
			 */
			array(
				'type'              => 'heading',
				'label'             => __( 'Excludes', 'kpir' ),
				'configuration'     => 'both',
			),
			array(
				'name'              => 'exclude_categories',
				'type'              => 'serialize',
				'th'                => __( 'Categories', 'kpir' ),
				'sanitize_callback' => 'iworks_kpir_exclude_categories_sanitize_callback',
				'callback'          => 'iworks_kpir_exclude_categories',
			),
			array(
				'name'              => 'exclude_tags',
				'type'              => 'serialize',
				'th'                => __( 'Tags', 'kpir' ),
				'sanitize_callback' => 'iworks_kpir_exclude_tags_sanitize_callback',
				'callback'          => 'iworks_kpir_exclude_tags',
			),
		),
		'metaboxes' => array(
			'configuration_mode' => array(
				'title' => __( 'Choose configuration mode', 'kpir' ),
				'callback' => 'iworks_kpir_options_choose_configuration_mode',
				'context' => 'side',
				'priority' => 'core',
			),
			'need_assistance' => array(
				'title' => __( 'Need Assistance?', 'kpir' ),
				'callback' => 'iworks_kpir_options_need_assistance',
				'context' => 'side',
				'priority' => 'core',
			),
		),
	);
	return $iworks_kpir_options;
}

function iworks_kpir_get_post_types() {

	$data = array();
	$post_types = get_post_types( null, 'objects' );
	foreach ( $post_types as $post_type_key => $post_type ) {
		if ( preg_match( '/^(post|page|attachment|revision|nav_menu_item)$/', $post_type_key ) ) {
			continue;
		}
		$data[ $post_type_key ]  = __( 'Custom post type: ', 'kpir' );
		$data[ $post_type_key ] .= isset( $post_type->labels->name )? $post_type->labels->name:$post_type_key;
		$data[ $post_type_key ] .= '.';
	}
	return $data;
}

function iworks_kpir_get_compare_option() {

	$data = array();
	if ( is_plugin_active( plugin_basename( 'yet-another-related-posts-plugin/yarpp.php' ) ) ) {
		$data['yarpp']['label'] = __( 'Related Posts (YARPP)', 'yarpp' );
		$data['yarpp']['label'] .= __( '. Works only with post and/or pages.', 'kpir' );
	} else {
		$data['yarpp-disabled']['label'] = __( 'Related Posts (YARPP)', 'kpir' );
	}
	return $data;
}

/**
 * sanitize offset value
 */
function iworks_kpir_sanitize_callback_offset_percent( $value = null ) {

	if ( is_null( $value ) ) {
		return 100;
	}
	if ( ! is_numeric( $value ) || $value < 0 || $value > 100 ) {
		return 75;
	}
	return $value;
}

/**
 * sanitize GA account
 */
function iworks_kpir_sanitize_callback_ga_account( $value = 'UA-XXXXX-X' ) {

	if ( preg_match( '/^UA\-\d{5}\-\d$/i', $value ) ) {
		return strtoupper( $value );
	}
	return null;
}

/**
 * buy pro page
 */
function iworks_kpir_buy_pro() {

	global $iworks_kpir;
	return $iworks_kpir->buy_pro_page();
}

/**
 * exclude_categories
 */
function iworks_kpir_exclude_categories( $values = array() ) {

	global $iworks_kpir;
	return $iworks_kpir->build_exclude_categories( $values );
}
function iworks_kpir_exclude_categories_sanitize_callback( $values ) {

	if ( is_array( $values ) ) {
		$ids = array();
		foreach ( $values as $id => $value ) {
			$ids[] = $id;
		}
		return $ids;
	}
	return null;
}

/**
 * exclude_tags
 */
function iworks_kpir_exclude_tags( $values = array() ) {

	global $iworks_kpir;
	return $iworks_kpir->build_exclude_tags( $values );
}
function iworks_kpir_exclude_tags_sanitize_callback( $values ) {

	if ( is_array( $values ) ) {
		$ids = array();
		foreach ( $values as $id => $value ) {
			$ids[] = $id;
		}
		return $ids;
	}
	return null;
}

/**
 * sanitize_callback: configuration
 */
function iworks_kpir_sanitize_callback_configuration( $option_value ) {

	if ( preg_match( '/^(simple|advance)$/', $option_value ) ) {
		return $option_value;
	}
	return 'simple';
}
/**
 * callback: layout
 */
function iworks_kpir_callback_layout( $value ) {

	global $iworks_kpir;
	return $iworks_kpir->build_layout_chooser( $value );
}
/**
 * callback: donate
 */
function iworks_kpir_callback_is_pro() {

	global $iworks_kpir;
	return ! $iworks_kpir->is_pro();
}

function iworks_kpir_options_choose_configuration_mode( $iworks_kpir ) {

	$configuration = $iworks_kpir->get_option( 'configuration' );
?>
<p><?php _e( 'Below are some links to help spread this plugin to other users', 'kpir' ); ?></p>
<ul>
    <li><input type="radio" name="iworks_kpir_configuration" value="simple" id="iworks_kpir_configuration_simple"   <?php checked( $configuration, 'simple' ); ?>/> <label for="iworks_kpir_configuration_simple"><?php _e( 'simple', 'kpir' ); ?></label></li>
    <li><input type="radio" name="iworks_kpir_configuration" value="advance" id="iworks_kpir_configuration_advance" <?php checked( $configuration, 'advance' ); ?>/> <label for="iworks_kpir_configuration_advance"><?php _e( 'advance', 'kpir' ); ?></label></li>
</ul>
<?php
}

function iworks_kpir_options_loved_this_plugin( $iworks_kpir ) {

?>
<p><?php _e( 'Below are some links to help spread this plugin to other users', 'kpir' ); ?></p>
<ul>
    <li><a href="http://wordpress.org/support/view/plugin-reviews/kpir#postform"><?php _e( 'Give it a five stars on Wordpress.org', 'kpir' ); ?></a></li>
    <li><a href="http://wordpress.org/extend/plugins/kpir/"><?php _e( 'Link to it so others can easily find it', 'kpir' ); ?></a></li>
</ul>
<?php
}

function iworks_kpir_options_need_assistance( $iworks_kpir ) {

?>
<p><?php _e( 'Problems? The links bellow can be very helpful to you', 'kpir' ); ?></p>
<ul>
    <li><a href="<?php _e( 'http://wordpress.org/tags/kpir', 'kpir' ); ?>"><?php _e( 'Wordpress Help Forum', 'kpir' ); ?></a></li>
</ul>
<?php
}
