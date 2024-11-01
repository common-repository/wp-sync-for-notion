<?php
/**
 * Blocks Notion field.
 * (fake Notion field to manage blocks https://developers.notion.com/reference/get-block-children)
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Blocks_Field class.
 */
class Notion_WP_Sync_Blocks_Field extends Notion_WP_Sync_Abstract_Field implements Notion_WP_Sync_Support_HTML_Value {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $type = '__notionwpsync_blocks';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $default_value_type = Notion_WP_Sync_Support_HTML_Value::class;

	/**
	 * Returns HTML from Notion_WP_Sync_Blocks_Parser.
	 *
	 * @param string $value_type The expected value type.
	 * @param array  $params Extra params.
	 *
	 * @return mixed|null
	 */
	public function get_value( $value_type, $params = array() ) {
		$value = null;
		switch ( $value_type ) {
			case Notion_WP_Sync_Support_HTML_Value::class:
				$value = $this->get_html_value( $params );
				break;
		}
		return $value;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array $params Extra params.
	 *
	 * @return string
	 */
	public function get_html_value( $params ): string {
		return Notion_WP_Sync_Blocks_Parser::get_instance()->parse_blocks( $this->get_raw_value(), $params );
	}
}
