var featuredAudio = {},
    $doc = document,
    prefix = "btn-podcasts-",
    $uploadAudio = $doc.getElementById(prefix+"featured-audio-upload"),
    $removeAudio = $doc.getElementById(prefix+"featured-audio-remove"),
    $audioAttachment = $doc.getElementById("audio-attachment-id"),
    $audioAttachmentTitle = $doc.getElementById("audio-attachment-title"),
    $audioPreviewContainer = $doc.getElementById("audio-preview-container");

(function ($) {
    "use strict";
	featuredAudio = {
		container: '',
		frame: '',
		settings: btnPodcastsData || {},
		init: function() {
			featuredAudio.container =  $doc.getElementById(prefix+"featured-audio");
			featuredAudio.initFrame();
            $uploadAudio.onclick = function (e){
                featuredAudio.openAudioFrame();
            };
            $removeAudio.onclick = function (e){
                featuredAudio.removeAudio();
            };
            featuredAudio.initAudioPreview();
		},

		/**
		 * Open the featured audio media modal.
		 */
		openAudioFrame: function( event ) {
			if ( ! featuredAudio.frame ) {
				featuredAudio.initFrame();
			}
			featuredAudio.frame.open();
		},

		/**
		 * Create a media modal select frame, and store it so the instance can be reused when needed.
		 */
		initFrame: function() {
			featuredAudio.frame = wp.media({
				button: {
					text: featuredAudio.settings.l10n.select
				},
				states: [
					new wp.media.controller.Library({
						title:     featuredAudio.settings.l10n.featuredAudio,
						library:   wp.media.query({ type: 'audio' }),
						multiple:  false,
						date:      false
					})
				]
			});

			// When a file is selected, run a callback.
			featuredAudio.frame.on( 'select', featuredAudio.selectAudio );
		},

		/**
		 * Callback handler for when an attachment is selected in the media modal.
		 * Gets the selected attachment information, and sets it within the control.
		 */
		selectAudio: function() {
			// Get the attachment from the modal frame.
			var attachment = featuredAudio.frame.state().get( 'selection' ).first().toJSON();
            $audioAttachment.value = attachment.id;
            $audioAttachmentTitle.textContent = attachment.title;
			featuredAudio.audioEmbed( attachment );



		},

		/**
		 * Embed the audio player preview.
		 */
		audioEmbed: function( attachment ) {
			wp.ajax.send( 'parse-embed', {
				data : {
					post_ID: wp.media.view.settings.post.id,
					shortcode: '[audio src="' + attachment.url + '"][/audio]'
				}
			} ).done( function( response ) {
				var html = ( response && response.body ) || '';
				$audioPreviewContainer.innerHTML= html;
				$removeAudio.style.display = "inline-block";
				$uploadAudio.textContent = featuredAudio.settings.l10n.change ;
			} );
		},

		/**
		 * Remove the selected audio.
		 */
		removeAudio: function() {
            $audioAttachment.value = 0;
            $audioAttachmentTitle.textContent = '';
            $audioPreviewContainer.innerHTML = '';
            $uploadAudio.textContent =  featuredAudio.settings.l10n.select ;
            $removeAudio.style.display = "none";


		},

		/**
		 * Initialize featured audio preview.
		 */
		initAudioPreview: function() {
			var attachment = initialAudioAttachment;
			if ( attachment ) {
				featuredAudio.audioEmbed( attachment );
                $uploadAudio.textContent =   featuredAudio.settings.l10n.change ;
                $removeAudio.style.display = "block";
			}
		}
	}

	featuredAudio.init();

} )( jQuery );
