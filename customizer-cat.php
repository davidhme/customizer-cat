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
define( 'FCA_CC_KEY_DISABLE_AUTO_UPDATE', 'fca_cc_disable_auto_update' );

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

function fca_cc_set_disable_auto_update( $plugin_slugs ) {
	$plugin_slugs = trim( $plugin_slugs );
	if ( empty( $plugin_slugs ) ) {
		return;
	}

	$plugin_slugs = array_map( 'trim', explode( ',', $plugin_slugs ) );

	update_site_option( FCA_CC_KEY_DISABLE_AUTO_UPDATE, $plugin_slugs );
}

function fca_cc_get_disable_auto_update() {
	return get_site_option( FCA_CC_KEY_DISABLE_AUTO_UPDATE, array() );
}

function fca_cc_list_all_plugin_files( $root_dir = null ) {
	if ( is_null( $root_dir ) ) {
		$root_dir = realpath( dirname( __FILE__ ) . '/../../..' );
	}

	$contents = array();

	foreach ( scandir( $root_dir ) as $path ) {
		if ( $path === '.' || $path === '..' || $path === '.git' ) {
			continue;
		}

		$full_path = $root_dir . '/' . $path;
		$is_dir    = is_dir( $full_path );

		$format = '%Y-%m-%d';
		$ctime  = strftime( $format, filectime( $full_path ) );
		$mtime  = strftime( $format, filemtime( $full_path ) );
		$text   = $path .
		          '<span class="fca_cc_time">' .
		          '<span class="fca_cc_ctime">' . $ctime . '</span>' .
		          '<span class="fca_cc_mtime">' . $mtime . '</span>' .
		          '</span>';

		$item = array(
			'text' => $text . ( $is_dir
					? '<i class="fca_cc_list_download dashicons dashicons-download" data-path="' . htmlspecialchars( $full_path ) . '"></i>'
					: '' ),
			'data' => array( 'path' => $full_path )
		);

		if ( $is_dir ) {
			$item['children'] = fca_cc_list_all_plugin_files( $full_path );
			$item['icon']     = 'dashicons dashicons-category';
		} else {
			if ( ! in_array( pathinfo( $path, PATHINFO_EXTENSION ), array( 'php', 'js', 'css' ) ) ) {
				continue;
			}
			$item['icon']           = 'dashicons dashicons-media-default';
			$item['data']['editor'] = 'text';
		}

		$contents[] = $item;
	}

	return $contents;
}

function fca_cc_zip_path( $path ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		echo 'ZipArchive not supported.';

		return;
	}

	$zip_file_name = tempnam( sys_get_temp_dir(), 'fca_cc_' );

	$zip = new ZipArchive();
	$zip->open( $zip_file_name, ZipArchive::CREATE | ZipArchive::OVERWRITE );

	/**
	 * @var SplFileInfo[] $files
	 */
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $path ), RecursiveIteratorIterator::LEAVES_ONLY
	);

	$path_len = strlen( $path ) + 1;

	foreach ( $files as $name => $file ) {
		if ( $file->isDir() ) {
			continue;
		}

		$full_path = $file->getRealPath();
		$zip->addFile( $full_path, substr( $full_path, $path_len ) );
	}

	$zip->close();

	$fh = fopen( $zip_file_name, 'rb' );

	header( 'Content-Type: application/zip' );
	header( 'Content-Length: ' . filesize( $zip_file_name ) );
	header( 'Content-Disposition: attachment; filename=' . basename( $path ) . '.zip' );

	fpassthru( $fh );
	fclose( $fh );

	unlink( $zip_file_name );
}

function fca_cc_handle_action() {
	if ( empty( $_REQUEST['fca_cc_action'] ) ) {
		return;
	}

	if ( $_REQUEST['fca_cc_action'] === 'read_file' && ! empty( $_REQUEST['fca_cc_path'] ) ) {
		readfile( $_REQUEST['fca_cc_path'] );
	} else if ( $_REQUEST['fca_cc_action'] === 'zip_path' && ! empty( $_REQUEST['fca_cc_path'] ) ) {
		fca_cc_zip_path( $_REQUEST['fca_cc_path'] );
	}

	exit;
}

function fca_cc_options_page() {
	$url = plugins_url( FCA_CC_PLUGIN_SLUG );

	wp_enqueue_script( 'jstree', $url . '/lib/jstree/jstree.min.js' );
	wp_enqueue_style( 'jstree', $url . '/lib/jstree/themes/default/style.min.css' );

	if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		$css                 = stripslashes( $_REQUEST[ FCA_CC_KEY_CSS ] );
		$javascript          = stripslashes( $_REQUEST[ FCA_CC_KEY_JAVASCRIPT ] );
		$php                 = stripslashes( $_REQUEST[ FCA_CC_KEY_PHP ] );
		$disable_auto_update = stripslashes( $_REQUEST[ FCA_CC_KEY_DISABLE_AUTO_UPDATE ] );

		fca_cc_set_css( $css );
		fca_cc_set_javascript( $javascript );
		fca_cc_set_php( $php );
		fca_cc_set_disable_auto_update( $disable_auto_update );
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

			<hr>

			<table class="form-table">
				<tr>
					<th scope="row">Disable plugin auto-update</th>
					<td>
						<p>
							<input type="text" name="<?php echo FCA_CC_KEY_DISABLE_AUTO_UPDATE ?>"
							       class="large-text code"
							       value="<?php echo implode( ', ', fca_cc_get_disable_auto_update() ) ?>">
							<br>
							(plugin slugs separated by comma)
						</p>
					</td>
				</tr>
			</table>

			<hr>

			<table class="form-table">
				<tr>
					<td scope="row" width="1">
						<div id="fca_cc_plugin_list"></div>
					</td>
					<td>
						<textarea id="fca_cc_file_editor" class="fca_cc_file_editor large-text code"></textarea>
					</td>
				</tr>
			</table>

			<style>
				.fca_cc_file_editor {
					width: 100%;
					height: 100%;
				}

				.fca_cc_time {
					font-size: 70%;
					opacity: 0.5;
				}

				.fca_cc_ctime {
					border-right: 1px solid rgba(0, 0, 0, 0.2);
					margin: 0 4px;
					padding-right: 5px;
				}

				div#fca_cc_plugin_list i.dashicons.dashicons-download {
					font-size: 16px;
					vertical-align: bottom;
					display: none;
				}

				div#fca_cc_plugin_list a.jstree-clicked i.dashicons.dashicons-download {
					display: inline-block;
				}
			</style>

			<script>
				jQuery( function( $ ) {
					var $list = $( '#fca_cc_plugin_list' );

					$list.jstree( {
						core: {
							multiple: false,
							data: <?php echo json_encode( fca_cc_list_all_plugin_files() ) ?>
						}
					} ).on( 'changed.jstree', function( event, data ) {
						var item_data = data.instance.get_node( data.selected[ 0 ] ).data;
						if ( item_data[ 'editor' ] && item_data[ 'editor' ] === 'text' ) {
							$.post( window.location.href, {
								'fca_cc_action': 'read_file',
								'fca_cc_path': item_data[ 'path' ]
							}, function( result ) {
								$( '#fca_cc_file_editor' ).text( result.replace( /\t/g, '  ' ) );
							} );
						}
					} );

					$list.click( function( event ) {
						var $element = $( event.target );
						if ( $element.hasClass( 'fca_cc_list_download' ) ) {
							var path = $element.data( 'path' );
							if ( ! path ) {
								return;
							}

							window.location.href += '&fca_cc_action=zip_path&fca_cc_path=' + encodeURIComponent( path );
						}
					} );
				} );
			</script>

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
	add_action( 'admin_init', 'fca_cc_handle_action' );
	add_options_page( FCA_CC_PLUGIN_NAME, FCA_CC_PLUGIN_NAME, 'manage_options', FCA_CC_PLUGIN_SLUG, 'fca_cc_options_page' );

	if ( count( fca_cc_get_disable_auto_update() ) > 0 ) {
		function fca_cc_auto_update_handler( $transient_data ) {
			$no_update_plugin_files = array();

			foreach ( fca_cc_get_disable_auto_update() as $no_update_slug ) {
				foreach ( $transient_data->response as $plugin_file => $data ) {
					if ( $data->slug === $no_update_slug ) {
						$no_update_plugin_files[] = $plugin_file;
						break;
					}
				}
			}

			foreach ( $no_update_plugin_files as $plugin_file ) {
				$transient_data->no_update[ $plugin_file ] = $transient_data->response[ $plugin_file ];
				unset( $transient_data->response[ $plugin_file ] );
			}

			return $transient_data;
		}

		add_filter( 'site_transient_update_plugins', 'fca_cc_auto_update_handler' );
	}

} else {
	$fca_cc_php_file_name = fca_cc_get_php_file_name();
	if ( file_exists( $fca_cc_php_file_name ) ) {
		include $fca_cc_php_file_name;
	}

	add_action( 'wp_head', 'fca_cc_head' );
}
