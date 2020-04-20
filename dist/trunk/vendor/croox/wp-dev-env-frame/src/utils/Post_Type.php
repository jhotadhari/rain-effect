<?php

namespace croox\wde\utils;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Post_Type utility class
 *
 * Contains static utility methods for Post_Types.
 *
 * @package  wde
 */
class Post_Type {

	/**
	 * Returns an array of post types
	 *
	 * Includes 'post', 'page' and all custom post types except 'wp_block'
	 *
	 * since 0.1.0
	 *
	 * @param string $return_type        'array_keys'|'array_key_label' default: 'array_keys'.
	 * @param array  $exclude            Post Type keys to exclude
	 * @return depending on $return_type, an array of post type keys or an associative array with key => label
	 */
	public static function get_all( $return_type = 'array_keys', $exclude = array() ) {

		$exclude = array_merge( $exclude, array( 'wp_block' ) );

		switch ( $return_type ) {
			case 'array_keys':
				$post_types = array( 'post', 'page' );

				foreach ( get_post_types( array( '_builtin' => false ), 'names' ) as $post_type ) {
					array_push( $post_types, $post_type );
				}

				return array_filter(
					$post_types,
					function( $val ) use ( $exclude ) {
						return ( in_array( $val, $exclude ) ? false : true );
					}
				);
				break;
			case 'array_key_label':
				$post_types = array(
					'post' => __( 'Post', 'expa' ),
					'page' => __( 'Page', 'expa' ),
				);

				foreach ( get_post_types( array( '_builtin' => false ), 'objects' ) as $post_type ) {
					$post_types[ $post_type->name ] = __( $post_type->labels->name, 'expa' );
				}

				return array_filter(
					$post_types,
					function( $key ) use ( $exclude ) {
						return ( in_array( $key, $exclude ) ? false : true );
					},
					ARRAY_FILTER_USE_KEY
				);
				break;
		};

	}

}
