<?php

namespace croox\wde\utils;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Color utility class
 *
 * Contains static utility methods for color operations.
 *
 * @package  wde
 */
class Color {

	/**
	* Convert HEX to RGB colors
	*
	* @see https://stackoverflow.com/questions/15202079/convert-hex-color-to-rgb-values-in-php#answer-31934345
	* @since 0.8.0
	* @param string  	$hex
	* @param int  		$alpha
	* @param string  	$return		return type
	* @return string|array
	*/
	public static function hex_to_rgb( $hex, $alpha = 1, $return = 'array' ) {
		$hex      = str_replace('#', '', $hex);
		$length   = strlen($hex);
		$rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
		$rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
		$rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
		if ( $alpha ) {
			$rgb['a'] = $alpha;
		}

		switch( $return ) {
		case 'string':
			return implode(array_keys($rgb)) . '(' . implode(',', $rgb) . ')';
		default:
			return $rgb;
		}
	}


	/**
	* Convert HSL to HEX colors
	*
	* @see https://github.com/WordPress/twentynineteen/blob/b604f127c2cae10bd48bbbec0fbbbff2cd31f957/inc/template-functions.php#L345
	* @since 0.2.0
	* @param int  		$h
	* @param int  		$s
	* @param bool  	$to_hex
	* @return string   example: rgb(255, 255, 255)
	*/
	public static function hsl_to_hex( $h, $s, $l, $to_hex = true ) {

		$h /= 360;
		$s /= 100;
		$l /= 100;

		$r = $l;
		$g = $l;
		$b = $l;
		$v = ( $l <= 0.5 ) ? ( $l * ( 1.0 + $s ) ) : ( $l + $s - $l * $s );
		if ( $v > 0 ) {
			$m;
			$sv;
			$sextant;
			$fract;
			$vsf;
			$mid1;
			$mid2;

			$m = $l + $l - $v;
			$sv = ( $v - $m ) / $v;
			$h *= 6.0;
			$sextant = floor( $h );
			$fract = $h - $sextant;
			$vsf = $v * $sv * $fract;
			$mid1 = $m + $vsf;
			$mid2 = $v - $vsf;

			switch ( $sextant ) {
				case 0:
					$r = $v;
					$g = $mid1;
					$b = $m;
					break;
				case 1:
					$r = $mid2;
					$g = $v;
					$b = $m;
					break;
				case 2:
					$r = $m;
					$g = $v;
					$b = $mid1;
					break;
				case 3:
					$r = $m;
					$g = $mid2;
					$b = $v;
					break;
				case 4:
					$r = $mid1;
					$g = $m;
					$b = $v;
					break;
				case 5:
					$r = $v;
					$g = $m;
					$b = $mid2;
					break;
			}
		}
		$r = round( $r * 255, 0 );
		$g = round( $g * 255, 0 );
		$b = round( $b * 255, 0 );

		if ( $to_hex ) {

			$r = ( $r < 15 ) ? '0' . dechex( $r ) : dechex( $r );
			$g = ( $g < 15 ) ? '0' . dechex( $g ) : dechex( $g );
			$b = ( $b < 15 ) ? '0' . dechex( $b ) : dechex( $b );

			return "#$r$g$b";

		}

		return "rgb($r, $g, $b)";
	}

	/**
	 * Build editor_gradient_presets array from associative editor_color_palette array
	 *
	 * @since 0.8.0
	 * @param array	$gradients				Descriptsion of gradients. example: array(
												array(
													'type' => 'linear',
													'deg' => '90',
													'steps' => array(
														// 		slug 	alpha 	step
														array( 'white',		1,	0 ),
														array( 'black', 	1, 	100 ),
													),
												),
											);
	 * @param array	$editor_color_palette	The editor_color_palette array has to be associative. Each item requires a key equal to the items slug.
	 * @return array						The editor_gradient_presets array
	 */
	public static function build_editor_gradient_presets( $gradients, $editor_color_palette ) {
		return array_map( function( $gradient ) use ( $editor_color_palette ) {
			// parse defaults
			$gradient = wp_parse_args( $gradient, array(
				'type' => 'linear',
				'deg' => '90',
			) );
			// fill steps with color values from editor_color_palette
			$gradient['steps'] = array_map( function( $step ) use ( $editor_color_palette ) {
				return array_merge( $editor_color_palette[$step[0]], array(
					'alpha'	=> $step[1],
					'step'	=> $step[2],
				) );
			}, $gradient['steps'] );
			// build the gradient preset
			return array(
				'name'     => implode( ' ' . __( 'to', 'ruem' ) . ' ', array_map( function( $step ) { return $step['name']; }, $gradient['steps'] ) ),
				'slug'     => implode( '--', array_map( function( $step ) { return $step['slug']; }, $gradient['steps'] ) )  . '-' . $gradient['type'],
				'gradient' => $gradient['type'] . '-gradient(' . ( 'linear' === $gradient['type'] ? $gradient['deg'] . 'deg,' : '' ) . implode( ',', array_map( function( $step ) {
					return static::hex_to_rgb( $step['color'], $step['alpha'], 'string' ) . ' ' . $step['step'] . '%';
				}, $gradient['steps'] ) ) . ')',
			);
		}, $gradients );
	}

	/**
	 * Build inline css for background gradients.
	 * Generic classes for each item in editor_gradient_presets
	 *
	 * @since 0.8.0
	 * @param array	$editor_gradient_presets	As used with add_theme_support 'editor-gradient-presets'
	 * @return string							Css string to be used in wp_add_inline_style
	 */
	public static function build_gradient_presets_css( $editor_gradient_presets ) {
		return implode( PHP_EOL, array_map( function( $grad_preset ) {
			return ".has-{$grad_preset['slug']}-gradient-background {
				background-image: {$grad_preset['gradient']};
			}";
		}, $editor_gradient_presets ) );
	}

}
