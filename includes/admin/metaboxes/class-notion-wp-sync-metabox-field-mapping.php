<?php
/**
 * Manages field mapping.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Metabox_Field_Mapping class.
 */
class Notion_WP_Sync_Metabox_Field_Mapping {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
	}

	/**
	 * Add metabox
	 */
	public function add_meta_box() {
		$tooltip_html = '<span class="notionwpsync-tooltip" aria-label="' . esc_attr__( 'Add all the Notion properties you want to synchronize and then select the corresponding data types where they will be imported into your post.', 'wp-sync-for-notion' ) . '">?</span>';
		add_meta_box(
			'notionwpsync-mapping',
			__( 'Field Mapping', 'wp-sync-for-notion' ) . $tooltip_html,
			array( $this, 'display' ),
			'nwpsync-connection',
			'normal',
			'high'
		);
	}

	/**
	 * Output metabox HTML
	 */
	public function display() {
		$view = include_once NOTION_WP_SYNC_PLUGIN_DIR . 'views/metabox-mapping.php';
		$view();
	}
}
