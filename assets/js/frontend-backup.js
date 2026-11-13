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
  btnPodCastsPlaylistData = [];


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

      btnPlayer['playlists'] = btnPodCastsPlayerData;
      thisPodCasts.initPlayer();
      Amplitude.init(btnPlayer);
      Amplitude.pause();
      thisPodCasts.initTabs();
      thisPodCasts.addAndRemoveToPlayList();
      thisPodCasts.groupToggle();
      thisPodCasts.sleepTimer();
      thisPodCasts.libraryPlaySong();
      thisPodCasts.playerSongController();
      thisPodCasts.startPlaylist();
	  thisPodCasts.stopAutoNextLibraryTab();




      //Added this to fixed the time duration on mobile
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
		  var timer = setInterval(function() {
			  if (Amplitude.getSongPlayedPercentage() > 99) {
				  var $activeSong = $doc.querySelector(".black-player-song.amplitude-playing"),
					  $all = $doc.querySelectorAll(".amplitude-play-pause"),
					  librarySong = $activeSong.getAttribute("data-amplitude-playlist");
				  if($activeSong.hasAttribute("data-amplitude-playlist")){
					  Amplitude.pause();
					  Amplitude.stop();
					  for(var i = 0; i < $all.length; i++){
						  $all[i].classList.add("amplitude-paused");
						  $all[i].classList.remove("amplitude-playing");
					  }
				  }
			  }
		  }, 1000);
	  },
    initPlayer: function(){
      var playerSongsCount = btnPodCastPlayListGenerateUserData.length;
      //console.log(playerSongsCount);
      if(playerSongsCount == 0){
        $doc.querySelector(".btn-podcats-player-wrapper").style.display = "none";
        $doc.querySelector(".btn-podcasts-no-playlist").style.display = "block";
        $doc.querySelector(".btn-podcasts-start-playlist").setAttribute("btn-podcasts-playlist-start","notstarted");
      }else {
        $doc.querySelector(".btn-podcats-player-wrapper").style.display = "block";
        $doc.querySelector(".btn-podcasts-no-playlist").style.display = "none";
      }
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
          getPlaylistActiveSong = $parentPlaylist.querySelectorAll(".black-player .amplitude-playing"),
          playerSongsCount = btnPodCastPlayListGenerateUserData.length;
      if(Amplitude.getSongPlayedPercentage() > 0 && playerSongsCount > 0 && getPlaylistActiveSong.length > 0){
          $parentLibrary.querySelector(".black-player-controls-container").style.display = "none";
          $parentLibrary.querySelector(".btn-podcasts-player-notice").style.display = "block";
      }else{
          $parentLibrary.querySelector(".black-player-controls-container").style.display = "block";
          $parentLibrary.querySelector(".btn-podcasts-player-notice").style.display = "none";

      }

      //Playlist
      if(getPlaylistActiveSong.length > 0){
          $parentPlaylist.querySelector(".black-player-controls-container").style.display = "block";
          $parentPlaylist.querySelector(".btn-podcasts-start-playlist").style.display = "none";
      }else{
          $parentPlaylist.querySelector(".black-player-controls-container").style.display = "none";
          $parentPlaylist.querySelector(".btn-podcasts-start-playlist").style.display = "block";
      }


    },
    playerSongController: function() {
      $doc.addEventListener('click', function(event) {
        if(event.target.closest('.btn-podcasts-playlist-item')){
          $doc.querySelector(".btn-podcasts-start-playlist").setAttribute("btn-podcasts-playlist-start","started");
        }
        if (!event.target.closest('.black-player-song')) return;
        var activeTab = $doc.querySelector(".tab-pane.active"),
          playerControllerElement = activeTab.querySelector("#black-player-controls");
          playerControllerElement.style.display = "block";
          thisPodCasts.getPlaylistSongState();
      }, false);
    },
    libraryPlaySong: function() {
      var $playerButton = $doc.querySelectorAll('.black-player-song');
      for (var i = 0; i < $playerButton.length; i++) {
        $playerButton[i].addEventListener('click', function(e) {
          var playList = this.getAttribute("data-amplitude-playlist"),
            playerControllerParent = $doc.querySelector("#black-player-controls"),
            playerSongIndex = this.getAttribute("data-amplitude-song-index"),
            addToPlayList = this.getAttribute("data-add-to-playlist"),
            amplitudePlaylist = playerControllerParent.querySelectorAll('[data-amplitude-playlist]'),
            playerController = playerControllerParent.querySelector('[data-add-to-playlist]'),
            playerClass = this.getAttribute("data-add-song-class"),
            $playerSongParent = this.parentNode,
            playerSongTitle = $playerSongParent.querySelector(".individual-song-name").textContent

          playerController.setAttribute("data-add-to-playlist", addToPlayList);
          playerController.setAttribute("song-to-add", playerSongIndex);
          playerController.setAttribute("data-group-add", playList);
          playerControllerParent.querySelector(".song-name").textContent = playerSongTitle;


          if(this.classList.contains("amplitude-playing")){
            playerControllerParent.querySelector("#play-pause-black").classList.remove("amplitude-paused");
            playerControllerParent.querySelector("#play-pause-black").classList.add("amplitude-playing");
          }else{
            playerControllerParent.querySelector("#play-pause-black").classList.add("amplitude-paused");
            playerControllerParent.querySelector("#play-pause-black").classList.remove("amplitude-playing");

          }
          //console.log(playerSongTitle);
          for (var p = 0; p < amplitudePlaylist.length; p++) {
            amplitudePlaylist[p].setAttribute("data-amplitude-playlist", playList);
          }
          if (playerController.classList.contains('btn-podcasts-add-to-playlist')) {
            playerController.classList.remove("btn-podcasts-add-to-playlist");
            playerController.classList.add(playerClass);
          } else {
            playerController.classList.remove("btn-podcasts-remove-to-playlist");
            playerController.classList.add(playerClass);
          }

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
        $parent = $doc.querySelector("#btn-podcasts-playlist"),
        updateClass = $doc.querySelector("#btn-podcasts-library span[data-add-to-playlist='" + playList + "']"),
        songToAddRemove = $parent.querySelector("span[data-add-to-playlist='" + playList + "']"),
        $songToAddElement = $doc.querySelectorAll("span[data-add-to-playlist='" + playList + "']"),
        $songToAddDiv = $doc.querySelector("div[data-add-to-playlist = '" + playList + "']"),
        songToRemove = songToAddRemove.getAttribute("song-to-add"),
        addSongClass = null,
        $parentElement = $this.parentNode,
        btnPodCastsSongs = btnPlayer['songs'],
        removeIndex = null,
        btnPlaylistStarted = $doc.querySelector(".btn-podcasts-start-playlist").getAttribute("btn-podcasts-playlist-start");

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
          }
            songToAddRemove.closest(".black-player").remove();
            var playListSongItemIndex = $parent.querySelectorAll(".btn-podcasts-add-song");
            Amplitude.removeSong(songToRemove);
            var btnSongs = btnPodCastPlayListGenerateUserData.length;
            thisPodCasts.playSongItemIndexes();
            if(songToRemove == Amplitude.getActiveIndex() && btnPlaylistStarted == "started"){
              Amplitude.next();
              Amplitude.playSongAtIndex(0);
            }

            if (btnSongs === 0 && btnPlaylistStarted == "started") {
              Amplitude.next();
              var playerControllerElement = $parent.querySelector("#black-player-controls");
              playerControllerElement.style.display = "none";

            }
            Amplitude.bindNewElements();
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
        $songToAddElement = $doc.querySelectorAll("span[data-add-to-playlist='" + playList + "']"),
        $songToAddDiv = $doc.querySelector("div[data-add-to-playlist = '" + playList + "']"),
        addSongClass = $songToAddDiv.setAttribute("data-add-song-class", "btn-podcasts-remove-to-playlist"),
        $parentElement = $this.parentNode,
        btnPlaylistStarted = $doc.querySelector(".btn-podcasts-start-playlist").getAttribute("btn-podcasts-playlist-start"),
        checkPlayerSong = (Array.isArray(btnPlayer['songs']) && btnPlayer['songs'].length)? true: false;
        btnPodCastPlayListGenerateUserData.push(playList);


        for (var i = 0; i < $songToAddElement.length; i++) {
          $songToAddElement[i].classList.remove("btn-podcasts-add-to-playlist");
          $songToAddElement[i].classList.add("btn-podcasts-remove-to-playlist");
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
        if (btnPodCastPlayListGenerateUserData.length === 1 && btnPlaylistStarted == "started") {
          Amplitude.next();
		  Amplitude.stop();
        }
        Amplitude.bindNewElements();

        //console.log(newIndex);
        if (btnPlayer['songs'].length > 0) {
          var playerControllerElement = $parent.querySelector("#black-player-controls");
          playerControllerElement.style.display = "block";
        }

        thisPodCasts.savePlayerList();
        thisPodCasts.initPlayer();


    },
    playSongItemIndexes: function(start) {
      var $parent = $doc.querySelector("#btn-podcasts-playlist"),
        playListSongItem = $parent.querySelectorAll(".black-player-song");

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

  var playlistElement = document.querySelector('#podcasts-playlist-cont .right'),
    playlistSong = document.createElement('div'),
    playlistSongRemove = document.createElement('span'),
    playlistSongPlayer = document.createElement('div'),
    playListSongItem = document.createElement('div'),
    playlistSongMeta = document.createElement('div'),
    playlistSongImgCont = document.createElement('div'),
    playlistSongImg = document.createElement('img'),
    playlistSongName = document.createElement('span');

  //Song Remove
  playlistSongRemove.setAttribute('class', 'btn-podcasts-add-song btn-podcasts-remove-to-playlist');
  playlistSongRemove.setAttribute('song-to-add', index);
  playlistSongRemove.setAttribute('data-add-to-playlist', id);


  //Song Name
  playlistSongName.setAttribute('class', 'playlist-song-name');
  playlistSongName.innerHTML = song.name;

  //Song Meta
  playlistSongMeta.appendChild(playlistSongName);
  playlistSongMeta.setAttribute('class', 'song-meta-container');

  //Song Image Src
  playlistSongImg.setAttribute('src', song.cover_art_url);

  //Song Image Cont
  playlistSongImgCont.setAttribute('class', 'player-img');
  playlistSongImgCont.appendChild(playlistSongImg);

  //Songs
  playlistSong.setAttribute('class', 'black-player');
  playlistSong.appendChild(playlistSongPlayer);

  //Song Player
  playlistSongPlayer.setAttribute('class', 'black-player-playlist');
  playlistSongPlayer.appendChild(playListSongItem);
  playlistSongPlayer.appendChild(playlistSongRemove);

  //Song Item
  playListSongItem.setAttribute('class', "black-player-song amplitude-song-container amplitude-play-pause amplitude-active-song-container amplitude-paused black-player-song-added btn-podcasts-playlist-item");
  playListSongItem.setAttribute('data-amplitude-song-index', index);
  playListSongItem.appendChild(playlistSongImgCont);
  playListSongItem.appendChild(playlistSongMeta);


  //Song Element
  playlistElement.appendChild(playlistSong);

};

// Utility : Make POST Request
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
