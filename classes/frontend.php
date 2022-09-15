<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * Frontend Class
 *
 * @since      1.0.0
 * @package    btn-podcasts
 * @subpackage btn-podcasts/classes
 * @author     Curtis Krauter <curtis@businesstechninjas.com>
 */
final class btn_podcasts_frontend {

    // Wordpress Hooks ( Actions & Filters )
    function add_wp_hooks() {

		add_action('wp_enqueue_scripts',[$this,'enqueue']);
		add_action('wp_footer', [$this,'frontend_print_scripts']);
		add_action('wp_head', [$this,'test']);

		// Shortcodes
		$this->register_shortcodes();
    }


	// Register Shortcodes
	function register_shortcodes(){

		$prefix = "btn_podcasts_";
		$this->shortcode_map = [
			"{$prefix}player"	=> "{$prefix}shortcodes",
		];
		foreach ($this->shortcode_map as $tag => $class) {
			add_shortcode($tag, [$this, "shortcode_mapping"]);
		}
	}

	// Shortcode Mapping Function
	// Only includes suporting classes as needed
	function shortcode_mapping( $atts, $content, $tag ){
		$html = '';
		if( isset($this->shortcode_map[$tag]) ){
			$class = $this->shortcode_map[$tag];
			if( class_exists($class) ){
				$prefix = "btn_podcasts_";
				$func = str_replace($prefix, '', $tag);
				if( method_exists($class, $func) ){
					$html = call_user_func([$class, $func], $atts, $content, $tag);
				}
				else {
					error_log("Function {$class} does not exist");

				}
			}
			else {
				error_log("Class {$class} does not exist");
			}
		}
		return $html;
	}

	// Enqueue Scripts
    function enqueue(){
		$url = BTN_PODCASTS_ASSESTS_URL;
		$v = BTN_PODCASTS_VERSION;
		wp_register_style('btn-podcasts-frontend-css', "{$url}css/frontend.css", [], $v, 'all');
		wp_enqueue_style('btn-podcasts-frontend-css');
		$dep = [];
		wp_register_script('btn-podcasts-frontend-js', "{$url}js/frontend.js", $dep, $v, 'all' );
	}

	// Footer Scripts
	function frontend_print_scripts(){
		$to_json = $this->get_json();
	    // Nothing Doing
	    if ( empty($to_json) ){
	        return;
		}
	    // Print Scripts And Data for JS
	    else {
			$to_json['user_id'] = get_current_user_id();
            $to_json['ajax_url'] = admin_url( 'admin-ajax.php' );
			wp_localize_script( 'btn-podcasts-frontend-js', 'btn_podcasts_data', $to_json );
			wp_enqueue_script( 'amplitudejs' );
			wp_enqueue_script( 'btn-podcasts-frontend-js' );
		}
	}

	// Set JSON Data
    function set_json($key, $value = false) {
		if ($value) {
			$this->to_json[$key] = $value;
		}
		else {
			unset($this->to_json[$key]);
		}
	}

    // Get JSON Data
	function get_json($key = false) {
		if ($key) {
			return (isset($this->to_json[$key])) ? $this->to_json[$key] : null;
		}
		else {
			return $this->to_json;
		}
	}

	function __construct(){}

	// JSON Data for JS
	private $to_json = [];
	private $enqueue_css = false;
	// Shortcode Mapping
	private $shortcode_map;
	// Current User Data
	private $user = null;
}
