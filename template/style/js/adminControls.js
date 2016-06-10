// VOLUME KNOB
$(function() {
  $('#volume').knob({
    'min' : 1,
    'step' : 2,
    'width' : '200',

    'thickness' : .1,
    'fgColor' : '#ff1e53',
    'bgColor' : '#222222',
    'displayInput' : false,
    'displayPrevious' : true,
    'release' : function(v) {
      $.post(appendQueryString(window.location.href, {'setVolume':v}), function(data) {

      });
    },
  });
});
// VOLUME KNOB END

// MUTE BUTTON
$(document).on('click', '#muteButton', function(e) {
  $.post(appendQueryString(window.location.href, {'setMute':''}));
});
function setMuteButton(boolean) {
  var muteButton = $('#muteButton');
  if (boolean) {
    muteButton.addClass('muted');
    muteButton.html('<i class=\"fa fa-volume-off\" aria-hidden=\"true\"></i>');
  }
  else {
    muteButton.removeClass('muted');
    muteButton.html('<i class=\"fa fa-volume-up\" aria-hidden=\"true\"></i>');
  }
}
// MUTE BUTTON END

// PAUSE BUTTON
$(document).on('click', '#pauseButton', function(e) {
  $.post(appendQueryString(window.location.href, {'setPause':''}));
});
function setPauseButton(boolean) {
  var pauseButton = $('#pauseButton');
  if (boolean) {
    pauseButton.addClass('paused');
    pauseButton.html('<i class=\"fa fa-play\" aria-hidden=\"true\"></i>');
  }
  else {
    pauseButton.removeClass('paused');
    pauseButton.html('<i class=\"fa fa-pause\" aria-hidden=\"true\"></i>');
  }
}
// PAUSE BUTTON END

// PREVIOUS BUTTON
$(document).on('click', '#prevButton', function(e) {
  $.post(appendQueryString(window.location.href, {'setPrevTrack':''}));
});
// PREVIOUS BUTTON END

// NEXT BUTTON
$(document).on('click', '#nextButton', function(e) {
  $.post(appendQueryString(window.location.href, {'setNextTrack':''}));
});
// NEXT BUTTON END


// STATE UPDATER
var stateUpdater
function updateState() {
  if ($('#playerState').length) {
    $.get({
      url: '/download/playerState.php',
      cache: false
    }).done(function(data){

      // SONG NAME
      if ($('#songName').html() != data.track) {
        $('#songName').html(data.track);
      }

      // ARTIST NAME
      if ($('#artistName').html() != data.artist) {
        $('#artistName').html(data.artist);
      }

      // MUTE STATE
      var buttonMuted = $('#muteButton').hasClass('muted');
      if (buttonMuted && !data.mute) {
        setMuteButton(false);
      }
      else if (!buttonMuted && data.mute) {
        setMuteButton(true);
      }

      // PAUSE STATE
      var buttonPaused = $('#pauseButton').hasClass('paused');
      console.log(buttonPaused);
      console.log(data.pause);
      if (buttonPaused && !data.pause) {
        setPauseButton(false);
      }
      else if (!buttonPaused && data.pause) {
        setPauseButton(true);
      }
    }).fail(function(){
      $('#songName').html('&nbsp;');
      $('#artistName').html('&nbsp;');
    });
  }
}
$('body').on('loadEnd', function() {
  if (stateUpdater == null) {
    stateUpdater = setInterval(function(){updateState();}, 2000);
  }
});
updateState();
// STATE UPDATER END