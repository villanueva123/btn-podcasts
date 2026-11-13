'use strict';

var $doc = document;


// Document ready
var btnPodCastsReady = function btnPodCastsReady(callBack) {
  if ($doc.readyState !== 'loading') {
    callBack();
  } else if ($doc.addEventListener) {
    $doc.addEventListener('DOMContentLoaded', callBack);
  } else {
    $doc.attachEvent('onreadystatechange', function() {
      if ($doc.readyState === 'complete') {
        callBack();
      }
    });
  }
};

// Dom Ready
btnPodCastsReady(function() {
  if (typeof btnPodcastsData !== "undefined") {
     btnPodCasts(btnPodcastsData);
  }
});


var btnPodCasts = function(data) {
   var $btnTrigger = $doc.querySelector("#btn_streak_submit"),
       userId = data.user_id,
       $btnReset = $doc.querySelector("#btn_streak_reset");


     if (!$btnReset || !$btnTrigger) return;
     $btnReset.onclick = function(e){
         e.preventDefault();
         if (confirm('Are you sure you want to reset streak on this user? This will reset the current streak and longest streak to 0')) {
             btnPodCastsPost({
               action: 'btn_streak_reset_action',
               user_id: userId,
               nonce: data.nonce,
               }, function(response) {
                 if (response.success) {
                     location.reload();
                 }
             });
         } else {
          // Do nothing!
          console.log('Thing was not saved to the database.');
        }
     };
    $btnTrigger.onclick = function(e){
        var currentStreakOriginal = $doc.querySelector("input[name=btn_current_streak_original]").value,
            longestStreakOriginal = $doc.querySelector("input[name=btn_longest_streak_original]").value,
            currentStreak = $doc.querySelector("input[name=btn_current_streak]").value,
            longestStreak = $doc.querySelector("input[name=btn_longest_streak]").value;
        e.preventDefault();
        if( currentStreak != currentStreakOriginal || longestStreak !=longestStreakOriginal ){
            if(parseInt(longestStreak) < parseInt(currentStreak)){
                alert("Please Make sure the longest Streak is greather than the current streak");
            }
            else{
                btnPodCastsPost({
                  action: 'btn_streak_action',
                  currentStreakOriginal: parseInt(currentStreakOriginal),
                  longestStreakOriginal: parseInt(longestStreakOriginal),
                  currentStreak: parseInt(currentStreak),
                  longestStreak: parseInt(longestStreak),
                  user_id: userId,
                  nonce: data.nonce,
                  }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            }

        }
    };


};




// Utility : Make POST Request
var btnPodCastsPost = function(postData, callback) {
  var request = new XMLHttpRequest();
  var encodedData = Object.keys(postData).map(function(key) {
    return key + '=' + encodeURIComponent(postData[key])
  }).join('&');
  request.open('POST', btnPodcastsData.ajax_url, true);
  request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
  request.onload = function() {
    // Process the response
    if (request.status >= 200 && request.status < 300) {
      var response = false;
      try {
        response = JSON.parse(request.responseText);
      } catch (err) {
        response = false;
      }
      callback(response);
    } else {
      console.log({
        func: 'btnPodCastsPost',
        status: request.status,
        statusText: request.statusText
      });
    }
  }
  request.send(encodedData);
};
