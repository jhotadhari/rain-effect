<?php

namespace croox\wde\utils;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Attachment utility class
 *
 * Contains static utility methods for Attachments.
 *
 * @package  wde
 */
class Attachment {

	/**
	 * Return an ID of an attachment by searching the database with the file URL.
	 *
	 * First checks to see if the $url is pointing to a file that exists in
	 * the wp-content directory. If so, then we search the database for a
	 * partial match consisting of the remaining path AFTER the wp-content
	 * directory. Finally, if a match is found the attachment ID will be
	 * returned.
	 *
	 * since 0.1.0
	 *
	 * @see https://frankiejarrett.com/2013/05/get-an-attachment-id-by-url-in-wordpress/
	 * @param string $url The URL of the image (ex: http://mysite.com/wp-content/uploads/2013/05/test-image.jpg)
	 * @return int|null $attachment Returns an attachment ID, or null if no attachment is found
	 */
	public static function get_id_by_url( $url ) {
		// Split the $url into two parts with the wp-content directory as the separator
		$parsed_url = explode( parse_url( WP_CONTENT_URL, PHP_URL_PATH ), $url );

		// Get the host of the current site and the host of the $url, ignoring www
		$this_host = str_ireplace( 'www.', '', parse_url( home_url(), PHP_URL_HOST ) );
		$file_host = str_ireplace( 'www.', '', parse_url( $url, PHP_URL_HOST ) );

		// Return nothing if there aren't any $url parts or if the current host and $url host do not match
		if ( ! isset( $parsed_url[1] ) || empty( $parsed_url[1] ) || ( $this_host != $file_host ) ) {
			return;
		}

		// Now we're going to quickly search the DB for any attachment GUID with a partial path match
		// Example: /uploads/2013/05/test-image.jpg
		global $wpdb;

		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->prefix}posts WHERE guid RLIKE %s;", $parsed_url[1] ) );

		// Returns null if no attachment is found
		return $attachment[0];
	}

	/**
	 * Get an HTML img element for given url
	 *
	 * If attachment is found for given url,
	 * `wp_get_attachment_image` will be used to get the html tag (uses `$size` and `$attr`),
 	 * otherwise url is wrapped in img tag with given atts.
	 *
	 * @param string $url		The URL of the image (ex: http://mysite.com/wp-content/uploads/2013/05/test-image.jpg)
	 * @param string $size 		(Optional) Image size
	 * @param array $attr 		(Optional) Attributes for the image markup.
	 * @return string 			HTML img element or empty string on failure.
	 */
	public static function get_image_by_url( $url, $size = 'thumbnail', $attr = array() ){
		if ( strlen( $url ) === 0 )
			return '';

		$id = static::get_id_by_url( $url );

		if ( is_int( $id ) ) {
			$image = wp_get_attachment_image( $id, $size, $attr )[0];
		} else {
			$attr = array_map( 'esc_attr', $attr );
			$image = '<img';
			$image .= ' src="' . esc_url( $url ) . '"';
			foreach ( $attr as $name => $value ) {
				$image .= ' ' . $name . '=' . '"' . $value . '"';
			}
			$image .= ' />';
		}

		return $image;
	}

}
