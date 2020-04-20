<?php
/**
 * @package wde
 */

namespace croox\wde;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class List_Table_Meta_Column {

	public $post_types;

	public $column;

	function __construct( $post_types = array(), $column = array() ) {
		$this->post_types = $post_types;

		$this->column = wp_parse_args( $column,  array(
			'title' => '',
			'metakey' => '',				// required
			'sortable' => false,
			'insert_after' => 'title',
			'order_by_cb' => null,			// optional custom order_by callback
			'order_by_inner_cb' => null,	// optional custom order_by inner callback
			'render_cb' => null,			// optional custom render callback
			'render_inner_cb' => null,		// optional custom render inner callback
		) );

		array_walk( $this->column, function( &$val, $key ){
			switch( $key ) {
				case 'title':
					return sanitize_title( $val );
				case 'metakey':
				case 'insert_after':
					return trim( $val );
				default:
					return $val;
			}
		} );

		if ( ! empty( $this->column['metakey'] ) ){
			add_action( 'current_screen', array( $this, 'hooks' ) );
		}
	}

	public function hooks( $screen ) {

		if ( ( ! in_array( $screen->post_type, $this->post_types ) ) || 'edit' !== $screen->base )
			return;

		foreach( $this->post_types as $post_type ){

			// add column
			add_filter( 'manage_' . $post_type . '_posts_columns', array( $this, 'add_column' ) );

			// render column
			$render_cb = is_string( $this->column['render_cb'] ) && ! empty( $this->column['render_cb'] )
				? $this->column['render_cb']
				: array( $this, 'render_column' );
			add_action( 'manage_' . $post_type . '_posts_custom_column', $render_cb, 10, 2 );

			// may be sortable
			if ( $this->column['sortable'] ) {
				add_filter( 'manage_edit-' . $post_type . '_sortable_columns', array( $this, 'make_column_sortable' ) );

				$order_by_cb = is_string( $this->column['order_by_cb'] ) && ! empty( $this->column['order_by_cb'] )
					? $this->column['order_by_cb']
					: array( $this, 'order_by' );
				add_action( 'pre_get_posts', $order_by_cb );
			}

		}
	}

	public function add_column( $columns ) {
		// we checked screen already

		$new_columns = array();

		if ( ! array_key_exists( $this->column['insert_after'], $columns ) ) {
			$this->column['insert_after'] = 'title';
		}

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( $key === $this->column['insert_after'] ) {
				$new_columns[$this->column['key']] = $this->column['title'];
			}
		}

		return $new_columns;
	}

	public function render_column( $column, $post_id ) {
		if( $column === $this->column['key'] ) {
			if ( is_string( $this->column['render_inner_cb'] ) && ! empty( $this->column['render_inner_cb'] ) ) {
				$this->column['render_inner_cb']( $column, $post_id, $this );
			} else {
				echo get_post_meta( $post_id, $this->column['metakey'], true );
			}
		}
	}

	public function make_column_sortable( $sortable_columns ) {
		if ( $this->column['sortable'] ) {
			$sortable_columns[ $this->column['key'] ] = $this->column['metakey'];
		}
		return $sortable_columns;
	}

	public function order_by( $query ) {
		// we checked screen already

		$orderby = $query->get( 'orderby');

		if( $this->column['metakey'] === $orderby ) {

			if ( is_string( $this->column['order_by_inner_cb'] ) && ! empty( $this->column['order_by_inner_cb'] ) ) {
				$this->column['order_by_inner_cb']( $query, $this );
			} else {
				$query->set( 'meta_key', $this->column['metakey'] );
				$query->set( 'orderby', 'meta_value_num' );
			}

		}

	}

}





/*
// example implementation
function prefix_my_list_table_meta_column() {

	$post_types = array(
		'some_cpt',
		'some_other_cpt',
	);

	$column = array(
		'key' => 'prefix_key',
		'metakey' => 'prefix_meta_key',
		'title' => __( 'Some Title', 'textdomain' ),
		'sortable' => true,
	);

	return new croox\wde\List_Table_Meta_Column( $post_types, $column );

}
add_action( 'admin_init', 'prefix_my_list_table_meta_column' );
*/

