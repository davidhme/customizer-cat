<?php

/**
 * Plugin Name: Customizer Cat
 * Plugin URI: https://fatcatapps.com/
 * Version: 1.0
 * Author: Fatcat Apps
 * Author URI: https://fatcatapps.com/
 * Description: Adds customization options
 */

if ( ! function_exists( 'is_admin' ) ) {
	exit();
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-includes/pluggable.php';

$plugin_data = get_plugin_data( __FILE__ );

define( 'FCA_CC_PLUGIN_NAME', $plugin_data['Name'] );
define( 'FCA_CC_PLUGIN_SLUG', strtolower( preg_replace( '/\W+/', '-', FCA_CC_PLUGIN_NAME ) ) );

define( 'FCA_CC_KEY_CSS', 'fca_cc_custom_css' );
define( 'FCA_CC_KEY_JAVASCRIPT', 'fca_cc_custom_javascript' );
define( 'FCA_CC_KEY_PHP', 'fca_cc_custom_php' );

function fca_cc_get_css() {
	return get_site_option( FCA_CC_KEY_CSS, '' );
}

function fca_cc_set_css( $css ) {
	update_site_option( FCA_CC_KEY_CSS, $css );
}

function fca_cc_get_javascript() {
	return get_site_option( FCA_CC_KEY_JAVASCRIPT, '' );
}

function fca_cc_set_javascript( $javascript ) {
	update_site_option( FCA_CC_KEY_JAVASCRIPT, $javascript );
}

function fca_cc_get_php_file_name() {
	return dirname( __FILE__ ) . '/custom.php';
}

function fca_cc_get_php() {
	$file_name = fca_cc_get_php_file_name();

	return file_exists( $file_name ) ? file_get_contents( $file_name ) : '';
}

function fca_cc_set_php( $php ) {
	$file_name = fca_cc_get_php_file_name();

	if ( file_exists( $file_name ) ) {
		unlink( $file_name );
	}

	$php = trim( $php );
	if ( ! empty( $php ) ) {
		file_put_contents( $file_name, $php );
	}
}

function fca_cc_options_page() {
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		$css        = $_REQUEST[ FCA_CC_KEY_CSS ];
		$javascript = $_REQUEST[ FCA_CC_KEY_JAVASCRIPT ];
		$php        = $_REQUEST[ FCA_CC_KEY_PHP ];

		$css        = stripslashes( $css );
		$javascript = stripslashes( $javascript );
		$php        = stripslashes( $php );

		fca_cc_set_css( $css );
		fca_cc_set_javascript( $javascript );
		fca_cc_set_php( $php );
	}

	$css        = esc_html( fca_cc_get_css() );
	$javascript = esc_html( fca_cc_get_javascript() );
	$php        = esc_html( fca_cc_get_php() );

	?>
	<div class="wrap">
		<form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="post">
			<h2><?php echo esc_html( FCA_CC_PLUGIN_NAME ) ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row">Custom CSS</th>
					<td>
						<p>
							<textarea rows="10" cols="50" name="<?php echo FCA_CC_KEY_CSS ?>"
							          class="large-text code"><?php echo $css ?></textarea>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Custom JavaScript</th>
					<td>
						<p>
							<textarea rows="10" cols="50" name="<?php echo FCA_CC_KEY_JAVASCRIPT ?>"
							          class="large-text code"><?php echo $javascript ?></textarea>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Custom PHP</th>
					<td>
						<p>
							<textarea rows="10" cols="50" name="<?php echo FCA_CC_KEY_PHP ?>"
							          class="large-text code"><?php echo $php ?></textarea>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

function fca_cc_head() {
	$css = fca_cc_get_css();
	if ( ! empty( $css ) ) {
		echo '<style type="text/css">' . $css . '</style>';
	}

	$javascript = fca_cc_get_javascript();
	if ( ! empty( $javascript ) ) {
		echo '<script type="text/javascript">' . $javascript . '</script>';
	}
}

if ( is_admin() ) {
	add_options_page( FCA_CC_PLUGIN_NAME, FCA_CC_PLUGIN_NAME, 'manage_options', FCA_CC_PLUGIN_SLUG, 'fca_cc_options_page' );
} else {
	$fca_cc_php_file_name = fca_cc_get_php_file_name();
	if ( file_exists( $fca_cc_php_file_name ) ) {
		include $fca_cc_php_file_name;
	}

	add_action( 'wp_head', 'fca_cc_head' );
}
