<?php
foreach($pods_playlist as $key => $podcasts){
    $player_index = 0;
    foreach($podcasts["songs"] as $players){
        $cover_img = $players['cover_art_url'];
        $cover_img = ($cover_img > '') ? $cover_img : BTN_PODCASTS_ASSESTS_URL."images/default-img.png";
        $html .="<div class=\"white-player-playlist-song amplitude-song-container amplitude-play-pause btn-podcasts-playlist-item\"  data-amplitude-song-index=\"{$player_index}\">";
          $html .="<img src=\"{$cover_img}\"/>";
          $html .="<div class=\"playlist-song-meta\">";
            $html .="<span class=\"playlist-song-name\">{$players['name']}</span>";
            $html .="<span class=\"playlist-artist-album\">Slimpod</span>";
            $html .= "<span song-to-add =\"{$player_index}\"  data-add-to-playlist=\"{$players['id']}\" class=\"btn-podcasts-remove-to-playlist btn-podcasts-add-song \"></span>";
          $html .="</div>";
        $html .="</div>";
        $player_index++;
    }
}
?>
