<?php
/**
 * Interface to declare boolean value support for Notion field.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Support_Boolean_Value interface.
 */
interface Notion_WP_Sync_Support_Boolean_Value {
	/**
	 * Returns a boolean value.
	 *
	 * @param array $params Extra params.
	 *
	 * @return bool
	 */
	public function get_boolean_value( $params ): bool;
}
