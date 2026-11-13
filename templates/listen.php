<?php
//$consecutive_days = btn_podcasts()->pods()->pod_listen(0);
$current_streak = get_user_meta($user_id, 'podcast_current_streak', true);
if(empty($current_streak)){
	$current_consecutive = "0 Day";
}
else{
	$current_consecutive = $current_streak;
}

$longest = get_user_meta($user_id, 'memb__ConsecutivedayslistenedtoPods', true);
if($longest > 0){
	$consecutive_days = ($longest > 1)? $longest." Days" : $longest." Day";
}
else{
	if($current_streak > 0 ){
		$consecutive_days = "{$current_streak} Day";
	}
	else{
		$consecutive_days = "0 Day";
	}
	
}

//$last_week = btn_podcasts()->pods()->pod_listen(1);
//$last_month = btn_podcasts()->pods()->pod_listen(2);
//$last_3month = btn_podcasts()->pods()->pod_listen(3);
//$last_6month = btn_podcasts()->pods()->pod_listen(4);
//$last_year = btn_podcasts()->pods()->pod_listen(5);
$img_src= BTN_PODCASTS_ASSESTS_URL."images/icon_clock.png";
$cover_img = "<img src=\"{$img_src}\">";
$hide = ($user_streak == 0)? "btn-hidden": "";
$html .="<div class=\"podcast-calendar {$hide}\">";
$html .="<div class=\"podcast-item-listen podcast-current-streak\">";
            $html .="{$cover_img}";
			$html .="<div class=\"podcast-listen-wrapper\">";

				$html .="<div class=\"podcast-count current-streak\">{$current_consecutive}</div>";
				$html .="<h3>Current Streak</h3>";
			 $html .="</div>";
        $html .="</div>";
    $html .="<div class=\"podcast-item-listen podcast-consecutive-days\">";

	$html .="{$cover_img}";
	$html .="<div class=\"podcast-listen-wrapper\">";
$html .="<div class=\"podcast-count long-streak\">{$consecutive_days}</div>";
		$html .="<h3>Longest Streak</h3>";

	$html .="</div>";
    $html .="</div>";
/*
        $html .="<div class=\"podcast-item-listen podcast-item-listen-custom\">";
             $html .="{$cover_img}";
			$html .="<div class=\"podcast-listen-wrapper\">";

            $html .="<div class=\"podcast-count\">{$last_week}</div>";
		$html .="<h3>Last 7 days</h3>";
			$html .="</div>";
        $html .="</div>";
        $html .="<div class=\"podcast-item-listen  podcast-item-listen-custom\">";


             $html .="{$cover_img}";
$html .="<div class=\"podcast-listen-wrapper\">";
            $html .="<div class=\"podcast-count\">{$last_month}</div>";
 $html .="<h3>Last 30 days</h3>";
        $html .="</div>";
$html .="</div>";
      */
        /*$html .="<div class=\"podcast-item-listen\">";
            $html .="<h3>Last 180 days</h3>";
            $html .="<i aria-hidden=\"true\" class=\"fas fa-microphone-alt\"></i>";
            $html .="<div class=\"podcast-count\">{$last_6month}</div>";
        $html .="</div>";
        $html .="<div class=\"podcast-item-listen\">";
            $html .="<h3>Last 365 days</h3>";
            $html .="<i aria-hidden=\"true\" class=\"fas fa-microphone-alt\"></i>";
            $html .="<div class=\"podcast-count\">{$last_year}</div>";
        $html .="</div>";*/
$html .="</div>";
?>