<?php
/**
 * Manage the destination options: post type, shortcode and post properties (status, author...).
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Metabox_Post_Settings
 */
class Notion_WP_Sync_Metabox_Post_Settings {
	/**
	 * List of available importers
	 *
	 * @var Notion_WP_Sync_Importer[]
	 */
	protected $importers = array();

	/**
	 * Constructor
	 *
	 * @param Notion_WP_Sync_Importer[] $importers Available importers.
	 */
	public function __construct( $importers ) {
		$this->importers = $importers;

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
	}

	/**
	 * Add metabox
	 */
	public function add_meta_box() {
		add_meta_box(
			'notionwpsync-post-settings',
			__( 'Import As...', 'wp-sync-for-notion' ),
			array( $this, 'display' ),
			'nwpsync-connection',
			'normal',
			'high'
		);
	}

	/**
	 * Output metabox HTML
	 *
	 * @param WP_Post $post The connection.
	 */
	public function display( $post ) {
		$importer   = Notion_WP_Sync_Helpers::get_importer_by_id( $this->importers, $post->ID );
		$post_types = array_filter(
			Notion_WP_Sync_Helpers::get_post_types(),
			function ( $post_type ) use ( $importer ) {
				return ( ! $importer || ( $importer->config()->get( 'post_type' ) !== 'custom' || $importer->config()->get( 'post_type_name' ) !== $post_type['value'] ) ) && 'nwpsync-content' !== $post_type['value'];
			}
		);

		$post_stati   = Notion_WP_Sync_Helpers::get_post_stati();
		$post_authors = Notion_WP_Sync_Helpers::get_post_authors();
		$view         = include_once NOTION_WP_SYNC_PLUGIN_DIR . 'views/metabox-post-settings.php';
		$view( $post_types, $post_stati, $post_authors );
	}
}
