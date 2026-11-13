/* BTN Podcasts - lightweight playback completion signal.
 * No polling, progress heartbeats, or client-side Keap requests.
 * The server ONLY persists events for tag 3716.
 */
(function () {
  'use strict';
  if (typeof btnClinicalListen === 'undefined' || !btnClinicalListen.loggedIn) return;

  function initialize() {
    if (typeof Amplitude === 'undefined' || typeof Amplitude.getAudio !== 'function') return;
    var audio = Amplitude.getAudio();
    if (!audio || typeof audio.addEventListener !== 'function') return;

    var current = null;
    var sessionCounter = 0;

    function sessionKey() {
      sessionCounter += 1;
      if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
        var bytes = new Uint8Array(16);
        window.crypto.getRandomValues(bytes);
        return Array.prototype.map.call(bytes, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
      }
      return 'play' + Date.now().toString(36) + Math.random().toString(36).slice(2) + sessionCounter.toString(36);
    }

    function activePodId() {
      var song = Amplitude.getActiveSongMetadata();
      var id = song && parseInt(song.id, 10);
      return Number.isInteger(id) && id > 0 ? id : 0;
    }

    function reset(podId) {
      current = {
        id: podId,
        key: sessionKey(),
        played: 0,
        lastPosition: null,
        lastWall: null,
        pending: false,
        submitted: false,
        attempts: 0
      };
    }

    function submit(duration) {
      if (!current || current.pending || current.submitted || current.attempts >= 2) return;
      var session = current;
      session.attempts++;
      session.pending = true;
      var data = new URLSearchParams();
      data.append('action', 'btn_podcasts_qualified_listen');
      data.append('nonce', btnClinicalListen.nonce);
      data.append('podcast_id', String(session.id));
      data.append('session_key', session.key);
      data.append('duration', String(duration));
      data.append('played', String(Math.floor(session.played)));

      fetch(btnClinicalListen.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: data.toString()
      }).then(function (response) {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.json();
      }).then(function (result) {
        if (result && result.success) {
          session.submitted = true;
          // Preserve the current/longest streak display already in the player.
          var streak = result.data && result.data.streak;
          if (streak) {
            var currentEl = document.querySelector('.current-streak');
            var longestEl = document.querySelector('.long-streak');
            if (currentEl) currentEl.textContent = streak.current_streak;
            if (longestEl) longestEl.textContent = streak.long_streak;
          }
        } else {
          session.attempts = 2; // Do not retry rejected/invalid requests.
        }
      }).catch(function () {
        // At most one additional request with the same deduplication key.
      }).finally(function () {
        session.pending = false;
      });
    }

    audio.addEventListener('timeupdate', function () {
      var id = activePodId();
      if (!id || !Number.isFinite(audio.duration) || audio.duration < 5 || audio.paused || audio.seeking) return;
      if (!current || current.id !== id) reset(id);
      var position = audio.currentTime;
      var wall = Date.now() / 1000;
      if (current.lastPosition !== null && current.lastWall !== null) {
        var mediaDelta = position - current.lastPosition;
        var wallDelta = wall - current.lastWall;
        // A seek forward isn't listening. Background playback is still allowed
        // when progress and elapsed wall time advance together.
        if (mediaDelta > 0 && wallDelta > 0 && mediaDelta <= wallDelta + 1.5) {
          current.played += mediaDelta;
        }
      }
      current.lastPosition = position;
      current.lastWall = wall;
      var duration = Math.round(audio.duration);
      if (Math.floor(current.played) >= Math.ceil(duration / 2)) submit(duration);
    });

    audio.addEventListener('seeking', function () {
      if (current) { current.lastPosition = null; current.lastWall = null; }
    });
    audio.addEventListener('seeked', function () {
      if (current) { current.lastPosition = audio.currentTime; current.lastWall = Date.now() / 1000; }
    });
    audio.addEventListener('ended', function () { current = null; });
    // When selecting the same pod again from the beginning, this is a new play.
    audio.addEventListener('play', function () {
      if (current && current.submitted && audio.currentTime < 1.0) current = null;
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
  else initialize();
}());
