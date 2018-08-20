<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

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

		wp_register_script( 'rain_effect_loader', Rain_Rain_effect::plugin_dir_url() . '/js/rain_effect_loader.min.js', array( 'jquery', 'underscore' ), '20180118', true );


		$loc_data = array(
			'ajaxurl'	=>	admin_url( 'admin-ajax.php' ),
			'images'	=>	array(
				'dropShine'	=> Rain_Rain_effect::plugin_dir_url() . '/images/drop-shine2.png',
				'dropAlpha'	=> Rain_Rain_effect::plugin_dir_url() . '/images/drop-alpha.png',
				'dropColor'	=> Rain_Rain_effect::plugin_dir_url() . '/images/drop-color.png',
			)
		);

		wp_localize_script( 'rain_effect_loader', 'rain_localize', $loc_data );

		wp_enqueue_script( 'rain_effect_loader' );
	}

	protected function _ajax_return( $response = true ) {
		echo json_encode( $response );
		exit;
	}

	public function ajax_thumbnail() {

		if ( ! array_key_exists( 'srcFull', $_POST ) || ! is_string( $_POST['srcFull']) )
			$this->_ajax_return( new WP_Error( 'rain-effect-something-missing', __( 'rain-effect-something-missing ???', 'rain-effect' ) ) );

		$url = $_POST['srcFull'];
		$attachment_id = $this->get_attachment_id( $url );

		if ( $attachment_id === 0 )
			$this->_ajax_return( new WP_Error( 'rain-effect-thumbnail-not-found', __( 'rain-effect-thumbnail-not-found ???', 'rain-effect' ) ) );

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


	/**
	 * Get an attachment ID given a URL.
	 *
	 * https://wpscholar.com/blog/get-attachment-id-from-wp-image-url/
	 *
	 * @param string $url
	 *
	 * @return int Attachment ID on success, 0 on failure
	 */
	protected function get_attachment_id( $url ) {
		$attachment_id = 0;
		$dir = wp_upload_dir();

		if ( strpos( $url, 'https' ) === 0 ){
			// if url starts with 'https'
			$from = '/'.preg_quote( 'http', '/').'/';
			$baseurl = strpos( $dir['baseurl'], 'https' ) !== 0 ? preg_replace( $from, 'https', $dir['baseurl'], 1) . '/'  : $dir['baseurl'];
		} else if ( strpos( $url, 'http' ) === 0 ){
			// if url starts with 'http'
			$from = '/'.preg_quote( 'https', '/').'/';
			// $baseurl = preg_replace( $from, 'http', $dir['baseurl'], 1) . '/' ;
			$baseurl = strpos( $dir['baseurl'], 'https' ) !== 0 ? preg_replace( $from, 'http', $dir['baseurl'], 1) . '/'  : $dir['baseurl'];
		}


		if ( false !== strpos( $url, $baseurl ) ) { // Is URL in uploads directory?
			$file = basename( $url );
			$query_args = array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'fields'      => 'ids',
				'meta_query'  => array(
					array(
						'value'   => $file,
						'compare' => 'LIKE',
						'key'     => '_wp_attachment_metadata',
					),
				)
			);

			// polylang support
			if ( function_exists( 'pll_languages_list' ) ) {
				$languages = pll_languages_list( 'slug' );
				$query_args['lang'] = implode( $languages, ',' );
			}

			$query = new WP_Query( $query_args );
			if ( $query->have_posts() ) {
				foreach ( $query->posts as $post_id ) {
					$meta = wp_get_attachment_metadata( $post_id );
					$original_file       = basename( $meta['file'] );
					$cropped_image_files = wp_list_pluck( $meta['sizes'], 'file' );
					if ( $original_file === $file || in_array( $file, $cropped_image_files ) ) {
						$attachment_id = $post_id;
						break;
					}
				}
			}
		}
		return $attachment_id;
	}






}


function rain_effect_loader_init() {
	return Rain_Effect_Loader::get_instance();
}
rain_effect_loader_init();

?>