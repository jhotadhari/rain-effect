<?php

namespace croox\wde;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Base class for Blocks. Registers a Block and enqueues the assets.
 * Don't forget to add the required `js` and `scss` files!
 *
 * Extend this class
 * - (required) Overwrite the `initialize` method to setup class properties.
 * - If only certain assets should be loaded, overwrite the `setup_handles` method.
 * - Overwrite the `enqueue_*` methods if different dependencies are needed.
 *
 * Static utility methods for Blocks:
 * - decode_attributes
 * - post_has_block
 *
 * @package	wde
 * @since  	0.7.0
 */
abstract class Block {

	private static $_instances = array();

	// Block type name excluding namespace
	protected $name = '';

	protected $handles = array();

	public static function get_instance() {
		$class = get_called_class();
		if ( ! isset( self::$_instances[ $class ] ) ) {
			$new_class                  = new $class();
			self::$_instances[ $class ] = $new_class;
		}
		return self::$_instances[ $class ];
	}

	function __construct() {
		$this->initialize();
		$this->setup_handles();
		$this->hooks();
	}

	/**
	 * Setup class properties
	 *
	 * This method needs to be overwritten!
	 * @since  	0.7.0
	 */
	protected function initialize() {
		// Block type name excluding namespace. Use dashes.
		$this->name = str_replace( '_', '-',
			sanitize_title_with_dashes( '' )	// Set a sanitized name!
		);

		$this->project_class_name = '';			// Set the project class name!
	}

	/**
	 * Set handles for frontend and editor/admin assets
	 *
	 * Overwrite this method if certain assets should not be loaded.
	 * @since  	0.7.0
	 */
	protected function setup_handles() {
		$prefix = $this->project_class_name::get_instance()->prefix;
		$_name = str_replace( '-', '_', $this->name );

		$this->handles = array(
			'style_admin'     => $prefix . '_block_' . $_name . '_admin',
			'script_admin'    => $prefix . '_block_' . $_name . '_admin',
			'style_frontend'  => $prefix . '_block_' . $_name . '_frontend',
			'script_frontend' => $prefix . '_block_' . $_name . '_frontend',
		);
	}

	/**
	 * Initiate our hooks
	 * @since  	0.7.0
	 */
	public function hooks() {

		add_action( 'init', array( $this, 'register_block' ) );

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_style_admin' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_script_admin' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script_frontend' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style_frontend' ) );
	}

	/**
	 * Register the block
	 *
	 * Arguments for block assets in frontend and editor will be set, whether handles are defined.
	 * @since  	0.7.0
	 */
	public function register_block() {
		if ( function_exists( 'register_block_type' ) ) {

			$prefix = $this->project_class_name::get_instance()->prefix;
			$block_type_name = $prefix . '/' . $this->name;

			$args = array();

			if ( $this->get_handle( 'script_admin' ) )
				$args = array_merge( $args, array( 'editor_script' => $this->get_handle( 'script_admin' ) ) );

			if ( $this->get_handle( 'style_admin' ) )
				$args = array_merge( $args, array( 'editor_style' => $this->get_handle( 'style_admin' ) ) );

			if ( $this->get_handle( 'script_frontend' ) )
				$args = array_merge( $args, array( 'script' => $this->get_handle( 'script_frontend' ) ) );

			if ( $this->get_handle( 'style_frontend' ) )
				$args = array_merge( $args, array( 'style' => $this->get_handle( 'style_frontend' ) ) );

			if ( method_exists( $this, 'render' ) )
				$args = array_merge( $args, array( 'render_callback' => array( $this, 'render' ) ) );

			register_block_type( $block_type_name, $args );
		}
	}

	/**
	 * Loads the required scripts in admin.
	 * If `$handle` is set.
	 *
	 * Overwrite this method if other dependencies or localize_data needed.
	 * @since  	0.7.0
	 */
	public function enqueue_script_admin() {
		$handle = $this->get_handle( 'script_admin' );
		if ( ! $handle || ! method_exists( $this->project_class_name, 'register_script' ) )
			return;
		$this->project_class_name::get_instance()->register_script( array(
			'handle'		=> $handle,
			'deps'			=> array(
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-edit-post'
			),
			// 'localize_data'	=> array(),
			'in_footer'		=> true,
			'enqueue'		=> true,
		) );
	}

	/**
	 * Loads the required scripts in frontend.
	 * If `$handle` is set.
	 *
	 * Overwrite this method if other dependencies or localize_data needed.
	 * @since  	0.7.0
	 */
	public function enqueue_script_frontend() {
		$handle = $this->get_handle( 'script_frontend' );
		if ( ! $handle || ! method_exists( $this->project_class_name, 'register_script' ) )
			return;
		$this->project_class_name::get_instance()->register_script( array(
			'handle'		=> $handle,
			// 'deps'			=> array(),
			// 'localize_data'	=> array(),
			'in_footer'		=> true,
			'enqueue'		=> true,
		) );
	}

	/**
	 * Loads the required styles in admin.
	 * If `$handle` is set.
	 *
	 * Overwrite this method if other dependencies or media needed.
	 * @since  	0.7.0
	 */
	public function enqueue_style_admin() {
		$handle = $this->get_handle( 'style_admin' );
		if ( ! $handle || ! method_exists( $this->project_class_name, 'register_style' ) )
			return;

		$this->project_class_name::get_instance()->register_style( array(
			'handle'	=> $handle,
			'deps'		=> array( 'wp-edit-blocks' ),
			// 'media'		=> 'all',
			'enqueue'	=> true,
		) );
	}

	/**
	 * Loads the required styles in frontend.
	 * If `$handle` is set.
	 *
	 * Overwrite this method if other dependencies or media needed.
	 * @since  	0.7.0
	 */
	public function enqueue_style_frontend() {
		$handle = $this->get_handle( 'style_frontend' );
		if ( ! $handle || ! method_exists( $this->project_class_name, 'register_style' ) )
			return;

		$this->project_class_name::get_instance()->register_style( array(
			'handle'	=> $handle,
			// 'deps'		=> array(),
			// 'media'		=> 'all',
			'enqueue'	=> true,
		) );
	}

	/**
	 * @since  	0.7.0
	 */
	protected function get_handle( $key ) {
		$handles = $this->handles;
		if ( array_key_exists( $key, $handles ) ) {
			return $handles[$key];
		}
		return false;
	}

	/**
	 * Receives an Block Attributes array, decodes stringified_values and returns new attributes array
	 *
	 * @param array $attributes             Block Attributes
	 * @param array $stringified_values     List of keys
	 * @return array        $decoded_attributes     Decoded Block Attributes
	 * @since  	0.7.0
	 */
	public static function decode_attributes( $attributes = array(), $stringified_values = array() ) {
		foreach ( $stringified_values as $key ) {
			if ( array_key_exists( $key, $attributes ) && 'string' === gettype( $attributes[ $key ] ) && ! empty( $attributes[ $key ] ) ) {
				$value = json_decode( $attributes[ $key ], true ) !== null ? json_decode( $attributes[ $key ], true ) : false;
				if ( $value ) {
					$attributes[ $key ] = $value;
				} else {
					unset( $attributes[ $key ] );
				}
			}
		}
		return $attributes;
	}

	/**
	 * Check if a post has one or more specific blocks.
	 *
	 * @param WP_Post|numeric	$post      			The Post id or object to check.
	 * @param array|string		$block_type_names 	Block type name or array of block type names including namespace
	 * @return boolean
	 * @since  	0.7.0
	 */
	public static function post_has_block( $post = null, $block_type_names = array() ){

		if ( null === $post )
			return false;

		if ( is_numeric( $post ) )
			$post = get_post( $post );

		if ( ! $post instanceof \WP_Post || empty( $post->post_content ) )
			return false;

		if ( is_string( $block_type_names ) )
			$block_type_names = array( $block_type_names );

		foreach( $block_type_names as $block_type_name ) {
			$block_pattern = (
				'/<!--\s+wp:(' .
				str_replace( '/', '\/',                 // Escape namespace, not handled by preg_quote.
					preg_quote( $block_type_name )
				) .
				')(\s+(\{.*?\}))?\s+(\/)?-->/'
			);
			if ( preg_match( $block_pattern, $post->post_content, $block_matches ) === 1 )
				return true;
		}
		return false;
	}

}

