<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

function rain_include_template_tags() {

	$paths = array(
		'inc/template_tags/rain_get_custom_footer.php',
		'inc/template_tags/rain_the_custom_footer.php',
	);

	if ( count( $paths ) > 0 ) {
		foreach( $paths as $path ) {
			include_once( rain\Rain::get_instance()->dir_path . $path );
		}
	}

}

?>