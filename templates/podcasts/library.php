<?php

foreach($pods as $key => $podcasts){
    $locked = $podcasts['locked'];
    $img_url = $podcasts['group_cover_img'];
    $img_url = ($img_url > '') ? $img_url : BTN_PODCASTS_ASSESTS_URL."images/default-img.png";
    $locked_img = BTN_PODCASTS_ASSESTS_URL."images/locked.png";
    $warning_img = BTN_PODCASTS_ASSESTS_URL."images/warning.png";
    $amplitude_playlist = 'btn_podcasts_group_'.$key;
    $player_index = 0;
	$tracks = ($podcasts['tracks'] > 1)? "tracks" : "track";
    $html .="<div class=\"podcasts-container {$amplitude_playlist}-wrapper\">";
        if(!$locked){
            $html .= "<div class=\"btn-podcasts-cont\">";
            $html .= "<a  class=\"btn-podcasts-group-title\" href=\"#{$amplitude_playlist}_cont\"><img src=\"{$img_url}\"><div class=\"btn-pods-title\"> {$podcasts['groups_title']} <span>{$podcasts['tracks']} {$tracks}</span></div></a>";
            $html .= "<div class=\"btn-podcasts-group-playlist\" id=\"{$amplitude_playlist}_cont\">";
                foreach($podcasts["btn_podcasts_group"] as $players){
                    foreach($players as $player){
                        $id = $player['id'];
                        $add_to_playlist_class = (!in_array($id, $user_meta))? 'btn-podcasts-add-to-playlist':'btn-podcasts-remove-to-playlist';
                        $html .= "<div class=\"black-player\">";
                        $html .= "<span data-add-to-playlist=\"{$id}\" data-group-add =\"{$amplitude_playlist}\" song-to-add =\"{$player_index}\" class=\"{$add_to_playlist_class} btn-podcasts-add-song\"></span>";
                          $html .= "<div class=\"black-player-playlist\">";
                            $html .= "<div class=\"black-player-song  amplitude-song-container amplitude-play-pause\" data-add-song-class=\"{$add_to_playlist_class}\" data-add-to-playlist =\"{$id}\" data-amplitude-song-index=\"{$player_index}\" data-amplitude-playlist=\"{$amplitude_playlist}\">";
                             $html .= " <div class=\"player-icon\"></div>";
                              $html .= "<div class=\"song-meta-container\">";
                               $html .= " <span class=\"individual-song-name\">{$player['name']}</span>";
                                $html .="</div>";
                              $html .= "</div>";
                            $html .= "</div>";
                          $html .= "</div>";
                        $player_index++;
                    }
                }
            $html .="</div>";
        $html .= "</div>";
        }
        else{
            $html .= "<div class=\"btn-podcasts-cont btn-podcasts-locked\">";
                $html .= "<div class=\"btn-podcasts-group-title\" href=\"#{$amplitude_playlist}_cont\"><img class=\"img-locked\" src=\"{$locked_img}\"><div class=\"btn-pods-title\"> {$podcasts['groups_title']} <span class=\"btn-pods-wrapper-title\"><img src=\"{$warning_img}\"> <span>{$podcasts['locked_text']}</span></span></div></div>";
            $html .= "</div>";
        }
$html .='</div>';
}

$amplitude_playlist = "btn_podcasts_group_16";
$html .="<div id=\"black-player-controls\" class=\"black-player-controls\">";
    $html .="<div class=\"black-player-controls-container\">";
      $html .="<img data-amplitude-song-info=\"cover_art_url\" data-amplitude-playlist=\"{$amplitude_playlist}\" class=\"playlist-album-art\"/>";
      $html .="<span data-amplitude-song-info=\"name\" data-amplitude-playlist=\"{$amplitude_playlist}\" class=\"song-name\"></span>";
      $html .="<div class=\"btn-podcast-player\">";
        $html .="<div class=\"amplitude-play-pause\" data-amplitude-playlist=\"{$amplitude_playlist}\" id=\"play-pause-black\"></div>";
        $html .= "<span data-add-to-playlist=\"\" data-group-add =\"{$amplitude_playlist}\" song-to-add =\"\" class=\"btn-podcasts-add-to-playlist btn-podcasts-add-song\"></span>";
      $html .="</div>";
      $html .="<div id=\"time-container\">";
          $html .="<div id=\"progress-container-black\">";
            $html .="<input type=\"range\" class=\"amplitude-song-slider\" data-amplitude-playlist=\"{$amplitude_playlist}\"/>";
            $html .="<progress id=\"song-played-progress-black\" class=\"amplitude-song-played-progress\" data-amplitude-playlist=\"{$amplitude_playlist}\"></progress>";
            $html .="<progress id=\"song-buffered-progress-black\" class=\"amplitude-buffered-progress\" value=\"0\"></progress>";
          $html .="</div>";
          $html .="<span class=\"current-time\">";
            $html .="<span class=\"amplitude-current-minutes\">00</span>:<span class=\"amplitude-current-seconds\">00</span>";
          $html .="</span>";
          $html .="<span class=\"duration\">";
            $html .="<span class=\"amplitude-duration-minutes\">03</span>:<span class=\"amplitude-duration-seconds\">00</span>";
          $html .="</span>";
      $html .="</div>";

    $html .="</div>";
    $html .="<div class=\"btn-podcasts-player-notice\">{$player_control_notice}</div>";
$html .="</div>";
?>
