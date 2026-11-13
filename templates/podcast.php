<?php
$user_id = get_current_user_id();

$user_streak = get_user_meta($user_id,'btn_streak_enable',true);
$user_streak = $user_streak = ''? 1 : $user_streak;

$html .="<div class=\"podcast-white-container\">";

     $html .="<div class=\"btn-podcasts-no-playlist\" style=\"display:none;\">{$no_playlist_notice}</div>";
	$html .="<div class=\"podcast-white-container-inner\">";
     $html .="<div class=\"left btn-podcats-player-wrapper\">";

 		$html .="<div id=\"white-player-wrapper\">";
 $html .="<div id=\"white-player\">";
       $html .="<div id=\"white-player-center-img\">";
         $html .="<img data-amplitude-song-info=\"cover_art_url\" class=\"main-album-art\"/>";
         $html .="<div class=\"song-meta-data\">";
           $html .="<span data-amplitude-song-info=\"name\" class=\"song-name\"></span>";
           $html .="<span data-amplitude-song-info=\"artist\" class=\"song-artist\"></span>";
         $html .="</div>";
       $html .="</div>";

               $html .="<div id=\"white-player-controls\">";
                  $html .="<div class=\"amplitude-shuffle amplitude-shuffle-off tooltip\" id=\"shuffle\"><span class=\"tooltiptext\">Shuffle</span></div>";
                  $html .="<div class=\"amplitude-prev \" id=\"previous\"></div>";
                  $html .="<div class=\"amplitude-play-pause amplitude-paused\" id=\"play-pause\"></div>";
                  $html .="<div class=\"amplitude-next \" id=\"next\"></div>";
                  $html .="<div class=\"amplitude-repeat tooltip\" id=\"repeat\"><span class=\"tooltiptext\">Repeat</span></div>";
				  //$html .="<div class=\"amplitude-repeat-song tooltip\" id=\"repeat\"><span class=\"tooltiptext\">Repeat</span></div>";
                $html .="</div>";


         $html .="<div id=\"white-player-center\">";
           $html .="<div class=\"time-progress\">";
             $html .="<div id=\"progress-container\">";
               $html .="<input type=\"range\" class=\"amplitude-song-slider\"/>";
               $html .="<progress id=\"song-played-progress\" class=\"amplitude-song-played-progress\"></progress>";
               $html .="<progress id=\"song-buffered-progress\" class=\"amplitude-buffered-progress\" value=\"0\"></progress>";
             $html .="</div>";
             $html .="<div class=\"time-container\">";
               $html .="<span class=\"current-time\">";
                 $html .="<span class=\"amplitude-current-minutes\"></span>:<span class=\"amplitude-current-seconds\"></span>";
               $html .="</span>";
               $html .="<span class=\"duration\">";
                   $html .="<span class=\"amplitude-duration-minutes\"></span>:<span class=\"amplitude-duration-seconds\"></span>";
                $html .="</span>";
             $html .="</div>";
           $html .="</div>";
         $html .="</div>";
		  $html .="</div>";
	$html .="<div class=\"btn-podcast-listen\" id=\"desktop-listen\">";
			 include btn_podcasts()->template_part_path('listen.php');
			 $html .="<a href=\"#\" class=\"streak-update\">Manually add 1 to your streak </a>";
		 $html .="</div>";
       $html .="</div>";

         $html .="<div id=\"white-player-playlist-container\">";
           $html .="<div class=\"white-player-playlist-top\">";
             $html .="<div>";
             $html .="</div>";
             $html .="<div>";
               $html .="<span class=\"queue\">Queue</span>";
             $html .="</div>";
             $html .="<div>";
                 $close = BTN_PODCASTS_ASSESTS_URL."images/";
                 $html .="<img src=\"{$close}close.svg\" class=\"close-playlist\"/>";
             $html .="</div>";
            $html .="</div>";
           $html .="<div class=\"white-player-up-next\">";
             $html .="My Playlist";
           $html .="</div>";


           $html .="<div class=\"white-player-playlist\">";
              include btn_podcasts()->template_part_path('podcasts/playlist-sleep-timer.php');
				$html .="<div class=\"btn-wrapper-streak\">";
					$html .="<label class=\"btn-switch\">Show Streak Counters</label>";
					$html .="<label class=\"switch\">";
					$check = ($user_streak == 0)? "" : "checked";
					$html .="<input type=\"checkbox\" {$check}>";
					$html .="<span class=\"slider round\"></span>";
					$html .="</label>";
				$html .="</div>";
              include btn_podcasts()->template_part_path('playlist/playlist.php');
           $html .="</div>";

           $html .="<div class=\"white-player-playlist-controls\">";
             $html .="<img data-amplitude-song-info=\"cover_art_url\" class=\"playlist-album-art\"/>";

             $html .="<div class=\"playlist-controls\">";
               $html .="<div class=\"playlist-meta-data\">";
                   $html .="<span data-amplitude-song-info=\"name\" class=\"song-name\"></span>";
                   $html .="<span data-amplitude-song-info=\"artist\" class=\"song-artist\"></span>";
               $html .="</div>";

               $html .="<div class=\"playlist-control-wrapper\">";
                 $html .="<div class=\"amplitude-prev\" id=\"playlist-previous\"></div>";
                 $html .="<div class=\"amplitude-play-pause\" id=\"playlist-play-pause\"></div>";
                 $html .="<div class=\"amplitude-next\" id=\"playlist-next\"></div>";
               $html .="</div>";
             $html .="</div>";
           $html .="</div>";
         $html .="</div>";
     $html .="</div>";
	$html .="<div class=\"btn-podcast-listen\" id=\"mobile-listen\">";

	//include btn_podcasts()->template_part_path('listen.php');
	$html .="</div>";
	$html .="</div>";
     $html .="<div class=\"right\">";
       include btn_podcasts()->template_part_path('playlist/library.php');
     $html .="</div>";
   $html .="</div>";
