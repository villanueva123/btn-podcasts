'use strict';

var $doc = document,
    btnPodCastsPlayer = false,
    btnPodCastsPlayerData = {},
    btnPlayer = {},
    btnPodCastPlayList = '',
    btnPodCastsCount = 0,
    btnPodcastsTime = 0,
    btnPodcastsTimerIsOn = 0;


// Document ready
var btnPodCastsReady = function btnLmsReady(callBack) {
    if ($doc.readyState !== 'loading') {
        callBack();
    }
    else if ($doc.addEventListener) {
        $doc.addEventListener('DOMContentLoaded', callBack);
    }
    else {
        $doc.attachEvent('onreadystatechange', function() {
            if ($doc.readyState === 'complete') {
                callBack();
            }
        });
    }
};

// Dom Ready
btnPodCastsReady(function() {

    if ( typeof btn_podcasts_data !== "undefined" ) {
        if( btn_podcasts_data.hasOwnProperty('podcasts') ){

            btnPodCastsPlayer = btn_podcasts_data.podcasts;
            btnPodCastPlayList =  btn_podcasts_data.pods_playlist;
            var btnPodCastsPlaylist = btnPodCasts( btnPodCastsPlayer );
            btnPodCastsPlaylist.init();


    }
}

});


var btnPodCasts = function (data) {
    var thisPodCasts,
        sleepTimerSelector = $doc.querySelector(".btn-playlist-sleep-timer-selector");

    return {
        init : function (){
            thisPodCasts = this;
            thisPodCasts.initTabs();
            thisPodCasts.libraryPlaySong();
            thisPodCasts.addAndRemoveToPlayList();
            thisPodCasts.groupToggle();
            thisPodCasts.sleepTimer();
            //console.log(btn_podcasts_data);
            btnPodCastsPlayerData = btnPodCastPlayList;
            Object.keys(btnPodCastsPlayer).forEach(function(key) {
                var podCastGroup = "btn_podcasts_group_"+key;
                btnPodCastsPlayerData[podCastGroup] = btnPodCastsPlayer[key]['btn_podcasts_group'];
            });

            btnPlayer['songs'] = btnPodCastsPlayerData['emancipator']['songs'];
            btnPlayer['playlists'] = btnPodCastsPlayerData;
            console.log(btnPlayer);
            Amplitude.init(btnPlayer);
            Amplitude.pause();



        },
        initTabs : function(){

            var $tab = $doc.getElementById('podcasts-tab');
                $tab.addEventListener('click', function (e) {
                    e.preventDefault();
                    var actives = $doc.querySelectorAll('.active');
                    for (var i=0; i < actives.length; i++){
                        actives[i].classList.remove('active');
                    }
                    event.target.parentElement.className += ' active';
                    $doc.getElementById(event.target.href.split('#')[1]).className += ' active';
                }, false);
        },
        libraryPlaySong : function(){
            var $playerButton =  $doc.querySelectorAll('.black-player-song');
            for(var i = 0 ; i < $playerButton.length; i++){
                $playerButton[i].addEventListener('click', function (e) {
                    var playList = this.getAttribute("data-amplitude-playlist"),
                        playerControllerParent = $doc.querySelector("#black-player-controls"),
                        playerSongIndex = this.getAttribute("data-amplitude-song-index"),
                        addToPlayList = this.getAttribute("data-add-to-playlist"),
                        amplitudePlaylist = playerControllerParent.querySelectorAll('[data-amplitude-playlist]'),
                        playerController = playerControllerParent.querySelector('[data-add-to-playlist]'),
                        playerClass = this.getAttribute("data-add-song-class");
                        for(var p = 0; p < amplitudePlaylist.length;p++){
                            amplitudePlaylist[p].setAttribute("data-amplitude-playlist",playList);
                        }
                        playerController.setAttribute("data-add-to-playlist",addToPlayList);
                        playerController.setAttribute("song-to-add",playerSongIndex);
                        playerController.setAttribute("data-group-add",playList);
                        if(playerController.classList.contains('btn-podcasts-add-to-playlist')){
                            playerController.classList.remove("btn-podcasts-add-to-playlist");
                            playerController.classList.add(playerClass);
                        }else{
                            playerController.classList.remove("btn-podcasts-remove-to-playlist");
                            playerController.classList.add(playerClass);
                        }


                }, false);
            }
        },
        addAndRemoveToPlayList: function(){
            thisPodCasts = this;
            $doc.addEventListener('click', function (event) {
                // If the clicked element doesn't have the right selector, bail
                if (!event.target.matches('.btn-podcasts-add-song')) return;
                event.preventDefault();
                var $this = event.target;
                if($this.classList.contains("btn-podcasts-add-to-playlist")){
                    thisPodCasts.addSongData($this);
                }else{
                    thisPodCasts.removeSongData($this);
                }
            }, false);

        },
        groupToggle: function(){
            var groupToggle =  $doc.querySelectorAll('.btn-podcasts-group-title');
            for(var i = 0 ; i < groupToggle.length; i++){
                groupToggle[i].addEventListener('click', function (e) {
                    e.preventDefault();
                    if(this.classList.contains('btn-podcasts-active')){
                        this.classList.remove("btn-podcasts-active");
                        $doc.getElementById(this.href.split('#')[1]).classList.remove("btn-podcasts-group-active");
                    }else{
                        this.classList.add("btn-podcasts-active");
                        $doc.getElementById(this.href.split('#')[1]).classList.add("btn-podcasts-group-active");
                    }

                }, false);
            }
        },
        removeSongData: function($this){
            var playList = $this.getAttribute("data-add-to-playlist"),
            songToAddIndex = $this.getAttribute("song-to-add"),
            $parent = $doc.querySelector("#btn-podcasts-playlist"),
            updateClass = $doc.querySelector("#btn-podcasts-library span[data-add-to-playlist='"+playList+"']"),
            songToAddRemove = $parent.querySelector("span[data-add-to-playlist='"+playList+"']"),
            $songToAddElement = $doc.querySelectorAll("span[data-add-to-playlist='"+playList+"']"),
            $songToAddDiv = $doc.querySelector("div[data-add-to-playlist = '"+playList+"']"),
            addSongClass = $songToAddDiv.setAttribute("data-add-song-class","btn-podcasts-add-to-playlist");
            console.log(songToAddRemove);
            btnPodCastsPost( {
                action      : 'btn_podcasts_action',
                value       : playList,
                type        : 'remove'
            }, function(response){
                console.log(response.data);
                btnPodCastPlayList = response.data;
                console.log(btnPlayer);
                Amplitude.bindNewElements();
                if(songToAddRemove != null){
                    songToAddRemove.parentNode.remove();
                }
                for(var i = 0; i< $songToAddElement.length; i++){
                    $songToAddElement[i].classList.add("btn-podcasts-add-to-playlist");
                    $songToAddElement[i].classList.remove("btn-podcasts-remove-to-playlist");
                }

            });
        },
        addSongData: function($this){
            var playList = $this.getAttribute("data-add-to-playlist"),
                songToAddIndex = $this.getAttribute("song-to-add"),
                songGroup = $this.getAttribute("data-group-add"),
                $thisSong = $this,
                $songToAddElement = $doc.querySelectorAll("span[data-add-to-playlist='"+playList+"']"),
                $songToAddDiv = $doc.querySelector("div[data-add-to-playlist = '"+playList+"']"),
                addSongClass = $songToAddDiv.setAttribute("data-add-song-class","btn-podcasts-remove-to-playlist");

            btnPodCastsPost( {
                action      : 'btn_podcasts_action',
                value       : playList,
                type        : 'add'
            }, function(response){
                var songToAdd = btnPlayer['playlists'][songGroup]['songs'][songToAddIndex];
                btnPlayer['songs'].push(songToAdd);
                var newIndex = Amplitude.addSong(btnPlayer['songs'][songToAddIndex]),
                    index = newIndex - 1,
                    checkSongItem = $doc.querySelector("#btn-podcasts-playlist span[data-add-to-playlist='"+playList+"']");
                    if(checkSongItem == null){
                        btnPodCastsAppendToSongDisplay(songToAdd,index,playList);
                    }
                Amplitude.bindNewElements();
                for(var i = 0; i< $songToAddElement.length; i++){
                    $songToAddElement[i].classList.remove("btn-podcasts-add-to-playlist");
                    $songToAddElement[i].classList.add("btn-podcasts-remove-to-playlist");
                }

            });
        },
        sleepTimer: function(){
            thisPodCasts = this;
            sleepTimerSelector.addEventListener("change", function(){
                var value = this.value,
                    selectorValue = (value * 60),
                    testTimer = value,
                    lastSongIndex = btnPlayer['songs'].length - 1;

                  btnPodCastsStartCount();
                  var timer = setInterval(function(){
                      //To be remove, used for testing the sleep timer only
                      if(value == 20 && btnPodCastsCount == testTimer){
                         thisPodCasts.playerControllerPause(timer);
                      }

                      //Sleep Timer
                      if(btnPodCastsCount == selectorValue && value > 0){
                          thisPodCasts.playerControllerPause(timer);
                      }

                      if(value == -1 && lastSongIndex == Amplitude.getActiveIndex() && Amplitude.getSongPlayedPercentage() > 99){
                          thisPodCasts.playerControllerPause(timer);
                      }
                      console.log();
                  },1000);

                  if(value == 0){
                      btnPodCastsStopCount();
                  }


            });
        },
        playerControllerPause : function(timer){
                var playerController = $doc.querySelectorAll(".amplitude-play-pause");
                for(var i = 0; i < playerController.length; i++){
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



var btnPodCastsTimedCount = function(){
    btnPodCastsCount = btnPodCastsCount + 1;
    console.log(btnPodCastsCount);
    btnPodcastsTime = setTimeout(btnPodCastsTimedCount, 1000);
}

var btnPodCastsStartCount = function(){
    if (!btnPodcastsTimerIsOn) {
      btnPodcastsTimerIsOn = 1;
      btnPodCastsTimedCount();
    }
}

var btnPodCastsStopCount = function(){
    clearTimeout(btnPodcastsTime);
    btnPodcastsTimerIsOn = 0;
}


var  btnPodCastsAppendToSongDisplay = function( song, index ,id){

    var playlistElement = document.querySelector('#podcasts-playlist-cont .right'),
        playlistSong = document.createElement('div'),
        playlistSongRemove = document.createElement('span'),
        playlistSongPlayer = document.createElement('div'),
        playListSongItem = document.createElement('div'),
        playlistSongMeta = document.createElement('div'),
        playlistSongImgCont = document.createElement('div'),
        playlistSongImg = document.createElement('img'),
        playlistSongName = document.createElement('span');


        playlistSong.setAttribute('class', 'black-player');


        playlistSongRemove.setAttribute('class','btn-podcasts-add-song btn-podcasts-remove-to-playlist');
        playlistSongRemove.setAttribute('data-add-to-playlist',id);


        playlistSongPlayer.setAttribute('class','black-player-playlist');


         playListSongItem.setAttribute('class',"black-player-song amplitude-song-container amplitude-play-pause amplitude-paused");
         playListSongItem.setAttribute('data-amplitude-song-index', index);

        playlistSongMeta.setAttribute('class', 'song-meta-container');

        playlistSongImgCont.setAttribute('class', 'player-img');

        playlistSongImg.setAttribute('src', song.cover_art_url);

        playlistSongName.setAttribute('class', 'playlist-song-name');
        playlistSongName.innerHTML = song.name;




        playlistSongMeta.appendChild( playlistSongName );

        playlistSongImgCont.appendChild( playlistSongImg );

        playlistSong.appendChild( playlistSongPlayer );

        playlistSongPlayer.appendChild( playListSongItem );
        playlistSongPlayer.appendChild( playlistSongRemove );

        playListSongItem.appendChild( playlistSongImgCont );
        playListSongItem.appendChild( playlistSongMeta );

        playlistElement.appendChild( playlistSong );

};

// Utility : Make POST Request
var btnPodCastsPost = function( postData, callback ){
    var request = new XMLHttpRequest();
    var encodedData = Object.keys(postData).map(function(key) {
        return key + '=' + encodeURIComponent(postData[key])
    }).join('&');
    request.open('POST', btn_podcasts_data.ajax_url, false);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    request.onload = function () {
        // Process the response
        if (request.status >= 200 && request.status < 300) {
            var response = false;
            try {
                response = JSON.parse(request.responseText);
            }
            catch (err) {
                response = false;
            }
            callback(response);
        }
        else{
            console.log({
                func: 'btnLmsPost',
                status: request.status,
                statusText: request.statusText
            });
        }
    }
    request.send(encodedData);
};
