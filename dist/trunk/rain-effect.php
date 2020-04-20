<?php
/*
	Plugin Name: Rain Effect
	Plugin URI: https://github.com/jhotadhari/rain-effect
	Description: Let it rain
	Version: 0.1.0
	Author: jhotadhari
	Author URI: https://waterproof-webdesign.info
	License: GNU General Public License v2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
	Text Domain: rain-effect
	Domain Path: /languages
	Tags: rain,image
	GitHub Plugin URI: https://github.com/jhotadhari/rain-effect
	Release Asset: true
*/
?><?php
/**
 * Rain Effect Plugin init
 *
 * @package rain-effect
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

include_once( dirname( __FILE__ ) . '/vendor/autoload.php' );

function rain_init() {

	$init_args = array(
		'version'		=> '0.1.0',
		'slug'			=> 'rain-effect',
		'name'			=> 'Rain Effect',
		'prefix'		=> 'rain',
		'textdomain'	=> 'rain-effect',
		'project_kind'	=> 'plugin',
		'FILE_CONST'	=> __FILE__,
		'db_version'	=> 0,
		'wde'			=> array(
			'generator-wp-dev-env'	=> '0.14.2',
			'wp-dev-env-grunt'		=> '0.9.7',
			'wp-dev-env-frame'		=> '0.8.0',
		),
		'deps'			=> array(
			'php_version'	=> '5.6',		// required php version
			'wp_version'	=> '4.7',			// required wp version
			'plugins'    	=> array(
				/*
				'woocommerce' => array(
					'name'              => 'WooCommerce',               // full name
					'link'              => 'https://woocommerce.com/',  // link
					'ver_at_least'      => '3.0.0',                     // min version of required plugin
					'ver_tested_up_to'  => '3.2.1',                     // tested with required plugin up to
					'class'             => 'WooCommerce',               // test by class
					//'function'        => 'WooCommerce',               // test by function
				),
				*/
			),
			'php_ext'     => array(
				/*
				'xml' => array(
					'name'              => 'Xml',                                           // full name
					'link'              => 'http://php.net/manual/en/xml.installation.php', // link
				),
				*/
			),
		),
	);

	// see ./classes/Rain.php
	return rain\Rain::get_instance( $init_args );
}
rain_init();

?>