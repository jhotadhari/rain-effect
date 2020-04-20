<?php
/**
 * project init
 *
 * @package wde
 */

namespace croox\wde;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

abstract class Project {

	private static $_instances = array();

	public static function get_instance( $init_args = array() ) {
		$class = get_called_class();
		if ( ! isset( self::$_instances[ $class ] ) ) {
			$new_class                  = new $class( $init_args );
			self::$_instances[ $class ] = $new_class;
			$new_class->initialize();
			$new_class->hooks();
		}
		return self::$_instances[ $class ];
	}




	protected $deps       = array();
	protected $version    = '';
	protected $db_version = 0;
	protected $slug       = '';
	protected $name       = '';
	protected $prefix     = '';
	protected $textdomain     = '';

	protected $project_kind      = '';
	protected $deactivate_notice = '';
	protected $dependencies_ok   = false;
	protected $style_deps        = array();

	protected $dir_url      = '';
	protected $dir_path     = '';
	protected $dir_basename = '';
	protected $FILE_CONST   = '';

	function __construct( $init_args = array() ) {

		$deps = array(
			'plugins' => array_key_exists( 'plugins', $init_args['deps'] ) && is_array( $init_args['deps']['plugins'] )
				? $init_args['deps']['plugins']
				: array(),
			'php_ext' => array_key_exists( 'php_ext', $init_args['deps'] ) && is_array( $init_args['deps']['php_ext'] )
				? $init_args['deps']['php_ext']
				: array(),
		);
		if ( array_key_exists( 'php_version', $init_args['deps'] ) && is_string( $init_args['deps']['php_version'] ) )
			$deps['php_version'] = $init_args['deps']['php_version'];
		if ( array_key_exists( 'wp_version', $init_args['deps'] ) && is_string( $init_args['deps']['wp_version'] ) )
			$deps['wp_version'] = $init_args['deps']['wp_version'];

		$this->deps       	= $deps;
		$this->version    	= utils\Arr::get( $init_args, 'version', '' );
		$this->db_version 	= utils\Arr::get( $init_args, 'db_version', '' );
		$this->slug      	= utils\Arr::get( $init_args, 'slug', '' );
		$this->name      	= utils\Arr::get( $init_args, 'name', '' );
		$this->prefix     	= utils\Arr::get( $init_args, 'prefix', '' );
		$this->textdomain 	= utils\Arr::get( $init_args, 'textdomain', '' );
		$this->wde        	= utils\Arr::get( $init_args, 'wde', array() );
		$this->project_kind	= utils\Arr::get( $init_args, 'project_kind', '' );
	}

	public function hooks() {
		add_filter( 'admin_init', array( $this, 'may_be_admin_notices' ) );
	}

	protected function init_options() {
		update_option( $this->prefix . '_version', $this->version );
		add_option( $this->prefix . '_db_version', $this->db_version );
	}

	// check DB_VERSION and require the update class if necessary
	protected function maybe_update() {
		if ( get_option( $this->prefix . '_db_version' ) < $this->db_version ) {
			// require_once( $this->dir_path . 'inc/class-' . $this->prefix . '_update.php' );
			// new Update();
			// class Update is missing ??? !!!
		}
	}

	protected function check_dependencies() {

		if ( ! $this->dependencies_ok ) {

			$error_msgs = array();

			// check php version
			if ( version_compare( PHP_VERSION, $this->deps['php_version'], '<' ) ) {
				$err_msg = sprintf( 'PHP version %s or higher', $this->deps['php_version'] );
				array_push( $error_msgs, $err_msg );
			}

			// check php extensions
			if ( array_key_exists( 'php_ext', $this->deps ) && is_array( $this->deps['php_ext'] ) ) {
				foreach ( $this->deps['php_ext'] as $php_ext_key => $php_ext_val ) {
					if ( ! extension_loaded( $php_ext_key ) ) {
						$err_msg = sprintf(
							'<a href="%s" target="_blank">%s</a> php extension to be installed',
							$php_ext_val['link'],
							$php_ext_val['name']
						);
						array_push( $error_msgs, $err_msg );
					}
				}
			}

			// check wp version
			// include an unmodified $wp_version
			include ABSPATH . WPINC . '/version.php';
			if ( version_compare( $wp_version, $this->deps['wp_version'], '<' ) ) {
				$err_msg = sprintf( 'WordPress version %s or higher', $this->deps['wp_version'] );
				array_push( $error_msgs, $err_msg );
			}

			// check plugin dependencies
			if ( array_key_exists( 'plugins', $this->deps ) && is_array( $this->deps['plugins'] ) ) {
				foreach ( $this->deps['plugins'] as $dep_plugin ) {
					$err_msg = sprintf(
						' <a href="%s" target="_blank">%s</a> Plugin version %s (tested up to %s)',
						$dep_plugin['link'],
						$dep_plugin['name'],
						$dep_plugin['ver_at_least'],
						$dep_plugin['ver_tested_up_to']
					);
					// check by class
					if ( array_key_exists( 'class', $dep_plugin ) && strlen( $dep_plugin['class'] ) > 0 ) {
						if ( ! class_exists( $dep_plugin['class'] ) ) {
							array_push( $error_msgs, $err_msg );
						}
					}
					// check by function
					if ( array_key_exists( 'function', $dep_plugin ) && strlen( $dep_plugin['function'] ) > 0 ) {
						if ( ! function_exists( $dep_plugin['function'] ) ) {
							array_push( $error_msgs, $err_msg );
						}
					}
				}
			}

			// maybe set deactivate_notice and return false
			if ( count( $error_msgs ) > 0 ) {
				$this->deactivate_notice = implode(
					'',
					array(
						'<h3>',
						$this->name,
						' ',
						$this->project_kind,
						' requires:</h3>',
						'<ul style="padding-left: 1em; list-style: inside disc;">',
						'<li>',
						implode( '</li><li>', $error_msgs ),
						'</li>',
						'</ul>',
					)
				);
				return false;
			}

			$this->dependencies_ok = true;
		}

		return true;
	}

	abstract public function initialize();

	/**
	 * Public getter method for retrieving protected/private variables
	 * @param  string  		$field Field to retrieve
	 * @return mixed        Field value or exception is thrown
	 */
	public function __get( $field ) {
		// Allowed fields to retrieve
		if ( in_array( $field, array(
			'version',
			'slug',
			'name',
			'prefix',
			'textdomain',
			'dir_url',
			'dir_path',
			'dir_basename',
			'FILE_CONST',
		), true ) ) {
			return $this->{$field};
		}

		throw new Exception( 'Invalid property: ' . $field );
	}

	public function get_dir_url() {
		error_log( 'get_dir_url() is deprecated. Use public getter method ->dir_url instead' );
		return $this->dir_url;                  // no trailing slash
	}

	public function get_dir_path() {
		error_log( 'get_dir_path() is deprecated. Use public getter method ->dir_path instead' );
		return $this->dir_path;                 // trailing slash
	}

	public function get_dir_basename() {
		error_log( 'get_dir_basename() is deprecated. Use public getter method ->dir_basename instead' );
		return $this->dir_basename;             // no trailing slash
	}

	public function get_file() {
		error_log( 'get_file() is deprecated. Use public getter method ->FILE_CONST instead' );
		return $this->FILE_CONST;               // theme file abs path
	}

	public function get_prefix() {
		error_log( 'get_prefix() is deprecated. Use public getter method ->prefix instead' );
		return $this->prefix;
	}

	public function get_textdomain() {
		error_log( 'get_textdomain() is deprecated. Use public getter method ->textdomain instead' );
		return $this->textdomain;
	}
	public function get_version() {
		error_log( 'get_version() is deprecated. Use public getter method ->version instead' );
		return $this->version;
	}

	protected function _include( $key ) {
		if ( file_exists( $this->dir_path . 'inc/' . $this->prefix . '_include_' . $key . '.php' ) ) {
			include_once $this->dir_path . 'inc/' . $this->prefix . '_include_' . $key . '.php';
			if ( function_exists( $this->prefix . '_include_' . $key . '' ) ) {
				$include_function = $this->prefix . '_include_' . $key . '';
				$include_function();
			}
		}
	}

	// include files to register post types and taxonomies
	protected function register_post_types_and_taxs() {
		// include inc/post_types_taxs/*.php
		$this->_include( 'post_types_taxs' );
	}

	// include files to add user roles and capabilities
	protected function add_roles_and_capabilities() {
		// include inc/roles_capabilities/*.php
		$this->_include( 'roles_capabilities' );
	}

	public function auto_include() {
		// include inc/fun/*.php
		$this->_include( 'fun' );
	}

	abstract public function load_textdomain();

	public function the_deactivate_notice() {
		echo implode(
			'',
			array(
				'<div class="notice error">',
				$this->deactivate_notice,
				'<p>The ',
				$this->project_kind,
				' will be deactivated.</p>',
				'</div>',
			)
		);
	}

	public function the_deactivate_error_notice() {
		echo implode(
			'',
			array(
				'<div class="notice error">',
				$this->deactivate_notice,
				'<p>An error occurred when deactivating the ',
				$this->project_kind,
				'. It needs to be deactivated manually.</p>',
				'</div>',
			)
		);
	}

	abstract public function activate();

	abstract public function start();

	abstract public function on_deactivate( $new_name, $new_theme, $old_theme );

	abstract public function deactivate();

	/**
	 * Helper to register, localize, set translations and enqueue a script from the projects `js` directory.
	 *
	 * - Uses `register_script` function.
	 *   Specifies the script version, using the project version and the script file modification time.
	 * - If `$args['localize_data']` is not empty, the script will be localized.
	 * - If `$deps` contains `wp-i18n`, script translations will be set..
	 *
	 * @since 0.6.0
	 * @param array  $args      Arguments array
	 *    $args = array(
	 *      'handle'		=> 	(string)	(Required)
	 *							Name of the script. Should be unique.
	 *      'deps'			=> 	(array)	(Optional)
	 *							An array of registered script handles this script depends on. Default `array()`.
	 *      'in_footer'     => 	(bool)	(Optional)
	 *							Whether to enqueue the script before `</body>` instead of in the `<head>`. Default `false`.
	 *      'localize_data'	=> 	(array)	(Optional)
	 *							Data to be available to the script. If empty, script won't be localized. Default `array()`.
	 *      'localize_name'	=> 	(string)	(Optional)
	 *							The name of the variable which will contain the localized data. Default `$args['handle'] . '_data'`.
	 *      'enqueue'		=> 	(int) 	(Optional)
	 *							Whether to enqueue the script directly or not. Default `false`.
	 *    )
	 * @return bool       		Whether the script has been registered. True on success, false on failure.
	 */
	public function register_script( $args ) {

		$args = wp_parse_args( $args, array(
			'handle'		=> '',
			'deps'			=> array(),
			'in_footer'		=> false,
			'localize_data'	=> array(),
			'enqueue'		=> false,
		) );
		if ( ! array_key_exists( 'localize_name', $args ) )
			$args['localize_name'] = $args['handle'] . '_data';

		$_src = 'js/' . $args['handle'] . '.min.js';
		$src = $this->dir_url . '/' . $_src;
		$ver = $this->version . '.' . filemtime( $this->dir_path . $_src );

		$registered = wp_register_script(
			$args['handle'],
			$src,
			$args['deps'],
			$ver,
			$args['in_footer']
		);

		if ( ! $registered )
			return $registered;

		if ( ! empty( $args['localize_data'] ) )
			wp_localize_script(
				$args['handle'],
				$args['localize_name'],
				$args['localize_data']
			);

		if ( in_array( 'wp-i18n', $args['deps'] ) )
			wp_set_script_translations(
				$args['handle'],
				$this->textdomain,
				$this->dir_path . 'languages'
			);

		if ( $args['enqueue'] )
			wp_enqueue_script( $args['handle'] );

		return $registered;
	}

	/**
	 * Helper to register a stylesheet from the projects `css` directory.
	 *
	 * - Uses `register_style` function.
	 *   Specifies the stylesheet version, using the project version and the stylesheet file modification time.
	 *
	 * @since 0.6.0
	 * @param array  $args      Arguments array
	 *    $args = array(
	 *      'handle'		=> 	(string)	(Required)
	 *							Name of the script. Should be unique.
	 *      'deps'			=> 	(array)	(Optional)
	 *							An array of registered script handles this script depends on. Default `array()`.
	 *      'media'			=> 	(string)	(Optional)
	 *							String specifying the media for which this stylesheet has been defined. Default `'all'`.
	 *      'enqueue'		=> 	(int) 	(Optional)
	 *							Whether to enqueue the stylesheet directly or not. Default `false`.
	 *    )
	 * @return bool       		Whether the stylesheet has been registered. True on success, false on failure.
	 */
	public function register_style( $args ) {

		$args = wp_parse_args( $args, array(
			'handle'	=> '',
			'deps'		=> array(),
			'media'		=> 'all',
			'enqueue'	=> false,
		) );

		$_src = 'css/' . $args['handle'] . '.min.css';
		$src = $this->dir_url . '/' . $_src;
		$ver = $this->version . '.' . filemtime( $this->dir_path . $_src );

		$registered = wp_register_style(
			$args['handle'],
			$src,
			$args['deps'],
			$ver,
			$args['media']
		);

		if ( $registered && $args['enqueue'] )
			wp_enqueue_style( $args['handle'] );

		return $registered;

	}

	public static function get_active_frame() {
		$composer_json_path = explode( '/', dirname( __FILE__ ) );
		array_splice( $composer_json_path, -1 );
		$composer_json_path = implode( '/', $composer_json_path );

		$composer_json = json_decode( file_get_contents( $composer_json_path . '/composer.json' ), true );
		$composer_json = null === $composer_json ? array() : $composer_json;

		$active_frame = array(
			'version'	=> utils\Arr::get( $composer_json, 'version', '' ),
			'path'		=> $composer_json_path,
		);

		return $active_frame;
	}

	public function may_be_admin_notices() {
		if ( ! isset( $this->wde ) || empty( $this->wde ) )
			return;

		$required_frame = utils\Arr::get( $this->wde, 'wp-dev-env-frame', '' );
		$active_frame = Project::get_active_frame();

		if ( $active_frame['version'] !== $required_frame ) {
			add_action( 'admin_notices', function() use( $required_frame, $active_frame ) {
				echo implode( '', array(
					'<div class="notice notice-error is-dismissible">',

						'<p>' . sprintf(
							__( '%s <b>%s</b> requires <b>%s</b> version <b>%s</b>.', $this->textdomain ),
							ucfirst( $this->project_kind ),
							$this->name,
							'croox/wp-dev-env-frame',
							$required_frame
						) . '</p>',

						'<p>' . sprintf(
							__( "But version <b>%s</b> is loaded. This might be compatible but propably won't. The only safe way is to have all plugins and themes running the same frame version.", $this->textdomain ),
							$active_frame['version']
						) . '</p>',

						'<p>' . sprintf(
							__( 'The frame package is loaded from this path: %s', $this->textdomain ),
							$active_frame['path']
						) . '</p>',

					'</div>',
				) );
			} );
		}
	}

}


