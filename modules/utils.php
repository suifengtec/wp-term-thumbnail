<?php
/**
 * @Author: suifengtec
 * @Date:   2017-06-16 15:40:06
 * @Last Modified by:   suifengtec
 * @Last Modified time: 2017-06-16 19:12:31
 */

  

if ( ! defined( 'ABSPATH' ) ){
	exit;	
}

/*


*/
if(!class_exists('WP_Term_Thumbnail_Module_Utils')):

class WP_Term_Thumbnail_Module_Utils{

	public function __construct(){}

	public function hooks(){

		add_action( 'load-edit-tags.php', array($this,'add_fields'));
		add_action( 'wp_ajax_wpTermThumbnail_set',  array($this,'wpTermThumbnail_set' ));
		add_action( 'wp_ajax_wpTermThumbnail_delete', array($this, 'wpTermThumbnail_delete' ));



	}



	public function wpTermThumbnail_delete() {

		if ( empty( $_POST['term_ID'] ) || empty( $_POST['tt_ID'] ) || empty( $_POST['taxonomy'] ) ) {
			wp_send_json_error();
		}

		check_ajax_referer( 'update-tag_' . $_POST['term_ID'], '_wpnonce' );

		$taxonomy = esc_attr( $_POST['taxonomy'] );
		$taxonomy = get_taxonomy( $taxonomy );

		if ( ! $taxonomy || is_wp_error( $taxonomy ) || ! current_user_can( $taxonomy->cap->edit_terms ) ) {
			wp_send_json_error();
		}

		$term_id = self::is_valid_wp_version()  ? absint( $_POST['term_ID'] ) : absint( $_POST['tt_ID'] );

		if ( $term_id && ( ! self::get_term_thumbnail_id( $term_id ) || $this->delete_term_thumbnail( $term_id ) ) ) {
			wp_send_json_success();
		}

		wp_send_json_error();
	}


	public function wpTermThumbnail_set() {
		if ( empty( $_POST['term_ID'] ) || empty( $_POST['tt_ID'] ) || empty( $_POST['taxonomy'] ) || empty( $_POST['id'] ) ) {
			wp_send_json_error();
		}

		check_ajax_referer( 'update-tag_' . $_POST['term_ID'], '_wpnonce' );

		$taxonomy = esc_attr( $_POST['taxonomy'] );
		$taxonomy = get_taxonomy( $taxonomy );

		if ( ! $taxonomy || is_wp_error( $taxonomy ) || ! current_user_can( $taxonomy->cap->edit_terms ) ) {
			wp_send_json_error();
		}

		$term_id      = self::is_valid_wp_version() ? absint( $_POST['term_ID'] ) : absint( $_POST['tt_ID'] );
		$thumbnail_id = absint( $_POST['id'] );

		if ( $term_id && $thumbnail_id ) {
			if ( self::get_term_thumbnail_id( $term_id ) === $thumbnail_id ) {
				wp_send_json_success();
			}
			if ( self::set_term_thumbnail( $term_id, $thumbnail_id ) ) {
				wp_send_json_success();
			}
		}

		wp_send_json_error();
	}


	public static function is_valid_wp_version(){

		return  function_exists( 'get_term_meta' ) && get_option( 'db_version' ) >= 34370;
	}



	public function get_taxonomies() {
		$taxonomies = get_taxonomies( array(
			'public'  => true,
			'show_ui' => true,
		) );
		return apply_filters( 'wpTermThumbnail_taxonomies', $taxonomies );
	}

	public function add_fields(){

		global $taxnow;
		$taxonomies = array_flip( $this->get_taxonomies() );

		if ( $taxnow && isset( $taxonomies[ $taxnow ] ) ) {

			/*add new term*/
			add_action( $taxnow . '_add_form_fields', array($this,'new_term_field'), 20 );
			/*edit term*/
			add_action( $taxnow . '_edit_form',  array($this,'edit_term_field'), 20, 2 );
			
			add_action( 'admin_enqueue_scripts',  array($this,'enqueue_scripts') );
		}

	}


	public static function get_term_thumbnail_id( $term_id ) {

		$term_id = absint( $term_id );

		if ( $term_id ) {
			$thumbnail_id = get_term_meta( $term_id, '_thumbnail_id', true );
			return $thumbnail_id ? absint( $thumbnail_id ) : false;
		}

		return false;
	}

	public static function delete_term_thumbnail( $term_id ) {
		return self::set_term_thumbnail( $term_id, false );
	}


	public static function has_term_thumbnail( $term_id ) {
		return (bool) self::get_term_thumbnail_id( $term_id );
	}


	public static function the_term_thumbnail( $term_id, $size = 'post-thumbnail', $attr = '' ) {
		if(self::has_term_thumbnail( $term_id )){
			echo self::get_term_thumbnail( $term_id, $size, $attr );
		}
	}


	public static function get_term_thumbnail( $term_id, $size = 'post-thumbnail', $attr = '' ) {

		$term_thumbnail_id = self::get_term_thumbnail_id( $term_id );

		$size = apply_filters( 'term_thumbnail_size', $size );

		if ( $term_thumbnail_id ) {

			do_action( 'wp_term_thumbnail_begin_fetch_term_thumbnail_html', $term_id, $term_thumbnail_id, $size );

			$html = wp_get_attachment_image( $term_thumbnail_id, $size, false, $attr );

			// Make sure SVGs are not displayed 1px wide.
			$html = preg_replace( '@^<img width="1" height="1" src="(.+)\.svg" @', '<img src="$1.svg" ', $html );


			do_action( 'wp_term_thumbnail_end_fetch_term_thumbnail_html', $term_id, $term_thumbnail_id, $size );

		}
		else {
			$html = '';
		}

		return apply_filters( 'wp_term_thumbnail_html', $html, $term_id, $term_thumbnail_id, $size, $attr );
	}


	public static function get_term_thumbnail_url( $term_id=0, $size='full',$atts = array(), $onlySrc = true){

		if(!empty($atts)){
	        $params = array_merge(array(
	            'term_id' => null,
	          	'size'    => 'full'
	        ), $atts);

	        $term_id = $params['term_id'];
	        $size    = $params['size'];
		}

        if (!$term_id) {
            $term    = get_queried_object();
            $term_id = $term->term_id;
        }
        if (!$term_id) {
            return;
        }

        $attachment_id = self::get_term_thumbnail_id( $term_id );

        if(empty($attachment_id)){
        	return;
        }

        if ($onlySrc) {
            $src = wp_get_attachment_image_src( $attachment_id, $size, false);
            return is_array($src) ? $src[0] : null;
        }

        return wp_get_attachment_image($attachment_id, $size, false, $attr);

	}


	public static function set_term_thumbnail( $term_id, $thumbnail_id ) {
		$term_id      = absint( $term_id );
		$thumbnail_id = absint( $thumbnail_id );

		if ( ! $term_id ) {
			return false;
		}

		if ( $thumbnail_id && get_post( $thumbnail_id ) && wp_get_attachment_image( $thumbnail_id, 'thumbnail' ) ) {
			return update_term_meta( $term_id, '_thumbnail_id', $thumbnail_id );
		}

		return delete_term_meta( $term_id, '_thumbnail_id' );
	}


	public static function upload_image_base64( $img_base64Str, $objectType='term', $objectID=0 ){

		global $blog_id, $wpdb;
		if(!empty($img_base64Str)) {

			list($type, $img_base64Str) = explode(';', $img_base64Str);
			list(, $type)        = explode(':', $type);
			list(, $type)        = explode('/', $type);
			list(, $img_base64Str)      = explode(',', $img_base64Str);

			$type = strtolower($type);

			$img = base64_decode($img_base64Str);
			
			/*
			wp_upload_dir( null, false );
			 */
			$wp_upload_dir = wp_upload_dir();	
			$filename = 'cwp_term_thumb_'.time().".$type";
			$path_to_file = $wp_upload_dir['path']."/".$filename;
			$filetype = wp_check_filetype( basename( $filename), null );


			if( !function_exists( 'wp_handle_upload' ) ){
			    require_once( ABSPATH . 'wp-admin/includes/file.php' );
			    require_once( ABSPATH . 'wp-admin/includes/image.php' );
			}

			@file_put_contents( $path_to_file, $img );

			$attachment = array(
				'post_author' => 1,
				'post_content' => '',
				'post_content_filtered' => '',
				'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
				'post_excerpt' => '',
				'post_status' => 'inherit',
				'post_type' => 'attachment',
				'post_mime_type' => $filetype['type'],
				'comment_status' => 'closed',
				'ping_status' => 'closed',
				'post_password' => '',
				'to_ping' =>  '',
				'pinged' => '',
				'post_parent' => 0,
				'menu_order' => 0,
				'guid' => $wp_upload_dir['url'].'/'.$filename,
			);
			$wpdb->insert("{$wpdb->prefix}posts", $attachment); 
			$attach_id = $wpdb->insert_id;
			if($attach_id == false){
				return false;
			}


			$attach_data = wp_generate_attachment_metadata( $attach_id, $path_to_file );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			
			update_attached_file($attach_id, $path_to_file);


			switch($objectTypee){

				default:
				case 'term':
					self::set_term_thumbnail( $objectTypeID, $attach_id );
				break;
				case 'post':
					do_action('wp_term_thumbnail_after_image_uploaded_set_post_thumbnail', $objectTypeID, $attach_id);
				break;

				case 'user':

					do_action('wp_term_thumbnail_after_image_uploaded_set_user_avatar', $objectTypeID, $attach_id);
				break;

			}

			
			return true;
		}
		return false;

		
	}

/*



 */
	public function edit_term_field( $term, $taxonomy ) {

		$term_id      = self::is_valid_wp_version() ? absint( $term->term_id ) : absint( $term->term_taxonomy_id );
		$thumbnail_id =  self::get_term_thumbnail_id( $term_id );
		$thumbnail    = '';

		if ( $thumbnail_id ) {
			$thumbnail = self::get_term_thumbnail( $term_id, 'medium', array(
				'title' => trim( strip_tags( get_the_title( $thumbnail_id ) ) ),
			) );
			if ( ! $thumbnail ) {
				$thumbnail_id = '';
			}
		}
		?>
		<table class="form-table">
			<tbody>
				<tr class="form-field term-thumbnail-wrap">
					<th scope="row">
						<label for="thumbnail"><?php _e( 'Thumbnail' ); ?></label>
					</th>
					<td>
						<div id="wp-thumbnail-wrap" class="wp-thumbnail-wrap wp-editor-wrap hide-if-js">
							<input type="number" name="thumbnail" value="<?php echo $thumbnail_id; ?>" id="thumbnail" autocomplete="off" title="显示图像ID" /><br/>
							<?php echo $thumbnail; ?>
						</div>

						<div id="thumbnail-field-wrapper" class="thumbnail-field-wrapper hide-if-no-js" aria-hidden="true" data-tt-id="<?php echo $term_id; ?>">
							<?php
							if ( $thumbnail ) {
								$orientation = wp_get_attachment_image_src( $thumbnail_id, 'medium' );
								$orientation = $orientation[1] >= $orientation[2] ? 'landscape' : 'portrait';
								echo '<button type="button" class="change-term-thumbnail add-term-thumbnail attachment" id="thumbnail-button" title="更换缩略图">';
									echo '<span class="attachment-preview type-image ' . $orientation . '"><span class="thumbnail"><span class="centered">' . $thumbnail . '</span></span></span>';
								echo '</button><div class="clear"></div>';
								echo '<button type="button" class="remove-term-thumbnail button button-secondary button-large delete">移除缩略图</button>';
							}
							else {
								echo '<div class="clear"></div><button type="button" class="add-term-thumbnail button button-secondary button-large" id="thumbnail-button">设置一个缩略图</button>';
							}
							?>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}


	public function new_term_field() {
		?>
		<div class="form-field term-thumbnail">
			<label for="thumbnail"><?php _e( 'Thumbnail' ); ?></label>
			<div id="wp-thumbnail-wrap" class="wp-thumbnail-wrap wp-editor-wrap hide-if-js">
				<input type="number" name="thumbnail" value="" id="thumbnail" autocomplete="off" title="<?php esc_attr_e( 'Indicate an image ID', 'sf-taxonomy-thumbnail' ); ?>" />
			</div>

			<p id="thumbnail-field-wrapper" class="thumbnail-field-wrapper hide-if-no-js" aria-hidden="true">
				<button type="button" class="add-term-thumbnail button button-secondary button-large" id="thumbnail-button"><?php _e( 'Set a thumbnail', 'sf-taxonomy-thumbnail' ); ?></button>
			</p>
		</div>
		<?php
	}


	public function enqueue_scripts(){

			wp_enqueue_style( 'wp-term-thumbnail', TERMT_PLUGIN_URL . 'assets/css/style.css', false, false, 'all' );

			wp_enqueue_media();

			$dependencies = array( 'jquery', 'media-editor' );
			if ( version_compare( $GLOBALS['wp_version'], '4.2', '>=' ) ) {
				$dependencies[] = 'wp-a11y';
			}
			wp_enqueue_script( 'wp-term-thumbnail',  TERMT_PLUGIN_URL . 'assets/js/script.js', $dependencies, false, true );

		$i18n = array(
			'setImage'       => '设置缩略图',
			'changeImage'    => '更改缩略图',
			'removeImage'    => '移除缩略图',
			'chooseImage'    => '选择缩略图',
			'selectImage'    => '选择缩略图',
			'loading'        => '载入中&hellip;',
			'successSet'     => '缩略图设置成功',
			'successRemoved' => '缩略图移除成功',
			'errorSet'       =>  '出错了:设置失败!',
			'errorRemoved'   => '出错了:删除失败!',
		);
		wp_localize_script( 'wp-term-thumbnail', 'wpTermThumbnail', $i18n );


	}


}
endif;
/*EOF*/
