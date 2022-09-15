<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * Main Admin Class
 *
 * @since      1.0.0
 * @package    btn-podcasts
 * @subpackage btn-podcasts/classes
 * @author     Augustus Villanueva <augustus@businesstechninjas.com>
 */
final class btn_podcasts_admin {

    // Wordpress Hooks ( Actions & Filters )
    function add_wp_hooks() {

		// Load Group Admin
		$group_slug = btn_podcasts()->pods()->get_taxonomy_slug();
		add_action( 'load-edit-tags.php', [$this, 'load_group_admin'] );
		add_action( 'load-term.php', [$this, 'load_group_admin'] );
		// Save Group Meta
		add_action( "create_{$group_slug}", ['btn_podcasts_groups_screen', 'save_taxonomy_meta'] );
        add_action( "edit_{$group_slug}", ['btn_podcasts_groups_screen', 'save_taxonomy_meta'] );


		add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
		add_action( 'save_post', [$this,'save_metabox'] );
    }

	function load_group_admin(){

		$taxonomy = !empty($_GET['taxonomy']) ? $_GET['taxonomy'] : false;
		if( $taxonomy ){
			$group_slug = btn_podcasts()->pods()->get_taxonomy_slug();
			if( $taxonomy === $group_slug ){
				$group_admin = new btn_podcasts_groups_screen;
				$group_admin->init();
			}
		}
	}

	// Post Type Group Selector
	static function group_selector( $pod ){
		$slug = btn_podcasts()->pods()->get_taxonomy_slug();
		$post_term_ids = wp_get_post_terms( $pod->ID, $slug, ['fields' => 'ids'] );
		$terms = get_terms([
			'taxonomy'   => $slug,
			'hide_empty' => false,
			//todo meta order
		]);
		$select = "<select name=\"tax_input[$slug][]\" id=\"{$slug}\" class=\"widefat\">";
		$select .= "<option value=''>".__('Select group', 'btn-podcasts')."</option>";
		if ( ! empty( $terms ) && is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$selected = ( in_array($term->term_id, $post_term_ids) ) ? " selected=\"selected\"" : "";
				$select .= "<option value=\"{$term->slug}\"{$selected}>{$term->name}</option>";
			}
		}
		$select .= "</select>";
		echo $select;
	}


	// Enqueue Scripts and Styles
	function enqueue_scripts( $hook ){
		// Enqueue Templater Settings

		$enqueue_scripts = false;
		$handle = 'btn-podcasts';
		$assets = BTN_PODCASTS_ASSESTS_URL;
		$v = BTN_PODCASTS_VERSION;
		$btnPodcastsData = [];
		// Post Type Pages
		if( in_array($hook, $this->post_type_pages) ){
			$post_type = isset($_GET['post_type']) ? $_GET['post_type'] : false;
			if( ! $post_type ){
				$post_id = isset($_GET['post']) ? (int)$_GET['post'] : 0;
				$post_type = ( $post_id > 0 ) ? get_post_type($post_id) : false;
			}
			if( $post_type){
				$enqueue_scripts = true;
			}
		}

		if( $enqueue_scripts ){
			$url = $assets . 'js/btn-podcasts-uploader.js';
			wp_enqueue_media();
			wp_register_script($handle, $url, [], $v, true);
			$btnPodcastsData['audioAttachment'] = $audio_attachment;
			$btnPodcastsData['l10n'] = [
				'featuredAudio' => __( 'Pod Audio', 'btn-podcasts' ),
				'select' => __( 'Select', 'btn-podcasts' ),
				'change' => __( 'Change', 'btn-podcasts' ),
			];

		}
		if(in_array($hook, ['edit-tags.php','term.php']) ){
			$enqueue_scripts = true;
			$url = $assets . 'css/admin.css';
			wp_enqueue_style($handle, $url, [], $v, 'all');
		}
		if($enqueue_scripts){
			wp_enqueue_script($handle);
			wp_localize_script( $handle, 'btnPodcastsData', $btnPodcastsData);
		}
	}


	/**
     * Save Meta Boxes
     */
    function save_metabox($post_id){

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			return;
		}
		// Check permissions
		if (! empty($_POST['post_type']) && 'page' == $_POST['post_type']) {
			if (! current_user_can('edit_pages', $post_id)) {
				return;
			}
		}
		else {
			if (! current_user_can('edit_posts', $post_id)) {
				return;
			}
		}

        // Pod Audio
        $pod_audio_key = btn_podcasts()->pods()->get_pod_audio_meta_key();
        $new_pod_audio_key = (isset($_POST[$pod_audio_key])) ? $_POST[$pod_audio_key] : false;
        $existing_pod_audio_key = get_post_meta($post_id, $pod_audio_key, true);
        if($new_pod_audio_key){
            // Updated
            if($existing_pod_audio_key != $new_pod_audio_key){
                update_post_meta($post_id, $pod_audio_key, $new_pod_audio_key);
            }
        }
        else{
            // Deleting
            if($existing){
                delete_post_meta($post_id, $pod_audio_key);
            }
        }

		//Option Link
        $option_link_key = btn_podcasts()->pods()->get_option_link_meta_key();
        $new_option_link_key = (isset($_POST[$option_link_key])) ? $_POST[$option_link_key] : false;
        $existing_option_link_key = get_post_meta($post_id, $option_link_key, true);
        if($new_option_link_key){
            // Updated
            if($existing_option_link_key != $new_option_link_key){
                update_post_meta($post_id, $option_link_key, $new_option_link_key);
            }
        }
        else{
            // Deleting
            if($existing){
                delete_post_meta($post_id, $option_link_key);
            }
        }
    }

	/**
	 * Show Podcasts Pod Audio Meta Boxes
	 */
	static function show_podcasts_metabox( $post ){
		$post_id = $post->ID;
		$meta_store_audio = btn_podcasts()->pods()->get_pod_audio_meta_key();
		$pod_audio = get_post_meta($post_id, $meta_store_audio, true);
		include BTN_PODCASTS_DIR . 'templates/pods-feature-audio.php';
	}

	/**
	 * Show Podcasts Option Meta Boxes
	 */
	static function show_podcasts_option_link_metabox( $post ){
		$post_id = $post->ID;
		$option_link_key = btn_podcasts()->pods()->get_option_link_meta_key();
		$pod_option_link = get_post_meta($post_id, $option_link_key, true);
		echo '<input type="text" name="'.$option_link_key.'" value="'.$pod_option_link.'">';
	}

	function __construct(){}

	private $permissions = 'manage_options';
	private $post_type_pages = ['post.php','post-new.php'];

}
