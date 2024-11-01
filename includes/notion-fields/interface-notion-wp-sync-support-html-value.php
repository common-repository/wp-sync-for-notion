<?php
/**
 * Interface to declare html value support for Notion field.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Support_HTML_Value class.
 */
interface Notion_WP_Sync_Support_HTML_Value {

	/**
	 * Returns a string value that contains HTML.
	 *
	 * @param array $params Extra params.
	 *
	 * @return string
	 */
	public function get_html_value( $params ): string;
}
