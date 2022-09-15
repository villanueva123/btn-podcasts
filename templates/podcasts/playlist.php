<?php
    $amplitude_playlist = 'emancipator';
    $html .="<div class=\"podcasts-container\" id=\"podcasts-playlist-cont\">";
            include btn_podcasts()->template_part_path('podcasts/playlist-sleep-timer.php');
            include btn_podcasts()->template_part_path('podcasts/playlist-item.php');
    $html .='</div>';

        $html .="<div id=\"black-player-controls\">";
            $html .="<div class=\"black-player-controls-container\" id=\"btn-player-playlist\">";
             $html .="<div class=\"btn-playlist-img-cont\">";
                  $html .="<img data-amplitude-song-info=\"cover_art_url\"  class=\"playlist-album-art\"/>";
                  $html .="<span data-amplitude-song-info=\"name\"  class=\"song-name\"></span>";
              $html .="</div>";

                $html .="<div id=\"progress-container-black\">";
                  $html .="<input type=\"range\" class=\"amplitude-song-slider\" />";
                  $html .="<progress id=\"song-played-progress-black\" class=\"amplitude-song-played-progress\" ></progress>";
                  $html .="<progress id=\"song-buffered-progress-black\" class=\"amplitude-buffered-progress\" value=\"0\"></progress>";
                $html .="</div>";
                $html .="<div id=\"time-container\">";
                $html .="<span class=\"current-time\">";
                  $html .="<span class=\"amplitude-current-minutes\">00</span>:<span class=\"amplitude-current-seconds\">00</span>";
                $html .="</span>";
                $html .="<span class=\"duration\">";
                  $html .="<span class=\"amplitude-duration-minutes\">03</span>:<span class=\"amplitude-duration-seconds\">00</span>";
                $html .="</span>";
                $html .="</div>";


              $html .="<div class=\"btn-playlist-container-control\">";
                $html .="<div class=\"amplitude-repeat repeat-playlist\" data-tooltip = \"Repeat Playlist\" id=\"repeat-black-playlist\"></div>";
                $html .="<div class=\"amplitude-prev\"  id=\"previous-black\"></div>";
                $html .="<div class=\"amplitude-play-pause\"  id=\"play-pause-black\"></div>";
                $html .="<div class=\"amplitude-next\"  id=\"next-black\"></div>";
                $html .="<div class=\"amplitude-repeat-song\" data-tooltip = \"Repeat Song\" id=\"repeat-black\"></div>";

              $html .="</div>";
            $html .="</div>";
        $html .="</div>";





?>
