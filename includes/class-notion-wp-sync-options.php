<?php
/**
 * Manages sync options.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Options class.
 */
class Notion_WP_Sync_Options extends Notion_WP_Sync_Abstract_Settings {
	/**
	 * WP option slug.
	 *
	 * @var string
	 */
	protected $option_slug = 'notion_wp_sync_options';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( $this->load_options() );
	}

	/**
	 * Save settings to DB
	 */
	public function save() {
		update_option( $this->option_slug, $this->settings );
	}

	/**
	 * Load options from DB
	 */
	protected function load_options() {
		return get_option( $this->option_slug );
	}
}
