<?php

namespace croox\wde;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use croox\wde\utils\Arr;

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

	protected $attributes;

	protected $handles = array();

	protected $hook_priorities = array();

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
		$this->set_hook_priorities();
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

		$this->attributes = array();			// Define block attributes here and they'll be localized.
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

	protected function set_hook_priorities() {
		$this->hook_priorities = array(
			'style_admin'     => 10,
			'script_admin'    => 10,
			'style_frontend'  => 10,
			'script_frontend' => 10,
		);
	}

	/**
	 * Initiate our hooks
	 * @since  	0.7.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'register_block' ) );

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_style_admin' ), Arr::get( $this->hook_priorities, 'style_admin', 10 ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_script_admin' ), Arr::get( $this->hook_priorities, 'script_admin', 10 ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style_frontend' ), Arr::get( $this->hook_priorities, 'style_frontend', 10 ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script_frontend' ), Arr::get( $this->hook_priorities, 'script_frontend', 10 ) );
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

			if ( $handle = Arr::get( $this->handles, 'script_admin', false ) )
				$args = array_merge( $args, array( 'editor_script' => $handle ) );

			if ( $handle = Arr::get( $this->handles, 'style_admin', false ) )
				$args = array_merge( $args, array( 'editor_style' => $handle ) );

			if ( $handle = Arr::get( $this->handles, 'script_frontend', false ) )
				$args = array_merge( $args, array( 'script' => $handle ) );

			if ( $handle = Arr::get( $this->handles, 'style_frontend', false ) )
				$args = array_merge( $args, array( 'style' => $handle ) );

			if ( method_exists( $this, 'render' ) )
				$args = array_merge( $args, array( 'render_callback' => array( $this, 'render' ) ) );

			if ( property_exists( $this, 'attributes' ) && $this->attributes && ! empty( $this->attributes ) )
				$args = array_merge( $args, array( 'attributes' => $this->attributes ) );

			if ( property_exists( $this, 'supports' ) && $this->supports && ! empty( $this->supports ) ) {
				$args = array_merge( $args, array( 'supports' => $this->supports ) );
			} else {
				$args = array_merge( $args, array( 'supports' => array() ) );
			}

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
		$handle = Arr::get( $this->handles, 'script_admin', false );
		if ( ! $handle || ! method_exists( $this->project_class_name, 'register_script' ) )
			return;

		$args = array(
			'handle'		=> $handle,
			'deps'			=> $this->get_script_deps_editor(),
			'in_footer'		=> true,
			'enqueue'		=> true,
			'localize_data'	=> $this->get_localize_data_editor(),
		);

		$this->project_class_name::get_instance()->register_script( $args );
	}

	/**
	 * Loads the required scripts in frontend.
	 * If `$handle` is set.
	 *
	 * Overwrite this method if other dependencies or localize_data needed.
	 * @since  	0.7.0
	 */
	public function enqueue_script_frontend() {
		$handle = Arr::get( $this->handles, 'script_frontend', false );
		if ( ! $handle || ! method_exists( $this->project_class_name, 'register_script' ) )
			return;
		$this->project_class_name::get_instance()->register_script( array(
			'handle'		=> $handle,
			'deps'			=> $this->get_script_deps_frontend(),
			'localize_data'	=> $this->get_localize_data_frontend(),
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
		$handle = Arr::get( $this->handles, 'style_admin', false );
		if ( ! $handle || ! method_exists( $this->project_class_name, 'register_style' ) )
			return;

		$this->project_class_name::get_instance()->register_style( array(
			'handle'	=> $handle,
			'deps'		=> $this->get_style_deps_editor(),
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
		$handle = Arr::get( $this->handles, 'style_frontend', false );
		if ( ! $handle || ! method_exists( $this->project_class_name, 'register_style' ) )
			return;

		$this->project_class_name::get_instance()->register_style( array(
			'handle'	=> $handle,
			'deps'		=> $this->get_style_deps_frontend(),
			// 'media'		=> 'all',
			'enqueue'	=> true,
		) );
	}

	protected function get_script_deps_editor( $deps = array() ) {
		$prefix = $this->project_class_name::get_instance()->prefix;

		$deps = array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
			'wp-edit-post'
		);

		return apply_filters( "{$prefix}_block_{$this->name}_script_deps_editor", $deps );
	}

	protected function get_localize_data_editor( $localize_data = array() ) {
		$prefix = $this->project_class_name::get_instance()->prefix;

		if ( property_exists( $this, 'attributes' ) && $this->attributes && ! empty( $this->attributes ) ) {
			$localize_data = array_merge( $localize_data, array( 'attributes'	=> $this->attributes ) );
		}

		if ( property_exists( $this, 'supports' ) && $this->supports && ! empty( $this->supports ) ) {
			$localize_data = array_merge( $localize_data, array( 'supports'	=> $this->supports ) );
		} else {
			$localize_data = array_merge( $localize_data, array( 'supports'	=> array() ) );
		}

		return apply_filters( "{$prefix}_block_{$this->name}_localize_data_editor", $localize_data );
	}

	protected function get_script_deps_frontend( $deps = array() ) {
		$prefix = $this->project_class_name::get_instance()->prefix;
		return apply_filters( "{$prefix}_block_{$this->name}_script_deps_frontend", $deps );
	}

	protected function get_localize_data_frontend( $localize_data = array() ) {
		$prefix = $this->project_class_name::get_instance()->prefix;
		return apply_filters( "{$prefix}_block_{$this->name}_localize_data_frontend", $localize_data );
	}

	protected function get_style_deps_editor( $deps = array() ) {
		$prefix = $this->project_class_name::get_instance()->prefix;
		$deps = array(
			'wp-edit-blocks'
		);
		return apply_filters( "{$prefix}_block_{$this->name}_style_deps_editor", $deps );
	}

	protected function get_style_deps_frontend( $deps = array() ) {
		$prefix = $this->project_class_name::get_instance()->prefix;
		return apply_filters( "{$prefix}_block_{$this->name}_style_deps_frontend", $deps );
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

