<?php

namespace croox\wde\utils;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Wpml utility class
 *
 * Contains static utility methods to work with wpml.
 *
 * @package  wde
 */
class Wpml {

	/**
	 * Wrapper around `update_post_meta` to update the value of a meta key
	 * for all wpml translations for the specified post.
	 *
	 * @since 0.4.0
	 * @param object	$post			Object of post which contains the field you will edit.
	 * @param string	$meta_key		The key of the custom field you will edit.
	 * @param string	$meta_value		The key of the custom field you will edit.
	 * @return mixed       				If post has translations, returns an array of `update_post_meta` return values,
	 *									otherwise returns the return value of `update_post_meta`.
	 */
	public static function update_post_meta( $post, $meta_key, $meta_value ) {
		$element_type = 'post_' . $post->post_type;
		$trid = apply_filters( 'wpml_element_trid', NULL, $post->ID , $element_type );

		if ( null === $trid ) {
			return update_post_meta( $post->ID, $meta_key, $meta_value );
		} else {
			$return = array();
			$translations = apply_filters( 'wpml_get_element_translations', NULL, $trid, $element_type );
			foreach( $translations as $lang => $translation ) {
				$return[$lang] = update_post_meta( $translation->element_id, $meta_key, $meta_value );
			}
			return $return;
		}
	}

	/**
	* Setup hooks to switch wpml global language for taxonomy and post rest requests.
	*
	* Hooks into taxonomy and post_type registration filter
	* to hook `self::_rest_query_switch_lang` into each `rest_{$object}_query` filter.
	* The `rest_{$object}_query` filter has access to the request.
	* The language will be determinated based on the requests referer language.
	*
	* Fix this issue https://wpml.org/forums/topic/gutenberg-editor-sidebar-taxonomy-panels-load-wrong-language/
	*
	* Should be hooked early. Before post_types or taxonomies are registered.
	* Themes can hook this into: `after_setup_theme`.
	* Plugins can hook this into: `plugins_loaded`.
	*
	* @since 0.5.1
	*/
	public static function rest_setup_switch_lang() {

		// taxonomies, hook `self::_rest_query_switch_lang` into rest_{$taxonomy}_query
		add_filter( 'register_taxonomy_args', function( $args, $taxonomy, $object_type ) {
			// // taxonomies will be registered:
			// category
			// post_tag
			// nav_menu
			// link_category
			// post_format
			// translation_priority
			// ... plus custom

			$exclude = array(
				'translation_priority',
			);

			if ( ! in_array( $taxonomy, $exclude ) ) {
				add_filter( "rest_{$taxonomy}_query", array( __CLASS__, '_rest_query_switch_lang' ), 1, 2 );
			}
			return $args;
		}, 10, 3 );

		// post_types, hook `self::_rest_query_switch_lang` into rest_{$post_type}_query
		add_filter( 'register_post_type_args', function( $args, $post_type ) {
			// // post_types will be registered:
			// post
			// page
			// attachment
			// revision
			// nav_menu_item
			// custom_css
			// customize_changeset
			// oembed_cache
			// user_request
			// wp_block
			// ... plus custom

			$exclude = array(
				'revision',
				'nav_menu_item',
				'custom_css',
				'customize_changeset',
				'oembed_cache',
				'user_request',
				'wp_block',
			);

			if ( ! in_array( $post_type, $exclude ) ) {
				add_filter( "rest_{$post_type}_query", array( __CLASS__, '_rest_query_switch_lang' ), 1, 2 );
			}
			return $args;
		}, 10, 2 );

	}

	/**
	* Switches wpml global language before the rest query is set up.
	*
	* Hooked by `self::rest_setup_switch_lang`
	*
	* @since 0.5.1
	* @param array             $args  		Array of arguments to be passed to query.
	* @param WP_REST_Request   $request     The current request.
	* @return array            $args 		Returns $args unchanged.
	*/
	public static function _rest_query_switch_lang( $args, $request ) {
		$params = $request->get_params();

		// Do not switch language, if lang parameter already set
		if ( array_key_exists( 'lang', $params )
			|| array_key_exists( 'wpml_language', $params )
		) {
			return $args;
		}

		self::_rest_request_switch_lang( $request );
		return $args;
	}

	/**
	 * Switches wpml global language to the requests referer language.
	 *
	 * @todo ??? Doesn't work if a different domain is used per language.
	 * @since 0.5.1
	 * @param WP_REST_Request	$request	The request used.
	 */
	public static function _rest_request_switch_lang( $request ){

		$language_negotiation_type = apply_filters( 'wpml_setting', false, 'language_negotiation_type' );
		// '1': Different languages in directories.
		// '2': A different domain per language.
		// '3': Language name added as a parameter.

		$referer_url = Arr::get( $request->get_headers(), 'referer.0', false );
		if ( ! $referer_url ) { // probably internal request
			return;
		}

		$referer_parsed = parse_url( $referer_url );
		// Use path after site_url, instead of parsed url path.
		$referer_path_arr = array_values( array_filter(
			explode(
				'/',
				str_replace( site_url(), '', $referer_url
			) ),
			'strlen'
		) );
		$is_admin = 'wp-admin' === $referer_path_arr[0];

		if ( $is_admin || '3' === $language_negotiation_type) {
			if ( ! array_key_exists( 'query', $referer_parsed ) )
				return;	// do nothing

			$query = array();
			parse_str( $referer_parsed['query'], $query );
			if ( array_key_exists( 'lang', $query ) ) {
				return do_action( 'wpml_switch_language', $query['lang'] );
			}
		}

		if ( '2' === $language_negotiation_type ) {
			/// ??? missing
			return;	// do nothing
		}

		if ( '1' === $language_negotiation_type ) {
			$maybe_lang = $referer_path_arr[0];
			$active_langs = array_map( function( $lang ) {
				return $lang['language_code'];
			}, apply_filters( 'wpml_active_languages', NULL, array( 'skip_missing' => 0 ) ) );

			if ( in_array( $maybe_lang, $active_langs ) ) {
				return do_action( 'wpml_switch_language', $maybe_lang );
			}
		}

		if ( ! $is_admin ) {
			return do_action( 'wpml_switch_language', apply_filters( 'wpml_default_language', NULL ) );
		}

	}

}
