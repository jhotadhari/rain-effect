<?php
/**
 * Childtheme init
 *
 * @package wde
 */

namespace croox\wde;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

abstract class Childtheme_Hello_Elementor extends Childtheme {

	public function enqueue_parent_styles() {
		if ( get_stylesheet_directory_uri() !== get_template_directory_uri() ) {
			$parent_style = 'hello-elementor-theme-style';
			array_push( $this->style_deps, $parent_style );
		}
	}

}
