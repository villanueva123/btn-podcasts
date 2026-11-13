<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * Pods Class
 *
 * @since      1.0.0
 * @package    btn-podcasts
 * @subpackage btn-podcasts/classes
 * @author     Augustus Villanueva <augustus@businesstechninjas.com>
 */
final class btn_podcasts_pods {

    function register() {

		// Group Taxonomy
        $group_args = [
            'labels'            	=>  [
						'name'          =>  __( 'Groups', 'btn-podcasts' ),
						'singular_name' =>  __( 'Group', 'btn-podcasts' ),
						'add_new' 				=> _x( 'Add New', 'Group Pod', 'btn-podcasts' ),
						'add_new_item' 			=> __( 'Add New Group Pod', 'btn-podcasts' ),
						'edit_item' 			=> __( 'Edit Group Pod', 'btn-podcasts' ),
						'new_item' 				=> __( 'New Group Pod', 'btn-podcasts' ),
						'view_item' 			=> __( 'View Group Pod', 'btn-podcasts' ),
						'search_items' 			=> __( 'Search Group Pods', 'btn-podcasts' ),
						'not_found' 			=> __( 'Nothing found', 'btn-podcasts' ),
						'not_found_in_trash'	=> __( 'Nothing found in Trash', 'btn-podcasts' ),
						],
            'public'            	=> false,
            'rewrite'           	=> false,
            'hierarchical'      	=> true,
						'show_ui'				=> true,
						'show_admin_column' 	=> true,
						'meta_box_cb'			=> ['btn_podcasts_admin', 'group_selector'],
        ];
        register_taxonomy( self::TAX_SLUG, self::POST_SLUG, $group_args );

        // Pods Post Type
        $pod_args = [
			'labels' 				=> [
    			'name'					=> _x( 'Pods','post type general name', 'btn-podcasts' ),
    			'singular_name' 		=> _x( 'Pod', 'post type singular name', 'btn-podcasts' ),
    			'add_new' 				=> _x( 'Add New', 'Pod', 'btn-podcasts' ),
    			'add_new_item' 			=> __( 'Add New Pod', 'btn-podcasts' ),
    			'edit_item' 			=> __( 'Edit Pod', 'btn-podcasts' ),
    			'new_item' 				=> __( 'New Pod', 'btn-podcasts' ),
    			'view_item' 			=> __( 'View Pod', 'btn-podcasts' ),
    			'search_items' 			=> __( 'Search Pods', 'btn-podcasts' ),
    			'not_found' 			=> __( 'Nothing found', 'btn-podcasts' ),
    			'not_found_in_trash'	=> __( 'Nothing found in Trash', 'btn-podcasts' ),
    		],
			'public' 				=> false,
			'has_archive'         	=> false,
			'publicly_queryable' 	=> false,
			'show_ui' 				=> true,
			'show_in_menu'			=> true,
			'query_var' 			=> true,
			'capability_type' 		=> 'post',
			'hierarchical' 			=> false,
			'menu_position' 		=> null,
			'menu_icon'				=> 'dashicons-format-audio',
			'supports' 				=> ['title', 'excerpt', 'page-attributes', 'thumbnail'],
			'exclude_from_search' 	=> true,
			'register_meta_box_cb'	=> ['btn_podcasts_pods','meta_boxes'],
		];
		register_post_type( self::POST_SLUG, $pod_args );

    }

	/**
	 * Meta Boxes
	 */
	static function meta_boxes( $post ){
		$slug = self::POST_SLUG;

		if( $post->post_type === $slug && is_admin() ){
			add_meta_box(
				$slug . '-metabox',
				__('Pod Audio', $slug),
				['btn_podcasts_admin', 'show_podcasts_metabox'],
				$slug,
				'normal',
				'high'
			);

		}
	}

	static function taxonomy_access( $taxonomies ){
		if( ! in_array( self::TAX_SLUG, $taxonomies) ){
			$taxonomies[] = self::TAX_SLUG;
		}
		return $taxonomies;
	}

	/**
	 * Group Cover Art
	 *
	 * @param int		$term_id
	 * @param string	$size Image Size to return
	 * @param string	$return   id | src | array
	 * @return mixed
	 */
	function get_cover_art( $term_id, $return = 'array', $size = 'Medium' ){

		if( (int)$term_id < 1 ){
			// todo add default cover ID
			return false;
		}

		$meta_key = $this->get_cover_art_meta_key();
		$img_id = get_term_meta( $term_id, $meta_key, true );
		if( ! $img_id ){
			return false;
		}

		if( $return === 'id' ){
			return $img_id;
		}
		else if( $return === 'src' || $return === 'array' ){

			$src = wp_get_attachment_image_src( $img_id, $size );
			$src = ( is_array($src) ) ? $src[0] : false;
			if( $return === 'src' ){
				return $src;
			}
			else{
				return [
					'id'		=> $img_id,
					'src'		=> $src,
					'srcset'	=> wp_get_attachment_image_srcset($img_id),
					'alt'		=> get_post_meta( $img_id, '_wp_attachment_image_alt', true )
				];
			}
		}
	}


    function get_post_slug(){
        return self::POST_SLUG;
    }

    function get_taxonomy_slug(){
        return self::TAX_SLUG;
    }

	function get_cover_art_meta_key(){
		return self::ART_KEY;
	}

	function get_group_order_meta_key(){
		return self::GROUP_MENU_ORDER;
	}

	function get_pod_audio_meta_key(){
		return self::META_POD_AUDIO;
	}

	function get_user_meta_key(){
		return self::USER_META;
	}

	function get_unlocked_meta_key(){
		return self::UNLOCKED;
	}
	function get_unlocked_text_meta_key(){
		return self::UNLOCKED_TEXT;
	}

	function pod_listen($category){
		$return = '';
		global $wpdb;
		$table = "{$wpdb->prefix}podcast";
		$user_id = get_current_user_id();
		if($category === 0){
			$sql = "SELECT COUNT(*) max_streak FROM ( SELECT x.* , CASE WHEN @prev = listened - INTERVAL 1 DAY THEN @i:=@i ELSE @i:=@i+1 END i , @prev:=listened
         	FROM ( SELECT DISTINCT listened FROM  {$table}  where user_id = %s) x JOIN  ( SELECT @prev:=null,@i:=0 ) vars ORDER BY listened)
			a GROUP BY i ORDER BY max_streak DESC LIMIT 1;
			";
			$podcast = $wpdb->get_row( $wpdb->prepare($sql, $user_id));
			$number = (!empty($podcast))? $podcast->max_streak : 0 ;
			$days = ($number > 1 )? "Days" : "Day";
			$return =  $number . " {$days}";
		}
		else if($category === 1){
			//$return = $this->get_date_range("1","week");
		}
		else if($category === 2){
			//$return = $this->get_date_range("1","month");
		}
		else if($category === 3){
			//$return =$this->get_date_range("3","month");
		}
		else if($category === 4){
			$return =$this->get_date_range("6","month");
		}
		else if($category === 5){
			//$return =$this->get_date_range("1","year");
		}
		else if($category === 6){
			$previous = date("Y-m-d", strtotime("-1 week +1 day"));
			$today = date('Y-m-d');
			$sql = "SELECT MAX(CAST(streak AS UNSIGNED)) AS streak FROM (SELECT user_id, D,DATEDIFF('%s', D),@streak := IF( DATEDIFF('%s', D) - @days_diff > 1, @streak,IF(@days_diff := DATEDIFF('%s', D), @streak+1, @streak+1))  AS streak FROM (SELECT user_id, listened AS D FROM {$table} GROUP BY user_id, listened) AS a CROSS JOIN (SELECT @streak := 0, @days_diff := -1) AS vars WHERE user_id = '%s' AND D <= '%s' ORDER BY D DESC) AS t";
			$podcast = $wpdb->get_row( $wpdb->prepare($sql,$today,$today,$today,$user_id,$today));
			$number = (!empty($podcast->streak))? $podcast->streak : 0 ;
			if(empty($podcast->streak)){
				$today = date("Y-m-d", strtotime("yesterday"));
				$sql = "SELECT MAX(CAST(streak AS UNSIGNED)) AS streak FROM (SELECT user_id, D,DATEDIFF('%s', D),@streak := IF( DATEDIFF('%s', D) - @days_diff > 1, @streak,IF(@days_diff := DATEDIFF('%s', D), @streak+1, @streak+1))  AS streak FROM (SELECT user_id, listened AS D FROM {$table} GROUP BY user_id, listened) AS a CROSS JOIN (SELECT @streak := 0, @days_diff := -1) AS vars WHERE user_id = '%s' AND D <= '%s' ORDER BY D DESC) AS t";

			$podcast = $wpdb->get_row( $wpdb->prepare($sql,$today,$today,$today,$user_id,$today));
			$number = (!empty($podcast->streak))? $podcast->streak : 0 ;
			}

			$days = ($number > 1 )? "Days" : "Day";
			$return =  $number . " {$days}";
		}
		return $return;
	}
	function get_date_range($number, $range){
		global $wpdb;
		$user_id = get_current_user_id();
		$table = "{$wpdb->prefix}podcast";
		$previous = date("Y-m-d", strtotime("-{$number} {$range} +1 day"));
		//var_dump($previous);
		$today = date('Y-m-d');
		$sql = "SELECT * FROM {$table} WHERE listened BETWEEN '%s' AND '%s' AND user_id = %s GROUP BY listened ORDER BY listened DESC";
		$podcast = $wpdb->get_results($wpdb->prepare($sql,$previous,$today, $user_id));
		$number = (!empty($podcast))? count($podcast) : 0 ;
		$pods = ($number > 1 )? "Days" : "Days";

		//consecutive days
		$sql = "SELECT COUNT(*) max_streak FROM ( SELECT x.* , CASE WHEN @prev = listened - INTERVAL 1 DAY THEN @i:=@i ELSE @i:=@i+1 END i , @prev:=listened
				FROM ( SELECT DISTINCT listened FROM  {$table}  where (listened BETWEEN '%s' AND '%s' AND user_id = %s) ) x JOIN  ( SELECT @prev:=null,@i:=0 ) vars ORDER BY listened)
		a GROUP BY i ORDER BY max_streak DESC LIMIT 1;
		";
		$podcast = $wpdb->get_row( $wpdb->prepare($sql, $previous, $today, $user_id));
		$html = '';
		$html .= "Number of {$pods} : <b>{$number}</b>";
		$number = (!empty($podcast))? $podcast->max_streak : 0 ;
		$html .= "<br>Consecutive of Days : <b>{$number}</b>";
		return $html;
	}



	function __construct(){}

	const POST_SLUG = 'pods';
	const TAX_SLUG = 'pod-groups';
	const ART_KEY = 'btn/podcasts/art';
	const GROUP_MENU_ORDER = 'btn/podcasts/order';
	const META_POD_AUDIO = 'btn/podcasts/pod-audio';
	const USER_META = 'btn/podcasts/playlists';
	const UNLOCKED = 'btn/podcasts/unlocked/';
	const UNLOCKED_TEXT = 'btn/podcasts/unlocked/text';

}
