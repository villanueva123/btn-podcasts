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
		add_action('wp_ajax_btn_podcasts_listened', [$this, 'btn_podcasts_listened']);
		add_action('wp_ajax_btn_podcasts_streak', [$this, 'btn_podcasts_streak']);
		add_action('wp_ajax_btn_streak_action', [$this, 'btn_update_streak']);
		add_action('wp_ajax_btn_streak_reset_action', [$this, 'btn_streak_reset_action']);
		add_action('wp_ajax_btn_podcasts_achievement', [$this, 'btn_podcasts_achievement']);
		
    }

	function btn_podcasts_streak(){
		$user_id = get_current_user_id();
		$streak_enable = ( !empty($_POST['streak_enable']) ) ? $_POST['streak_enable'] : 0;
		update_user_meta($user_id, 'btn_streak_enable', $streak_enable);
		wp_send_json_success($streak_enable);
	}
	function btn_podcasts_achievement(){
		$user_id = get_current_user_id();
		$streak_enable = ( !empty($_POST['streak_enable']) ) ? $_POST['streak_enable'] : 0;
		update_user_meta($user_id, 'btn_achivement_enable', $streak_enable);
		wp_send_json_success($streak_enable);
	}
    function btn_podcasts_action(){
        $key = btn_podcasts()->pods()->get_user_meta_key();
    		$data = ( !empty($_POST['data']) ) ? $_POST['data'] : false;
        $user_id = get_current_user_id();
        $user_meta = get_user_meta($user_id, $key, true);

				if(!empty($data)){
					$user_meta_arr = explode(",", $data);
					update_user_meta( $user_id, $key, $user_meta_arr);
				}

				if(!$data){
					delete_user_meta($user_id, $key);
				}

				wp_send_json_success($data);

    }

	/** Legacy/manual streak update. Intentionally does NOT count as a clinical pod listen. */
    function btn_podcasts_listened(){
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Login required.' ), 401 );
        }
        $user_id = get_current_user_id();
        $previous = (int) get_user_meta( $user_id, 'podcast_current_streak_date', true );
        $today = current_time( 'Y-m-d' );
        $already_updated = $previous && date( 'Y-m-d', $previous ) === $today;
        $result = btn_podcasts_clinical::update_streak( $user_id );
        if ( ! get_user_meta( $user_id, 'podcast_listend', true ) ) {
            update_user_meta( $user_id, 'podcast_listend', 'listen' );
        }
        wp_send_json_success( array(
            'current_streak' => $result['current_streak'],
            'long_streak' => $result['long_streak'],
            'already_updated' => $already_updated ? 1 : 2,
        ) );
    }


	function btn_update_streak(){
        $target_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
        if ( ! $target_id || ! current_user_can( 'edit_user', $target_id ) ) {
            wp_send_json_error( array( 'message' => 'Not permitted.' ), 403 );
        }
        check_ajax_referer( 'btn_podcasts_admin_streak', 'nonce' );
			$user_id = $target_id;
			$lso = ( ! empty($_POST['longestStreakOriginal']) ) ? absint($_POST['longestStreakOriginal']) : 0;
			$cso = ( ! empty($_POST['currentStreakOriginal']) ) ? absint($_POST['currentStreakOriginal']) : 0;
			$cs = ( ! empty($_POST['currentStreak']) ) ? absint($_POST['currentStreak']) : 0;
			$ls = ( ! empty($_POST['longestStreak']) ) ? absint($_POST['longestStreak']) : 0;


			global $wpdb;
			$table = $wpdb->prefix.'podcast';
            $lst = '';
            $cst = '';
            if($lso != $ls){
				$contact_id = btn_podcasts()->memberium()->contact_id_by_user_id($user_id);
                if ( $contact_id ) {
                    btn_podcasts()->memberium()->set_contact_field('_ConsecutivedayslistenedtoPods', $ls, $contact_id);
                }
				update_user_meta($user_id, 'memb__ConsecutivedayslistenedtoPods', $ls);
				$lst = "success";
			}
			if($cso != $cs){
				if($cs > 0){
					$days = ($cs > 1 )? "Days" : "Day";
					$val = $cs . " {$days}";
					update_user_meta($user_id, 'podcast_current_streak', $val);
				}
				$cst = "success";
			}
			$return = [
				"lst" => $lst,
				"cst" => $cst,
			];
		  wp_send_json_success($return);
	}

	function btn_streak_reset_action(){
        $target_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
        if ( ! $target_id || ! current_user_can( 'edit_user', $target_id ) ) {
            wp_send_json_error( array( 'message' => 'Not permitted.' ), 403 );
        }
        check_ajax_referer( 'btn_podcasts_admin_streak', 'nonce' );
		$user_id = $target_id;
		update_user_meta($user_id, 'podcast_current_streak', "0 Day");
		update_user_meta($user_id, 'memb__ConsecutivedayslistenedtoPods', "0");
		wp_send_json_success($user_id);
	}
	function __construct(){}



}
