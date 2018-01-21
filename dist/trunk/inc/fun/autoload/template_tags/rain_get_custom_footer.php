<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! function_exists( 'rain_get_custom_footer' ) ) {
	/**
	 * Returns HTML with meta information for the current post-date/time and author.
	 */
	function rain_get_custom_footer(){
		return Rain_Customizer::get_instance()->get_footer_markup();
	}
	
}

?>