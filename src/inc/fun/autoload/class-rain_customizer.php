<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


// https://codex.wordpress.org/Theme_Customization_API
// another example: https://github.com/bueltge/Documentation/blob/master/inc/theme-customize.php

// snippets: https://github.com/bueltge/Wordpress-Theme-Customizer-Custom-Controls

// advanced-controls
// https://code.tutsplus.com/tutorials/a-guide-to-the-wordpress-theme-customizer-advanced-controls--wp-33461


// how to load scripts again on frontend
// https://florianbrinkmann.com/en/3790/selective-refresh-customizer/

// customizer add-ons and wrappers
// https://github.com/lucatume/wp-customizer/tree/master/src

// selective-refresh-javascript-events
// https://developer.wordpress.org/themes/customize-api/tools-for-improved-user-experience/#selective-refresh-javascript-events


// types
// 	text 	textarea 	date
// 	range 	url 	email
// 	password 	hidden 	checkbox
// 	radio 	select 	dropdown-pages
// 	number 	time 	datetime
// 	week 	search

// contextual-panels-sections-and-controls
// https://developer.wordpress.org/themes/customize-api/the-customizer-javascript-api/#contextual-panels-sections-and-controls

if ( ! class_exists( 'Rain_Customizer' ) ) {
	
	class Rain_Customizer {
	
		protected static $instance = null;
		protected $plugin_key = '';
		protected $option_key = '';
		
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}	
		
		protected function __construct( $args = NULL ) {
			// Set option key
			if ( NULL === $args ) {
				$args[ 'plugin_key' ] = strtolower( Rain_Rain_effect::PLUGIN_SLUG );
			}                                    
			// Set option key
			$this->plugin_key = $args[ 'plugin_key' ];
			$this->option_key = $this->plugin_key . '_customizer';

		}
		
		public function run(){
			
			// add theme support for custom headers, if not already
			if ( ! current_theme_supports( 'custom-header' ) )
				$this->add_theme_support_custom_header();
			
			// register our custom settings
			add_action( 'customize_register', array( $this, 'register' ) );
			
			// Scripts for Preview
			add_action( 'customize_preview_init', array( $this, 'preview_js' ) );
			
			// call Rain_Effect_Loader::apply_effect() if is rain
			add_action( 'customize_preview_init', array( $this, 'loader_apply_effect' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'loader_apply_effect' ) );			
			
			// filter header image tag
			add_filter( 'get_header_image_tag', array( $this, 'filter_header_image_tag' ), 10, 3 );
			
			// add footer image to wp footer
			add_action( 'wp_footer', array( $this, 'hook_footer_image' ) );
			
		}
		
		protected function add_theme_support_custom_header(){
			add_theme_support( 'custom-header', apply_filters( 'rain_custom_header_args', array(
				'flex-width'    		=> true,
				'width'         		=> 1000,
				'flex-height'    		=> true,
				'height'        		=> 250,		
			) ) );	
		}
		
		/**
		 *
		 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
		 */
		public function register( $wp_customize ) {
			
			$defaults = $this->get_default_options();
			
			// extend header_image section
			$this->section_header_image( $wp_customize, $defaults );
			
			// register footer_media section
			$this->section_footer_media( $wp_customize, $defaults );
			
			// register rain_effect section
			$this->section_rain_effect( $wp_customize, $defaults );
						
		}
		
		protected function section_header_image( $wp_customize, $defaults ) {
			
			// section is added with theme support custom-header
			$section_id = 'header_image';
			
			// setting header_is_rain
			$setting_id = $this->option_key . '[header_is_rain]';
			if ( ! self::is_setting_registered( $wp_customize, $setting_id ) ) {
				$wp_customize->add_setting(
					$setting_id,
					array(
						'default'    => $defaults[ 'header_is_rain' ],
						'type'       => 'option',
						'capability' => 'edit_theme_options',
						'transport'  => 'postMessage'
					)
				);
				$wp_customize->add_control(
					'header_image' . '_is_rain',
					array(
						'label'    => esc_attr__( 'Use Rain Effect for Header Image', 'rain-effect' ),
						'section'  => $section_id,
						'settings' => $setting_id,
						'type'     => 'checkbox',	// https://developer.wordpress.org/themes/customize-api/customizer-objects/#controls
					)
				);
			
				$wp_customize->selective_refresh->add_partial(
					$setting_id,
					array(
						'selector' => '#wp-custom-header',
						'container_inclusive' => true,
						'render_callback' => 'the_custom_header_markup'
					)
				);			
			}
	
		}
		
		protected function section_footer_media( $wp_customize, $defaults ) {
			
			$section_id = $this->option_key . '_footer_media';
			
			// add section footer_media
			if ( ! self::is_section_registered( $wp_customize, $section_id ) )
				$wp_customize->add_section(
					$section_id,
					array(
						'title'       => esc_attr__( 'Footer Media', 'rain-effect' ),
						'description' => esc_attr__( 'Customize the Footer', 'rain-effect' ),
						'priority'    => 81
					)
				);
				
			$setting_ids = array();
				
			// setting footer_image
			$setting_id = $this->option_key . '[footer_image]';
			$setting_ids[] = $setting_id;
			if ( ! self::is_setting_registered( $wp_customize, $setting_id ) ) {
				$wp_customize->add_setting(
					$setting_id,
					array(
						'default'    => $defaults[ 'footer_image' ],
						'type'       => 'option',
						'capability' => 'edit_theme_options',
						'transport'  => 'postMessage'
					)
				);
				$wp_customize->add_control(
					new WP_Customize_Cropped_Image_Control (
						$wp_customize,
						$setting_id,
						array(
							'label'      	=> __( 'Footer Image', 'rain-effect' ),
							'settings'   	=> $setting_id,
							'section'       => $section_id,
							'priority'      => 10,
							'height'        => 250,
							'width'         => 1000,
							'flex_height'   => true,
							'flex_width'    => true,
							'button_labels' => array(
								'select'       => __( 'Select Footer Image', 'rain-effect' ),
								'change'       => __( 'Change Footer Image', 'rain-effect' ),
								'remove'       => __( 'Remove', 'rain-effect' ),
								'default'      => __( 'Default', 'rain-effect' ),
								'placeholder'  => __( 'No Footer Image selected', 'rain-effect' ),
								'frame_title'  => __( 'Select Footer Image', 'rain-effect' ),
								'frame_button' => __( 'Choose Footer Image', 'rain-effect' ),
							),
						)
					)
				);
			}
				
			// setting footer_is_rain
			$setting_id = $this->option_key . '[footer_is_rain]';
			$setting_ids[] = $setting_id;
			if ( ! self::is_setting_registered( $wp_customize, $setting_id ) ) {
				$wp_customize->add_setting(
					$setting_id,
					array(
						'default'    => $defaults[ 'footer_is_rain' ],
						'type'       => 'option',
						'capability' => 'edit_theme_options',
						'transport'  => 'postMessage'
					)
				);			
				$wp_customize->add_control(
					'footer_image' . '_is_rain',
					array(
						'label'    => esc_attr__( 'Use Rain Effect for Footer Image', 'rain-effect' ),
						'section'  => $section_id,
						'settings' => $setting_id,
						'type'     => 'checkbox',	// https://developer.wordpress.org/themes/customize-api/customizer-objects/#controls
						'priority' => 11,
					)
				);
			}
			
			// setting footer_is_rain
			$setting_id = $this->option_key . '[footer_hook]';
			$setting_ids[] = $setting_id;
			if ( ! self::is_setting_registered( $wp_customize, $setting_id ) ) {
				$wp_customize->add_setting(
					$setting_id,
					array(
						'default'    => $defaults[ 'footer_hook' ],
						'type'       => 'option',
						'capability' => 'edit_theme_options',
						'transport'  => 'postMessage'
					)
				);			
				$wp_customize->add_control(
					'footer_image' . '_hook',
					array(
						'label'    =>
							esc_attr__( 'Hook the Footer Image into wp_footer.' , 'rain-effect' ) . ' ' . 
							esc_attr__( 'You can also use the template tags:' , 'rain-effect' ) . ' ' . 
							esc_attr( 'rain_get_custom_footer()' ) . ' ' . 
							esc_attr( 'rain_the_custom_footer()' ),
						'section'  => $section_id,
						'settings' => $setting_id,
						'type'     => 'checkbox',	// https://developer.wordpress.org/themes/customize-api/customizer-objects/#controls
						'priority' => 11,
					)
				);
			}
			
			foreach( $setting_ids as $setting_id ) {
				$wp_customize->selective_refresh->add_partial(
					$setting_id,
					array(
						'selector' => '#rain-custom-footer',
						'container_inclusive' => true,
						'render_callback' => array( $this, 'hook_footer_image' )
					)
				);			
			}			
			
		}
		
		protected function section_rain_effect( $wp_customize, $defaults ) {
			
			$section_id = $this->option_key . '_rain_effect';
			
			// add section footer_media
			if ( ! self::is_section_registered( $wp_customize, $section_id ) )
				$wp_customize->add_section(
					$section_id,
					array(
						'title'       => esc_attr__( 'Rain Effect', 'rain-effect' ),
						'description' =>
							esc_attr__( 'The rain effect script will be loaded when the option for the header or footer image is set on default.', 'rain-effect' ) . ' ' . // heaeaea grammar ???
							esc_attr__( 'The script will apply the effect on every element with class="rain-effect".', 'rain-effect' ),
					)
				);
			
			// setting header_is_rain
			$setting_id = $this->option_key . '[apply_global]';
			if ( ! self::is_setting_registered( $wp_customize, $setting_id ) ) {
				$wp_customize->add_setting(
					$setting_id,
					array(
						'default'    => $defaults[ 'apply_global' ],
						'type'       => 'option',
						'capability' => 'edit_theme_options',
					)
				);
				$wp_customize->add_control(
					'rain_effect' . '_apply_global',
					array(
						'label'    => esc_attr__( 'When to load rain effect script?', 'rain-effect' ),
						'section'  => $section_id,
						'settings' => $setting_id,
						'type'     => 'checkbox',	// https://developer.wordpress.org/themes/customize-api/customizer-objects/#controls
					
						'type'     => 'radio',
						'choices'  => array(
							'header_footer_only'	=> esc_attr__( 'Load only when the option for the header or footer image is set.', 'roots' ),
							'global'  				=> esc_attr__( 'Load rain effect script everytime. Even when not applied for header or footer image.', 'rain-effect' ),
							'never'					=> esc_attr__( "Disable rain effect everywhere. Don't load the script at all.", 'roots' ),
						),						
						
					)
				);
			}
		
		}			
				
		public function get_footer_image_tag( $attr = array() ) {
			
			$footer_image_id = absint( $this->get_options()['footer_image'] );
			
			if ( ! $footer_image_id ) {
				return '';
			}
			
			$attr = wp_parse_args(
				$attr,
				array(
					'alt' => get_bloginfo( 'name' ),
				)
			);
			
			// append 'rain-effect' to class
			if ( $this->get_options()['footer_is_rain'] ){
				if ( array_key_exists( 'class', $attr ) ) {
					$attr['class'] = $attr['class'] . ' ' . esc_attr('rain-effect');
				} else {
					$attr['class'] = esc_attr('rain-effect');
				}			
			}			
			
			$html = wp_get_attachment_image( $footer_image_id, 'full', false, $attr );
			
			/**
			 * Filters the markup of footer images.
			 *
			 * @param string $html  			The HTML image tag markup being filtered.
			 * @param int $footer_image_id 	The custom footer image id
			 * @param array  $attr  			Array of the attributes for the image tag.
			 */
			return apply_filters( 'rain_get_footer_image_tag', $html, $footer_image_id, $attr );
		}
		
		public function get_footer_markup() {
			if ( ! $this->get_options()['footer_image'] ) {
				return '';
			}
		 
			return sprintf(
				'<div id="rain-custom-footer" class="wp-custom-footer rain-custom-footer">%s</div>',
				$this->get_footer_image_tag()
			);
		}
				
		public function hook_footer_image() {
			if ( $this->get_options()['footer_hook'] )
				echo $this->get_footer_markup();
		}
		
		public function filter_header_image_tag( $html, $header, $attr ){
			
			$this->get_footer_image_tag();
		
			// check if header image is rain
			if ( ! $this->get_options()['header_is_rain'] )
				return $html;
			
			// append 'rain-effect' to class
			if ( array_key_exists( 'class', $attr ) ) {
				$attr['class'] = $attr['class'] . ' ' . esc_attr('rain-effect');
			} else {
				$attr['class'] = esc_attr('rain-effect');
			}
			
			// create html
			$html = '<img';
			foreach ( $attr as $name => $value ) {
				$html .= ' ' . $name . '="' . $value . '"';
			}
			$html .= ' />';
				
			return $html;
		}
		
		public function get_options( $value = NULL ) {
			$saved    = (array) get_option( $this->option_key );
			$defaults = $this->get_default_options();
			$options = wp_parse_args( $saved, $defaults );
			$options = array_intersect_key( $options, $defaults );
			$options = apply_filters( $this->option_key . '_options', $options );
			if ( NULL !== $value ) {
				return $options[ $value ];
			}
			return $options;
		}
		
		public function get_default_options( $value = NULL ) {
			$default_theme_options = array(
				'header_is_rain'     => false,
				'footer_image'       => null,
				'footer_is_rain'     => false,
				'footer_hook'    	 => true,
				'apply_global'    	 => 'header_footer_only',
			);
			if ( NULL !== $value ) {
				return $default_theme_options[ $value ];
			}
			return apply_filters( $this->plugin_key . '_default_options', $default_theme_options );
		}
		
		protected static function is_section_registered( $wp_customize = null, $section_id = null ){
			if ( ! $wp_customize || ! $section_id )
				return false;
			foreach( $wp_customize->sections() as $section ) {
				if ( $section_id === $section->id )
					return true;
			} 
			return false;
		}
		
		protected static function is_setting_registered( $wp_customize = null, $setting_id = null ){
			
			if ( ! $wp_customize || ! $setting_id )
				return false;
			foreach( $wp_customize->settings() as $setting ) {
				if ( $setting_id === $setting->id )
					return true;
			} 
			return false;
		}
		
		
		public function loader_apply_effect() {
		
			
			
			
			if ( $this->get_options()['apply_global'] !== 'never' )
				if ( $this->get_options()['footer_is_rain'] || $this->get_options()['header_is_rain'] || $this->get_options()['apply_global'] === 'global' )
					return Rain_Effect_Loader::get_instance()->apply_effect();
			
		}
		
		/**
		* Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
		*/
		public function preview_js() {
			// wp_enqueue_script( 'rain_customizer', Rain_Rain_effect::plugin_dir_url() . '/js/rain_customizer.min.js', array( 'customize-preview' ), '20151215', true );
		}
		
	}

}

function rain_customizer_init() {
	return Rain_Customizer::get_instance()->run();
}
rain_customizer_init();

?>