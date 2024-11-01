<?php
/**
 * Interface to declare string value support for Notion field.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion field support string value
 */
interface Notion_WP_Sync_Support_String_Value {

	/**
	 * Returns a string value.
	 *
	 * @param array $params Extra params.
	 *
	 * @return string
	 */
	public function get_string_value( $params ): string;
}
