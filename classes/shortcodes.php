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
			'template'		=> 'podcast.php',
			'sleep_timer_label' => 'Use the Sleep Timer to stop your pod playlist automatically after the desired amount of time: ',
			'no_playlist_notice' => "<p>It looks like you haven't added any tracks to your playlist yet. Don't worry, it's easy to do. Simply tap the red arrow on a phase (for example, Slimpod Gold Phase 1), then tap 'add to playlist' on the pod you want to add.</p>",
			'player_control_notice' => "You are currently playing a playlist. Switch to the playlist tab to control it. If you would like to stop your playlist and play a track from your library, just click the 'play' button next to the track you would like to listen to.",
			'playlist_button_label' => "Start Playlist",
			'playlist_label_notice' => "You are not currently playing the playlist."
		], $atts );
				$no_playlist_notice = $args['no_playlist_notice'];
				$playlist_button_label = $args['playlist_button_label'];
				$playlist_label_notice = $args['playlist_label_notice'];
				$player_control_notice = $args['player_control_notice'];
        $template = btn_podcasts()->template_part_path( $args['template'] );
		$html = "";
        $pods = btn_podcasts()->user()->generate_group_pods();
        $pods_playlist = btn_podcasts()->user()->generate_playlist();
		$pods_sleep_timer = btn_podcasts()->user()->sleep_timer();
        $key = btn_podcasts()->pods()->get_user_meta_key();
		$sleep_timer_label = $args['sleep_timer_label'];
        $user_id = get_current_user_id();
        $user_meta = get_user_meta($user_id, $key, true);
        if($template > ''){
            include $template;
        }
        btn_podcasts()->frontend()->set_json('podcasts', $pods);
        btn_podcasts()->frontend()->set_json('pods_playlist', $pods_playlist);
		btn_podcasts()->frontend()->set_json('pods_playlist_data', $user_meta);
		$user_id = get_current_user_id();
		$listen = get_user_meta($user_id, 'podcast_listend', true);
		if(empty($listen)){
			$contact_id = memb_getContactIdByUserId($user_id);
			btn_podcasts()->memberium()->set_tags('2085',$contact_id);
			update_user_meta($user_id, 'podcast_listend','listen');
		}
        return $html;
	}

static function streak_show($atts, $content, $tag){
		$args = shortcode_atts( [
			'class_name'	=> '',
		], $atts );
		$user_id = get_current_user_id();
		$user_streak = get_user_meta($user_id,'btn_streak_enable',true);
		$user_streak = $user_streak = ''? 1 : $user_streak;
		$html ="";
		$html .="<div class=\"btn-wrapper-streak\">";
					$html .="<label class=\"btn-switch\">Show Streak Counters</label>";
					$html .="<label class=\"switch\">";
					$check = ($user_streak == 0)? "" : "checked";
					$html .="<input type=\"checkbox\" {$check}>";
					$html .="<span class=\"slider round\"></span>";
					$html .="</label>";
		$html .="</div>";
		
        btn_podcasts()->frontend()->set_json('show_podcast', $html);
		
        return $html;
	}
	
	static function achievement_show($atts, $content, $tag){
		$args = shortcode_atts( [
			'class_name'	=> '',
		], $atts );
		$user_id = get_current_user_id();
		$user_achievement = get_user_meta($user_id,'btn_achivement_enable',true);
		$user_achievement = $user_achievement = ''? 1 : $user_achievement;
		$html ="";
		$html .="<div class=\"btn-wrapper-achievement\">";
					$html .="<label class=\"btn-switch-achivement\">Show Achievements</label>";
					$html .="<label class=\"switch\">";
					$check = ($user_achievement == 0)? "" : "checked";
					$html .="<input type=\"checkbox\" {$check}>";
					$html .="<span class=\"slider round\"></span>";
					$html .="</label>";
		$html .="</div>";
		
        btn_podcasts()->frontend()->set_json('show_podcast', $html);
		
        return $html;
	}
	
	static function achievement_show_check($atts, $content, $tag){
		$args = shortcode_atts( [
			'class_name'	=> '',
		], $atts );
		$user_id = get_current_user_id();
		$user_streak = get_user_meta($user_id,'btn_achivement_enable',true);
		$user_streak = ($user_streak == 0)? 'btn-hidden-achievement' : '';
		
        return $user_streak;
	}
	
	static function streak_show_check($atts, $content, $tag){
		$args = shortcode_atts( [
			'class_name'	=> '',
		], $atts );
		$user_id = get_current_user_id();
		$user_streak = get_user_meta($user_id,'btn_streak_enable',true);
		$user_streak = empty($user_streak)? 'btn-hidden' : '';
		
        return $user_streak;
	}
    function __construct(){}
}
