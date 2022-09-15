<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * Main Plugin Class
 *
 * @since      1.0.0
 * @package    btn-podcasts
 * @subpackage btn-podcasts/classes
 * @author     Augustus Villanueva <augustus@businesstechninjas.com>
 */
final class btn_podcasts {

	function init(){
		// Text Domain / Localization
		$this->load_text_domain();

		// Add Memberium Access Settings
		add_filter('wpal/controlled/access/taxonomies', ['btn_podcasts_pods', 'taxonomy_access']);

		// Init Hooks
		add_action('init',[$this,'init_hooks']);
    }

    // Init
    function init_hooks(){

		$this->pods()->register();
		add_action( 'wp_enqueue_scripts', [$this, 'register_player_script'] );

		if (is_admin()) {
			$this->admin()->add_wp_hooks();
		}
		else{
			$this->frontend()->add_wp_hooks();
		}

		// AJAX Hooks
		if ( wp_doing_ajax() ) {

		}
			$this->ajax()->add_wp_hooks();
    }

	// Register Player Script for Pod Post Type Admin & Frontend Shortcodes
	function register_player_script(){
		/**
		 * @link Versioning		https://github.com/521dimensions/amplitudejs/releases
		 * @link Configuration 	https://521dimensions.com/open-source/amplitudejs/docs/configuration/
		*/
		$version = '5.2.0';
		wp_register_script('amplitudejs', "https://cdn.jsdelivr.net/npm/amplitudejs@{$version}/dist/amplitude.js", [], $version);
	}

	// Get Admin Class
    function frontend(){
		static $frontend = null;
        if( is_null($frontend) ){
            $frontend = new btn_podcasts_frontend;
        }
        return $frontend;
    }

	// Get Admin Class
    function ajax(){
		static $ajax = null;
        if( is_null($ajax) ){
            $ajax = new btn_podcasts_ajax;
        }
        return $ajax;
    }
	// Get Admin Class
    function admin(){
		static $admin = null;
        if( is_null($admin) ){
            $admin = new btn_podcasts_admin;
        }
        return $admin;
    }

    // Get Memberium Class
    function memberium(){
		static $memberium = null;
        if( is_null($memberium) ){
            $memberium = new btn_podcasts_memberium;
        }
        return $memberium;
    }

	// Get User Class
    function user(){
		static $user = null;
        if( is_null($user) ){
            $user = new btn_podcasts_user;
        }
        return $user;
    }

	// Get Pods Class
    function pods(){
		static $pods = null;
        if( is_null($pods) ){
            $pods = new btn_podcasts_pods;
        }
        return $pods;
    }

	/**
	 * Return templates path checks child theme first
	 *
	 * @param string $filename
	 * @return string template path admin error or false
	*/
	function template_part_path( $filename, $directory_name = '' ){

		$not_found = [];
		$directory_name = $directory_name > '' ? trailingslashit($directory_name) : '';
		$theme_template = "{$directory_name}{$filename}";

		// Locate Template in Themes
		$template = locate_template($theme_template, false);
		// Get Plugin Defaults
		if( ! is_file($template) ){
			$not_found['theme'] = $theme_template;
			$template = BTN_PODCASTS_DIR . 'templates/' . $filename;
			if( ! is_file($template) ){
				$not_found['extension'] = $template;
				$template = false;
			}
		}

		$template = apply_filters('btn/podcasts/template/path', $template, $filename, $directory_name);
		if ( ! is_file($template) )	{
			if ( is_admin() ) {
				$notice = __('File not found in any of the following locations :', 'btn-podcasts');
				$notice .= '<ul>';
				foreach ($not_found as $path) {
					$notice .= "<li>{$path}</li>";
				}
				$notice .= '</ul>';
				return $this->admin_error_msg($notice);
			}
			else{
				return false;
			}
		}
		else{
			return $template;
		}
	}

	// Text Domain
	function load_text_domain(){
		load_plugin_textdomain('btn-podcasts', false, BTN_PODCASTS_DIR . '/languages' );
	}

    // Write Log
    function write_log( $log, $print = false ){
        $error_log = ( is_array( $log ) || is_object( $log ) ) ? print_r( $log, true ) : $log;
        if($print){
            return '<pre>'.$error_log.'</pre>';
        }
        else{
            error_log($error_log);
        }
    }



    // Singleton Instance
    private function __construct(){}
	public static function get_instance() {
        static $instance = null;
        if ( is_null( $instance ) ) {
            $instance = new self;
        }
        return $instance;
    }

}
