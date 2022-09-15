<?php
if (! defined('ABSPATH')) {
	header('HTTP/1.0 403 Forbidden');
	die();
}

/**
 * Memberium Plugin Class
 *
 * @since      1.0.0
 * @package    btn-podcasts
 * @subpackage btn-podcasts/classes
 * @author     Augustus Villanueva <Augustus@businesstechninjas.com>
 * @NOTE : Tested up to Memberium Version 2.150.11
 */
final class btn_podcasts_memberium {

	private $app = null;
	private $contact_fields_map = null;
	private $tag_map = null;

    // Memberium Has Post Access
    function has_post_access( $post_id ){

        if( function_exists('memb_hasPostAccess') ){
            if( ! memb_hasPostAccess($post_id) ){
                $post_id = false;
            }
        }
        return $post_id;
    }

	// Has Any Tag
	function has_tag_access( $tags ){

		$has_access = false;
		if( function_exists('memb_hasAnyTags') ){
			// Check for all negative values and reverse
			$is_all_negatives = $this->all_negatives($tags);
			if( $is_all_negatives ){
				foreach ($is_all_negatives as $key => $tag) {
					if(memb_hasAnyTags($tag)){
						return false;
					}
				}
				return true;
			}
			else{
				$has_access = memb_hasAnyTags($tags);
			}
        }

		return $has_access;
	}

	function has_all_tags( $tags ){

		$has_access = false;
		if( function_exists('memb_hasAllTags') ){
			// Check for all negative values and reverse
			$is_all_negatives = $this->all_negatives($tags);
			if( $is_all_negatives ){
				foreach ($is_all_negatives as $key => $tag) {
					if(memb_hasAllTags($tag)){
						return false;
					}
				}
				return true;
			}
			else{
				$has_access = memb_hasAllTags($tags);
			}
		}

		return $has_access;
	}

	function get_contact_field( $fieldname ){
		$field_value = '';
		if( function_exists('memb_getContactField') ){
			$field_value = memb_getContactField($fieldname);
        }
		return $field_value;
	}

	function set_contact_field( $field_name, $value, $contact_id ){
		if( ! $contact_id > '' ){
			$contact_id = memb_getContactId();
		}
		if( $contact_id > '' ){
			if( function_exists('memb_setContactField') ){
				memb_setContactField($field_name, $value, $contact_id);
	        }
		}
	}

	function contact_id_by_user_id( $user_id ){
		$contact_id = '';
		if( function_exists('memb_getContactIdByUserId') ){
			$contact_id = memb_getContactIdByUserId($user_id);
		}
		return $contact_id;
	}

	function i2sdk(){

		if( is_null($this->app) ){

			if( array_key_exists('i2sdk', $GLOBALS) ){
				$this->app = $GLOBALS['i2sdk'];
			}
			else {
				$this->app = false;
			}
		}

		return $this->app;
	}


	function set_tags( $tags, $contact_id ){

		if( $tags > '' && $contact_id > '' ){
			if( function_exists('memb_setTags') ){
				memb_setTags($tags, $contact_id);
			}
			else{
				btn_podcasts()->write_log('Error : Func: '.__FUNCTION__.' Memberium function memb_setTags no longer exists');
			}
		}

	}

	function contact_id_by_email( $email ){
		$contact_id = false;
		$i2sdk = $this->i2sdk();
		if( $email > '' && $i2sdk ){
			$data = $i2sdk->isdk->findByEmail($email, ['Id']);
			if (is_array($data) ) {
				$contact_id = empty($data[0]['Id']) ? false : $data[0]['Id'];
			}
		}
		return $contact_id;
	}

	/**
	 * Apply Contact Tags From Array
	*/
	function apply_contact_tags( $contact_id, $tags ) {

		if( ! $this->i2sdk() || (int) $contact_id < 1 || empty($tags) || ! is_array($tags) ){
			return;
		}

		foreach ($tags as $index => $tag) {
			$assign_tag = $this->i2sdk()->grpAssign( (int)$contact_id, (int)$tag );
		}

	}

    function get_tags_map( $negatives = true, $negative_sprintf = '' ){

		$cache_key = 'btn/podcasts/tags/all/' . md5(serialize(func_get_args()));

		if( isset($this->tag_map[$cache_key]) ){
			return $this->tag_map[$cache_key];
		}

		$tags = false;
		$tag_map = [];

		if( function_exists('memb_getTagMap') ){
			$tags = memb_getTagMap(true,$negatives);
		}

		if( $tags ){
			if( $negatives ){
				if( ! $negative_sprintf > '' ){
					$negative_sprintf = __('Remove %s (- %s)');
				}
			}
			$tags = ( isset($tags['mc']) ) ? $tags['mc'] : [];
            foreach ( $tags as $id => $tag ) {
				$tag_map[] = [
					'id' 	=> $id,
					'text'	=> $tag . ' (' . $id . ')'
				];
				if( $negatives ){
					$tag_map[] = [
						'id' 	=> '-' . $id,
						'text'	=> sprintf($negative_sprintf, $tag, $id)
					];
				}
            }

			$this->tag_map[$cache_key] = $tag_map ? $tag_map : [];
		}

        return $this->tag_map[$cache_key];
    }

	function get_contact_fields_map(){

		if( is_null($this->contact_fields_map) ){
	        $contact_fields = [];
	        $valid_fields = [];
	        if( function_exists('memb_getContactFieldsMap') ){
	            $valid_fields = memb_getContactFieldsMap();
	        }

	        if( !empty($valid_fields) && is_array($valid_fields) ){
	            foreach ($valid_fields as $key => $valid_field) {
					$contact_fields[] = [
						'id' 	=> $valid_field,
						'text'	=> $valid_field
					];
	            }
	        }

			$this->contact_fields_map = $contact_fields;
		}

        return $this->contact_fields_map;
	}


	// Checks if all values of array are negative and returns positive array | false
	function all_negatives($tags){
		$return = false;
		if( ! is_array($tags) ){
			$tags = ( $tags > '' ) ? explode(',', trim($tags, ',') ) : [];
		}
		if( !empty($tags) ){
			$negatives = 0;
			$positive_array = [];
			foreach ($tags as $t => $tag) {
				if( substr($tag, 0, 1) === '-' ){
					$negatives ++;
					$tag = abs($tag);
				}
				$positive_array[] = $tag;
			}
			if( $negatives === count($tags) ){
				$return = $positive_array;
			}
		}
		return $return;
	}

	// CRM Link URL
	function contact_url( $contact_id = '%s' ){
		$appName = memb_getAppName();
		$url = "https://{$appName}.infusionsoft.com/Contact/manageContact.jsp?view=edit&ID={$contact_id}";
		return $url;
	}

    // Construct
    function __construct(){}

}
