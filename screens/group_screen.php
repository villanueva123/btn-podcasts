<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

class btn_podcasts_groups_screen {

    function init(){
		$group_slug = btn_podcasts()->pods()->get_taxonomy_slug();
		add_action( "{$group_slug}_pre_add_form", [$this, 'enqueue'] );
        add_action( "{$group_slug}_pre_edit_form", [$this, 'enqueue'] );
        add_action( "{$group_slug}_add_form_fields", [$this, 'add_taxonomy_meta'] );
        add_action( "{$group_slug}_edit_form_fields", [$this, 'edit_taxonomy_meta'], 10, 2 );

		// Image Column
		add_filter( "manage_edit-{$group_slug}_columns", [$this, 'set_columns'] );
		add_filter( "manage_{$group_slug}_custom_column", [$this, 'column_content'], 10, 3 );
    }

	function enqueue(){
		$btnPodcastsGroupData = [];
		//load js to control media upload window
		 wp_enqueue_media();
		 $btnPodcastsGroupData['title'] = __( 'Group Pod Cover Image', 'btn-podcasts' );
		 $btnPodcastsGroupData['buttonText'] = __( 'Save', 'btn-podcasts' );
		 wp_enqueue_script( 'group-admin-js', BTN_PODCASTS_ASSESTS_URL . 'js/group-admin.js', ['jquery'], BTN_PODCASTS_VERSION, false );
		 wp_localize_script( 'group-admin-js', 'btnPodcastsGroupData', $btnPodcastsGroupData);
	}

    function add_taxonomy_meta( $taxonomy ){
		wp_nonce_field( 'group_cover_art_nonce', 'group_cover_art_nonce' );
		require_once BTN_PODCASTS_TMPL_DIR . 'add-group-meta.php';
    }

	function edit_taxonomy_meta( $term, $taxonomy ){
		$term_id = $term->term_id;
		$cover_art = btn_podcasts()->pods()->get_cover_art( $term_id );
		$btn_podcasts_group_order = get_term_meta($term_id, btn_podcasts()->pods()->get_group_order_meta_key(), true);
		$group_img_url = ( $cover_art && !empty($cover_art['src']) ) ? $cover_art['src'] : '';
		$group_img = ( $group_img_url > '' ) ? "<img src=\"{$group_img_url}\" />" : '';
		$group_img_id = ( $cover_art && !empty($cover_art['id']) ) ? $cover_art['id'] : '';
		$remove_display = ( $group_img_url > '' ) ? '' : 'style="display:none"';
		wp_nonce_field( 'group_cover_art_nonce', 'group_cover_art_nonce' );
		require_once BTN_PODCASTS_TMPL_DIR . 'edit-group-meta.php';
    }

    static function save_taxonomy_meta( $term_id ){

		// Verify that the nonce is set.
		if ( ! isset($_POST['group_cover_art_nonce']) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['group_cover_art_nonce'], 'group_cover_art_nonce' ) ) {
			return;
		}

		// Save Cover Art ID
		$cover_meta_key = btn_podcasts()->pods()->get_cover_art_meta_key();
		$old_id = btn_podcasts()->pods()->get_cover_art( $term_id, 'id' );
		$new_id = ( !empty($_POST['cover_art_id']) ) ? $_POST['cover_art_id'] : false;
		if( $old_id && ! $new_id ){
			delete_term_meta( $term_id, $cover_meta_key );
		}
		else if( $old_id != $new_id ){
			update_term_meta( $term_id, $cover_meta_key, $new_id );
		}

		$group_menu_order_meta_key = btn_podcasts()->pods()->get_group_order_meta_key();
		$old_order_meta_key = get_term_meta($term_id, $group_menu_order_meta_key, true);
		$new_order_meta_key = ( !empty($_POST['btn-podcasts-group-order']) ) ? $_POST['btn-podcasts-group-order'] : false;
		if( $old_order_meta_key && ! $new_order_meta_key ){
			delete_term_meta( $term_id, $group_menu_order_meta_key );
		}
		else if( $old_order_meta_key != $new_order_meta_key ){
			update_term_meta( $term_id, $group_menu_order_meta_key, $new_order_meta_key );
		}


    }

	function set_columns( $columns ){
		$res = array_slice($columns, 0, 1, true) +
			array( "cover_art" => __( 'Cover', 'btn-podcasts' ) ) +
			array_slice( $columns, 1, count($columns) - 1, true);
 		 $columns = $res;
		return $columns;
	}

	function column_content( $content, $column_name, $term_id ){

		if ( 'cover_art' == $column_name ) {
			$src = btn_podcasts()->pods()->get_cover_art( $term_id, 'src', 'Thumbnail' );
			if( $src ){
				echo "<img src=\"{$src}\" />";
			}
		}
		return $content;
	}

	function __construct(){}

}
