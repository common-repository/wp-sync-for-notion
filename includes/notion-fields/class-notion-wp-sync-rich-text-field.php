<?php
/**
 * Manages rich text Notion property type.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion field rich text
 */
class Notion_WP_Sync_Rich_Text_Field extends Notion_WP_Sync_Abstract_Field implements Notion_WP_Sync_Support_String_Value, Notion_WP_Sync_Support_HTML_Value {
	use Notion_WP_Sync_Rich_Text_Trait;
	use Notion_WP_Sync_Plain_Text_Trait;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $type = 'rich_text';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $default_value_type = Notion_WP_Sync_Support_HTML_Value::class;
}
