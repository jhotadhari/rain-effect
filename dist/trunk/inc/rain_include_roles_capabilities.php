<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

function rain_include_roles_capabilities() {

	$paths = array(
	);

	if ( count( $paths ) > 0 ) {
		foreach( $paths as $path ) {
			include_once( rain\Rain::get_instance()->dir_path . $path );
		}
	}

}

?>