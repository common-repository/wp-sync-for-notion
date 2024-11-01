<?php
/**
 * Manages checkbox Notion property type.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Checkbox_Field class.
 */
class Notion_WP_Sync_Checkbox_Field extends Notion_WP_Sync_Abstract_Field implements Notion_WP_Sync_Support_Boolean_Value {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $type = 'checkbox';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $default_value_type = Notion_WP_Sync_Support_Boolean_Value::class;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected $filter_type = 'checkbox';

	/**
	 * {@inheritDoc}
	 *
	 * @param array $params Extra params.
	 *
	 * @return bool
	 */
	public function get_boolean_value( $params ): bool {
		return ! ! $this->get_raw_value();
	}
}
