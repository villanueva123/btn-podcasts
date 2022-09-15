<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * Shortcode Class
 *
 * @since      1.0.0
 * @package    btn-podcasts
 * @subpackage btn-podcasts/classes
 * @author     Augustus Villanueva <augustus@businesstechninjas.com>
 */
final class btn_podcasts_shortcodes {
    // Display all modules
	static function player($atts, $content, $tag){
		$args = shortcode_atts( [
			'class_name'	=> '',
			'template'		=> 'pods-playlist.php',
		], $atts );
        $template = btn_podcasts()->template_part_path( $args['template'] );
        $pods = btn_podcasts()->user()->generate_group_pods();
        $pods_playlist = btn_podcasts()->user()->generate_playlist();
		$pods_sleep_timer = btn_podcasts()->user()->sleep_timer();
        $key = btn_podcasts()->pods()->get_user_meta_key();
        $user_id = get_current_user_id();
        $user_meta = get_user_meta($user_id, $key, true);
        if($template > ''){
            include $template;
        }
        btn_podcasts()->frontend()->set_json('podcasts', $pods);
        btn_podcasts()->frontend()->set_json('pods_playlist', $pods_playlist);
        return $html;
	}
    function __construct(){}
}
