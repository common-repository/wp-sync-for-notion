<?php
/**
 * Plugin Name: Notion to WordPress - WP Sync for Notion
 * Plugin URI: https://wpconnect.co/notion-wp-sync-plugin/
 * Description: Swiftly sync Notion to your WordPress website!
 * Version: 1.5.0
 * Requires at least: 5.7
 * Tested up to: 6.5
 * Requires PHP: 7.0
 * Author: WP connect
 * Author URI: https://wpconnect.co/
 * License: GPLv2 or later License
 * Text Domain: wp-sync-for-notion
 * Domain Path: /languages/
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'NOTION_WP_SYNC_VERSION', '1.5.0' );
define( 'NOTION_WP_SYNC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NOTION_WP_SYNC_PLUGIN_FILE', __FILE__ );
define( 'NOTION_WP_SYNC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NOTION_WP_SYNC_BASENAME', plugin_basename( __FILE__ ) );
define( 'NOTION_WP_SYNC_LOGDIR', wp_upload_dir( null, false )['basedir'] . '/notionwpsync-logs/' );

require_once NOTION_WP_SYNC_PLUGIN_DIR . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/class-notion-wp-sync.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/class-notion-wp-sync-blocks-parser.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/class-notion-wp-sync-rich-text-parser.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/class-notion-wp-sync-attachments-manager.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/class-notion-wp-sync-abstract-settings.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/class-notion-wp-sync-options.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/class-notion-wp-sync-action-consumer.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/class-notion-wp-sync-importer.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/class-notion-wp-sync-importer-settings.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/class-notion-wp-sync-helpers.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/admin/class-notion-wp-sync-admin.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/class-notion-wp-sync-notion-api-client.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/class-notion-wp-sync-cli.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/class-notion-wp-sync-api-abstract-route.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/class-notion-wp-sync-api-import-route.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/class-notion-wp-sync-notion-page.php';

require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/formatters/class-notion-wp-sync-terms-formatter.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/destinations/class-notion-wp-sync-abstract-destination.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/destinations/class-notion-wp-sync-post-destination.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/destinations/class-notion-wp-sync-meta-destination.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/destinations/class-notion-wp-sync-taxonomy-destination.php';

require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-models/class-notion-wp-sync-abstract-model.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-models/class-notion-wp-sync-page-model.php';

require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/interface-notion-wp-sync-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-abstract-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/interface-notion-wp-sync-support-string-value.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/interface-notion-wp-sync-support-html-value.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/interface-notion-wp-sync-support-files-value.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/interface-notion-wp-sync-support-datetime-value.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/interface-notion-wp-sync-support-multi-string-value.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/interface-notion-wp-sync-support-float-value.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/interface-notion-wp-sync-support-boolean-value.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/trait-notion-wp-sync-rich-text.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/trait-notion-wp-sync-plain-text.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-field-factory.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-generic-text-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-generic-multi-text-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-title-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-blocks-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-files-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-rich-text-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-date-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-select-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-multi-select-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-number-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-status-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-checkbox-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-url-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-email-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-phone-number-field.php';
require_once NOTION_WP_SYNC_PLUGIN_DIR . 'includes/notion-fields/class-notion-wp-sync-people-field.php';

register_activation_hook( __FILE__, __NAMESPACE__ . '\notion_wp_sync_activate' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\notion_wp_sync_deactivate' );

/**
 * The code that runs during plugin activation.
 */
function notion_wp_sync_activate() {
}

/**
 * The code that runs during plugin deactivation.
 */
function notion_wp_sync_deactivate() {
	// flush rewrite rules.
	flush_rewrite_rules();

	// Clear hooks.
	foreach ( _get_cron_array() as $cron ) {
		foreach ( array_keys( $cron ) as $hook ) {
			if ( strpos( $hook, 'notion_wp_sync_importer_' ) === 0 ) {
				wp_clear_scheduled_hook( $hook );
			}
		}
	}
}

// Init plugin.
new Notion_WP_Sync();
