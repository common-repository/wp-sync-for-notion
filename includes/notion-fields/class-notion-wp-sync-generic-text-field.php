<?php
/**
 * Generic subtype "text" to manage sub values from Notion field.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Generic text field
 */
class Notion_WP_Sync_Generic_Text_Field extends Notion_WP_Sync_Abstract_Field implements Notion_WP_Sync_Support_String_Value {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $type = 'nws_generic_text';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $default_value_type = Notion_WP_Sync_Support_String_Value::class;

	/**
	 * {@inheritDoc}
	 *
	 * @param array $params Extra params.
	 *
	 * @return string
	 */
	public function get_string_value( $params ): string {
		return $this->data->nws_generic_text ?? '';
	}
}
