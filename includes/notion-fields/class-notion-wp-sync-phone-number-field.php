<?php
/**
 * Manages phone number Notion property type.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Phone_Number_Field class.
 */
class Notion_WP_Sync_Phone_Number_Field extends Notion_WP_Sync_Abstract_Field implements Notion_WP_Sync_Support_String_Value {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $type = 'phone_number';

	/**
	 * {@inheritDoc}
	 *
	 * @var array
	 */
	protected static $default_value_type = Notion_WP_Sync_Support_String_Value::class;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 * @param array $params Extra params.
	 */
	public function get_string_value( $params ): string {
		return $this->data->phone_number ?? '';
	}
}
