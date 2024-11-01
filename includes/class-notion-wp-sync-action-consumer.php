<?php
/**
 * Action consumer manager.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_WP_Sync;

/**
 * Notion_WP_Sync_Action_Consumer class.
 */
class Notion_WP_Sync_Action_Consumer {
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
		add_action( 'notionwpsync_process_records', array( $this, 'consume' ), 10, 4 );
	}

	/**
	 * Consume a record from queue
	 *
	 * @param int    $importer_id Importer id.
	 * @param string $run_id Run id.
	 * @param string $item_id Item id.
	 */
	public function consume( $importer_id, $run_id, $item_id ) {
		// Get importer instance from id.
		$importer = Notion_WP_Sync_Helpers::get_importer_by_id( $this->importers, $importer_id );
		if ( ! $importer ) {
			return;
		}
		// Get Notion record saved as a temporary option.
		$records = get_option( $item_id );
		if ( ! $records ) {
			return;
		}

		foreach ( $records as $record ) {
			// Import record.
			$post_id = $importer->process_notion_record( $record );
			// Temporarily save created or saved post ID.
			if ( ! empty( $post_id ) && ! is_wp_error( $post_id ) ) {
				$this->append_run_result( $post_id, $importer );
			}
		}

		// Delete temporary option.
		delete_option( $item_id );

		if ( $run_id === $importer->get_run_id() && 0 === $this->get_remaining_actions_count( $importer_id, $run_id ) ) {
			$importer->delete_removed_posts();
			$importer->end_run( 'success' );
		}
	}

	/**
	 * Get count of remaining actions
	 *
	 * @param int         $importer_id Importer id.
	 * @param null|string $run_id Run id.
	 */
	protected function get_remaining_actions_count( $importer_id, $run_id = null ) {
		$args = array(
			'importer_id' => $importer_id,
		);

		if ( $run_id ) {
			$args['run_id'] = $run_id;
		}

		$actions = as_get_scheduled_actions(
			array(
				'hook'                  => 'notionwpsync_process_records',
				'status'                => \ActionScheduler_Store::STATUS_PENDING,
				'partial_args_matching' => 'like',
				'args'                  => $args,
				'per_page'              => -1,
			)
		);
		return count( $actions );
	}

	/**
	 * Append new post_id to the run result
	 *
	 * @param int                     $post_id Post id.
	 * @param Notion_WP_Sync_Importer $importer Importer.
	 */
	protected function append_run_result( $post_id, $importer ) {
		$post_ids = get_post_meta( $importer->infos()->get( 'id' ), 'post_ids', true );
		if ( ! is_array( $post_ids ) ) {
			$post_ids = array();
		}
		$post_ids[] = $post_id;
		update_post_meta( $importer->infos()->get( 'id' ), 'post_ids', $post_ids );
	}
}
