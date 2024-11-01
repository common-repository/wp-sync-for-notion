<?php
/**
 * Interface to declare float value support for Notion field.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion field support float value
 */
interface Notion_WP_Sync_Support_Float_Value {

	/**
	 * Returns a float value.
	 *
	 * @param array $params Extra params.
	 *
	 * @return float
	 */
	public function get_float_value( $params ): float;
}
