<?php
/**
 * My Slide Show
 *
 * @package my-slide-show
 *
 * Plugin Name: My Slide Show
 * Plugin URI:  https://github.com/mi112/my-slide-show
 * Description: This plugin provides functionality to create and display slideshows through shortcode.
 * Version:     1.0.1
 * Requires at least: 5.2
 * Tested Up to: 5.4.1
 * Requires PHP: 7.0
 * Author:      Mittala Nandle
 * Author URI:  https://www.mittala.com/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: my-slide-show
 * Domain Path: /languages
 */

// Exit if accessed directly.

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

/**
 * INCLUDE SCRIPTS/STYLES FOR FRONTEND.
 */
function mss_front_scripts() {

	// css, js for slideshow.

	wp_enqueue_style( 'mss-slideshow-style', plugins_url( 'lib/public/css/mss-public-styles.css', __FILE__ ), array(), '1.0' );
	wp_enqueue_style( 'bootstrap-css', plugins_url( 'lib/public/css/bootstrap.min.css', __FILE__ ), array(), '1.0' );
	wp_enqueue_script( 'bootstrap-js', plugins_url( 'lib/public/js/bootstrap.min.js', __FILE__ ), array( 'jquery' ), '1.0', false );

}
add_action( 'wp_enqueue_scripts', 'mss_front_scripts' );

/**
 * INCLUDE SCRIPTS/STYLES FOR 'MYSLIDESHOW' SETTIGNS PAGE.
 */
function mss_include_scripts() {

	// return if not 'MSS setting page'.

	if ( empty( $_GET['page'] ) || 'my-slide-show' !== $_GET['page'] ) { // phpcs:ignore
		return;
	}

	if ( ! did_action( 'wp_enqueue_media' ) ) {

		wp_enqueue_media();

	}

	// include upload media script.

	wp_enqueue_script( 'mss-media-upload', plugins_url( 'lib/admin/js/mss-media-upload.js', __FILE__ ), array( 'jquery' ), '1.0', false );

	// include css for setting page.

	wp_enqueue_style( 'mss-admin-style', plugins_url( 'lib/admin/css/mss-admin-styles.css', __FILE__ ), array(), '1.0' );

}
add_action( 'admin_enqueue_scripts', 'mss_include_scripts' );

/**
 * LOAD THE TEXT DOMAIN (i18n).
 */
function mss_load_textdomain() {
	load_plugin_textdomain( 'my-slide-show', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'mss_load_textdomain' );

/**
 * CREATE 'MYSLIDESHOW' SETTIGNS PAGE.
 */
function mss_menu_page() {

	add_menu_page( 'My Slide Show', __( 'My Slide Show', 'my-slide-show' ), 'administrator', 'my-slide-show', 'mss_menu_output' );

	add_action( 'admin_init', 'mss_save_settings' );

}
add_action( 'admin_menu', 'mss_menu_page' );

/**
 * 'MYSLIDESHOW' SAVE SETTINGS.
 */
function mss_save_settings() {

	$settings_options = array(

		'slide_id' => array( 'default' => '' ),

	);

	foreach ( $settings_options as $opt => $val ) {

		register_setting( 'myslideshow-settings-group', $opt, $val );

	}

}

/**
 * 'MYSLIDESHOW MENU' PAGE CONTENT.
 */
function mss_menu_output(){  ?>

	<form method="post" action="options.php"> 

	<?php settings_fields( 'myslideshow-settings-group' ); ?>

	<?php do_settings_sections( 'myslideshow-settings-group' ); ?>



	<h2><?php esc_html_e( 'My Slide Show', 'my-slide-show' ); ?></h2>

	<input type="button" class="add_new_image" name="add_new_image" value="<?php esc_attr_e( 'Add Images', 'my-slide-show' ); ?>" >

		<div class="addslides-container">
			<ul id="sortable">

			<?php

				// get ids of saved images.

				$slide_ids = get_option( 'slide_id' );

			if ( ! empty( $slide_ids ) ) {

				// display saved images.

				foreach ( $slide_ids as $key => $value ) {

					// todo: condition if source is unknown.

					if ( wp_get_attachment_url( $value ) ) {

						echo "<li class='slide-container '>"

						. "<span class='remove-this-slide'><b>X</b></span>"

						. "<img src='" . esc_url( wp_get_attachment_url( $value ) ) . "' >"

						. "<input type='hidden' name='slide_id[]' value='" . esc_attr( $value ) . "' >"

						. '</li>';

					}
				}
			} else {

				echo "<span class='no-images-yet'>";
				esc_html_e( 'Images are not added yet!', 'my-slide-show' );
				echo '</span>';

			}

			?>

			</ul>

		</div>	

	<?php submit_button(); ?>   

	</form>	

	<?php

}

/**
 * CREATE SHORTCODE TO DISPLAY SLIDESHOW.
 */
function myslideshow_output() {

	$slide_ids = get_option( 'slide_id' );

	$return = '';

	// check if images are selected for slideshow.

	if ( ! empty( $slide_ids ) ) {

		$return = "<div id='myslideshow' class='carousel slide' data-ride='carousel'>";

		// ADD INDICATORS.

		$return = $return . " <ol class='carousel-indicators'>";

		$i = 0;

		foreach ( $slide_ids as $key => $value ) {

			$return = $return . "<li data-target='#myslideshow' data-slide-to='" . esc_attr( $i ) . "'";

			if ( 0 === $i ) {

				$return = $return . "class='active'";

			}

			$return = $return . '></li>';

			$i++;

		}

		$return = $return . '</ol>';

		// ADD SLIDER WRAPPERS.

		$j = 0;

		$return = $return . "<div class='carousel-inner'>";

		foreach ( $slide_ids as $key => $value ) {

			$return = $return . "<div class='carousel-item";

			if ( 0 === $j ) {

				$return = $return . ' active';

			}

			$j++;

			$return = $return . "'><img class='mss-slide' src='" . esc_url( wp_get_attachment_url( $value ) ) . "' > 

   			</div>";

		}

		$return = $return . '</div>';// end - inner-carousel-items.

		// ADD LEFT-RIGHT CONTROLERS.

		$return = $return . "<a class='carousel-control-prev' href='#myslideshow' data-slide='prev'>

				  			<span class='carousel-control-prev-icon'></span>

				  		
				  		</a>

				  		<a class='carousel-control-next' href='#myslideshow' data-slide='next'>

				  			<span class='carousel-control-next-icon'></span>

				  		</a>";

		$return = $return . '</div>';// end - carousel-container.

	} // end - check if images are selected for slideshow.

	return $return;

}
add_shortcode( 'myslideshow', 'myslideshow_output' );

?>
