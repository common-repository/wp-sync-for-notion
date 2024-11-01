<?php
/**
 * Manages url Notion property type.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_URL_Field class.
 */
class Notion_WP_Sync_URL_Field extends Notion_WP_Sync_Abstract_Field implements Notion_WP_Sync_Support_String_Value {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $type = 'url';

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
		return $this->data->url ?? '';
	}
}
