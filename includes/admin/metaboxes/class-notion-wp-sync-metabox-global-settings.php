<?php
/**
 * Manages Notion settings options: API Key, page selection and page scope.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

use Exception;

/**
 * Notion_WP_Sync_Metabox_Global_Settings class.
 */
class Notion_WP_Sync_Metabox_Global_Settings {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'wp_ajax_notion_wp_sync_get_notion_objects', array( $this, 'get_notion_objects' ) );
	}

	/**
	 * Add metabox
	 */
	public function add_meta_box() {
		add_meta_box(
			'notionwpsync-global-settings',
			__( 'Notion Settings', 'wp-sync-for-notion' ),
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
		global $post;
		$config_array = json_decode( $post->post_content, true );
		$config       = new Notion_WP_Sync_Importer_Settings( $config_array );
		$client       = new Notion_WP_Sync_Notion_Api_Client( $config->get( 'api_key' ) );
		$object_type  = $config->get( 'object_type' );
		$objects_id   = $config->get( 'objects_id' );

		$default_objects = array(
			'page' => array(),
		);
		if ( ! is_array( $objects_id ) ) {
			$objects = $default_objects;
		} else {
			// @TODO: try / catch
			$objects = array_reduce(
				$objects_id,
				function ( $result, $object_id ) use ( $client, $object_type ) {
					$object = null;
					if ( 'page' === $object_type ) {
						$object = $client->get_page( $object_id );
					}

					if ( $object ) {
						$result[ $object_type ][ $object_id ] = $object;
					}

					return $result;
				},
				$default_objects
			);
		}
		$view = include_once NOTION_WP_SYNC_PLUGIN_DIR . 'views/metabox-notion-settings.php';
		$view( $objects );
	}

	/**
	 * Ajax action to get Notion objects (pages).
	 *
	 * @return void
	 */
	public function get_notion_objects() {
		// Nonce check.
		check_ajax_referer( 'notion-wp-sync-ajax', 'nonce' );

		$api_key     = isset( $_POST['apiKey'] ) ? sanitize_text_field( wp_unslash( $_POST['apiKey'] ) ) : '';
		$object_type = isset( $_POST['objectType'] ) ? sanitize_text_field( wp_unslash( $_POST['objectType'] ) ) : '';

		// Data check.
		if ( empty( $api_key ) || empty( $object_type ) || 'page' !== $object_type ) {
			wp_die();
		}

		$term = sanitize_text_field( wp_unslash( $_POST['term'] ?? '' ) );

		try {
			$client = new Notion_WP_Sync_Notion_Api_Client( $api_key );
			$result = array();
			if ( 'page' === $object_type ) {
				$result = $client->list_pages( array( 'page_size' => 10 ), $term );
			}

			wp_send_json_success(
				$result
			);
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'error' => $e->getMessage(),
				)
			);
		}
	}
}
