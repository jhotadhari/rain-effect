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

abstract class Childtheme_Enfold extends Childtheme {

	public function enqueue_styles() {

		if ( defined( $this->parent ) && 'enfold' === $this->parent && get_stylesheet_directory_uri() !== get_template_directory_uri() ) {
			// if parent is enfold we need to deregister the child style.css again
			// it was registered as 'avia-style'
			wp_deregister_style( 'avia-style' );
		}

		parent::enqueue_styles();
	}

}
