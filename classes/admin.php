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
 * @author     Curtis Krauter <curtis@businesstechninjas.com>
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

		add_action( 'show_user_profile', [$this,'user_profile_fields'],2,1 );
		add_action( 'edit_user_profile',  [$this,'user_profile_fields'],2,1) ;

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
        $select = '';
		$select .="<ul id=\"pod-groupschecklist\" class=\"categorychecklist form-no-clear\">";
						if ( ! empty( $terms ) && is_array( $terms ) ) {
							foreach ( $terms as $term ) {
								$term_id = $term->term_id;
								$group_selector = true;
								$has_access = btn_podcasts()->memberium()->user_has_membership($term_id,$slug,$group_selector);
								$class_name = ($has_access)? "btn-pods-rotected":"";
								$html = ($has_access)? "<span style=\"color:red\">(Content Protected)</span>": "<span></span>";
								$select .="<li id=\"{$slug}-{$term_id}\" class=\"popular-category\">";
											$selected = ( in_array($term_id, $post_term_ids) ) ? " checked=\"checked\" " : "";
											$link = get_edit_term_link($term_id,$slug);
											$href_edit = "<a href =\"{$link}\" target=\"_blank\">edit</a>";
											$select .="<label class=\"selectit\">";
												$select .="<input value=\"{$term_id}\" type=\"checkbox\" name=\"tax_input[$slug][]\" id=\"in-pod-groups-{$term_id}\" $selected> ";
												$select .= "{$term->name} {$html} ";
												$select .= $href_edit;
											$select .="</label>";
								$select .="</li>";
							}
						}
		$select .="</ul>";
		echo $select;
	}


	// Enqueue Scripts and Styles
	function enqueue_scripts( $hook ){
		// Enqueue Templater Settings
		global $post;
		$enqueue_scripts = false;
		$handle = 'btn-podcasts';
		$assets = BTN_PODCASTS_ASSESTS_URL;
		$v = BTN_PODCASTS_VERSION;
		$btnPodcastsData = [];
		$post_type = btn_podcasts()->pods()->get_post_slug();
		// Post Type Pages
		if( in_array($hook, $this->post_type_pages) ){
			$post_type = isset($_GET['post_type']) ? $_GET['post_type'] : false;
			if( ! $post_type ){
				$post_id = isset($_GET['post']) ? (int)$_GET['post'] : 0;
				$post_type = ( $post_id > 0 ) ? get_post_type($post_id) : false;
			}
			if($post_type && $post->post_type === $post_type){
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
		if(in_array($hook, ['edit-tags.php','term.php','user-edit.php','profile.php']) ){
			$enqueue_scripts = true;
			$url = $assets . 'css/admin.css';
			wp_enqueue_style($handle, $url, [], $v, 'all');

		}
		if(in_array($hook, ['user-edit.php','profile.php']) ){
			$url = $assets . 'js/admin.js';
			$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : get_current_user_id();
			$btnPodcastsData['user_id'] = $user_id;
            $btnPodcastsData['nonce'] = wp_create_nonce( 'btn_podcasts_admin_streak' );
			$btnPodcastsData['ajax_url'] = admin_url( 'admin-ajax.php' );
			wp_register_script($handle, $url, [], $v, true);
		}
		if($enqueue_scripts ){
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
            if($existing_pod_audio_key){
                delete_post_meta($post_id, $pod_audio_key);
            }
        }

    }

	function user_profile_fields( $profileuser ) {
				$user_id = $profileuser->ID;
				$html = "";
				$html .="<h2>Update User Streak</h2>";
				$html .="<div class=\"btn-streak\">";
					    $html .="<table class=\"form-table\">";

                            $streak = 0;
							$current_consecutive = get_user_meta($user_id,"podcast_current_streak",true);
							if(!empty($current_consecutive)){
								if (str_contains($current_consecutive, 'Days')) {
									$streak = str_replace(" Days","",$current_consecutive);
									$streak = (int)$streak;
								}
								else{
									$streak = str_replace(" Day","",$current_consecutive);
									$streak = (int)$streak;
								}
							}
							$current_consecutive = $streak;
							$longest_consecutive = get_user_meta($user_id,"memb__ConsecutivedayslistenedtoPods",true);
							$html .="<input type=\"hidden\" name=\"btn_longest_streak_original\"  id=\"btn_longest_streak_original\" value=\"{$longest_consecutive}\" class=\"regular-text\" required />";
							$html .="<input type=\"hidden\" name=\"btn_current_streak_original\"  id=\"btn_current_streak_original\" value=\"{$current_consecutive}\" class=\"regular-text\" required />";

							$html .="<tr>";
					            $html .="<th>";
					                $html .="<label for=\"user_date\">Current Streak</label>";
					            $html .="</th>";
					            $html .="<td>";
					                $html .="<input type=\"text\" name=\"btn_current_streak\"  id=\"btn_current_streak\" value=\"{$current_consecutive}\" class=\"regular-text\" required />";
					            $html .="</td>";
								$html .="<tr>";
    					            $html .="<th>";
    					                $html .="<label for=\"user_date\">Longest Streak</label>";
    					            $html .="</th>";
    					            $html .="<td>";
    					                $html .="<input type=\"text\" name=\"btn_longest_streak\"  id=\"btn_longest_streak\" value=\"{$longest_consecutive}\" class=\"regular-text\" required />";
    					            $html .="</td>";
					        $html .="</tr>";
									$html .="<tr>";
											$html .="<th>";
											$html .="</th>";
											$html .="<td>";
												$html .="<p class=\"submit\"><button id=\"btn_streak_submit\"class=\"button\">Update</button></p>";
											$html .="</td>";
									$html .="</tr>";
									$html .="<tr>";
											$html .="<th><label>This will reset the current and longest streak</label>";
											$html .="</th>";
											$html .="<td>";
												$html .="<p class=\"submit\"><button id=\"btn_streak_reset\"class=\"button\">Reset Streak</button></p>";
											$html .="</td>";
									$html .="</tr>";
							 $html .="</table>";

				$html .="</div>";
				$html .="<style>.btn-ts-ld-integration {padding: 10px;background: #fff;}.btn-ts-ld-integration input{max-width:220px;}.btn-ts-ld-integration button{cursor:pointer;}</style>";
				echo $html;

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


	function __construct(){}

	private $permissions = 'manage_options';
	private $post_type_pages = ['post.php','post-new.php'];

}
