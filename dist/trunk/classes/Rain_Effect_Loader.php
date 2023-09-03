<?php

namespace rain;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use croox\wde\utils\Attachment;

class Rain_Effect_Loader {

	protected static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	protected function __construct( $args = NULL ) {
		add_action( 'wp_ajax_rain_thumbnail', array( $this, 'ajax_thumbnail' ) );
		add_action( 'wp_ajax_nopriv_rain_thumbnail', array( $this, 'ajax_thumbnail' ) );
	}

	public function hook_scripts(){
		add_action( 'customize_preview_init', array( $this, 'apply_effect' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'apply_effect' ) );
	}

	public function apply_effect(){
        $handle = __NAMESPACE__ . '_effect_loader';
        Rain::get_instance()->register_script( array(
			'handle'	=> $handle,
			'localize_data'		=> array(
				'ajaxurl'	=>	admin_url( 'admin-ajax.php' ),
				'images'	=>	array(
					'dropShine'	=> Rain::get_instance()->dir_url . '/images/drop-shine2.png',
					'dropAlpha'	=> Rain::get_instance()->dir_url . '/images/drop-alpha.png',
					'dropColor'	=> Rain::get_instance()->dir_url . '/images/drop-color.png',
				)
			),
			'deps'		=> array(
				'jquery',
				// 'wp-hooks',
				// 'wp-api',
				// 'wp-data',
				// 'wp-i18n',
			),
			'in_footer'	=> true,	// default false
			'enqueue'	=> true,
		) );
	}

	protected function _ajax_return( $response = true ) {
		echo json_encode( $response );
		exit;
	}

	public function ajax_thumbnail() {

		if ( ! array_key_exists( 'srcFull', $_POST ) || ! is_string( $_POST['srcFull']) )
			$this->_ajax_return( new \WP_Error( 'rain-effect-something-missing', __( 'rain-effect-something-missing ???', 'rain-effect' ) ) );

		$url = $_POST['srcFull'];

		$attachment_id = Attachment::get_id_by_url( $url );

		if ( $attachment_id === 0 )
			$this->_ajax_return( new \WP_Error( 'rain-effect-thumbnail-not-found', __( 'rain-effect-thumbnail-not-found ???', 'rain-effect' ) ) );

		$srcThumbnail = wp_get_attachment_image_src( $attachment_id, 'thumbnail', false )[0];

		if ( strpos( $url, 'https' ) === 0 ){
			// if url starts with 'https'
			$from = '/'.preg_quote( 'http', '/').'/';
			$srcThumbnail = strpos( $srcThumbnail, 'https' ) !== 0 ? preg_replace( $from, 'https', $srcThumbnail, 1) : $srcThumbnail;
		} elseif ( strpos( $url, 'http' ) === 0 ){
			// if url starts with 'http'
			$from = '/'.preg_quote( 'https', '/').'/';
			$srcThumbnail = strpos( $srcThumbnail, 'https' ) !== 0 ? preg_replace( $from, 'http', $srcThumbnail, 1) : $srcThumbnail;
		}
		$response = array(
			'srcThumbnail' => $srcThumbnail
		);

		$this->_ajax_return( $response );
	}

}
