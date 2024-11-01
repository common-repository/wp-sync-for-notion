<?php
/**
 * Manages files & media Notion property type.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion field Files & media
 */
class Notion_WP_Sync_Files_Field extends Notion_WP_Sync_Abstract_Field implements Notion_WP_Sync_Support_String_Value, Notion_WP_Sync_Support_Files_Value {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $type = 'files';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $default_value_type = Notion_WP_Sync_Support_Files_Value::class;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected $filter_type = 'files';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 * @param array $params Extra params.
	 */
	public function get_string_value( $params ): string {
		$files = $this->get_files_value( $params );
		$urls  = array();
		foreach ( $files as $file_id ) {
			$url = wp_get_attachment_url( $file_id );
			if ( false !== $url ) {
				$urls[] = $url;
			}
		}

		return implode( _x( ', ', 'List separator', 'wp-sync-for-notion' ), $urls );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 * @param array $params Extra params.
	 */
	public function get_files_value( $params ): array {
		if ( ! isset( $this->data->files ) || ! is_array( $this->data->files ) ) {
			return array();
		}

		$notion_attachment_manager = Notion_WP_Sync_Attachments_Manager::get_instance();
		// Reformat files props.
		$block_id = $this->data->id;
		$files    = array_map(
			function ( $file ) use ( $block_id, $notion_attachment_manager ) {
				return $notion_attachment_manager->notion_file_to_media(
					$block_id,
					$file->name,
					$file
				);
			},
			$this->data->files
		);

		return $notion_attachment_manager->get_set_files( $files, $params['importer'], $params['post_id'] ?? null );
	}
}
