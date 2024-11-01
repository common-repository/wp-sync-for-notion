<?php
/**
 * Manages content import, registers custom post type and handles cron task.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

use DateTime, DateTimeZone, DateInterval;
use Exception, TypeError;
use WP_Error, WP_CLI;

/**
 * Notion_WP_Sync_Importer class.
 */
class Notion_WP_Sync_Importer {
	/**
	 * Import infos.
	 *
	 * @var Notion_WP_Sync_Importer_Settings
	 */
	public $infos;

	/**
	 * Saved config for the connection.
	 *
	 * @var Notion_WP_Sync_Importer_Settings
	 */
	public $config;

	/**
	 * Notion API Client.
	 *
	 * @var Notion_WP_Sync_Notion_Api_Client
	 */
	protected $api_client;

	/**
	 * Constructor.
	 *
	 * @param WP_Post $post The connection.
	 */
	public function __construct( $post ) {
		$this->load_settings( $post );
		$this->schedule_cron_event();
	}

	/**
	 * Infos getter.
	 */
	public function infos() {
		return $this->infos;
	}

	/**
	 * Config getter.
	 */
	public function config() {
		return $this->config;
	}

	/**
	 * Scheduled Sync next getter.
	 */
	public function get_next_scheduled_sync() {
		return wp_next_scheduled( $this->get_schedule_slug() );
	}

	/**
	 * Post Type getter.
	 */
	public function get_post_type() {
		return $this->config()->get( 'post_type' ) === 'custom' ? $this->config()->get( 'post_type_slug' ) : $this->config()->get( 'post_type' );
	}

	/**
	 * Fields getter.
	 */
	public function get_notion_fields() {
		return get_post_meta( $this->infos()->get( 'id' ), 'notion_fields', true );
	}

	/**
	 * Run ID getter
	 */
	public function get_run_id() {
		return get_post_meta( $this->infos()->get( 'id' ), 'run', true );
	}

	/**
	 * Cron action.
	 *
	 * @return boolean|WP_Error
	 */
	public function cron() {
		return $this->run();
	}

	/**
	 * Run importer.
	 *
	 * @throws Exception Another instance is already running; terminating.
	 * @return boolean|WP_Error
	 */
	public function run() {
		if ( $this->get_run_id() ) {
			return new WP_Error( 'notion-wp-sync-run-error', __( 'A sync is already running.', 'wp-sync-for-notion' ) );
		}

		try {
			// Define a unique id for this run.
			$run_id = uniqid();

			// Save run.
			update_post_meta( $this->infos()->get( 'id' ), 'run', $run_id );

			$this->log( sprintf( 'Starting importer...' ) );

			// Save table schema.
			update_post_meta( $this->infos()->get( 'id' ), 'notion_fields', $this->get_object_fields() );

			// Loop through all pages.
			$this->get_records( $run_id );

			return true;
		} catch ( Exception $e ) {
			$this->log( $e->getMessage() );
			$this->end_run( 'error', $e->getMessage() );
			return new WP_Error( 'notion-wp-sync-run-error', $e->getMessage() );
		} catch ( TypeError $e ) {
			$this->log( $e->getMessage() );
			$this->end_run( 'error', $e->getMessage() );
			return new WP_Error( 'notion-wp-sync-run-error', $e->getMessage() );
		}
	}

	/**
	 * Log message to file and WPCLI output.
	 *
	 * @param mixed  $message The message or an object to display in the logs.
	 * @param string $level The log level.
	 */
	public function log( $message, $level = 'log' ) {
		if ( ! is_dir( NOTION_WP_SYNC_LOGDIR ) ) {
			wp_mkdir_p( NOTION_WP_SYNC_LOGDIR );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$file = fopen( NOTION_WP_SYNC_LOGDIR . '/' . $this->infos()->get( 'slug' ) . '-' . gmdate( 'Y-m-d' ) . '-' . $this->get_run_id() . '.log', 'a' );
		if ( ! is_string( $message ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
				$message = var_export( $message, true );
			} else {
				$message = 'Notion_WP_Sync_Importer::log, the $message parameter is not a string, to debug the object turn on WP_DEBUG.';
			}
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fwrite( $file, "\n" . gmdate( 'Y-m-d H:i:s' ) . ' ' . $level . ' :: ' . $message );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $file );

		if ( class_exists( 'WP_CLI' ) ) {
			$method = method_exists( 'WP_CLI', $level ) ? $level : 'log';
			WP_CLI::$method( $message );
		}
	}

	/**
	 * Process a Notion record import.
	 *
	 * @param Notion_WP_Sync_Abstract_Model $record Record from Notion.
	 *
	 * @return int|WP_Error
	 */
	public function process_notion_record( $record ) {
		$current_user    = wp_get_current_user();
		$current_user_id = $current_user->ID;
		// Make sure we always work with the same user/capabilities, so we don't have diff. between import from admin and anonymous webhooks.
		wp_set_current_user( 0 );
		add_filter( 'user_has_cap', array( $this, 'allow_unfiltered_html_for_blocks' ) );
		kses_init();

		$this->log( sprintf( 'Record ID %s', $record->get_id() ) );

		$record = apply_filters( 'notionwpsync/importer/page', $record, $this );

		$return = 0;
		try {
			// Check if we have existing post for this record.
			$post_id = $this->get_object_id_from_record_id( $record->get_id(), $this->get_post_type() );
			if ( $post_id ) {
				$this->log( sprintf( '- Found matching post, ID %s', $post_id ) );
				// Check if we must update it.
				if ( 'add' !== $this->config()->get( 'sync_strategy' ) && $this->needs_update( $post_id, $record ) ) {
					$post_id = $this->import_record( $record, $post_id );
				} else {
					$this->log( sprintf( '- No update needed' ) );
				}
			} else {
				$post_id = $this->import_record( $record );
			}
			$return = $post_id;
		} catch ( Exception $e ) {
			$this->log( $e->getMessage() );
			$return = new WP_Error( 'notion-wp-sync-run-error', $e->getMessage() );
		} catch ( TypeError $e ) {
			$this->log( $e->getMessage() );
			$return = new WP_Error( 'notion-wp-sync-run-error', $e->getMessage() );
		}

		wp_set_current_user( $current_user_id );
		remove_filter( 'user_has_cap', array( $this, 'allow_unfiltered_html_for_blocks' ) );
		kses_init();

		return $return;
	}

	/**
	 * Get object fields from API
	 */
	protected function get_object_fields() {
		$objects_id = $this->config()->get( 'objects_id' );

		$object = null;
		if ( $this->config()->get( 'object_type' ) === 'page' ) {
			$object = $this->get_api_client()->get_page( $objects_id[0] );
		}

		$fields = array();
		if ( $object ) {
			$fields = $object->get_fields();
		}

		return apply_filters( 'notionwpsync/get_object_fields', $fields );
	}

	/**
	 * Get Notion records from API
	 *
	 * @param string $run_id Run id.
	 *
	 * @return void
	 */
	protected function get_records( $run_id ) {
		// Get records.
		$objects_id = $this->config()->get( 'objects_id' );
		$records    = $this->get_pages( $objects_id );
		if ( $this->config()->get( 'page_scope' ) === 'includes_children' ) {
			$records = $this->get_pages_children( $records, $records );
		}

		// Loop through all records.
		$chunks = array_chunk( $records, 10 );
		foreach ( $chunks as $chunk ) {
			// Save Notion record as a temporary option.
			$item_id = uniqid( 'notionwpsync-' . $this->infos()->get( 'id' ) . '-run-' . $this->get_run_id() . '-item-' );
			update_option( $item_id, $chunk );
			// Add it to queue.
			as_enqueue_async_action(
				'notionwpsync_process_records',
				array(
					'importer_id' => $this->infos()->get( 'id' ),
					'run_id'      => $run_id,
					'item_id'     => $item_id,
				)
			);
		}
	}

	/**
	 * Returns pages object from pages id.
	 *
	 * @param string[] $pages_id The pages id.
	 *
	 * @return array
	 */
	protected function get_pages( $pages_id ) {
		$importer = $this;
		$pages    = array_map(
			function ( $page_id ) use ( $importer ) {
				$page = $this->get_api_client()->get_page( $page_id );
				return apply_filters( 'notionwpsync/importer/page', $page, $importer );
			},
			$pages_id
		);
		return $pages;
	}

	/**
	 * Get pages children recursively.
	 *
	 * @param Notion_WP_Sync_Page_Model[] $pages A list of pages.
	 * @param Notion_WP_Sync_Page_Model[] $result A list of pages with their children.
	 *
	 * @return array|mixed
	 */
	protected function get_pages_children( $pages, $result = array() ) {
		foreach ( $pages as $page ) {
			$blocks_field = $page->get_field( '__notionwpsync_blocks' );
			if ( $blocks_field ) {
				$children_pages_id = Notion_WP_Sync_Blocks_Parser::get_instance()->get_page_children_id( $blocks_field->get_raw_value() );
				if ( count( $children_pages_id ) > 0 ) {
					$children = $this->get_pages( $children_pages_id );
					$result   = array_merge( $result, $children );
					$result   = $this->get_pages_children( $children, $result );
				}
			}
		}
		return $result;
	}

	/**
	 * Delete other posts existing in WP but deleted in Notion.
	 */
	public function delete_removed_posts() {
		if ( 'add_update_delete' !== $this->config()->get( 'sync_strategy' ) ) {
			return;
		}

		$post_ids = get_post_meta( $this->infos()->get( 'id' ), 'post_ids', true );
		if ( ! is_array( $post_ids ) ) {
			$post_ids = array();
		}

		$posts = get_posts(
			array(
				'post_type'      => $this->get_post_type(),
				'post_status'    => 'any',
				'post__not_in'   => $post_ids,
				'fields'         => 'ids',
				'posts_per_page' => -1,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => array(
					array(
						'key'   => '_notion_wp_sync_importer_id',
						'value' => $this->infos()->get( 'id' ),
					),
					array(
						'key'     => '_notion_wp_sync_record_id',
						'compare' => 'EXISTS',
					),
				),
			)
		);
		foreach ( $posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}


	/**
	 * End run
	 *
	 * @param string      $status Status.
	 * @param null|string $error Error message.
	 */
	public function end_run( $status = 'success', $error = null ) {
		global $wpdb;
		$importer_id = $this->infos()->get( 'id' );
		$run_id      = $this->get_run_id();

		// Delete any remaining AS actions.
		$action_ids = \ActionScheduler::store()->query_actions(
			array(
				'hook'                  => 'notionwpsync_process_records',
				'status'                => \ActionScheduler_Store::STATUS_PENDING,
				'partial_args_matching' => 'like',
				'args'                  => array(
					'importer_id' => $importer_id,
					'run_id'      => $run_id,
				),
				'per_page'              => -1,
			)
		);
		foreach ( $action_ids as $action_id ) {
			\ActionScheduler::store()->cancel_action( $action_id );
		}

		// Delete temporary options.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( sprintf( 'notionwpsync-%s-run-%s-', $importer_id, $run_id ) ) . '%'
			)
		);

		// Remove temporary metas.
		update_post_meta( $importer_id, 'post_ids', null );
		update_post_meta( $importer_id, 'run', null );
		update_post_meta( $importer_id, 'notion_fields', null );

		// Update status and error.
		update_post_meta( $importer_id, 'status', $status );
		update_post_meta( $importer_id, 'last_error', $error );

		// Save date if success.
		if ( 'success' === $status ) {
			update_post_meta( $importer_id, 'last_updated', gmdate( 'Y-m-d H:i:s' ) );
		}
	}

	/**
	 * Load importer settings from post object
	 *
	 * @param WP_Post $post The connection.
	 */
	protected function load_settings( $post ) {
		$infos = array(
			'id'       => $post->ID,
			'slug'     => $post->post_name,
			'title'    => $post->post_title,
			'modified' => $post->post_modified_gmt,
			'hash'     => wp_hash( $post->ID ),
		);

		$this->infos = new Notion_WP_Sync_Importer_Settings( $infos );

		$config       = json_decode( $post->post_content, true );
		$this->config = new Notion_WP_Sync_Importer_Settings( $config );
	}

	/**
	 * Get cron schedule slug
	 */
	protected function get_schedule_slug() {
		return 'notion_wp_sync_importer_' . $this->infos()->get( 'id' );
	}

	/**
	 * Init cron events
	 */
	protected function schedule_cron_event() {
		if ( 'cron' === $this->config()->get( 'scheduled_sync.type' ) && $this->config()->get( 'scheduled_sync.recurrence' ) ) {
			add_action( $this->get_schedule_slug(), array( $this, 'cron' ) );
			if ( false === $this->get_next_scheduled_sync() ) {
				$recurrence = $this->config()->get( 'scheduled_sync.recurrence' );
				if ( ! in_array( $recurrence, array( 'weekly', 'daily' ), true ) ) {
					return;
				}
				wp_schedule_event( $this->get_schedule_timestamp(), $recurrence, $this->get_schedule_slug() );
			}
		} elseif ( $this->get_next_scheduled_sync() ) {
				wp_clear_scheduled_hook( $this->get_schedule_slug() );
		}
	}

	/**
	 * Get Schedule timestamp
	 */
	protected function get_schedule_timestamp() {
		$datetime   = new DateTime( 'now', new DateTimeZone( wp_timezone_string() ) );
		$recurrence = $this->config()->get( 'scheduled_sync.recurrence' );
		if ( 'weekly' === $recurrence ) {
			if ( $this->config()->get( 'scheduled_sync.weekday' ) ) {
				$datetime->modify( 'next ' . $this->config()->get( 'scheduled_sync.weekday' ) );
			}
		}
		if ( in_array( $recurrence, array( 'weekly', 'daily' ), true ) ) {
			if ( $this->config()->get( 'scheduled_sync.time' ) ) {
				$time = explode( ':', $this->config()->get( 'scheduled_sync.time' ) );
				$datetime->setTime( $time[0], $time[1] );
			}
		} else {
			$schedules = wp_get_schedules();
			$interval  = isset( $schedules[ $recurrence ] ) ? $schedules[ $recurrence ]['interval'] : HOUR_IN_SECONDS;
			$datetime->add( new DateInterval( 'PT' . $interval . 'S' ) );
		}
		return $datetime->getTimestamp();
	}

	/**
	 * Get or instantiate Notion API client
	 */
	public function get_api_client() {
		if ( null === $this->api_client ) {
			$this->api_client = new Notion_WP_Sync_Notion_Api_Client( $this->config()->get( 'api_key' ) );
		}
		return $this->api_client;
	}

	/**
	 * Import Notion record.
	 *
	 * @param Notion_WP_Sync_Abstract_Model $record The Notion object to import.
	 * @param int|null                      $post_id The id to the post to update if any.
	 *
	 * @return int
	 * @throws Exception An exception triggered by wp_insert_post or wp_update_post.
	 */
	protected function import_record( $record, $post_id = null ) {
		$this->log( sprintf( $post_id ? '- Update record %s' : '- Create record %s', $record->get_id() ) );

		$record = apply_filters( 'notionwpsync/import_record_data', $record, $this );

		// ... omit keys for empty fields, lets add them with an empty string
		$mapping     = ! empty( $this->config()->get( 'mapping' ) ) ? $this->config()->get( 'mapping' ) : array();
		$notion_keys = array_map(
			function ( $mapping_pair ) {
				return $mapping_pair['notion'];
			},
			$mapping
		);

		$fields = array();
		foreach ( $notion_keys as $notion_key ) {
			$field                 = $record->get_field( $notion_key );
			$fields[ $notion_key ] = $field;
		}

		$fields = apply_filters( 'notionwpsync/import_record_fields', $fields, $this );

		$post_data = array(
			'post_type'   => $this->get_post_type(),
			'post_author' => $this->config()->get( 'post_author' ),
			'post_status' => $this->config()->get( 'post_status' ),
			'post_title'  => _x( 'Notion Imported Content', 'default imported post title', 'wp-sync-for-notion' ),
		);

		$post_metas = array(
			'_notion_wp_sync_record_id'   => $record->get_id(),
			'_notion_wp_sync_hash'        => Notion_WP_Sync_Helpers::generate_hash( $record, $this->config()->to_array() ),
			'_notion_wp_sync_importer_id' => $this->infos()->get( 'id' ),
			'_notion_wp_sync_updated_at'  => gmdate( 'Y-m-d H:i:s' ),
		);

		$post_data = apply_filters( 'notionwpsync/import_post_data', $post_data, $this, $fields, $record, $post_id );

		$post_data = array_map(
			function ( $value ) {
				return is_string( $value ) ? wp_encode_emoji( $value ) : $value;
			},
			$post_data
		);

		// Insert or update post.
		add_filter( 'wp_insert_post_empty_content', '__return_false' );

		if ( $post_id ) {
			$post_id = wp_update_post( array_merge( array( 'ID' => $post_id ), $post_data ), true );
		} else {
			$post_id = wp_insert_post( $post_data, true );
		}

		remove_filter( 'wp_insert_post_empty_content', '__return_false' );

		if ( is_wp_error( $post_id ) ) {
			$error_data = $post_id->get_error_data();
			if ( ! empty( $error_data ) ) {
				$this->log( $error_data, 'error' );
			}
			throw new Exception( esc_html( $post_id->get_error_message() ) );
		}

		// Handle metas.
		foreach ( $post_metas as $meta_key => $meta_value ) {
			update_post_meta( $post_id, $meta_key, $meta_value );
		}

		do_action( 'notionwpsync/import_record_after', $this, $fields, $record, $post_id );

		// Force wp_insert_post re-trigger after metas.
		do_action( 'wp_insert_post', $post_id, get_post( $post_id ), true );

		return $post_id;
	}

	/**
	 * Get WP object id from Notion record id.
	 *
	 * @param string $record_id The Notion object id.
	 * @param string $post_type The post type where the record should have been imported to.
	 *
	 * @return WP_Post|null
	 */
	public function get_object_id_from_record_id( $record_id, $post_type ) {
		$objects = get_posts(
			array(
				'fields'      => 'ids',
				'post_type'   => $post_type,
				'post_status' => 'any',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'  => array(
					array(
						'key'   => '_notion_wp_sync_importer_id',
						'value' => $this->infos()->get( 'id' ),
					),
					array(
						'key'   => '_notion_wp_sync_record_id',
						'value' => $record_id,
					),
				),
			)
		);
		return array_shift( $objects );
	}

	/**
	 * Compare hashes to check if WP object needs update.
	 *
	 * @param int                           $post_id The destination post id.
	 * @param Notion_WP_Sync_Abstract_Model $record The Notion object to import.
	 *
	 * @return bool
	 */
	protected function needs_update( $post_id, $record ) {
		if ( defined( 'NOTION_WP_SYNC_FORCE_UPDATES' ) && NOTION_WP_SYNC_FORCE_UPDATES ) {
			return true;
		}
		return Notion_WP_Sync_Helpers::generate_hash( $record, $this->config()->to_array() ) !== $this->get_post_hash( $post_id );
	}

	/**
	 * Get stored post hash.
	 *
	 * @param int $post_id The post id.
	 *
	 * @return mixed
	 */
	protected function get_post_hash( $post_id ) {
		return get_post_meta( $post_id, '_notion_wp_sync_hash', true );
	}

	/**
	 * Add "unfiltered_html" cap to the current user.
	 *
	 * @param boolean[] $caps User capabilities.
	 *
	 * @return array
	 */
	public function allow_unfiltered_html_for_blocks( $caps ) {
		$caps['unfiltered_html'] = true;
		return $caps;
	}
}
