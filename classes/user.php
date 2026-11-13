<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * User Class
 *
 * @since      1.0.0
 * @package    btn-podcasts
 * @subpackage btn-podcasts/classes
 * @author     Augustus Villanueva <augustus@businesstechninjas.com>
 */
final class btn_podcasts_user {

	function generate_group_pods(){
		$pods = [];
		$meta_key = btn_podcasts()->pods()->get_group_order_meta_key();
		$taxonomy = btn_podcasts()->pods()->get_taxonomy_slug();

		$args =  [
				'taxonomy'=> $taxonomy,
				'orderby'	 => 'meta_value_num',
				'order' 	 => 'ASC',
				'meta_query' => [
					'relation' => 'OR',
							[
									'key'     => $meta_key,
									'compare' => 'NOT EXISTS'
							],
							[
									'key'       => $meta_key,
									'value'     => '0',
									'compare'   => '>',
									'type'			=> "numeric"
							]
				]

		 ];
		$terms = get_terms($args);
		// Check if any term exists
		if ( ! empty( $terms ) && is_array( $terms ) ) {
		    foreach ( $terms as $term ) {
				$term_id = $term->term_id;
				$pods_query = $this->generate_pods_query($term_id, $taxonomy);
				$track_count = count($pods_query);
				$has_access = btn_podcasts()->memberium()->user_has_membership($term_id,$taxonomy);
				$unlocked  = get_term_meta($term_id, btn_podcasts()->pods()->get_unlocked_meta_key(), true);
				$unlocked_text  = get_term_meta($term_id, btn_podcasts()->pods()->get_unlocked_text_meta_key(), true);
				$show_locked_group = (!empty($unlocked));
				$cover_art = btn_podcasts()->pods()->get_cover_art( $term_id );
				$pods_group = 'btn_podcasts_group';
				$cover_art_url = ($cover_art > '')? $cover_art['src'] : BTN_PODCASTS_ASSESTS_URL."images/default-img.png";
				if($has_access || $show_locked_group || current_user_can('administrator')){
					$pods[$term_id] = [
						'locked'		  => (!$has_access)? $show_locked_group : '',
						'locked_text'	  => $unlocked_text,
						'tracks'		  => $track_count,
						'groups_title' 	  => $term->name,
						'group_cover_img' => $cover_art_url,
						$pods_group	 =>  [
							'songs' => $this->generate_pods_playlist($term_id,$taxonomy,$cover_art_url)
						],
					];
				}
			}
		}
		return $pods;
	}



	function generate_pods_query($term_id,$taxonomy){
		$post_type = btn_podcasts()->pods()->get_post_slug();
		$args = [
			'post_type'		=> $post_type,
		  'showposts'		=> -1,
			'post_status'	=> 'publish',
			'orderby'			=> 'menu_order',
			'order'				=> 'ASC',
		    'tax_query' => [
				[
					'taxonomy'	=> $taxonomy,
					'terms' 	=> $term_id,
					'field'		=> 'term_id',
				]
			],
			'fields' => ['post_title','ids'],
		];
		$pods_query = get_posts($args);
		return 	$pods_query;
	}
	function generate_pods_playlist($term_id, $taxonomy, $cover_art){
		$pod_url = [];
		$meta_pods = btn_podcasts()->pods()->get_pod_audio_meta_key();
		$pods_query = $this->generate_pods_query($term_id, $taxonomy);
		foreach($pods_query as $podcasts){
			$post_id = $podcasts->ID;
			$pod_audio = get_post_meta($post_id, $meta_pods, true);
			$pods = get_post( $pod_audio );
			$pods_attachment_title = $podcasts->post_title;
			$pod_url[] = [
					'name' => $pods_attachment_title,
					'url'	=> $pod_audio,
					'cover_art_url' => $cover_art,
					'id' => $post_id,
			];
		}
		return $pod_url;
	}

	function generate_playlist(){
		$key = btn_podcasts()->pods()->get_user_meta_key();
		$user_id = get_current_user_id();
		$user_meta = get_user_meta($user_id, $key, false);
		$meta_pods = btn_podcasts()->pods()->get_pod_audio_meta_key();
		$taxonomy = btn_podcasts()->pods()->get_taxonomy_slug();
		$pods_playlist = [];
		$pods_url = [];

		foreach($user_meta as $key => $value){
			foreach($value as $post_id){
				$pod_audio = get_post_meta($post_id, $meta_pods, true);
				$terms = get_the_terms($post_id, $taxonomy);
				$term_id = $terms[0]->term_id;
				$has_access = btn_podcasts()->memberium()->user_has_membership($term_id,$taxonomy);
				if($has_access){
					$pods = get_post( $pod_audio );
					$pods_attachment_title = get_the_title($post_id);
					$term_list = get_the_terms($post_id, $taxonomy);
					$cover_art = '';
					$term_id = '';
					foreach($term_list as $term_single) {
						$term_id = $term_single->term_id;
					}
					$cover_art = btn_podcasts()->pods()->get_cover_art( $term_id );
					$cover_art_url = ($cover_art > '')? $cover_art['src'] : BTN_PODCASTS_ASSESTS_URL."images/default-img.png";
					$pods_url[] = [
						'name' => $pods_attachment_title,
						'url'	=> $pod_audio,
						'cover_art_url' => $cover_art_url,
						'id' => $post_id,
					];
				}
			}

		}
		$pods_playlist['emancipator'] = [
			'songs' => $pods_url
		];
		return $pods_playlist;
	}

	function sleep_timer(){
		$args =  [
				'Turn Off Timer' => 0,
				'1 minute' => 1,
				'5 minutes'		 => 5,
				'10 minutes' 	 => 10,
				'15 minutes' 	 => 15,
				'30 minutes' 	 => 30,
				'45 minutes' 	 => 45,
				'1 hour'	   	 => 60,
				'End of Track'	 => -1,

		 ];
		 return $args;
	}

	function check_group_selector_access($term_id){
		  $group_selector = true;
			$has_access = btn_podcasts()->memberium()->user_has_membership($term_id,$group_selector);
			$html = ($has_access)? "<span>(Content Protected - True)</span>": "<span>(Content Protected - False)</span>";
			return $html;
	}


	function __construct(){}

	// Current User Data
	protected $user = null;
	protected $pods_data = [];
	protected $memberships = null;

}
