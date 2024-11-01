<?php
/**
 * Interface to declare Datetime value support for Notion field.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion field support datetime value
 */
interface Notion_WP_Sync_Support_DateTime_Value {

	/**
	 * Returns a DateTimeInterface object or false of the date time could not be parsed.
	 *
	 * @param array $params Extra params.
	 *
	 * @return \DateTimeInterface|null
	 */
	public function get_datetime_value( $params );
}
