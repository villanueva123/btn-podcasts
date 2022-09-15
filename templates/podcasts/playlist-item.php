<?php
$html .= "<div class=\"right\">";
foreach($pods_playlist as $key => $podcasts){
    $player_index = 0;
            foreach($podcasts["songs"] as $players){
                    $cover_img = $players['cover_art_url'];
                    $cover_img = ($cover_img > '') ? $cover_img : BTN_PODCASTS_ASSESTS_URL."images/default-img.png";
                    $html .= "<div class=\"black-player\">";
                    $html .= "<span song-to-add =\"{$player_index}\"  data-add-to-playlist=\"{$players['id']}\" class=\"btn-podcasts-remove-to-playlist btn-podcasts-add-song\"></span>";
                      $html .= "<div class=\"black-player-playlist\">";
                        $html .= "<div class=\"black-player-song amplitude-song-container amplitude-play-pause\" data-amplitude-song-index=\"{$player_index}\" >";
                         $html .= " <div class=\"player-img\"><img src=\"{$cover_img}\"></div>";
                          $html .= "<div class=\"song-meta-container\">";
                           $html .= " <span class=\"individual-song-name\">{$players['name']}</span>";
                            $html .="</div>";
                          $html .= "</div>";
                        $html .= "</div>";
                      $html .= "</div>";
                    $player_index++;
            }

}
$html .= "</div>";
