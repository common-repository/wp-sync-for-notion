<?php
/**
 * Manages admin pages registration.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/admin/class-notion-wp-sync-admin-connections-list.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/admin/class-notion-wp-sync-admin-connection.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/admin/metaboxes/class-notion-wp-sync-metabox-field-mapping.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/admin/metaboxes/class-notion-wp-sync-metabox-global-settings.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/admin/metaboxes/class-notion-wp-sync-metabox-post-settings.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/admin/metaboxes/class-notion-wp-sync-metabox-sync-settings.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/admin/metaboxes/class-notion-wp-sync-metabox-import-infos.php';

/**
 * Admin
 */
class Notion_WP_Sync_Admin {
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

		add_action( 'admin_menu', array( $this, 'add_menu' ), 9 );
		add_action( 'in_admin_header', array( $this, 'in_admin_header' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_styles_scripts' ) );

		add_filter( 'plugin_action_links_' . NOTION_WP_SYNC_BASENAME, array( $this, 'plugin_action_links' ) );

		new Notion_WP_Sync_Admin_Connections_List( $importers );
		new Notion_WP_Sync_Admin_Connection( $importers );
	}

	/**
	 * Add menu
	 */
	public function add_menu() {
		add_menu_page(
			__( 'WP Sync for Notion', 'wp-sync-for-notion' ),
			__( 'WP Sync for Notion', 'wp-sync-for-notion' ),
			apply_filters( 'notionwpsync/manage_options_capability', 'manage_options' ),
			'edit.php?post_type=nwpsync-connection',
			false,
			'data:image/svg+xml;base64,PHN2ZyBoZWlnaHQ9IjI1MDAiIHdpZHRoPSIyNTAwIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjEyIDAuMTkgNDg3LjYxOSA1MTAuOTQxIj48cGF0aCBkPSJNOTYuMDg1IDkxLjExOGMxNS44MSAxMi44NDUgMjEuNzQxIDExLjg2NSA1MS40MyA5Ljg4NGwyNzkuODg4LTE2LjgwNmM1LjkzNiAwIDEtNS45MjItLjk4LTYuOTA2TDM3OS45NCA0My42ODZjLTguOTA3LTYuOTE1LTIwLjc3My0xNC44MzQtNDMuNTE2LTEyLjg1M0w2NS40MDggNTAuNmMtOS44ODQuOTgtMTEuODU4IDUuOTIyLTcuOTIyIDkuODgzem0xNi44MDQgNjUuMjI4djI5NC40OTFjMCAxNS44MjcgNy45MDkgMjEuNzQ4IDI1LjcxIDIwLjc2OWwzMDcuNTk3LTE3Ljc5OWMxNy44MS0uOTc5IDE5Ljc5NC0xMS44NjUgMTkuNzk0LTI0LjcyMlYxMzYuNTdjMC0xMi44MzYtNC45MzgtMTkuNzU4LTE1Ljg0LTE4Ljc3bC0zMjEuNDQyIDE4Ljc3Yy0xMS44NjMuOTk3LTE1LjgyIDYuOTMxLTE1LjgyIDE5Ljc3NnptMzAzLjY1OSAxNS43OTdjMS45NzIgOC45MDMgMCAxNy43OTgtOC45MiAxOC43OTlsLTE0LjgyIDIuOTUzdjIxNy40MTJjLTEyLjg2OCA2LjkxNi0yNC43MzQgMTAuODctMzQuNjIyIDEwLjg3LTE1LjgzMSAwLTE5Ljc5Ni00Ljk0NS0zMS42NTQtMTkuNzZsLTk2Ljk0NC0xNTIuMTl2MTQ3LjI0OGwzMC42NzcgNi45MjJzMCAxNy43OC0yNC43NSAxNy43OGwtNjguMjMgMy45NThjLTEuOTgyLTMuOTU4IDAtMTMuODMyIDYuOTIxLTE1LjgxbDE3LjgwNS00LjkzNVYyMTAuN2wtMjQuNzIxLTEuOTgxYy0xLjk4My04LjkwMyAyLjk1NS0yMS43NCAxNi44MTItMjIuNzM2bDczLjE5NS00LjkzNEwzNTguMTg2IDMzNS4yMlYxOTguODM2bC0yNS43MjMtMi45NTJjLTEuOTc0LTEwLjg4NCA1LjkyNy0xOC43ODcgMTUuODE5LTE5Ljc2N3pNNDIuNjUzIDIzLjkxOWwyODEuOS0yMC43NmMzNC42MTgtMi45NjkgNDMuNTI1LS45OCA2NS4yODMgMTQuODI1bDg5Ljk4NiA2My4yNDdjMTQuODQ4IDEwLjg3NiAxOS43OTcgMTMuODM3IDE5Ljc5NyAyNS42OTN2MzQ2Ljg4M2MwIDIxLjc0LTcuOTIgMzQuNTk3LTM1LjYwOCAzNi41NjRMMTM2LjY0IDUxMC4xNGMtMjAuNzg1Ljk5MS0zMC42NzctMS45NzEtNDEuNTYyLTE1LjgxNWwtNjYuMjY3LTg1Ljk3OEMxNi45MzggMzkyLjUyIDEyIDM4MC42OCAxMiAzNjYuODI4VjU4LjQ5NWMwLTE3Ljc3OCA3LjkyMi0zMi42MDggMzAuNjUzLTM0LjU3NnoiIGZpbGwtcnVsZT0iZXZlbm9kZCIgZmlsbD0iI2E3YWFhZCIvPjwvc3ZnPg=='
		);
		add_submenu_page(
			'edit.php?post_type=nwpsync-connection',
			__( 'All Connections', 'wp-sync-for-notion' ),
			__( 'All Connections', 'wp-sync-for-notion' ),
			apply_filters( 'notionwpsync/manage_options_capability', 'manage_options' ),
			'edit.php?post_type=nwpsync-connection'
		);
		add_submenu_page(
			'edit.php?post_type=nwpsync-connection',
			__( 'Add New', 'wp-sync-for-notion' ),
			__( 'Add New', 'wp-sync-for-notion' ),
			apply_filters( 'notionwpsync/manage_options_capability', 'manage_options' ),
			'post-new.php?post_type=nwpsync-connection'
		);
	}

	/**
	 * Display plugin header
	 */
	public function in_admin_header() {
		$screen = get_current_screen();
		if ( 'nwpsync-connection' === $screen->post_type ) {
			$view = include NOTION_WP_SYNC_PLUGIN_DIR . 'views/header.php';
			$view();
		}
	}

	/**
	 * Register admin styles and scripts
	 */
	public function register_styles_scripts() {
		/**
		 * TODO: load only on our pages.
		 */
		wp_enqueue_style( 'notion-wp-sync-admin-select2', plugins_url( 'assets/css/select2.min.css', NOTION_WP_SYNC_PLUGIN_FILE ), false, NOTION_WP_SYNC_VERSION );
		wp_enqueue_style( 'notion-wp-sync-admin', plugins_url( 'assets/css/admin-page.css', NOTION_WP_SYNC_PLUGIN_FILE ), array( 'notion-wp-sync-admin-select2' ), NOTION_WP_SYNC_VERSION );
	}

	/**
	 * Show action links on the plugin screen
	 *
	 * @param string[] $actions     An array of plugin action links. By default this can include
	 *                              'activate', 'deactivate', and 'delete'. With Multisite active
	 *                              this can also include 'network_active' and 'network_only' items.
	 *
	 * @return mixed
	 */
	public function plugin_action_links( $actions ) {
		return array_merge(
			$actions,
			array(
				'upgrade' => '<a href="https://wpconnect.co/notion-wordpress-integration/#pricing-plan" target="_blank"><b>' . esc_html__( 'Upgrade to Pro Version', 'wp-sync-for-notion' ) . '</b></a>',
			)
		);
	}
}
