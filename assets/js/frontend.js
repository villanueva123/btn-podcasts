'use strict';

var $doc = document,
  btnPodCastsPlayer = false,
  btnPodCastsPlayerData = {},
  btnPlayer = {},
  btnPodCastPlayList = '',
  btnPodCastsCount = 0,
  btnPodCastsTime = 0,
  btnPodCastsTimerIsOn = 0,
  $btnPodCastsModal = null,
  btnPodCastsPlaylistDefault = {},
  btnPodCastPlayListUserData = [],
  btnPodCastPlayListGenerateUserData = [],
  btnPodCastsPlaylistData = [],
  currentSongSeconds = '',
  currentIndex = '';


// Document ready
var btnPodCastsReady = function btnLmsReady(callBack) {
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

  if (typeof btn_podcasts_data !== "undefined") {
	 if (btn_podcasts_data.hasOwnProperty('show_podcast')) { 
		 
		 var streak = $doc.querySelector(".btn-wrapper-streak input");
		 var achievement = $doc.querySelector(".btn-wrapper-achievement input");

         if (streak && !btn_podcasts_data.hasOwnProperty('podcasts')) {
		 streak.addEventListener("change", function() {
			 var streakEnable = 1,
				 wrapper = $doc.querySelector(".podcast-calendar-wrapper");
			
			 if(streak.checked){
				 streakEnable = 1;
                 if (wrapper) wrapper.classList.remove("btn-hidden");
			 }
			 else{
				 streakEnable = 0;
                 if (wrapper) wrapper.classList.add("btn-hidden");
			 }
			 btnPodCastsPost({
				 action: "btn_podcasts_streak",
				 streak_enable: streakEnable }
							 ,function ( response ) {

			 });
		 },false);
		 
		 }
         if (achievement) achievement.addEventListener("change", function() {
			 var streakEnable = 1,
				 wrapper = $doc.querySelectorAll(".podcast-achievement");
			 
			 if(achievement.checked){
				 streakEnable = 1;
				 for(var i = 0; i < wrapper.length;i++){
					 wrapper[i].classList.remove("btn-hidden-achievement");
				 }
				 
			 }
			 else{
				 streakEnable = 0;
				 for(var i = 0; i < wrapper.length;i++){
					 wrapper[i].classList.add("btn-hidden-achievement");
				 }
				
			 }
			 btnPodCastsPost({
				 action: "btn_podcasts_achievement",
				 streak_enable: streakEnable }
							 ,function ( response ) {

			 });
		 },false); 
	 }  
	  
    if (btn_podcasts_data.hasOwnProperty('podcasts')) {
      if(btn_podcasts_data.hasOwnProperty('pods_playlist_data')){
  		  var isArray = Array.isArray(btn_podcasts_data.pods_playlist_data);
  			btnPodCastsPlaylistData = btn_podcasts_data.pods_playlist_data;
  		  if(!isArray){
  			  btnPodCastsPlaylistData = Object.keys(btn_podcasts_data.pods_playlist_data);
  		  }
        btnPodCastPlayListUserData = JSON.parse(JSON.stringify(btnPodCastsPlaylistData));
        btnPodCastPlayListGenerateUserData = btnPodCastsPlaylistData;
      }
      btnPodCastsPlayer = btn_podcasts_data.podcasts;
      btnPodCastPlayList = btn_podcasts_data.pods_playlist;
      btnPodCastsPlaylistDefault = JSON.parse(JSON.stringify(btnPodCastPlayList));
      var btnPodCastsPlaylist = btnPodCasts(btnPodCastsPlayer);
      btnPodCastsPlaylist.init();

     //var btnPodCastsCalenders = btnPodCastsCalender();
     //btnPodCastsCalenders.init();

    }
  }

});


var btnPodCasts = function(data) {
  var thisPodCasts,
    sleepTimerSelector = $doc.querySelector(".btn-playlist-sleep-timer-selector"),
    checkSongPlaylist = (Array.isArray(btnPodCastPlayList['emancipator']['songs']) && btnPodCastPlayList['emancipator']['songs'].length)? true:false;

  return {
    init: function() {
      thisPodCasts = this;

      //console.log(btn_podcasts_data);
      if(checkSongPlaylist){
          btnPodCastsPlayerData = btnPodCastPlayList;
          console.log(btnPodCastPlayList['emancipator']['songs']);
      }
      Object.keys(btnPodCastsPlayer).forEach(function(key) {
        var podCastGroup = "btn_podcasts_group_" + key;
        btnPodCastsPlayerData[podCastGroup] = btnPodCastsPlayer[key]['btn_podcasts_group'];
      });

    if(checkSongPlaylist){
        btnPlayer['songs'] = btnPodCastsPlayerData['emancipator']['songs'];
    }
    else{
        btnPlayer['songs'] = [];
    }
	  
      btnPlayer['playlists'] = btnPodCastsPlayerData;
      thisPodCasts.initPlayer();
      Amplitude.init(btnPlayer);
      Amplitude.pause();
      //thisPodCasts.initTabs();
      thisPodCasts.addAndRemoveToPlayList();
      thisPodCasts.groupToggle();
      thisPodCasts.sleepTimer();
      thisPodCasts.libraryPlaySong();
      thisPodCasts.playerSongController();
      //thisPodCasts.startPlaylist();
      thisPodCasts.newPlayer();
	  thisPodCasts.stopAutoNextLibraryTab();
	  thisPodCasts.saveAudioListened();
	
      //Added this to fixed the time duration on mobile
    },
    newPlayer: function(){
        /*
          Shows the playlist
       
        $doc.getElementsByClassName('show-playlist')[0].addEventListener('click', function(){
          $doc.getElementById('white-player-playlist-container').classList.remove('slide-out-top');
          $doc.getElementById('white-player-playlist-container').classList.add('slide-in-top');
          $doc.getElementById('white-player-playlist-container').style.display = "block";
        });

        /*
          Hides the playlist
       
        $doc.getElementsByClassName('close-playlist')[0].addEventListener('click', function(){
          $doc.getElementById('white-player-playlist-container').classList.remove('slide-in-top');
          $doc.getElementById('white-player-playlist-container').classList.add('slide-out-top');
          $doc.getElementById('white-player-playlist-container').style.display = "none";
        }); */

        var $playerButton = $doc.querySelectorAll('.btn-play-pod'),
            scrollArgs = {
                behavior    : 'smooth',
                block       : 'nearest',
                inline      : 'start'
            };
        for (var i = 0; i < $playerButton.length; i++) {
	
          $playerButton[i].addEventListener('click', function(e) {
              var addToPlayList = this.closest(".song-to-add").querySelector(".add-to-playlist-button"),
                  $parent = $doc.querySelector("#white-player-controls"),
                  playList =  $parent.querySelector(".add-to-playlist-button");
              
                  if(playList !== null){
                      playList.remove();
                  }
                  setTimeout(function(){
                      $parent.insertAdjacentHTML('beforeend', addToPlayList.outerHTML);
                  },500);

                  $doc.querySelector(".btn-podcats-player-wrapper").style.display = "flex";
                  $doc.querySelector(".btn-podcasts-no-playlist").style.display = "none";
              for (var a = 0; a < $playerButton.length; a++) {
                  $playerButton[a].innerHTML = "Play Pod";
              }
              this.innerHTML = "Pause Pod";
              if(this.classList.contains('amplitude-playing')){
                  this.innerHTML = "Pause Pod";
                  $doc.getElementById("white-player").scrollIntoView(scrollArgs);
              }
              else{
                  this.innerHTML = "Play Pod";
              }

          }, false);
        }

    },
    initTabs: function() {
		
      var $tab = $doc.getElementById('podcasts-tab');
      $tab.addEventListener('click', function(e) {
        e.preventDefault();
        var actives = $doc.querySelectorAll('.active');
        for (var i = 0; i < actives.length; i++) {
          actives[i].classList.remove('active');
        }
        event.target.className += ' active';
        $doc.getElementById(event.target.href.split('#')[1]).className += ' active';
        thisPodCasts.getPlaylistSongState();
      }, false);

    },
    stopAutoNextLibraryTab: function(){
        // Keep legacy method name; remove its empty 1-second polling timer.
    },
    initPlayer: function(){
      var playerSongsCount = btnPodCastPlayListGenerateUserData.length;
      //console.log(playerSongsCount);
      if(playerSongsCount == 0){
        $doc.querySelector(".btn-podcats-player-wrapper").style.display = "none";
        $doc.querySelector(".btn-podcasts-no-playlist").style.display = "block";
      }
      else{
         $doc.querySelector(".btn-podcats-player-wrapper").style.display = "flex";
         $doc.querySelector(".btn-podcasts-no-playlist").style.display = "none";
      }
		
	 var streak = $doc.querySelector(".btn-wrapper-streak input");
         if (streak) streak.addEventListener("change", function() {
			 var streakEnable = 1,
				 wrapper = $doc.querySelector(".podcast-calendar");
			 if(streak.checked){
				 streakEnable = 1;
                 if (wrapper) wrapper.classList.remove("btn-hidden");
			 }
			 else{
				 streakEnable = 0;
                 if (wrapper) wrapper.classList.add("btn-hidden");
			 }
			 btnPodCastsPost({
				 action: "btn_podcasts_streak",
				 streak_enable: streakEnable }
							 ,function ( response ) {
	console.log(response);
			 });
		 },false);
    },
    savePlayerList: function(){
        var saveData = (JSON.stringify(btnPodCastPlayListUserData) === JSON.stringify(btnPodCastPlayListGenerateUserData))? true:false;
          if(!saveData){
            var data = new FormData();
            data.append('action', 'btn_podcasts_action');
            data.append('data', btnPodCastPlayListGenerateUserData);
            navigator.sendBeacon(btn_podcasts_data.ajax_url, data);
          }
    },
    startPlaylist: function(){

      var startPlaylistButton = $doc.querySelector(".btn-podcasts-start-playlist");
          startPlaylistButton.addEventListener('click', function(e) {
          e.preventDefault();
            Amplitude.next();
            Amplitude.pause();
            Amplitude.playSongAtIndex(0);
            thisPodCasts.getPlaylistSongState();
            this.setAttribute("btn-podcasts-playlist-start","started");
          }, false);
    },
    getPlaylistSongState: function(){
      var $parentLibrary = $doc.querySelector("#btn-podcasts-library"),
          $parentPlaylist = $doc.querySelector("#btn-podcasts-playlist"),
          getLibraryActiveSong = $parentLibrary.querySelectorAll(".podcasts-container .amplitude-playing"),
          getPlaylistActiveSong = $parentPlaylist.querySelectorAll(".white-player .amplitude-playing"),
          playerSongsCount = btnPodCastPlayListGenerateUserData.length;
      if(Amplitude.getSongPlayedPercentage() > 0 && playerSongsCount > 0 && getPlaylistActiveSong.length > 0){
          $parentLibrary.querySelector(".white-player-controls-container").style.display = "none";
          $parentLibrary.querySelector(".btn-podcasts-player-notice").style.display = "block";
      }else{
          $parentLibrary.querySelector(".white-player-controls-container").style.display = "block";
          $parentLibrary.querySelector(".btn-podcasts-player-notice").style.display = "none";

      }

      //Playlist
      if(getPlaylistActiveSong.length > 0){
          $parentPlaylist.querySelector(".white-player-controls-container").style.display = "block";
          $parentPlaylist.querySelector(".btn-podcasts-start-playlist").style.display = "none";
      }else{
          $parentPlaylist.querySelector(".white-player-controls-container").style.display = "none";
          $parentPlaylist.querySelector(".btn-podcasts-start-playlist").style.display = "block";
      }


    },
    playerSongController: function() {
      $doc.addEventListener('click', function(event) {
        if(event.target.closest('.btn-podcasts-playlist-item')){

        }
        if (!event.target.closest('.white-player-song')) return;
          thisPodCasts.getPlaylistSongState();
      }, false);
    },
    libraryPlaySong: function() {
      var $playerButton = $doc.querySelectorAll('.add-to-playlist-button');
      for (var i = 0; i < $playerButton.length; i++) {
        $playerButton[i].addEventListener('click', function(e) {
          var playList = this.getAttribute("data-amplitude-playlist");
        }, false);
      }

    },
    addAndRemoveToPlayList: function() {
      //console.log(btnPodCastsIsMobileDevice() + "test");
      if (btnPodCastsIsMobileDevice()) {
        // Then we bind via thát event. This way we only bind one event, instead of the two as below
        $doc.addEventListener('touchstart', function(event) {
      			if (event.target.matches('.btn-podcasts-add-to-playlist')) {
      				event.preventDefault();
      				thisPodCasts.addSongData(event.target);
      			} else if (event.target.matches('.btn-podcasts-remove-to-playlist')) {
      				event.preventDefault();
      				thisPodCasts.removeSongData(event.target);
      			} else {
      				return;
      			}
        }, false);
      } else {
        $doc.addEventListener('click', function(event) {
      			if (event.target.matches('.btn-podcasts-add-to-playlist')) {
      				event.preventDefault();
      				thisPodCasts.addSongData(event.target);
      			} else if (event.target.matches('.btn-podcasts-remove-to-playlist')) {
      				event.preventDefault();
					
					
      				thisPodCasts.removeSongData(event.target);
      			}else {
      				return;
      			}
        }, false);
      }
    },
    groupToggle: function() {
      var groupToggle = $doc.querySelectorAll('.btn-podcasts-group-title');
      for (var i = 0; i < groupToggle.length; i++) {
        groupToggle[i].addEventListener('click', function(e) {
          e.preventDefault();
		  var locked = this.closest(".btn-podcasts-locked");
			if(locked !== null && locked !== ''){

			}
			else{
				if (this.classList.contains('btn-podcasts-active')) {
					this.classList.remove("btn-podcasts-active");
					$doc.getElementById(this.href.split('#')[1]).classList.remove("btn-podcasts-group-active");
				} else {
					this.classList.add("btn-podcasts-active");
					$doc.getElementById(this.href.split('#')[1]).classList.add("btn-podcasts-group-active");

				}

			}

        }, false);
      }
    },
    removeSongData: function($this) {
      var playList = $this.getAttribute("data-add-to-playlist"),
        songToAddIndex = $this.getAttribute("song-to-add"),
        $parent = $doc.querySelector("#white-player-playlist-container"),
        updateClass = $doc.querySelector(".right [data-add-to-playlist='" + playList + "']"),
        songToAddRemove = $parent.querySelector("[data-add-to-playlist='" + playList + "']"),
        $songToAddElement = $doc.querySelectorAll("[data-add-to-playlist='" + playList + "']"),
        $songToAddDiv = $doc.querySelector("[data-add-to-playlist = '" + playList + "']"),
        songToRemove = songToAddRemove.getAttribute("song-to-add"),
        addSongClass = null,
        $parentElement = $this.parentNode,
        btnPodCastsSongs = btnPlayer['songs'],
        removeIndex = null;
		
	  
		
      //btnPodCastsPreLoader($parentElement);
          Array.prototype.remove_by_value = function(val) {
              for (var i = 0; i < this.length; i++) {
                if (this[i] === val) {
                  this.splice(i, 1);
                  i--;
                }
              }
              return this;
          }

          btnPodCastPlayListGenerateUserData.remove_by_value(playList);
          //console.log(btnPodCastPlayListGenerateUserData);
          thisPodCasts.savePlayerList();
          thisPodCasts.initPlayer();
          for (var i = 0; i < $songToAddElement.length; i++) {
            $songToAddElement[i].classList.add("btn-podcasts-add-to-playlist");
            $songToAddElement[i].classList.remove("btn-podcasts-remove-to-playlist");
             $songToAddElement[i].innerHTML = "Add to Playlist";
          }
            songToAddRemove.closest(".white-player-playlist-song").remove();
            var playListSongItemIndex = $parent.querySelectorAll(".btn-podcasts-add-song");
		    var percentage  = Amplitude.getSongPlayedPercentage();
            Amplitude.removeSong(songToRemove);
            var btnSongs = btnPodCastPlayListGenerateUserData.length;
            thisPodCasts.playSongItemIndexes();
            if(parseInt(songToRemove) === parseInt(currentIndex)){
              Amplitude.next();
              Amplitude.playSongAtIndex(0);
			  Amplitude.pause();
			  
			 var playerController = $doc.querySelectorAll(".amplitude-play-pause");
			  for (var i = 0; i < playerController.length; i++) {
				playerController[i].classList.remove("amplitude-playing");
				playerController[i].classList.add("amplitude-paused");
			  }
            }
			else{
			
				if(btnSongs == currentIndex){
					currentIndex =  currentIndex - 1;
					currentIndex = (currentIndex > 0)? currentIndex: 0;
					Amplitude.skipTo(currentSongSeconds, currentIndex);
				}
				else{
					Amplitude.skipTo(currentSongSeconds, currentIndex);
				}
			
		 		
			}
		    
		
		
			
            if (btnSongs === 0 ) {
                Amplitude.next();
            }
		   
            //Amplitude.bindNewElements();
            if ($songToAddDiv !== null && $songToAddDiv !== '') {
              addSongClass = $songToAddDiv.setAttribute("data-add-song-class", "btn-podcasts-add-to-playlist");
            }
            for (var i = 0; i < playListSongItemIndex.length; i++) {
              playListSongItemIndex[i].setAttribute("song-to-add", i);
            }

    },
    addSongData: function($this) {
      var playList = $this.getAttribute("data-add-to-playlist"),
        songToAddIndex = $this.getAttribute("song-to-add"),
        songGroup = $this.getAttribute("data-group-add"),
        $parent = $doc.querySelector("#btn-podcasts-playlist"),
        $thisSong = $this,
        $songToAddElement = $doc.querySelectorAll("a[data-add-to-playlist='" + playList + "']"),
        $songToAddDiv = $doc.querySelector("[data-add-to-playlist = '" + playList + "']"),
        addSongClass = $songToAddDiv.setAttribute("data-add-song-class", "btn-podcasts-remove-to-playlist"),
        $parentElement = $this.parentNode,
        checkPlayerSong = (Array.isArray(btnPlayer['songs']) && btnPlayer['songs'].length)? true: false;
        btnPodCastPlayListGenerateUserData.push(playList);
        for (var i = 0; i < $songToAddElement.length; i++) {
          $songToAddElement[i].classList.remove("btn-podcasts-add-to-playlist");
          $songToAddElement[i].classList.add("btn-podcasts-remove-to-playlist");
          $songToAddElement[i].innerHTML = "Remove from Playlist";
        }
        if(!checkPlayerSong){
          btnPlayer['songs'] = [];
        }
        var songToAdd = btnPlayer['playlists'][songGroup]['songs'][songToAddIndex],
          checkSongItem = $doc.querySelector("#btn-podcasts-playlist span[data-add-to-playlist='" + playList + "']"),
          newIndex = 0,
          $checkPlaylist = 0,
          newIndex = Amplitude.addSong(songToAdd);
        if (checkSongItem == null) {
          btnPodCastsAppendToSongDisplay(songToAdd, newIndex, playList);
        }
        if (btnPodCastPlayListGenerateUserData.length === 1) {
          Amplitude.next();
		  Amplitude.stop();
        }
        Amplitude.bindNewElements();

        //console.log(newIndex);


        thisPodCasts.savePlayerList();
        thisPodCasts.initPlayer();


    },
    playSongItemIndexes: function(start) {
      var $parent = $doc.querySelector("#white-player-playlist-container"),
        playListSongItem = $parent.querySelectorAll(".white-player-playlist-song");

      for (var i = 0; i < playListSongItem.length; i++) {
        playListSongItem[i].setAttribute("data-amplitude-song-index", i);
      }

    },
    sleepTimer: function() {
      thisPodCasts = this;
      sleepTimerSelector.addEventListener("change", function() {
        var value = this.value,
          selectorValue = (value * 60),
          testTimer = value,
          lastSongIndex = btnPlayer['songs'].length - 1;
        //console.log(lastSongIndex);
        btnPodCastsStartCount();
        var timer = setInterval(function() {
		 console.log({
				  selectorValue:selectorValue,
				  timer:btnPodCastsCount
			  })
          //To be remove, used for testing the sleep timer only
          if (value == 20 && btnPodCastsCount == testTimer) {
            thisPodCasts.playerControllerPause(timer);
          }
			
          //Sleep Timer
          if (btnPodCastsCount == selectorValue && value > 0) {
			
            thisPodCasts.playerControllerPause(timer);
          }

          if (value == -1 && lastSongIndex == Amplitude.getActiveIndex() && Amplitude.getSongPlayedPercentage() > 99) {
            thisPodCasts.playerControllerPause(timer);
          }

        }, 1000);

        if (value == 0) {
          btnPodCastsStopCount();
        }
      });
    },
    saveAudioListened: function (){
        var manualStreakButton = document.querySelector(".streak-update");
        if (!manualStreakButton) return;
		manualStreakButton.addEventListener('click', function(event) {
				event.preventDefault();
				
				var duration_save = 500,
                    activeSong_save = 2662;
                btnPodCastsPost({
                         action: "btn_podcasts_listened",
                         duration : duration_save,
                         podcast_id: activeSong_save
				}
                    ,function ( response ) {
                    if (!response || !response.success || !response.data) return;
					if(response.data.already_updated == 1){
						alert("You can update your streak again tomorrow");
					}
					else{
						document.querySelector(".current-streak").textContent   = response.data.current_streak;
						document.querySelector(".long-streak").textContent   = response.data.long_streak;
						makeItRain();
					}
                });
			
		 }, false);
		
        // Qualified listening is now handled by the event-driven listener.
        // Do not poll or send repeated AJAX requests while audio is playing.
    },
    playerControllerPause: function(timer) {
      var playerController = $doc.querySelectorAll(".amplitude-play-pause");
      for (var i = 0; i < playerController.length; i++) {
        playerController[i].classList.remove("amplitude-playing");
        playerController[i].classList.add("amplitude-paused");
      }

      var selectOptions = sleepTimerSelector.options;
      //Loop through these options using a for loop.
      for (var opt, j = 0; opt = selectOptions[j]; j++) {
        //If the option of value is equal to the option we want to select.
        if (opt.value == 0) {
          //Select the option and break out of the for loop.
          sleepTimerSelector.selectedIndex = j;
          break;
        }
      }
      btnPodCastsCount = 0;
      Amplitude.stop();
      btnPodCastsStopCount();
      clearInterval(timer);

    }

  }
};


var btnPodCastsTimedCount = function() {
  btnPodCastsCount = btnPodCastsCount + 1;
  btnPodCastsTime = setTimeout(btnPodCastsTimedCount, 1000);  
}

var btnPodCastsStartCount = function() {
  if (!btnPodCastsTimerIsOn) {
    btnPodCastsTimerIsOn = 1;
    btnPodCastsTimedCount();
  }
}

var btnPodCastsStopCount = function() {
  clearTimeout(btnPodCastsTime);
  btnPodCastsTimerIsOn = 0;
}


var btnPodCastsAppendToSongDisplay = function(song, index, id) {

  var playlistElement = document.querySelector('.white-player-playlist'),
    playlistSongRemove = document.createElement('span'),
    playListSongItem = document.createElement('div'),
    playlistSongMeta = document.createElement('div'),
    playlistSongArtist = document.createElement('span'),
    playlistSongImgCont = document.createElement('div'),
    playlistSongImg = document.createElement('img'),
    playlistSongName = document.createElement('span');

  //Song Remove
  playlistSongRemove.setAttribute('class', 'btn-podcasts-add-song btn-podcasts-remove-to-playlist ');
  playlistSongRemove.setAttribute('song-to-add', index);
  playlistSongRemove.setAttribute('data-add-to-playlist', id);


  //Song Name
  playlistSongName.setAttribute('class', 'playlist-song-name');
  playlistSongName.innerHTML = song.name;


  playlistSongArtist.setAttribute('class', 'playlist-artist-album');
  playlistSongArtist.innerHTML = "Slimpod";
  //Song Meta
  playlistSongMeta.appendChild(playlistSongName);
  playlistSongMeta.appendChild(playlistSongArtist);
  playlistSongMeta.setAttribute('class', 'playlist-song-meta');

  //Song Image Src
  playlistSongImg.setAttribute('src', song.cover_art_url);

  //Song Image Cont
  playlistSongImgCont.setAttribute('class', 'player-img');
  playlistSongImgCont.appendChild(playlistSongImg);

  //Song Remove
  playlistSongRemove.setAttribute('class', 'btn-podcasts-add-song btn-podcasts-remove-to-playlist');
  playlistSongRemove.setAttribute('song-to-add', index);
  playlistSongRemove.setAttribute('data-add-to-playlist', id);


 playListSongItem.appendChild(playlistSongRemove);
  //Song Item
  playListSongItem.setAttribute('class', "white-player-playlist-song amplitude-song-container amplitude-play-pause btn-podcasts-playlist-item amplitude-paused");
  playListSongItem.setAttribute('data-amplitude-song-index', index);
  playListSongItem.appendChild(playlistSongImgCont);
  playListSongItem.appendChild(playlistSongMeta);

  //Song Element
  playlistElement.appendChild(playListSongItem);

};

// Utility : Make POST Request
/*
var btnPodCastsPost = function(postData, callback) {
  var request = new XMLHttpRequest();
  var encodedData = Object.keys(postData).map(function(key) {
    return key + '=' + encodeURIComponent(postData[key])
  }).join('&');
  request.open('POST', btn_podcasts_data.ajax_url, false);
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
*/

//btnPodCastsPost function but with retries
var btnPodCastsPost = function(postData, callback) {
  var maxRetries = 2; 
  var retryCount = 0;

  var btnPodSendRequest = function() {
    var request = new XMLHttpRequest();
    var encodedData = Object.keys(postData).map(function(key) {
      return key + '=' + encodeURIComponent(postData[key]);
    }).join('&');

    request.open('POST', btn_podcasts_data.ajax_url, true); // asynchronous request instead
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

    request.onload = function() {
      if (request.status >= 200 && request.status < 300) {
        var response = false;
        try {
          response = JSON.parse(request.responseText);
        } catch (err) {
          response = false;
        }
        if (typeof callback === "function") callback(response);
      } else {
        console.log({
          func: 'btnPodCastsPost',
          status: request.status,
          statusText: request.statusText
        });
        // retry the request until max is reached
        if (request.status >= 500 && retryCount < maxRetries) {
          retryCount++;
          console.log('Retrying request...');
          console.log(retryCount);
          setTimeout(function(){
            btnPodSendRequest(); // retry the request
          },1000);
        } else {
          console.log('Max retries reached. Request failed.');
        }
      }
    };

    // network error handler
    request.onerror = function() {
      console.log('Network error occurred. Retrying request...');
      console.log(retryCount);
      // retry the request until max is reached
      if (retryCount < maxRetries) {
        retryCount++;
        setTimeout(function(){
            btnPodSendRequest(); // retry the request
        },1000);
      } else {
        console.log('Max retries reached. Request failed.');
      }
    };

    request.send(encodedData);
  };

  //send the request
  btnPodSendRequest();
};





var btnPodCastsIsMobileDevice = function() {
  return [
      'iPad Simulator',
      'iPhone Simulator',
      'iPod Simulator',
      'iPad',
      'iPhone',
      'iPod'
    ].includes(navigator.platform)
    // iPad on iOS 13 detection
    ||
    (navigator.userAgent.includes("Mac") && "ontouchend" in document);
};
var btnPodCastsPreLoader = function($parent) {
  if ($btnPodCastsModal != null) {
    $btnPodCastsModal.remove();
  }
  var html = '<div id="btn_podcasts_preloader_wrap" class="btn_podcasts_overlay">';
  html += '<div class="btn_podcasts_preloader"></div>';
  html += '</div>';

  $parent.insertAdjacentHTML('beforeend', html);
  $btnPodCastsModal = $parent.querySelector('#btn_podcasts_preloader_wrap');
};


/*var btnPodCastsCalender = function(){
    var thisPodCasts;
    return {
      init: function() {
        thisPodCasts = this;
        var today = new Date(),
            currentMonth = today.getMonth(),
            currentYear = today.getFullYear(),
            selectYear = document.getElementById("year"),
            selectMonth = document.getElementById("month"),
            createYear = thisPodCasts.generateYearRange(1970, 2050);
        document.getElementById("year").innerHTML = createYear;
        var calendar = document.getElementById("calendar");
        var lang = calendar.getAttribute('data-lang');
        var months = "";
        var days = "";
        var monthDefault = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        var dayDefault = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
        if (lang == "en") {
            months = monthDefault;
            days = dayDefault;
        } else if (lang == "id") {
            months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
            days = ["Ming", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];
        } else if (lang == "fr") {
            months = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"];
            days = ["dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi"];
        } else {
            months = monthDefault;
            days = dayDefault;
        }
        var $dataHead = "<tr>";
        for ($dataHead in days) {
            $dataHead += "<th data-days='" + days[$dataHead] + "'>" + days[$dataHead] + "</th>";
        }
        $dataHead += "</tr>";

        //alert($dataHead);
        document.getElementById("thead-month").innerHTML = $dataHead;


        var monthAndYear = document.getElementById("monthAndYear");
        thisPodCasts.showCalendar(currentMonth, currentYear);

        $doc.querySelector('#next').addEventListener('click', function(){
          thisPodCasts.next();
        });

        $doc.querySelector('#previous').addEventListener('click', function(){
          thisPodCasts.previous();
        });
        $doc.querySelector('#month').addEventListener('click', function(){
          thisPodCasts.jump();
        });
        $doc.querySelector('#year').addEventListener('click', function(){
          thisPodCasts.jump();
        });


      },
    generateYearRange: function(start, end){
        var years = "";
        for (var year = start; year <= end; year++) {
        years += "<option value='" + year + "'>" + year + "</option>";
        }
        return years;
    },
    next: function(){
        currentYear = (currentMonth === 11) ? currentYear + 1 : currentYear;
        currentMonth = (currentMonth + 1) % 12;
        thisPodCasts.showCalendar(currentMonth, currentYear);
    },
    previous: function(){
        currentYear = (currentMonth === 0) ? currentYear - 1 : currentYear;
        currentMonth = (currentMonth === 0) ? 11 : currentMonth - 1;
        thisPodCasts.showCalendar(currentMonth, currentYear);
    },
    jump: function(){
        currentYear = parseInt(selectYear.value);
        currentMonth = parseInt(selectMonth.value);
        thisPodCasts.showCalendar(currentMonth, currentYear);
    },
    showCalendar: function(){
        var firstDay = ( new Date( year, month ) ).getDay();
        var tbl = document.getElementById("calendar-body");

        tbl.innerHTML = "";

        monthAndYear.innerHTML = months[month] + " " + year;
        selectYear.value = year;
        selectMonth.value = month;
        // creating all cells
        var date = 1;
        for ( var i = 0; i < 6; i++ ) {
            var row = document.createElement("tr");

            for ( var j = 0; j < 7; j++ ) {
                if ( i === 0 && j < firstDay ) {
                    cell = document.createElement( "td" );
                    cellText = document.createTextNode("");
                    cell.appendChild(cellText);
                    row.appendChild(cell);
                } else if (date > thisPodCasts.daysInMonth(month, year)) {
                    break;
                } else {
                    cell = document.createElement("td");
                    cell.setAttribute("data-date", date);
                    cell.setAttribute("data-month", month + 1);
                    cell.setAttribute("data-year", year);
                    cell.setAttribute("data-month_name", months[month]);
                    cell.className = "date-picker";
                    cell.innerHTML = "<span>" + date + "</span>";

                    if ( date === today.getDate() && year === today.getFullYear() && month === today.getMonth() ) {
                        cell.className = "date-picker selected";
                    }
                    row.appendChild(cell);
                    date++;
                }

            }
            tbl.appendChild(row);
        }
    },
    daysInMonth: function(){
        return 32 - new Date(iYear, iMonth, 32).getDate();
    }
}
};*/
//Closest Polyfill
window.Element && !Element.prototype.closest && (Element.prototype.closest = function(e) {
  var t, o = (this.document || this.ownerDocument).querySelectorAll(e),
    n = this;
  do {
    for (t = o.length; --t >= 0 && o.item(t) !== n;);
  } while (t < 0 && (n = n.parentElement));
  return n
});
//Navigator Beacon Polyfill
!function(t,e){"object"==typeof exports&&"undefined"!=typeof module?e():"function"==typeof define&&define.amd?define(e):e()}(0,function(){"use strict";function t(e){return(t="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t})(e)}var e=function(t){return"string"==typeof t},n=function(t){return t instanceof Blob};(function(){"navigator"in this||(this.navigator={});"function"!=typeof this.navigator.sendBeacon&&(this.navigator.sendBeacon=function(t,o){var i=this.event&&this.event.type,r="unload"===i||"beforeunload"===i,f="XMLHttpRequest"in this?new XMLHttpRequest:new ActiveXObject("Microsoft.XMLHTTP");f.open("POST",t,!r),f.withCredentials=!0,f.setRequestHeader("Accept","*/*"),e(o)?(f.setRequestHeader("Content-Type","text/plain;charset=UTF-8"),f.responseType="text"):n(o)&&o.type&&f.setRequestHeader("Content-Type",o.type);try{f.send(o)}catch(t){return!1}return!0}.bind(this))}).call("object"===("undefined"==typeof window?"undefined":t(window))?window:{})});
