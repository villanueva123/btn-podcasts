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
            'hierarchical'      	=> false,
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
			'supports' 				=> ['title', 'excerpt', 'page-attributes'],
			'exclude_from_search' 	=> true,
			'register_meta_box_cb'	=> [__CLASS__,'meta_boxes'],
		];
		register_post_type( self::POST_SLUG, $pod_args );

    }

	/**
	 * Meta Boxes
	 */
	function meta_boxes( $post ){
		$slug = self::POST_SLUG;

		if( $post->post_type === $slug && is_admin() ){
			add_meta_box(
				$slug . '-metabox',
				__('Pod Audio', $slug),
				['btn_podcasts_admin', 'show_podcasts_metabox'],
				$slug,
				'side',
				'high'
			);

			add_meta_box(
				$slug . 'option-link-metabox',
				__('Option Link', $slug),
				['btn_podcasts_admin', 'show_podcasts_option_link_metabox'],
				$slug,
				'side',
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

	function get_option_link_meta_key(){
		return self::META_OPTION_LINK;
	}

	function get_user_meta_key(){
		return self::USER_META;
	}

	function __construct(){}

    const POST_SLUG = 'pods';
    const TAX_SLUG = 'pod-groups';
	const ART_KEY = 'btn/podcasts/art';
	const GROUP_MENU_ORDER = 'btn/podcasts/order';
	const META_POD_AUDIO = 'btn/podcasts/pod-audio';
	const USER_META = 'btn/podcasts/playlists';
	const META_OPTION_LINK = 'btn/podcasts/option-link';

}
