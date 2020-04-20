<?php

namespace croox\wde;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Abstract base class to register meta fields to existing post_types
 * and make the meta data accessable to the REST API.
 *
 * The `api_field_update_cb` method should be overwritten, but is not mandatory.
 *
 * The `api_register_meta` method is the default method to register
 * the meta field for all $this->post_types.
 * To use a different method for a certain field,
 * add a method and name it `api_register_meta_{$field['title']['key']}`.
 *
 * @example: To use this Class overwrite it, define a function to initialize and call the function.
 *	class mynamescpace\Register_Event_Meta extends croox\wde\Register_Post_Meta {
 *		public function api_field_update_cb( $value, $object, $field_name ) {
 *			switch( $field_name ) {
 *				case 'myprefix_start_date':
 *				case 'myprefix_end_date':
 *					return $this->field_update__string( $value, $object, $field_name );
 *				default:
 *					return false;
 *			}
 *		}
 *	}
 *	function myprefix_register_event_meta_init() {
 *		return mynamescpace\Register_Event_Meta::get_instance( array(
 *			'post_types' => array( 'myprefix_event' ),
 *			'fields' => array(
 *				array(
 *					'title' => array(
 *						'key' => 'myprefix_start_date',
 *					),
 *				),
 *				array(
 *					'title' => array(
 *						'key' => 'myprefix_end_date',
 *					),
 *				)
 *			),
 *		) );
 *	}
 *	myprefix_register_event_meta_init();
 *
 * @package  wde
 */
abstract class Register_Post_Meta {

	private static $_instances = array();

	public static function get_instance( $init_args = array() ) {
		$class = get_called_class();
		if ( ! isset( self::$_instances[ $class ] ) ) {
			$new_class                  = new $class( $init_args );
			self::$_instances[ $class ] = $new_class;
			$new_class->hooks();
		}
		return self::$_instances[ $class ];
	}

	protected $post_types = array();

	protected $fields = array();

	/**
	 * Constructor
	 *
	 * @param array	$init_args		Arguments to init. Holds arrays of `post_types` and `fields`
	 * 								Requires`['fields']['title']['key']` and `['post_types']` to be set.
	 *								Example:
	 *									array(
	 *										'post_types' => array( 'post', 'page' ),
	 *										'fields' => array(
	 *											array(
	 *												'title' => array(
	 *													'key' => 'myprefix_color',	// required
	 *													'val' => 'Color',			// defaults to value of`key`
	 *												),
	 *												'schema' => array(
	 *													'type' => 'string',			// defaults to `'string'`
	 *												),
	 *											),
	 *											// shorter
	 *											array(
	 *												'title' => array(
	 *													'key' => 'myprefix_size',
	 *												),
	 *												// ['schema']['type'] defaults to `'string'` anyway
	 *											)
	 *										)
	 *									);
	 * @since 0.5.0
	 */
	function __construct( $init_args ) {

		if ( array_key_exists( 'post_types', $init_args ) )
			$this->set_post_types( $init_args['post_types'] );

		if ( array_key_exists( 'fields', $init_args ) )
			$this->add_fields( $init_args['fields'] );

	}

	/**
	 * Initiate our hooks
	 */
	public function hooks() {
		add_action( 'rest_api_init', array( $this, 'fields_call_register' ) );
	}

	/**
	 * Set the Post Types for which the meta fields will be registered.
	 *
	 * @param array	$post_types		???
	 * @since 0.5.0
	 */
	protected function set_post_types( $post_types = array() ) {
		if ( ! is_array( $post_types ) ) {
			error_log( '$post_types must be array in croox\wde\Register_Post_Meta::set_post_types' );
			$post_types = array();
		}
		$this->post_types = $post_types;
	}

	/**
	 * Add fields to be registered.
	 *
	 * @param array	$fields		???
	 * @since 0.5.0
	 */
	protected function add_fields( $fields = array() ) {
		if ( ! is_array( $fields ) ) {
			error_log( '$fields must be array in croox\wde\Register_Post_Meta::add_fields' );
			$fields = array();
		}

		foreach( $fields as $new_field ) {
			array_push( $this->fields, $new_field );
		}
	}

	/**
	 * Checks for fields, loops them and calls the methods to register each meta field.
	 *
	 * By default method `api_register_meta` will be called.
	 * Extend this class and provide a method with name `api_register_meta_{$field['title']['key']}`
	 * to use an alternative register method for this certain field.
	 *
	 * @since 0.5.0
	 */
	public function fields_call_register(){
		if ( ! empty( $this->fields ) ) {
			foreach( $this->fields as $field ){

				if ( ! empty( utils\Arr::get( $field, 'title.key' ) ) ) {
					$register_method_name = 'api_register_meta' . '_' . $field['title']['key'];
					if ( method_exists ( $this, $register_method_name ) ) {
						call_user_func_array( array( $this, $register_method_name ), array( $field ) );
					} else {
						$this->api_register_meta( $field );
					}
				}

			}
		}
	}

	/**
	 * Default method to register the meta field for all $this->post_types.
	 *
	 * @param array	$field		???
	 * @since 0.5.0
	 */
	protected function api_register_meta( $field ){

		$schema = utils\Arr::get( $field, 'schema', array() );
		if ( ! array_key_exists( 'description', $schema ) )
			$schema['description'] = utils\Arr::get(
				$field,
				'title.val',
				utils\Arr::get( $field, 'title.key' )	// fallback to key
			);
		if ( ! array_key_exists( 'context', $schema ) )
			$schema['context'] = array( 'view', 'edit' );
		if ( ! array_key_exists( 'type', $schema ) )
			$schema['type'] = 'string';


		foreach( $this->post_types as $post_type ) {
			register_rest_field(
				$post_type,
				$field['title']['key'],
				array(
					'get_callback'      => array( $this, 'api_field_get_cb' ),
					'update_callback'   => array( $this, 'api_field_update_cb' ),
					'schema'            => $schema
				)
			);
		}
	}

	/**
	 * Default field get_callback
	 *
	 * @param array	$object			???
	 * @param array	$field_name		???
	 * @param array	$request		???
	 * @since 0.5.0
	 * @return mixed				The post meta value.
	 */
	public function api_field_get_cb( $object, $field_name, $request ) {
		switch( $field_name ) {
			default:
				return get_post_meta( $object['id'], sanitize_key( $field_name ), true );
		}
	}

	/**
	 * Field update_callback. Calls an update method depending on $field_name.
	 *
	 * Overwride this method and use the switch statement to
	 * call different update methods for different field_names.
	 *
	 * @param array	$value			???
	 * @param array	$object			???
	 * @param array	$field_name		???
	 * @since 0.5.0
	 */
	public function api_field_update_cb( $value, $object, $field_name ) {
		switch( $field_name ) {
			default:
				return $this->field_update__string( $value, $object, $field_name );
		}
	}

	/**
	 * Default update method for string values.
	 * Just updates the post_meta with the new Value.
	 *
	 * @param array	$value			???
	 * @param array	$object			???
	 * @param array	$field_name		???
	 * @since 0.5.0
	 */
	protected function field_update__string( $value, $object, $field_name ) {
		return update_post_meta( $object->ID, $field_name, $value );
	}


	/**
	 * Default update method for serialized json string values.
	 * Checks if valid json, and updates the post_meta with the decoded Value.
	 * WordPress will store the value as serialized php associative array.
	 *
	 * @param array	$value			???
	 * @param array	$object			???
	 * @param array	$field_name		???
	 * @since 0.5.0
	 */
	protected function field_update__serialized( $value, $object, $field_name ) {
		if ( json_decode( $value, true ) === null ) {
			error_log( 'Meta value for field ' . $field_name . ' is not valid json.' );
			error_log( 'Meta value: ' . var_dump( $value ) );
			return false;
		}
		return update_post_meta( $object->ID, $field_name, json_decode( $value, true ) );
	}

}
