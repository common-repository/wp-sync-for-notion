<?php
/**
 * Main entry point for the plugin, initialize all features.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

use WP_CLI;

/**
 * Notion_WP_Sync class.
 */
class Notion_WP_Sync {
	/**
	 * List of available importers.
	 *
	 * @var Notion_WP_Sync_Importer[]
	 */
	protected $importers = array();

	/**
	 * Plugin settings.
	 *
	 * @var Notion_WP_Sync_Options
	 */
	protected $options;

	/**
	 * Constructor
	 */
	public function __construct() {
		// phpcs:disable WordPress.WP.CronInterval.CronSchedulesInterval
		add_action( 'init', array( $this, 'init' ), 100 );
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedules' ), 100 );
		add_action( 'activated_plugin', array( $this, 'deactivate_other_instances' ) );
		add_action( 'pre_current_active_plugins', array( $this, 'plugin_deactivated_notice' ) );
		add_filter( 'load_textdomain_mofile', array( $this, 'load_textdomain_mofile' ), 10, 2 );

		// Support for Yoast Duplicate Post.
		// Once a connection is duplicated remove connection metas.
		add_action( 'duplicate_post_post_copy', array( $this, 'clean_up_after_duplicate' ), 10, 1 );

		// phpcs:enable
	}

	/**
	 * Init plugin.
	 */
	public function init() {
		$this->load_textdomain();
		$this->setup();
		$this->load_importers();

		$this->options = new Notion_WP_Sync_Options();

		// Admin.
		if ( is_admin() ) {
			new Notion_WP_Sync_Admin( $this->importers );
		}

		// Initalize WP_CLI only in cli mode.
		if ( class_exists( 'WP_CLI' ) ) {
			WP_CLI::add_command( 'wp-sync-for-notion', new Notion_WP_Sync_CLI( $this->importers ) );
		}

		new Notion_WP_Sync_Action_Consumer( $this->importers );

		// Init API.
		new Notion_WP_Sync_Api_Import_Route( $this->importers );

		// Init Destinations with Formatters.
		new Notion_WP_Sync_Post_Destination();
		new Notion_WP_Sync_Meta_Destination();
		new Notion_WP_Sync_Taxonomy_Destination( new Notion_WP_Sync_Terms_Formatter() );

		// Init Notion page properties.
		Notion_WP_Sync_Notion_Page::init();
	}

	/**
	 * Checks if another version of WP Sync for Notion or Notion WP Sync pro is active and deactivates it.
	 * Hooked on `activated_plugin` so other plugin is deactivated when current plugin is activated.
	 *
	 * @param string $plugin The plugin being activated.
	 */
	public function deactivate_other_instances( $plugin ) {
		if ( ! in_array( $plugin, array( 'notion-wp-sync/notion-wp-sync.php', 'notion-wp-sync-pro/notion-wp-sync.php', 'notion-wp-sync-pro-plus/notion-wp-sync.php' ), true ) ) {
			return;
		}

		if ( is_multisite() && is_network_admin() ) {
			$active_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
			$active_plugins = array_keys( $active_plugins );
		} else {
			$active_plugins = (array) get_option( 'active_plugins', array() );
		}

		$plugin_to_deactivate  = 'notion-wp-sync/notion-wp-sync.php';
		$deactivated_notice_id = '1';

		// If we just activated the free version, deactivate the pro version.
		if ( $plugin === $plugin_to_deactivate ) {
			$plugin_to_deactivate  = 'notion-wp-sync-pro/notion-wp-sync.php';
			$deactivated_notice_id = '2';
			if ( in_array( 'notion-wp-sync-pro-plus/notion-wp-sync.php', $active_plugins, true ) ) {
				$plugin_to_deactivate = 'notion-wp-sync-pro-plus/notion-wp-sync.php';
			}
		}

		foreach ( $active_plugins as $plugin_basename ) {
			if ( $plugin_to_deactivate === $plugin_basename ) {
				set_transient( 'notionwpsync_deactivated_notice_id', $deactivated_notice_id, 1 * HOUR_IN_SECONDS );
				deactivate_plugins( $plugin_basename );
				return;
			}
		}
	}

	/**
	 * Displays a notice when either WP Sync for Notion or Notion WP Sync PRO is automatically deactivated.
	 */
	public function plugin_deactivated_notice() {
		$deactivated_notice_id = (int) get_transient( 'notionwpsync_deactivated_notice_id' );
		if ( ! in_array( $deactivated_notice_id, array( 1, 2 ), true ) ) {
			return;
		}

		$message = __( "WP Sync for Notion and Notion WP Sync Pro should not be active at the same time. We've automatically deactivated WP Sync for Notion.", 'wp-sync-for-notion' );
		if ( 2 === $deactivated_notice_id ) {
			$message = __( "WP Sync for Notion and Notion WP Sync Pro should not be active at the same time. We've automatically deactivated Notion WP Sync Pro.", 'wp-sync-for-notion' );
		}

		?>
		<div class="notice notice-warning">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php

		delete_transient( 'notionwpsync_deactivated_notice_id' );
	}

	/**
	 * Load translations
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'wp-sync-for-notion', false, dirname( NOTION_WP_SYNC_BASENAME ) . '/languages' );
	}

	/**
	 * Use our own translation files for pro version
	 *
	 * @param string $mofile Path to the MO file.
	 * @param string $domain Text domain. Unique identifier for retrieving translated strings.
	 */
	public function load_textdomain_mofile( $mofile, $domain ) {
		if ( 'wp-sync-for-notion' === $domain && false !== strpos( $mofile, WP_LANG_DIR . '/plugins/' ) ) {
			$locale = apply_filters( 'plugin_locale', determine_locale(), $domain );
			$mofile = WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/languages/' . $domain . '-' . $locale . '.mo';
		}
		return $mofile;
	}

	/**
	 * Add custom cron schedules
	 *
	 * @param array[] $schedules An array of non-default cron schedule arrays. Default empty.
	 */
	public function add_cron_schedules( $schedules ) {
		return array_merge(
			$schedules,
			array(
				'notionwpsync_fiveminutes'   => array(
					'interval' => 300,
					'display'  => __( 'Every 5 minutes', 'wp-sync-for-notion' ),
				),
				'notionwpsync_tenminutes'    => array(
					'interval' => 600,
					'display'  => __( 'Every 10 minutes', 'wp-sync-for-notion' ),
				),
				'notionwpsync_thirtyminutes' => array(
					'interval' => 1800,
					'display'  => __( 'Every 30 minutes', 'wp-sync-for-notion' ),
				),
			)
		);
	}

	/**
	 * Register Connection post type and fields.
	 */
	protected function setup() {
		register_post_type(
			'nwpsync-connection',
			array(
				'labels'          => array(
					'name'               => __( 'Connections', 'wp-sync-for-notion' ),
					'singular_name'      => __( 'Connection', 'wp-sync-for-notion' ),
					'add_new'            => __( 'Add New', 'wp-sync-for-notion' ),
					'add_new_item'       => __( 'Add New Connection', 'wp-sync-for-notion' ),
					'edit_item'          => __( 'Edit Connection', 'wp-sync-for-notion' ),
					'new_item'           => __( 'New Connection', 'wp-sync-for-notion' ),
					'view_item'          => __( 'View Connection', 'wp-sync-for-notion' ),
					'search_items'       => __( 'Search Connections', 'wp-sync-for-notion' ),
					'not_found'          => __( 'No Connections found', 'wp-sync-for-notion' ),
					'not_found_in_trash' => __( 'No Connections found in Trash', 'wp-sync-for-notion' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'_builtin'        => false,
				'capability_type' => 'post',
				'supports'        => array( 'title' ),
				'rewrite'         => false,
				'query_var'       => false,
			)
		);

		Notion_WP_Sync_Field_Factory::register_fields(
			array(
				Notion_WP_Sync_Generic_Multi_Text_Field::class,
				Notion_WP_Sync_Generic_Text_Field::class,
				Notion_WP_Sync_Title_Field::class,
				Notion_WP_Sync_Blocks_Field::class,
				Notion_WP_Sync_Files_Field::class,
				Notion_WP_Sync_Rich_Text_Field::class,
				Notion_WP_Sync_Date_Field::class,
				Notion_WP_Sync_Select_Field::class,
				Notion_WP_Sync_Multi_Select_Field::class,
				Notion_WP_Sync_Number_Field::class,
				Notion_WP_Sync_Status_Field::class,
				Notion_WP_Sync_Checkbox_Field::class,
				Notion_WP_Sync_URL_Field::class,
				Notion_WP_Sync_Email_Field::class,
				Notion_WP_Sync_Phone_Number_Field::class,
				Notion_WP_Sync_People_Field::class,
			)
		);
	}

	/**
	 * Load available importers.
	 */
	protected function load_importers() {
		$importer_posts = get_posts(
			array(
				'post_type'      => 'nwpsync-connection',
				'post_status'    => array( 'publish' ),
				'posts_per_page' => -1,
			)
		);

		foreach ( $importer_posts as $importer_post ) {
			$this->importers[] = new Notion_WP_Sync_Importer( $importer_post );
		}
	}

	/**
	 * Clean up importer metas after a plugin has duplicated the connection.
	 *
	 * @param int|\WP_Error $new_post_id The new post id or WP_Error object on error.
	 *
	 * @return void
	 */
	public function clean_up_after_duplicate( $new_post_id ) {
		if ( ! is_integer( $new_post_id ) ) {
			return;
		}

		if ( 'ntwpsync-connection' !== get_post_type( $new_post_id ) ) {
			return;
		}

		update_post_meta( $new_post_id, 'content_ids', null );
		update_post_meta( $new_post_id, 'post_ids', null ); // backward compatibility.
		update_post_meta( $new_post_id, 'run', null );
		update_post_meta( $new_post_id, 'notion_fields', null );

		delete_post_meta( $new_post_id, 'status' );
		delete_post_meta( $new_post_id, 'last_error' );
		delete_post_meta( $new_post_id, 'last_updated' );
	}
}
