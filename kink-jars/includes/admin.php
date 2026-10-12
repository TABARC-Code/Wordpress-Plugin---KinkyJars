<?php
/**
 * Settings > Kink Jars: the newbie-friendly bit.
 *
 * One button makes a page with the shortcode already in it, as a draft, so nothing adult goes
 * live until the site owner has had a look. A second button publishes it. Everything else on
 * the screen is explanation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const KINK_JARS_SLUG        = 'kink-jars';
const KINK_JARS_PAGE_OPTION = 'kink_jars_page_id';

function kink_jars_settings_url( $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => KINK_JARS_SLUG ), $args ), admin_url( 'options-general.php' ) );
}

/** The page we made earlier, if it still exists and hasn't been binned. */
function kink_jars_existing_page() {
	$id = (int) get_option( KINK_JARS_PAGE_OPTION, 0 );
	if ( ! $id ) {
		return null;
	}
	$page = get_post( $id );
	return ( $page && 'page' === $page->post_type && 'trash' !== $page->post_status ) ? $page : null;
}

/** Shortcode text for a layout choice. Unknown choices get the plain shortcode. */
function kink_jars_shortcode_for( $layout ) {
	return in_array( $layout, array( 'wide', 'full' ), true ) ? '[kink_jars align="' . $layout . '"]' : '[kink_jars]';
}

add_action( 'admin_menu', 'kink_jars_admin_menu' );
function kink_jars_admin_menu() {
	add_options_page(
		__( 'Kink Jars', 'kink-jars' ),
		__( 'Kink Jars', 'kink-jars' ),
		'edit_pages',
		KINK_JARS_SLUG,
		'kink_jars_render_settings'
	);
}

// A "Set up" link next to Deactivate on the Plugins screen, because that's where people look first.
add_filter( 'plugin_action_links_' . plugin_basename( KINK_JARS_FILE ), 'kink_jars_action_links' );
function kink_jars_action_links( $links ) {
	array_unshift( $links, '<a href="' . esc_url( kink_jars_settings_url() ) . '">' . esc_html__( 'Set up', 'kink-jars' ) . '</a>' );
	return $links;
}

add_action( 'admin_notices', 'kink_jars_welcome_notice' );
function kink_jars_welcome_notice() {
	if ( ! get_transient( 'kink_jars_welcome' ) || ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'settings_page_' . KINK_JARS_SLUG === $screen->id ) {
		return;
	}
	printf(
		'<div class="notice notice-success"><p><strong>%1$s</strong> %2$s <a class="button button-primary" href="%3$s">%4$s</a></p></div>',
		esc_html__( 'Kink Jars is installed.', 'kink-jars' ),
		esc_html__( 'One more step: make the page your visitors will use.', 'kink-jars' ),
		esc_url( kink_jars_settings_url() ),
		esc_html__( 'Set up Kink Jars', 'kink-jars' )
	);
}

add_action( 'admin_post_kink_jars_create_page', 'kink_jars_handle_create_page' );
function kink_jars_handle_create_page() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( esc_html__( 'You are not allowed to create pages on this site.', 'kink-jars' ), 403 );
	}
	check_admin_referer( 'kink_jars_create_page' );

	$title  = isset( $_POST['kink_jars_title'] ) ? sanitize_text_field( wp_unslash( $_POST['kink_jars_title'] ) ) : '';
	$layout = isset( $_POST['kink_jars_layout'] ) ? sanitize_key( wp_unslash( $_POST['kink_jars_layout'] ) ) : 'wide';
	if ( '' === $title ) {
		$title = __( 'Kink Jars', 'kink-jars' );
	}

	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'draft',
			'post_title'   => $title,
			'post_content' => "<!-- wp:shortcode -->\n" . kink_jars_shortcode_for( $layout ) . "\n<!-- /wp:shortcode -->",
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		wp_safe_redirect( kink_jars_settings_url( array( 'kj' => 'failed' ) ) );
		exit;
	}
	update_option( KINK_JARS_PAGE_OPTION, (int) $id, false );
	delete_transient( 'kink_jars_welcome' );
	wp_safe_redirect( kink_jars_settings_url( array( 'kj' => 'created' ) ) );
	exit;
}

add_action( 'admin_post_kink_jars_publish_page', 'kink_jars_handle_publish_page' );
function kink_jars_handle_publish_page() {
	check_admin_referer( 'kink_jars_publish_page' );
	$page = kink_jars_existing_page();
	if ( ! $page || ! current_user_can( 'publish_pages' ) || ! current_user_can( 'edit_post', $page->ID ) ) {
		wp_die( esc_html__( 'You are not allowed to publish this page.', 'kink-jars' ), 403 );
	}
	wp_update_post( array( 'ID' => $page->ID, 'post_status' => 'publish' ) );
	wp_safe_redirect( kink_jars_settings_url( array( 'kj' => 'published' ) ) );
	exit;
}

function kink_jars_render_settings() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	delete_transient( 'kink_jars_welcome' );

	$page    = kink_jars_existing_page();
	$message = isset( $_GET['kj'] ) ? sanitize_key( wp_unslash( $_GET['kj'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- display only.
	$notices = array(
		'created'   => array( 'success', __( 'Done. Your page is saved as a draft. Have a look with Preview, then press Publish when you are happy.', 'kink-jars' ) ),
		'published' => array( 'success', __( 'Published. Your Kink Jars page is live.', 'kink-jars' ) ),
		'failed'    => array( 'error', __( 'WordPress would not create the page. Try again, or add the shortcode to a page by hand (see below).', 'kink-jars' ) ),
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Kink Jars', 'kink-jars' ); ?></h1>

		<?php if ( isset( $notices[ $message ] ) ) : ?>
			<div class="notice notice-<?php echo esc_attr( $notices[ $message ][0] ); ?>"><p><?php echo esc_html( $notices[ $message ][1] ); ?></p></div>
		<?php endif; ?>

		<p style="max-width:720px">
			<?php esc_html_e( 'An adults-only questionnaire: visitors fill jars from 1 to 5, save a poster, and can compare with a partner. Names and answers stay in each visitor\'s own browser. This plugin stores nothing about them and sends nothing anywhere.', 'kink-jars' ); ?>
		</p>

		<h2><?php esc_html_e( 'Step 1: your page', 'kink-jars' ); ?></h2>

		<?php if ( $page ) : ?>
			<p>
				<?php
				printf(
					/* translators: 1: page title, 2: page status such as Draft or Published */
					esc_html__( 'Your page is "%1$s" and it is %2$s.', 'kink-jars' ),
					esc_html( get_the_title( $page ) ),
					'<strong>' . esc_html( 'publish' === $page->post_status ? __( 'live', 'kink-jars' ) : __( 'a draft, so only you can see it', 'kink-jars' ) ) . '</strong>'
				);
				?>
			</p>
			<div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:12px 0">
				<a class="button" href="<?php echo esc_url( get_permalink( $page ) ); ?>" target="_blank" rel="noopener"><?php echo 'publish' === $page->post_status ? esc_html__( 'View page', 'kink-jars' ) : esc_html__( 'Preview page', 'kink-jars' ); ?></a>
				<a class="button" href="<?php echo esc_url( get_edit_post_link( $page->ID ) ); ?>"><?php esc_html_e( 'Edit page', 'kink-jars' ); ?></a>
				<?php if ( 'publish' !== $page->post_status && current_user_can( 'publish_pages' ) ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0">
						<input type="hidden" name="action" value="kink_jars_publish_page">
						<?php wp_nonce_field( 'kink_jars_publish_page' ); ?>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Publish it now', 'kink-jars' ); ?></button>
					</form>
				<?php endif; ?>
			</div>
			<p class="description"><?php esc_html_e( 'Want another one? Make it below. The buttons above always point at the newest.', 'kink-jars' ); ?></p>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="kink_jars_create_page">
			<?php wp_nonce_field( 'kink_jars_create_page' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="kink_jars_title"><?php esc_html_e( 'Page title', 'kink-jars' ); ?></label></th>
					<td><input type="text" id="kink_jars_title" name="kink_jars_title" class="regular-text" value="<?php esc_attr_e( 'Kink Jars', 'kink-jars' ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="kink_jars_layout"><?php esc_html_e( 'Width', 'kink-jars' ); ?></label></th>
					<td>
						<select id="kink_jars_layout" name="kink_jars_layout">
							<option value="wide" selected><?php esc_html_e( 'Wide (recommended)', 'kink-jars' ); ?></option>
							<option value="full"><?php esc_html_e( 'Full width, edge to edge', 'kink-jars' ); ?></option>
							<option value="normal"><?php esc_html_e( 'Same as your text', 'kink-jars' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Most themes keep text in a narrow column, which squashes the app into its phone layout on a computer. Wide fixes that. If your theme ignores it, nothing breaks; it just stays narrow.', 'kink-jars' ); ?></p>
					</td>
				</tr>
			</table>
			<?php submit_button( $page ? __( 'Create another page', 'kink-jars' ) : __( 'Create my Kink Jars page', 'kink-jars' ), $page ? 'secondary' : 'primary' ); ?>
		</form>

		<h2><?php esc_html_e( 'Prefer to do it by hand?', 'kink-jars' ); ?></h2>
		<p><?php esc_html_e( 'Put this in any page or post. In the block editor, add a Shortcode block and paste it in:', 'kink-jars' ); ?></p>
		<p><code style="font-size:15px;padding:6px 10px">[kink_jars align="wide"]</code></p>
		<table class="widefat striped" style="max-width:720px">
			<thead><tr><th><?php esc_html_e( 'Option', 'kink-jars' ); ?></th><th><?php esc_html_e( 'What it does', 'kink-jars' ); ?></th></tr></thead>
			<tbody>
				<tr><td><code>align="wide"</code> / <code>align="full"</code></td><td><?php esc_html_e( 'Lets the app break out of a narrow text column.', 'kink-jars' ); ?></td></tr>
				<tr><td><code>height="800px"</code></td><td><?php esc_html_e( 'Height of the box. px from 100 to 9999, or vh from 10 to 999. Default 90vh (nearly a full screen).', 'kink-jars' ); ?></td></tr>
				<tr><td><code>title="..."</code></td><td><?php esc_html_e( 'Name read out by screen readers. The default is fine.', 'kink-jars' ); ?></td></tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'If something looks wrong', 'kink-jars' ); ?></h2>
		<ul style="list-style:disc;padding-left:20px;max-width:720px">
			<li><?php esc_html_e( 'Blank box where the app should be: a security plugin or your host may be blocking pages from showing inside frames. Look for a setting called X-Frame-Options or "clickjacking protection" and allow SAMEORIGIN.', 'kink-jars' ); ?></li>
			<li><?php esc_html_e( 'You see the text [kink_jars] instead of the app: it went into a paragraph or code block. Delete it and add it again in a Shortcode block.', 'kink-jars' ); ?></li>
			<li><?php esc_html_e( 'An old version still shows after updating: clear your caching plugin or host cache.', 'kink-jars' ); ?></li>
		</ul>

		<p class="description">
			<?php
			printf(
				/* translators: 1: plugin version, 2: app version */
				esc_html__( 'Plugin %1$s, app %2$s. Full guide and updates:', 'kink-jars' ),
				esc_html( KINK_JARS_VERSION ),
				esc_html( kink_jars_app_version() ?: '?' )
			);
			?>
			<a href="https://github.com/TABARC-Code/Wordpress-Plugin---KinkyJars" target="_blank" rel="noopener">github.com/TABARC-Code/Wordpress-Plugin---KinkyJars</a>
		</p>
	</div>
	<?php
}
