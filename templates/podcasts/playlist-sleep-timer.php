<?php

$html .="<div class=\"btn-playlist-sleep-timer\">";
    $html .="<label>Sleep Timer: </label>";
    $html .= "<select name = \"btn-playlist-sleep-timer-selector\" class=\"btn-playlist-sleep-timer-selector\">";
             foreach($pods_sleep_timer as $key => $sleep_timer){
                 $html .="<option value = \"{$sleep_timer}\">{$key}</option>";
             }
    $html .="</select>";
