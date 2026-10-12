<?php
// Unit checks for the plugin, using small stand-ins for the WordPress functions it calls.
// Usage: php tests/unit.php [path to the kink-jars plugin folder]
$dir = rtrim( $argv[1] ?? __DIR__ . '/../kink-jars', '/' ) . '/';
define( 'ABSPATH', __DIR__ );
define( 'KINK_JARS_VERSION', '9.9.9' );
define( 'KINK_JARS_FILE', $dir . 'kink-jars.php' );
define( 'KINK_JARS_DIR', $dir );
$GLOBALS['shortcodes'] = array();
function __( $s, $d = null ) { return $s; }
function esc_html__( $s, $d = null ) { return htmlspecialchars( $s, ENT_QUOTES ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); }
function esc_url( $s ) { return preg_match( '#^https?://#', $s ) ? htmlspecialchars( $s, ENT_QUOTES ) : ''; }
function plugins_url( $path, $file ) { return 'https://example.test/wp-content/plugins/kink-jars/' . $path; }
function add_query_arg( $k, $v, $url = null ) { return $url . '?' . $k . '=' . rawurlencode( $v ); }
function add_shortcode( $tag, $fn ) { $GLOBALS['shortcodes'][ $tag ] = $fn; }
function add_action() {}
function add_filter() {}
function plugin_basename( $f ) { return 'kink-jars/kink-jars.php'; }
function shortcode_atts( $defaults, $atts, $tag ) {
	$out = array();
	foreach ( $defaults as $k => $v ) { $out[ $k ] = is_array( $atts ) && array_key_exists( $k, $atts ) ? $atts[ $k ] : $v; }
	return $out;
}

require $dir . 'includes/shortcode.php';
require $dir . 'includes/admin.php';

$fails = 0;
function check( $name, $ok ) { global $fails; echo ( $ok ? 'ok   ' : 'FAIL ' ) . $name . "\n"; if ( ! $ok ) { $fails++; } }
$render = $GLOBALS['shortcodes']['kink_jars'] ?? null;
check( 'shortcode is registered', is_callable( $render ) );

$app = kink_jars_app_version();
check( 'reads the bundled app version', (bool) preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+$/', $app ) );

$html = $render( array() );
check( 'defaults to 90vh', strpos( $html, 'height:90vh' ) !== false );
check( 'points at the bundled app, cache-busted by both versions', strpos( $html, 'kink-jars/app/index.html?v=9.9.9-' . $app ) !== false );
check( 'allows clipboard and share', strpos( $html, 'allow="clipboard-write; web-share"' ) !== false );
check( 'has a full-screen link', strpos( $html, 'Open Kink Jars full screen' ) !== false );
check( 'accepts 800px', strpos( $render( array( 'height' => '800px' ) ), 'height:800px' ) !== false );
check( 'accepts 75vh', strpos( $render( array( 'height' => '75vh' ) ), 'height:75vh' ) !== false );
foreach ( array( '100px;background:url(x)', '9999999px', '5px', '80%', 'expression(alert(1))', '' ) as $bad ) {
	check( 'rejects height ' . json_encode( $bad ), strpos( $render( array( 'height' => $bad ) ), 'height:90vh' ) !== false );
}
check( 'align wide', strpos( $render( array( 'align' => 'wide' ) ), 'class="kink-jars-embed alignwide"' ) !== false );
check( 'align full', strpos( $render( array( 'align' => 'full' ) ), 'class="kink-jars-embed alignfull"' ) !== false );
check( 'other align values ignored', strpos( $render( array( 'align' => '" onload="x' ) ), 'class="kink-jars-embed"' ) !== false );
$evil = $render( array( 'title' => '"><script>alert(1)</script>' ) );
check( 'escapes the title', strpos( $evil, '<script>' ) === false && strpos( $evil, '&quot;&gt;&lt;script&gt;' ) !== false );

check( 'page maker: wide', kink_jars_shortcode_for( 'wide' ) === '[kink_jars align="wide"]' );
check( 'page maker: full', kink_jars_shortcode_for( 'full' ) === '[kink_jars align="full"]' );
check( 'page maker: anything else is plain', kink_jars_shortcode_for( 'normal' ) === '[kink_jars]' && kink_jars_shortcode_for( '"]x' ) === '[kink_jars]' );

exit( $fails ? 1 : 0 );
