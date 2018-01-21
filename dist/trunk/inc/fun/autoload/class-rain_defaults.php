<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Rain_defaults {


	protected $defaults = array();

	public function add_default( $arr ){
		$defaults = $this->defaults;
		$this->defaults = array_merge( $defaults , $arr);
	}
	
	public function get_default( $key ){
		if ( array_key_exists($key, $this->defaults) ){
			return $this->defaults[$key];

		}
			return null;
	}


}

function rain_init_defaults(){
	global $rain_defaults;
	
	$rain_defaults = new Rain_defaults();
	
	// $defaults = array(
	// 	// silence ...
	// );
	
	// $rain_defaults->add_default( $defaults );	
}
add_action( 'admin_init', 'rain_init_defaults', 1 );
add_action( 'init', 'rain_init_defaults', 1 );



?>