<?php
/**
 * @Author: suifengtec
 * @Date:   2017-06-16 16:44:02
 * @Last Modified by:   suifengtec
 * @Last Modified time: 2017-06-16 19:12:25
 */

  

if ( ! defined( 'ABSPATH' ) ){
	exit;	
}

class WP_Term_Thumbnail_Module_Misc{

	public function __construct(){

		add_filter( 'get_terms_args', array($this,'get_terms_args_filter'), 10, 2 );
		add_action( 'delete_attachment', array($this,'delete_attachment_filter') );
		add_action( 'wp_trash_post', array($this,'trash_post_filter') );


		add_action( 'created_term', array($this,'update_term_thumbnail_on_form_submit'), 10, 3 );
		add_action( 'edited_term',  array($this,'update_term_thumbnail_on_form_submit'), 10, 3 );



		add_action( 'wp_ajax_add-tag',  array($this,'add_columns'), 5 );

		add_action( 'admin_init',       array($this,'add_columns'), 5 );


	}





	public function add_columns() {

		global $taxnow;
		$taxonomy   = self::doing_ajax() && ! empty( $_POST['taxonomy'] ) ? esc_html( $_POST['taxonomy'] ) : $taxnow;
		$taxonomies = array_flip( WP_Term_Thumbnail_Module_Utils::get_taxonomies() );

		if ( $taxonomy && isset( $taxonomies[ $taxonomy ] ) ) {
			add_filter( 'manage_edit-' . $taxonomy . '_columns',  array($this,'add_column_header'), PHP_INT_MAX );
			add_filter( 'manage_' . $taxonomy . '_custom_column', array($this,'add_column_content'), 10, 3 );
		}
	}


	public static function doing_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX && is_admin();
	}

	public function add_column_content( $content, $column_name, $term_id ) {
		global $taxnow;

		if ( 'term-thumbnail' !== $column_name ) {
			return $content;
		}

		if ( WP_Term_Thumbnail_Module_Utils::is_valid_wp_version()  ) {
			return WP_Term_Thumbnail_Module_Utils::get_term_thumbnail( $term_id, array( 80, 60 ) );
		}

		$taxonomy         = self::doing_ajax() && ! empty( $_POST['taxonomy'] ) ? esc_html( $_POST['taxonomy'] ) : $taxnow; 
		$term             = get_term( $term_id, $taxonomy );
		$term_taxonomy_id = absint( $term->term_taxonomy_id );

		return WP_Term_Thumbnail_Module_Utils::get_term_thumbnail( $term_taxonomy_id, array( 80, 60 ) );
	}



	public function add_column_header( $columns ) {
		$default_column = $this->get_primary_columns( $columns );

		if ( $default_column ) {
			$out = array();

			foreach ( $columns as $column => $label ) {
				$out[ $column ] = $label;

				if ( $column === $default_column ) {
					$out['term-thumbnail'] = __( 'Thumbnail' );
					$out = array_merge( $out, $columns );
					break;
				}
			}
		}else {
			$out = array_slice( $columns, 0, 2, true );
			$out['term-thumbnail'] = __( 'Thumbnail' );
			$out += $columns;
		}

		return $out;
	}


	public function get_primary_columns( $columns ) {
		global $current_screen;

		$default = '';

		foreach ( $columns as $col => $column_name ) {
			if ( 'cb' === $col ) {
				continue;
			}

			$default = $col;
			break;
		}

		if ( ! isset( $columns[ $default ] ) ) {
			$default = isset( $columns['title'] ) ? 'title' : false;
		}

		$column = apply_filters( 'list_table_primary_column', $default, $current_screen->id );

		if ( empty( $column ) || ! isset( $columns[ $column ] ) ) {
			$column = $default;
		}

		return $column;
	}


	public function update_term_thumbnail_on_form_submit( $term_id, $term_taxonomy_id, $taxonomy ) {
		// The thumbnail is already set via ajax (or hasn't changed).
		if ( ! empty( $_POST['term-thumbnail-updated'] ) || ! isset( $_POST['thumbnail'] ) ) { 
			return;
		}

		if ( empty( $_POST['action'] ) || ( 'add-tag' !== $_POST['action'] && 'editedtag' !== $_POST['action'] ) ) { 
			return;
		}

		$thumbnail_id = absint( $_POST['thumbnail'] );
		$term_id      = WP_Term_Thumbnail_Module_Utils::is_valid_wp_version() ? $term_id : $term_taxonomy_id;

		if ( $thumbnail_id ) {
			WP_Term_Thumbnail_Module_Utils::set_term_thumbnail( $term_id, $thumbnail_id );
		}
		else {
			WP_Term_Thumbnail_Module_Utils::delete_term_thumbnail( $term_id );
		}
	}




	public function trash_post_filter( $post_id ) {

		$post = get_post( $post_id );

		if ( 'attachment' === $post->post_type && 0 === strpos( $post->post_mime_type, 'image/' ) ) {

			$this->delete_attachment_filter( $post_id );
		}
	}


	public function delete_attachment_filter( $post_id ) {
		$post_id = absint( $post_id );
		$deleted = delete_metadata( 'term', 0, '_thumbnail_id', $post_id, true );

		if ( $deleted ) {
			wp_cache_set( 'last_changed', microtime(), 'terms' );
		}
	}



	public function get_terms_args_filter( $args, $taxonomies ) {

		if ( empty( $args['with_thumbnail'] ) ) {
			return $args;
		}

		$args['meta_query'] = ! empty( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();

		// We need a "AND" relation here: if we meet a "OR" relation we build a nested query.
		if ( isset( $args['meta_query']['relation'] ) && 'AND' !== strtoupper( $args['meta_query']['relation'] ) ) {
			$args['meta_query'] = array( $args['meta_query'] );
		}

		$args['meta_query'][] = $this->get_meta_query();

		return $args;
	}



	public function get_meta_query() {
		return array(
			'key'     => '_thumbnail_id',
			'value'   => 0,
			'compare' => '>',
			'type'    => 'SIGNED',
		);
	}


}
/*EOF*/
