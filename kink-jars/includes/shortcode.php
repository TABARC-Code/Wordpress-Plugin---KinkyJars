<?php
/**
 * The [kink_jars] shortcode.
 *
 * Why an iframe and not a script tag: the app owns a whole page (fixed save bar, two modal
 * dialogues, its own fonts and body styles). Inside an iframe no theme can tread on it and it
 * can't tread on the theme. It's served from the same site, so browser storage, downloads and
 * the clipboard behave exactly as on the standalone page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads the app's own version out of app/index.html, so the settings page can show it.
 * Returns an empty string if the file has gone walkabout.
 */
function kink_jars_app_version() {
	$html = @file_get_contents( KINK_JARS_DIR . 'app/index.html' ); // phpcs:ignore -- local file, read once.
	if ( $html && preg_match( '/const VERSION = "([0-9]+\.[0-9]+\.[0-9]+)"/', $html, $m ) ) {
		return $m[1];
	}
	return '';
}

function kink_jars_app_url() {
	return add_query_arg( 'v', KINK_JARS_VERSION . '-' . kink_jars_app_version(), plugins_url( 'app/index.html', KINK_JARS_FILE ) );
}

function kink_jars_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'height' => '90vh',
			'align'  => '',
			'title'  => __( 'Kink Jars questionnaire', 'kink-jars' ),
		),
		$atts,
		'kink_jars'
	);

	// Only a plain number with px or vh gets through. Anything else falls back rather than leaking into CSS.
	$height = preg_match( '/^[0-9]{3,4}px$|^[0-9]{2,3}vh$/', (string) $atts['height'] ) ? $atts['height'] : '90vh';
	// wide or full borrows the theme's own alignwide/alignfull classes, so the app can escape a narrow text column.
	$align = in_array( $atts['align'], array( 'wide', 'full' ), true ) ? ' align' . $atts['align'] : '';
	$src   = kink_jars_app_url();

	return sprintf(
		'<div class="kink-jars-embed%5$s"><iframe src="%1$s" title="%2$s" style="display:block;width:100%%;height:%3$s;border:0" loading="lazy" allow="clipboard-write; web-share"></iframe><p class="kink-jars-open"><a href="%1$s" target="_blank" rel="noopener">%4$s</a></p></div>',
		esc_url( $src ),
		esc_attr( $atts['title'] ),
		esc_attr( $height ),
		esc_html__( 'Open Kink Jars full screen', 'kink-jars' ),
		esc_attr( $align )
	);
}
add_shortcode( 'kink_jars', 'kink_jars_shortcode' );
