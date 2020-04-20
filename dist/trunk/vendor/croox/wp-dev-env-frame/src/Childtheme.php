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

abstract class Childtheme extends Theme {

	protected $parent = '';

	function __construct( $init_args = array() ) {
		parent::__construct( $init_args );

		// parse init_args, apply defaults
		$init_args = wp_parse_args(
			$init_args,
			array()
		);

		if ( array_key_exists( 'parent', $init_args ) ) {
			$this->parent = $init_args['parent'];
		}

	}

	protected function enqueue_assets() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_parent_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 100 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
	}

	public function enqueue_parent_styles() {
		// if theme is childtheme, enqueue parent style and set parent as dependency
		if ( get_stylesheet_directory_uri() !== get_template_directory_uri() ) {
			$parent_style = 'style';
			wp_enqueue_style( 'style', get_template_directory_uri() . '/style.css' );
			array_push( $this->style_deps, $parent_style );
		}
	}

	public function enqueue_scripts() {
		// overwrite parent with some emptyness
	}

}
