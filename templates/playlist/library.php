<?php
/*global $wpdb;
$table = "{$wpdb->prefix}podcast";*/
$user_id = get_current_user_id();
$podcast_listen_data = get_user_meta($user_id, 'podcast_listen_data', true);
foreach($pods as $key => $podcasts){
    $locked = $podcasts['locked'];
    $img_url = $podcasts['group_cover_img'];
    $img_url = ($img_url > '') ? $img_url : BTN_PODCASTS_ASSESTS_URL."images/default-img.png";
    $locked_img = BTN_PODCASTS_ASSESTS_URL."images/locked.png";
    $warning_img = BTN_PODCASTS_ASSESTS_URL."images/warning.png";
    $amplitude_playlist = 'btn_podcasts_group_'.$key;
    $player_index = 0;
	$tracks = ($podcasts['tracks'] > 1)? "tracks" : "track";
    $locked_class = (!$locked)? "podcast-has-access" : "podcast-no-access";
    $html .="<div class=\"podcasts-container {$amplitude_playlist}-wrapper {$locked_class}\">";
        if(!$locked){
            $html .= "<a class=\"btn-podcasts-group-title\" href=\"#{$amplitude_playlist}_cont\"><img src=\"{$img_url}\"><div class=\"btn-pods-title\"> {$podcasts['groups_title']} <span>{$podcasts['tracks']} {$tracks}</span></div></a>";
            $html .= "<div class=\"btn-podcasts-group-playlist\" id=\"{$amplitude_playlist}_cont\">";
            foreach($podcasts["btn_podcasts_group"] as $players){
                foreach($players as $player){
                    $id = $player['id'];
                    $cover_img = $player['cover_art_url'];
                    if(!empty($user_meta)){
                        $add_to_playlist_class = (!in_array($id, $user_meta))? 'btn-podcasts-add-to-playlist':'btn-podcasts-remove-to-playlist';
                        $add_text = (!in_array($id, $user_meta))? "Add To Playlist" : "Remove from Playlist";
                    }
                    else{
                        $add_to_playlist_class = 'btn-podcasts-add-to-playlist';
                        $add_text = "Add To Playlist";
                    }


                    /*$sql = "SELECT * FROM {$table} WHERE podcast_id = %s AND user_id = %s ";
                    $podcast = $wpdb->get_row( $wpdb->prepare($sql, $id, $user_id));*/
					
                    $number = "";
					if(!empty($podcast_listen_data)){
						if(in_array($id, $podcast_listen_data)){
							$number = "btn-podcast-listened-check";
						}
					}
			
                    $cover_img = ($cover_img > '') ? $cover_img : BTN_PODCASTS_ASSESTS_URL."images/default-img.png";
                    $html .="<div class=\"song-to-add {$number}\" song-to-add =\"{$player_index}\" data-amplitude-playlist=\"{$amplitude_playlist}\">";
                      $html .="<img src=\"{$cover_img}\"/>";
                      $html .= " <span class=\"individual-song-name\">{$player['name']}</span>";
                      $html .="<a class=\"add-to-playlist-button btn-podcasts-add-song {$add_to_playlist_class}\" data-add-to-playlist=\"{$id}\" data-group-add =\"{$amplitude_playlist}\" song-to-add =\"{$player_index}\">";
                        $html .= $add_text;
                      $html .="</a>";
                      $html .="<a class=\"btn-play-pod white-player-playlist-song amplitude-song-container amplitude-play-pause btn-podcasts-playlist-item amplitude-paused amplitude-active-song-container\"  data-add-to-playlist-add=\"{$id}\" song-to-add =\"{$player_index}\" data-playlist-state=\"{$add_to_playlist_class}\" data-amplitude-song-index=\"{$player_index}\" data-group-add =\"{$amplitude_playlist}\" data-amplitude-playlist=\"{$amplitude_playlist}\">";
                        $html .= "Play Pod";
                      $html .="</a>";
                    $html .="</div>";
                    $player_index++;
                }
            }
            $html .="</div>";
        }
        else{
            $html .= "<div class=\"btn-podcasts-cont btn-podcasts-locked\">";
                $locked_text = (!empty($podcasts['locked_text']));
                $html .= "<div class=\"btn-podcasts-group-title\" href=\"#{$amplitude_playlist}_cont\">
                    <img class=\"img-locked\" src=\"{$locked_img}\">
                    <div class=\"btn-pods-title\"> {$podcasts['groups_title']}";
                    $html .="<span class=\"btn-pods-wrapper-title\">";
                if($locked_text){
                    $html .="<img src=\"{$warning_img}\">";
                }
                $html .= "<span>{$podcasts['locked_text']}</span></span></div></div>";
            $html .= "</div>";
        }
    $html .="</div>";
}

?>
