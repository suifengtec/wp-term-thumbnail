<?php
/**
 * @Author: suifengtec
 * @Date:   2017-06-16 15:38:14
 * @Last Modified by:   suifengtec
 * @Last Modified time: 2017-06-16 19:13:21
 */
/**
 * Plugin Name: WP Term Thumbnail
 * Plugin URI: http://coolwp.com/wp-term-thumbnail.html
 * Description: Description.
 * Author: suifengtec
 * Author URI: https://coolwp.com
 * Version: 0.9.0
 * Text Domain: wptermthumb
 * Domain Path: /languages/
 *
 */

/*

based on `SF Taxonomy Thumbnail` by Grégory Viguier

 */
if ( ! defined( 'ABSPATH' ) ){
	exit;	
}

if ( ! class_exists( 'WP_Term_Thumbnail' ) ) :

final class WP_Term_Thumbnail {

	private static $instance;

	public function __wakeup() {}
	public function __clone() {}
	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_Term_Thumbnail ) ) {
			self::$instance = new self();
			self::$instance->setup_constants();
			self::$instance->hooks();
		}

		return self::$instance;

	}

	public function hooks(){
		
		spl_autoload_register( array( __CLASS__, '_autoload' ));
		
        register_activation_hook( __FILE__, array(__CLASS__, 'install') );
        register_deactivation_hook( __FILE__, array(__CLASS__, 'uninstall') );
	
		add_action( 'plugins_loaded', array( __CLASS__, 'plugins_loaded' ) ,11);

		

		add_action( 'wp_enqueue_scripts', array(__CLASS__, 'wp_enqueue_scripts') );
		add_action( 'admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts') );

		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( __CLASS__, 'plugin_action_links' ) );
		/*add_action( 'wptermthumb_schedule_event_hook' , array(__CLASS__, 'schedule_event_hook' ) );*/




		
		

	}
	



	public static function plugins_loaded(){

		if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
			return;
		}

		global $wpTermThumbnail;

		$wpTermThumbnail = new WP_Term_Thumbnail_Module_Utils;

		$wpTermThumbnail->hooks();
		

		new WP_Term_Thumbnail_Module_Misc;
		

	}

	
	public static function plugin_action_links(  $links ){

        $links[] = '<a href="' . admin_url( 'admin.php?page=xxx' ) . '">Settings</a>';
        $links[] = '<a href="http://coolwp.com" target="_blank">Documentation</a>';

        return $links;

	}

	public static function schedule_event_hook(){

	}

	public static function wp_enqueue_scripts(){
		
		$scheme = is_ssl() ? 'https' : 'http';
		//wp_enqueue_style( 'wptermthumb-frontend-css', TERMT_PLUGIN_URL . 'assets/css/wptermthumb-f.css' );
		//wp_enqueue_script( 'wptermthumb-frontend-js', TERMT_PLUGIN_URL . 'assets/js/wptermthumb-f.js', array('jquery'), false, true );
		/*
		
        wp_localize_script(  'wptermthumb-frontend-js', 'TERMT_Data', array(
            'ajaxurl'       => admin_url( 'admin-ajax.php' ),
            'error_message' => __( 'Please fix the errors to proceed', 'TERMT' ),
            'nonce'         => wp_create_nonce( 'TERMT_nonce' )
        ) );

		 */

	}

	public static function admin_enqueue_scripts( $hook ){

	}

	public static function _autoload( $class ) {

	    if ( stripos( $class, 'WP_Term_Thumbnail_' ) !== false ) {

	        $admin = ( stripos( $class, '_Admin_' ) !== false ) ? true : false;
	        $module = ( stripos( $class, '_Module_' ) !== false ) ? true : false;
	        $view = ( stripos( $class, '_View_' ) !== false ) ? true : false;

	        if ( $admin ) {
	            $class_name = str_replace( array('WP_Term_Thumbnail_Admin_', '_'), array('', '-'), $class );
	            $filename = dirname( __FILE__ ) . '/includes/admin/' . strtolower( $class_name ) . '.php';
	            if ( !file_exists( $filename ) ) {
	             	$filename = dirname( __FILE__ ) . '/modules/admin/' . strtolower( $class_name ) . '.php';
	            }    
	        } elseif($module){

	            $class_name = str_replace( array('WP_Term_Thumbnail_Module_', '_'), array('', '-'), $class );
	            $filename = dirname( __FILE__ ) . '/includes/' . strtolower( $class_name ) . '.php';
	            if ( !file_exists( $filename ) ) {
	             	$filename = dirname( __FILE__ ) . '/modules/' . strtolower( $class_name ) . '.php';
	            }  
	        }elseif($view){

	            $class_name = str_replace( array('WP_Term_Thumbnail_View_', '_'), array('', '-'), $class );
	            $filename = dirname( __FILE__ ) . '/templates/' . strtolower( $class_name ) . '.php';
	            if ( !file_exists( $filename ) ) {
	             	$filename = dirname( __FILE__ ) . '/views/' . strtolower( $class_name ) . '.php';
	            }  
	        }else {
	            $class_name = str_replace( array('WP_Term_Thumbnail_', '_'), array('', '-'), $class );
	            $filename = dirname( __FILE__ ) . '/includes/' . strtolower( $class_name ) . '.php';
	            if ( !file_exists( $filename ) ) {
	             	$filename = dirname( __FILE__ ) . '/modules/' . strtolower( $class_name ) . '.php';
	            }  
	        }

			//var_dump($filename);

	        if ( file_exists( $filename ) ) {
	            require_once $filename;
	        }
	    }
	}


	public static function set_schedule_events(){

        wp_schedule_event( time(), 'daily', 'wptermthumb_schedule_event_hook' );

    }

	public static function install(){

		/*self::set_schedule_events();
		flush_rewrite_rules( false );*/

		


	}
	
	public static function uninstall(){

	}


	private function setup_constants() {

		if ( ! defined( 'TERMT_PLUGIN_DIR' ) ) {
			define( 'TERMT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}
		if ( ! defined( 'TERMT_PLUGIN_URL' ) ) {
			define( 'TERMT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}
		if ( ! defined( 'TERMT_PLUGIN_FILE' ) ) {
			define( 'TERMT_PLUGIN_FILE', __FILE__ );
		}


	}

}

global $wptermthumb;
$wptermthumb = WP_Term_Thumbnail::instance();

endif;
