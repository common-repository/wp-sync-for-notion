<?php
/**
 * Interface to declare array value support for Notion field.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion field support array of strings value
 */
interface Notion_WP_Sync_Support_Multi_String_Value {

	/**
	 * Returns a list of strings.
	 *
	 * @param array $params Extra params.
	 *
	 * @return array
	 */
	public function get_multi_string_value( $params ): array;
}
