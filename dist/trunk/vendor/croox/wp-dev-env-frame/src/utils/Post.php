<?php

namespace croox\wde\utils;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Post utility class
 *
 * Contains static utility methods for Posts.
 *
 * @package  wde
 */
class Post {

	/**
	 * Returns post for given slug
	 *
	 * since 0.1.0
	 *
	 * @param string       $slug       post slug
	 * @param string|array $post_type  include only certain post_type or array of post_types
	 * @return mixed            the post on success or false
	 */
	public static function get_by_slug( $slug = null, $post_type = null, $args = array() ) {

		if ( null === $slug ) {
			return false;
		}

		$posts = get_posts(
			array_merge( array(
				'name'           => $slug,
				'posts_per_page' => 1,
				'post_type'      => null === $post_type ? Post_Type::get_all() : $post_type,
			), $args )
		);

		return $posts && ! empty( $posts ) ? $posts[0] : false;

	}

}
