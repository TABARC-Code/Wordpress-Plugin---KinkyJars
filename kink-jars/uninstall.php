<?php
// Deleting the plugin removes the two things it ever saved: which page it made, and the welcome
// notice flag. The page itself stays; it's your content, and binning it uninvited would be rude.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
delete_option( 'kink_jars_page_id' );
delete_transient( 'kink_jars_welcome' );
