<?php
/**
 * Display connection state: status, last error, last updated date time, next sync.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

use Exception;

/**
 * Notion_WP_Sync_Metabox_Import_Infos
 */
class Notion_WP_Sync_Metabox_Import_Infos {
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
		add_action( 'wp_ajax_notion_wp_sync_trigger_update', array( $this, 'trigger_update' ) );
		add_action( 'wp_ajax_notion_wp_sync_get_progress', array( $this, 'get_progress' ) );
		add_action( 'wp_ajax_notion_wp_sync_cancel_import', array( $this, 'cancel_import' ) );
	}

	/**
	 * Add metabox
	 */
	public function add_meta_box() {
		add_meta_box(
			'notionwpsync-import-infos',
			__( 'Actions', 'wp-sync-for-notion' ),
			array( $this, 'display' ),
			'nwpsync-connection',
			'side'
		);
	}

	/**
	 * Output metabox HTML
	 *
	 * @param WP_Post $post The connection.
	 */
	public function display( $post ) {
		$importer            = Notion_WP_Sync_Helpers::get_importer_by_id( $this->importers, $post->ID );
		$importer_id         = $importer ? $importer->infos()->get( 'id' ) : 0;
		$importer_is_running = $importer && $importer->get_run_id();
		$view                = include_once NOTION_WP_SYNC_PLUGIN_DIR . 'views/metabox-import-infos.php';
		$view( $importer, $importer_id, $importer_is_running, $this );
	}

	/**
	 * Manual sync AJAX function
	 *
	 * @throws \Exception No connection found.
	 * @throws \Exception Error from importer.
	 */
	public function trigger_update() {
		// Nonce check.
		check_ajax_referer( 'notion-wp-sync-trigger-update', 'nonce' );

		$importer_id = (int) $_POST['importer'] ?? 0;

		try {
			$importer = Notion_WP_Sync_Helpers::get_importer_by_id( $this->importers, $importer_id );
			if ( ! $importer ) {
				throw new Exception( 'No connection found.' );
			}

			// Get Notion records and add theme to queue.
			$result = $importer->run();
			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}

			// Unlock Action Scheduler, force queue to start now.
			delete_option( 'action_scheduler_lock_async-request-runner' );

			wp_send_json_success(
				array(
					'feedback' => __( 'In progress...', 'wp-sync-for-notion' ),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'infosHtml' => $this->get_stats_html( $importer_id, $e->getMessage() ),
					'feedback'  => __( 'Finished with errors.', 'wp-sync-for-notion' ),
				)
			);
		}
	}

	/**
	 * Get sync progress
	 *
	 * @throws \Exception No connection found.
	 */
	public function get_progress() {
		// Nonce check.
		check_ajax_referer( 'notion-wp-sync-trigger-update', 'nonce' );

		$importer_id = (int) $_POST['importer'] ?? 0;

		try {
			$importer = Notion_WP_Sync_Helpers::get_importer_by_id( $this->importers, $importer_id );
			if ( ! $importer ) {
				throw new Exception( 'No connection found.' );
			}

			if ( $importer->get_run_id() ) {
				// Get actions in current run.
				$actions_remaining = as_get_scheduled_actions(
					array(
						'hook'                  => 'notionwpsync_process_records',
						'partial_args_matching' => 'like',
						'status'                => array( \ActionScheduler_Store::STATUS_RUNNING, \ActionScheduler_Store::STATUS_PENDING ),
						'args'                  => array(
							'importer_id' => $importer_id,
							'run_id'      => $importer->get_run_id(),
						),
						'per_page'              => -1,
					)
				);
				$all_actions       = as_get_scheduled_actions(
					array(
						'hook'                  => 'notionwpsync_process_records',
						'partial_args_matching' => 'like',
						'status'                => array( \ActionScheduler_Store::STATUS_RUNNING, \ActionScheduler_Store::STATUS_PENDING, \ActionScheduler_Store::STATUS_COMPLETE, \ActionScheduler_Store::STATUS_CANCELED, \ActionScheduler_Store::STATUS_FAILED ),
						'args'                  => array(
							'importer_id' => $importer_id,
							'run_id'      => $importer->get_run_id(),
						),
						'per_page'              => -1,
					)
				);

				// Shouldn't happen but make sure we went through end_run() if no actions left.
				if ( count( $actions_remaining ) === 0 ) {
					$importer->delete_removed_posts();
					$importer->end_run( 'success' );
				}
			}

			if ( ! $importer->get_run_id() ) {
				wp_send_json_success(
					array(
						'infosHtml' => $this->get_stats_html( $importer_id ),
						'feedback'  => __( 'Finished!', 'wp-sync-for-notion' ),
					)
				);
			} else {
				$progress_percent = count( $actions_remaining ) / count( $all_actions );
				$progress         = number_format( $progress_percent * 100 ) . '%';

				wp_send_json_success(
					array(
						/* translators: %s percentage */
						'feedback' => sprintf( __( 'In progress... %s', 'wp-sync-for-notion' ), $progress ),
					)
				);
			}
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'infosHtml' => $this->get_stats_html( $importer_id, $e->getMessage() ),
					'feedback'  => __( 'Finished with errors.', 'wp-sync-for-notion' ),
				)
			);
		}
	}

	/**
	 * Cancel import
	 *
	 * @throws \Exception No connection found.
	 */
	public function cancel_import() {
		// Nonce check.
		check_ajax_referer( 'notion-wp-sync-trigger-update', 'nonce' );

		$importer_id = (int) $_POST['importer'] ?? 0;

		try {
			$importer = Notion_WP_Sync_Helpers::get_importer_by_id( $this->importers, $importer_id );
			if ( ! $importer ) {
				throw new Exception( 'No connection found.' );
			}

			$importer->end_run( 'cancel' );

			wp_send_json_success(
				array(
					'infosHtml' => $this->get_stats_html( $importer_id ),
					'feedback'  => __( 'Canceled.', 'wp-sync-for-notion' ),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'infosHtml' => $this->get_stats_html( $importer_id, $e->getMessage() ),
					'feedback'  => __( 'Could not cancel import.', 'wp-sync-for-notion' ),
				)
			);
		}
	}


	/**
	 * Get importer statistics html
	 *
	 * @param int         $importer_id Importer id.
	 * @param null|string $forced_error Error to be displayed.
	 *
	 * @return false|string
	 */
	protected function get_stats_html( $importer_id, $forced_error = null ) {
		ob_start();
		$status       = $forced_error ? 'error' : ( $importer_id ? get_post_meta( $importer_id, 'status', true ) : '' );
		$last_updated = $importer_id ? get_post_meta( $importer_id, 'last_updated', true ) : '';
		$next_sync    = $importer_id ? wp_next_scheduled( 'notion_wp_sync_importer_' . $importer_id ) : '';
		$last_error   = $forced_error ? $forced_error : ( $importer_id ? get_post_meta( $importer_id, 'last_error', true ) : '' );

		$status_class = '';
		if ( 'success' === $status ) {
			$status_class = 'dashicons-before dashicons-yes-alt';
		} elseif ( 'error' === $status ) {
			$status_class = 'dashicons-before dashicons-dismiss';
		} elseif ( 'cancel' === $status ) {
			$status_class = 'dashicons-before dashicons-warning';
		}
		$view = include NOTION_WP_SYNC_PLUGIN_DIR . 'views/metabox-side/infos.php';
		$view( $status, $last_error, $last_updated, $next_sync, $status_class );
		return ob_get_clean();
	}
}
