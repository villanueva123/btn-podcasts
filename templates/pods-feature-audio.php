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

$url = (empty($audio_attachment['url']))? $pod_audio : $audio_attachment['url'];
?>
<div id="<?php echo $meta_store_audio;?>" class="piece-attachment">
		<input type="url" style="width:100%;" name="<?php echo $meta_store_audio; ?>" id="audio-attachment-id" value="<?php echo $url; ?>" />
</div>
