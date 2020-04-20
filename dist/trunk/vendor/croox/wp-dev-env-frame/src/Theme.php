<?php
/**
 * Theme init
 *
 * @package wde
 */

namespace croox\wde;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


abstract class Theme extends Project {

	function __construct( $init_args = array() ) {
		parent::__construct( $init_args );

		// parse init_args, apply defaults
		$init_args = wp_parse_args(
			$init_args,
			array()
		);

		// ??? is all exist and valid
		$this->dir_basename = basename( dirname( $init_args['FILE_CONST'] ) );           // no trailing slash
		$this->dir_url      = get_theme_root_uri() . '/' . $this->dir_basename;          // no trailing slash
		$this->dir_path     = trailingslashit( dirname( $init_args['FILE_CONST'] ) );    // trailing slash
		$this->FILE_CONST   = $init_args['FILE_CONST'];

	}

	public function initialize() {
		// activate
		$this->activate();
		// start
		$this->start();
	}

	public function hooks() {
		parent::hooks();
		// on deactivate
		add_action( 'switch_theme', array( $this, 'on_deactivate' ), 10, 3 );
	}

	public function load_textdomain() {
		load_theme_textdomain(
			$this->textdomain,
			$this->dir_path . 'languages'
		);
		// just a test string to ensure generated pot file will not be empty
		$test = __( 'test', $this->textdomain );
	}

	public function activate() {

		$option_key = $this->slug . '_activated';

		if ( ! $this->check_dependencies() ) {
			$this->deactivate();
		}

		if ( ! get_option( $option_key ) ) {

			$this->init_options();
			$this->register_post_types_and_taxs();
			$this->add_roles_and_capabilities();

			// hook the register post type functions, because init is to late
			do_action( $this->prefix . '_on_activate_before_flush' );
			flush_rewrite_rules();
			$this->maybe_update();

			update_option( $option_key, 1 );
			do_action( $this->prefix . '_theme_activated' );

		}

	}

	public function start() {
		if ( ! $this->check_dependencies() ) {
			$this->deactivate();
		}

		$this->auto_include();

		add_action( 'after_setup_theme', array( $this, 'load_textdomain' ) );
		$this->register_post_types_and_taxs();
		$this->add_roles_and_capabilities();
		$this->maybe_update();
		$this->enqueue_assets();
		do_action( $this->prefix . '_theme_loaded' );
	}

	protected function enqueue_assets() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
	}

	// ??? may be move to project
	public function auto_include() {
		parent::auto_include();
		// include inc/template_functions/*.php
		$this->_include( 'template_functions' );
		// include inc/template_tags/*.php
		$this->_include( 'template_tags' );
	}

	public function enqueue_scripts() {
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	public function enqueue_styles() {
		// // theme style.css, doesn't contain any style, just theme details
		// // we don't need to enqueue it so. Just WP wants it to be existing
		// wp_enqueue_style( $this->prefix, $this->dir_url . '/style.css' );
		// array_push($this->style_deps, $this->prefix );

		// the 'real' theme stylesheet, contains the style
		$handle = $this->prefix . '_frontend';
		$_src = '/css/' . $handle . '.min.css';
		$src = $this->dir_url . $_src;
		$ver = $this->version . '.' . filemtime( $this->dir_path . $_src );
		wp_enqueue_style( $handle, $src, $this->style_deps, $ver, 'all' );
	}

	public function on_deactivate( $new_name, $new_theme, $old_theme ) {

		if ( $old_theme->get_stylesheet() != $this->slug ) {
			return;
		}

		$option_key = $old_theme->get_stylesheet() . '_activated';

		delete_option( $option_key );

		flush_rewrite_rules();
		do_action( $this->prefix . '_theme_deactivated' );
	}


	public function deactivate() {
		$default = wp_get_theme( WP_DEFAULT_THEME );
		if ( $default->exists() ) {
			add_action( 'admin_notices', array( $this, 'the_deactivate_notice' ) );
			switch_theme( $default->get_stylesheet() );
		} else {
			$last_core = WP_Theme::get_core_default_theme();
			if ( $last_core ) {
				add_action( 'admin_notices', array( $this, 'the_deactivate_notice' ) );
				switch_theme( $last_core->get_stylesheet() );
			} else {
				add_action( 'admin_notices', array( $this, 'the_deactivate_error_notice' ) );
			}
		}
	}

}



