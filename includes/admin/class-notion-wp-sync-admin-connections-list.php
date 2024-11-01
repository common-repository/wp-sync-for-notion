<?php
/**
 * Manages admin connections list page.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Admin_Connections_List class.
 */
class Notion_WP_Sync_Admin_Connections_List {
	/**
	 * List of available importers
	 *
	 * @var Notion_WP_Sync_Importer[]
	 */
	protected $importers = array();

	/**
	 * Constructor
	 *
	 * @param Notion_WP_Sync_Importer[] $importers Importers.
	 */
	public function __construct( $importers ) {
		$this->importers = $importers;

		add_filter( 'manage_nwpsync-connection_posts_columns', array( $this, 'admin_table_columns' ), 10, 1 );
		add_action( 'manage_nwpsync-connection_posts_custom_column', array( $this, 'admin_table_columns_html' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'connection_row_actions' ), 10, 2 );
	}

	/**
	 * Customizes the admin table columns.
	 *
	 * @param string[] $post_columns An associative array of column headings.
	 */
	public function admin_table_columns( $post_columns ) {
		$columns = array(
			'cb'            => $post_columns['cb'],
			'title'         => $post_columns['title'],
			'modified-date' => __( 'Last Modified On', 'wp-sync-for-notion' ),
			'post-type'     => __( 'Post Type', 'wp-sync-for-notion' ),
			'sync-date'     => __( 'Last Synced On', 'wp-sync-for-notion' ),
			'sync-trigger'  => __( 'Trigger', 'wp-sync-for-notion' ),
		);
		return $columns;
	}

	/**
	 * Renders the admin table column HTML
	 *
	 * @param string $column_name The name of the column to display.
	 * @param int    $post_id The current post ID.
	 * @return void
	 */
	public function admin_table_columns_html( $column_name, $post_id ) {
		$importer = Notion_WP_Sync_Helpers::get_importer_by_id( $this->importers, $post_id );
		if ( ! $importer ) {
			return;
		}
		switch ( $column_name ) {
			case 'post-type':
				$post_type        = $importer->get_post_type();
				$post_type_object = get_post_type_object( $post_type );
				if ( $post_type_object ) {
					$menu_icon = $post_type_object->menu_icon ? $post_type_object->menu_icon : 'dashicons-admin-post';
					echo '<span class="dashicons ' . esc_attr( $menu_icon ) . '"></span> ';
					echo esc_html( $post_type_object->labels->name );
				}
				break;
			case 'sync-trigger':
				if ( 'manual' === $importer->config()->get( 'scheduled_sync.type' ) ) {
					esc_html_e( 'Manual only', 'wp-sync-for-notion' );
				}
				if ( 'cron' === $importer->config()->get( 'scheduled_sync.type' ) ) {
					esc_html_e( 'Recurring', 'wp-sync-for-notion' );
					if ( $importer->config()->get( 'scheduled_sync.recurrence' ) ) {
						$schedules = wp_get_schedules();
						foreach ( $schedules as $slug => $schedule ) {
							if ( $slug === $importer->config()->get( 'scheduled_sync.recurrence' ) ) {
								echo esc_html( ' (' . $schedule['display'] . ')' );
							}
						}
					}
				}
				if ( 'instant' === $importer->config()->get( 'scheduled_sync.type' ) ) {
					esc_html_e( 'Instant', 'wp-sync-for-notion' );
				}
				break;
			case 'sync-date':
				$last_updated = get_post_meta( $post_id, 'last_updated', true );
				echo esc_html( $last_updated ? Notion_WP_Sync_Helpers::get_formatted_date_time( $last_updated ) : '--' );
				break;
			case 'modified-date':
				$last_modified = $importer->infos()->get( 'modified' );
				echo esc_html( $last_modified ? Notion_WP_Sync_Helpers::get_formatted_date_time( $last_modified ) : '--' );
				break;
		}
	}

	/**
	 * Generates row action links
	 *
	 * @param array   $actions Row actions output for post.
	 * @param WP_Post $post The current post ID.
	 * @return array
	 */
	public function connection_row_actions( $actions, $post ) {
		if ( 'nwpsync-connection' === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );

			if ( isset( $actions['trash'] ) ) {
				$importer = Notion_WP_Sync_Helpers::get_importer_by_id( $this->importers, $post->ID );
				if ( $importer && $importer->config()->get( 'post_type' ) === 'custom' ) {
					$title            = _draft_or_post_title();
					$actions['trash'] = sprintf(
						'<a href="%s" class="submitdelete" aria-label="%s" onclick="return confirm(\'%s\')">%s</a>',
						get_delete_post_link( $post->ID ),
						/* translators: %s: Post title. */
						esc_attr( sprintf( __( 'Move &#8220;%s&#8221; to the Trash' ), $title ) ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
						__( 'You have a Custom Post Type declared using this connection. Are you sure to delete it?', 'wp-sync-for-notion' ),
						_x( 'Trash', 'verb' )  // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
					);
				}
			}
		}
		return $actions;
	}
}
