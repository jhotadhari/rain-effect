<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! function_exists( 'rain_the_custom_footer' ) ) {
	/**
	 * Prints HTML with meta information for the current post-date/time and author.
	 */
	function rain_the_custom_footer(){
		echo rain\Rain_Customizer::get_instance()->get_footer_markup();
	}

}
