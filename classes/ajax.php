<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * Ajax Class
 *
 * @since      1.0.0
 * @package    btn-podcasts
 * @subpackage btn-podcasts/classes
 * @author     Curtis Krauter <curtis@businesstechninjas.com>
 */
final class btn_podcasts_ajax {

    // Wordpress Hooks ( Actions & Filters )
    function add_wp_hooks() {
		add_action('wp_ajax_btn_podcasts_action', [$this, 'btn_podcasts_action']);
        add_action('wp_ajax_nopriv_btn_podcasts_action', [$this, 'btn_podcasts_action']);

        add_action('wp_ajax_btn_podcasts_playlist_action', [$this, 'btn_podcasts_playlist_action']);
        add_action('wp_ajax_nopriv_btn_podcasts_playlist_action', [$this, 'btn_podcasts_playlist_action']);
    }

    function btn_podcasts_action(){
        $key = btn_podcasts()->pods()->get_user_meta_key();
    	$value = ( ! empty($_POST['value']) ) ? $_POST['value'] : false;
        $type = ( ! empty($_POST['type']) ) ? $_POST['type'] : false;
        $user_id = get_current_user_id();
        $user_meta = '';
        $user_meta = get_user_meta($user_id, $key, true);
        if($type === 'add'){
            if(!in_array($value, $user_meta)){
                if( empty( $user_meta ) ) {
                    update_user_meta( $user_id, $key, array( $value ) );
                } else {
                    $user_meta_arr = ( is_array( $user_meta ) ) ? $user_meta : array( $user_meta );
                    $user_meta_arr[] = $value;
                    update_user_meta( $user_id, $key, $user_meta_arr );
                }
            }
        }else{
            if (($keys = array_search($value, $user_meta)) !== false) {
                unset($user_meta[$keys]);
            }
            update_user_meta( $user_id, $key, $user_meta );
        }
	    wp_send_json_success($user_meta);
    }

    function btn_podcasts_playlist_action(){
        $html .="";
        $pods_playlist = btn_podcasts()->user()->generate_playlist();
        include btn_podcasts()->template_part_path('podcasts/playlist.php');
        $return = [
            'html' => $html,
            'playlist' => $pods_playlist
        ];
        wp_send_json_success($return);
    }

	function __construct(){}



}
