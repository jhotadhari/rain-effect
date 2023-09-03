<?php

namespace croox\wde\utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Critical_Css_Loader {

	protected static $instance = null;

	protected $filename = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

    public function __construct() {
        add_action( 'init', array( $this, 'get_critical_css_filename' ), 10, 0 );
        add_action( 'wp_head', array( $this, 'print_inline_styles' ), 1, 1 );
        add_filter( 'style_loader_tag', array( $this, 'style_loader_tag' ), 999, 10, 4 );
    }

    public function get_critical_css_filename() {
        if ( ! is_admin() && null === $this->filename ) {
            $filename = implode( '/', array(
                WP_CONTENT_DIR,
                'critical_css',
                'critical' . ( wp_is_mobile() ? '-mobile' : '') . '.css',
            ) );
            $this->filename = file_exists( $filename )
                && false !== filesize( $filename )
                && filesize( $filename ) > 1
                    ? $filename
                    : false;
        }
    }

    public function print_inline_styles() {
        if (
            is_admin()
            || empty( $this->filename )
        ) {
            return;
        }
        $critical_css = file_get_contents( $this->filename );
        if ( $critical_css ) {
            echo implode( PHP_EOL, array(
                '<style>',
                $critical_css,
                '</style>',
                '',
            ) );
        }
    }

    public function style_loader_tag( $tag, $handle, $src, $media ) {
        if (
            is_admin()
            || empty( $this->filename )
            || 'print' === $media
            || false !== strpos( $tag, 'onload=' )
        ) {
            return $tag;
        }

        $tag_original = $tag;

        $new_tag = preg_replace(
            "/media='([^']+?)'/i",
            "media='print' onload='this.media=\"$1\"'",
            $tag_original
        );

        return implode( PHP_EOL, array(
            $new_tag,
            '<noscript>',
            $tag_original,
            '</noscript>',
        ) );
    }

}
