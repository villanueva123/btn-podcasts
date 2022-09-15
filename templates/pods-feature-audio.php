<?php
wp_nonce_field( basename( __FILE__ ), 'featured_audio_nonce' );
$stored_meta = get_post_meta( $post->ID );
$meta_prefix = "btn-podcasts-";
if ( isset ( $stored_meta[$meta_store_audio] ) && 0 !== absint( $stored_meta[$meta_store_audio][0] ) ) {
    $audio_attachment_id = absint( $stored_meta[$meta_store_audio][0] );
    $audio = get_post( $audio_attachment_id );
    $audio_attachment_title = $audio->post_title;
    $audio_attachment = wp_prepare_attachment_for_js( $audio_attachment_id );
} else {
    $audio_attachment_id = '';
    $audio_attachment_title = '';
    $audio_attachment = false;
}
?>
<div id="<?php echo $meta_store_audio;?>" class="piece-attachment">
		<script type="text/javascript">var initialAudioAttachment = <?php echo wp_json_encode( $audio_attachment ); ?></script>
		<p><strong id="audio-attachment-title"><?php echo $audio_attachment_title; ?></strong></p>
		<div id="audio-preview-container" style="margin-top: -.5em; margin-bottom: 1em;"></div>
		<button type="button" class="button button-secondary" id="btn-podcasts-featured-audio-upload"><?php _e( 'Select', 'btn-podcasts-featured-audio' ); ?></button>
		<button type="button" class="button-link" style="margin: .4em 0 0 .5em; display: none;" id="<?php echo $meta_prefix?>featured-audio-remove"><?php _e( 'Remove', 'btn-podcasts-featured-audio' ); ?></button>
		<input type="hidden" name="<?php echo $meta_store_audio; ?>" id="audio-attachment-id" value="<?php echo $audio_attachment_id; ?>" />
</div>
