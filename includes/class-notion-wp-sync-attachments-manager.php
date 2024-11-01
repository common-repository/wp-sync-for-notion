<?php
/**
 * Manages files import.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Attachments_Manager class.
 *
 * @TODO: define $media structure, new class? see notion_file_to_media
 */
class Notion_WP_Sync_Attachments_Manager {
	/**
	 * Notion_WP_Sync_Attachments_Manager instance
	 *
	 * @var Notion_WP_Sync_Attachments_Manager $instance
	 */
	private static $instance;

	/**
	 * Returns Notion_WP_Sync_Attachments_Manager instance
	 *
	 * @return Notion_WP_Sync_Attachments_Manager
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Importer.
	 *
	 * @var Notion_WP_Sync_Importer
	 */
	protected $importer;

	/**
	 * Import / update media from a list.
	 * Return imported attachments id.
	 *
	 * @param array                   $value A list of files to import.
	 * @param Notion_WP_Sync_Importer $importer Importer.
	 * @param int|null                $post_id Post id to attach the media to.
	 *
	 * @return array
	 */
	public function get_set_files( $value, $importer, $post_id = null ) {
		$this->importer = $importer;

		if ( empty( $value ) ) {
			return array();
		}

		// Make sure we have an array of medias.
		$medias         = ! is_array( $value ) ? array( $value ) : $value;
		$attachment_ids = array();
		foreach ( $medias as $media ) {
			$attachment_id = $this->get_object_id_from_record_id( $media->id, 'attachment' );
			if ( ! $attachment_id || $this->needs_update( $attachment_id, $media ) ) {
				$attachment_id = $this->import_media( $media, $post_id );
			}

			if ( $attachment_id > 0 ) {
				$attachment_ids[] = $attachment_id;
			}
		}

		return $attachment_ids;
	}


	/**
	 * Import a media.
	 * Return 0 if the media can't be imported.
	 *
	 * @param \stdClass $media The media to import.
	 * @param int|null  $post_id Post id to attach the media to.
	 *
	 * @return int
	 */
	protected function import_media( $media, $post_id = null ): int {
		$this->log( sprintf( '- Import media %s', $media->id ) );
		$media_metas = array(
			'_notion_wp_sync_record_id'  => $media->id,
			'_notion_wp_sync_hash'       => $this->generate_hash( $media ),
			'_notion_wp_sync_updated_at' => gmdate( 'Y-m-d H:i:s' ),
		);
		// Check if media already exists.
		$attachment_id = $this->get_object_id_from_record_id( $media->id, 'attachment' );
		// If not, create it.
		if ( ! $attachment_id ) {
			$this->log( sprintf( '- Create media %s', $media->id ) );
			$media_data = array(
				'post_title'  => $media->filename,
				'post_author' => 0,
				'post_parent' => $post_id,
			);

			$filename = $media->filename;

			// Force file extension from type field.
			if ( ! empty( $media->type ) ) {
				$new_filename = pathinfo( sanitize_file_name( $media->filename ), PATHINFO_FILENAME );
				$mime_to_ext  = apply_filters(
					'getimagesize_mimes_to_exts',
					array(
						'image/jpeg' => 'jpg',
						'image/png'  => 'png',
						'image/gif'  => 'gif',
						'image/bmp'  => 'bmp',
						'image/tiff' => 'tif',
						'image/webp' => 'webp',
					)
				);
				// Get file extension from type.
				if ( ! empty( $mime_to_ext[ $media->type ] ) ) {
					$extension = $mime_to_ext[ $media->type ];
					$filename  = "$new_filename.$extension";
				}
			}

			$result = $this->fetch_media( $media->url, $filename, null, $media_data );
			if ( is_wp_error( $result ) ) {
				$this->log( sprintf( '- ERROR: %s', $result->get_error_message() ), 'error' );
			} else {
				$this->log( sprintf( '- Success media %s', $result ) );
				$attachment_id = $result;
			}
		}
		if ( $attachment_id ) {
			// Add media metas.
			foreach ( $media_metas as $meta_key => $meta_value ) {
				update_post_meta( $attachment_id, $meta_key, $meta_value );
			}
		}

		return (int) $attachment_id;
	}

	/**
	 * Fetch a media using WP core functions
	 *
	 * @param string      $url The media URL.
	 * @param string|null $filename The media name.
	 * @param string|null $desc The media description.
	 * @param array       $post_data The attachment data.
	 * @param array       $post_meta The attachment metas.
	 *
	 * @return int|\WP_Error
	 */
	protected function fetch_media( $url, $filename = null, $desc = null, $post_data = array(), $post_meta = array() ) {
		require_once ABSPATH . 'wp-admin/includes/admin.php';

		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return new \WP_Error( 'notion_wp_sync_fetch_media_invalid_url', 'Invalid URL.' );
		}

		// Download file to temp location.
		$temp_file = download_url( $url );

		if ( empty( $filename ) ) {
			$filename = basename( urldecode( $url ) );
		}

		// Try to guess file extension from type field.
		if ( ! pathinfo( $filename, PATHINFO_EXTENSION ) ) {
			$mime_to_ext = apply_filters(
				'getimagesize_mimes_to_exts',
				array(
					'image/jpeg' => 'jpg',
					'image/png'  => 'png',
					'image/gif'  => 'gif',
					'image/bmp'  => 'bmp',
					'image/tiff' => 'tif',
					'image/webp' => 'webp',
				)
			);
			// Get file mimie type.
			$mime_type = wp_get_image_mime( $temp_file );
			// Get file extension from it.
			if ( ! empty( $mime_to_ext[ $mime_type ] ) ) {
				$extension = $mime_to_ext[ $mime_type ];
				$filename .= ".$extension";
			}
		}

		$file_data = array(
			'name'     => $filename,
			'tmp_name' => $temp_file,
		);

		if ( is_wp_error( $file_data['tmp_name'] ) ) {
			return $file_data['tmp_name'];
		}

		// Let WP handle this.
		$result = media_handle_sideload( $file_data, 0, $desc, $post_data );

		if ( is_wp_error( $result ) ) {
			wp_delete_file( $file_data['tmp_name'] );
			return $result;
		}

		$attachment_id = $result;
		foreach ( $post_meta as $meta_key => $meta_value ) {
			update_post_meta( $attachment_id, $meta_key, $meta_value );
		}
		return $attachment_id;
	}

	/**
	 * Get WP object id from Notion record id
	 *
	 * @param string $record_id Notion record id.
	 * @param string $post_type Post type.
	 *
	 * @return int
	 */
	protected function get_object_id_from_record_id( $record_id, $post_type ) {
		$objects = get_posts(
			array(
				'fields'      => 'ids',
				'post_type'   => $post_type,
				'post_status' => 'any',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'  => array(
					array(
						'key'   => '_notion_wp_sync_record_id',
						'value' => $record_id,
					),
				),
			)
		);

		if ( count( $objects ) === 0 ) {
			return 0;
		}
		return (int) array_shift( $objects );
	}

	/**
	 * Compare hashes to check if WP object needs update
	 *
	 * @param int       $post_id Post id.
	 * @param \stdClass $media Media.
	 *
	 * @return bool
	 */
	protected function needs_update( $post_id, $media ) {
		return $this->generate_hash( $media ) !== $this->get_post_hash( $post_id );
	}

	/**
	 * Get stored post hash
	 *
	 * @param int $post_id Post id.
	 */
	protected function get_post_hash( $post_id ) {
		return get_post_meta( $post_id, '_notion_wp_sync_hash', true );
	}

	/**
	 * Generate hash for given Media
	 *
	 * @param \stdClass $media Media.
	 */
	protected function generate_hash( $media ) {
		return md5( wp_json_encode( $media ) );
	}

	/**
	 * Log
	 *
	 * @param mixed  $message Message to log.
	 * @param string $level Log level.
	 */
	protected function log( $message, $level = 'log' ) {
		if ( $this->importer ) {
			$this->importer->log( $message, $level );
		}
	}

	/**
	 * Build a media object from Notion file prop.
	 *
	 * @param string    $block_id Block id.
	 * @param string    $filename File name.
	 * @param \stdClass $file_object File props.
	 * @param string    $file_ext File extension.
	 *
	 * @return object
	 */
	public function notion_file_to_media( $block_id, $filename, $file_object, $file_ext = '' ) {
		$url           = $file_object->{$file_object->type}->url;
		$ressource_url = remove_query_arg( array( 'X-Amz-Signature', 'X-Amz-Credential', 'X-Amz-Date' ), $url );
		return (object) array(
			'id'       => md5( $block_id . '_' . $ressource_url ),
			'filename' => sanitize_title( $filename ) . '.' . $file_ext,
			'url'      => $url,
		);
	}
}
