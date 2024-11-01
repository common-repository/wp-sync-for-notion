<?php
/**
 * Interface to declare files value support for Notion field.
 *
 * @see Notion_WP_Sync_Attachments_Manager
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion field support files value
 */
interface Notion_WP_Sync_Support_Files_Value {
	/**
	 * Returns a list of files.
	 *
	 * @param array $params Extra params.
	 *
	 * @return array
	 */
	public function get_files_value( $params ): array;
}
