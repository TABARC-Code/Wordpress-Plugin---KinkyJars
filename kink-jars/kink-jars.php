<?php
/**
 * Plugin Name:       Kink Jars
 * Plugin URI:        https://github.com/TABARC-Code/Wordpress-Plugin---KinkyJars
 * Description:       The Kink Jars questionnaire for adults, on any page. Go to Settings > Kink Jars and press one button to make the page. Answers stay in each visitor's browser; the plugin stores nothing about them.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            TABARC-Code
 * Author URI:        https://github.com/TABARC-Code
 * Text Domain:       kink-jars
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KINK_JARS_VERSION', '1.0.0' );
define( 'KINK_JARS_FILE', __FILE__ );
define( 'KINK_JARS_DIR', plugin_dir_path( __FILE__ ) );

require_once KINK_JARS_DIR . 'includes/shortcode.php';

if ( is_admin() ) {
	require_once KINK_JARS_DIR . 'includes/admin.php';
}

// A one-off welcome notice after activation, pointing at the setup page. It's a transient,
// so it tidies itself away after a day even if nobody clicks anything.
register_activation_hook( __FILE__, 'kink_jars_activate' );
function kink_jars_activate() {
	set_transient( 'kink_jars_welcome', 1, DAY_IN_SECONDS );
}
