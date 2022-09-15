<?php
$html .="<div class=\"podcasts-playlist-container\">";
    $html .="<ul class=\"podcasts-tab\" id=\"podcasts-tab\">";
        $html .="<li class=\"podcasts-list\">";
            $html .= "<a href=\"#btn-podcasts-library\" id=\"btn-podcasts-library-link\" class=\"btn-podcasts-title active\">Library</a>";
        $html .="</li>";
        $html .="<li class=\"podcasts-list\">";
            $html .= "<a href=\"#btn-podcasts-playlist\" class=\"btn-podcasts-title\" id=\"btn-podcasts-playlists\">Playlist</a>";
        $html .="</li>";
    $html .="</ul>";

    $html .="<div class=\"btn-podcasts-tab-content\">";
      $html .="<div class=\"tab-pane active\" id=\"btn-podcasts-library\">";
                include btn_podcasts()->template_part_path('podcasts/library.php');
      $html .="</div>";
       $html .="<div class=\"tab-pane\" id=\"btn-podcasts-playlist\">";
                include btn_podcasts()->template_part_path('podcasts/playlist.php');
      $html .="</div>";
    $html .="</div>";
    $html .="</div>";
