<?php
/**
 * Generic subtype "multi text" to manage sub values from Notion field.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Generic_Multi_Text_Field class.
 */
class Notion_WP_Sync_Generic_Multi_Text_Field extends Notion_WP_Sync_Abstract_Field implements Notion_WP_Sync_Support_Multi_String_Value {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $type = 'nws_generic_multi_text';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $default_value_type = Notion_WP_Sync_Support_Multi_String_Value::class;

	/**
	 * {@inheritDoc}
	 *
	 * @param array $params Extra params.
	 *
	 * @return array
	 */
	public function get_multi_string_value( $params ): array {
		return $this->data->nws_generic_multi_text ?? array();
	}
}
