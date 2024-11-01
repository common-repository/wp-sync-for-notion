<?php
/**
 * Notion page object.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Page_Model class.
 */
class Notion_WP_Sync_Page_Model extends Notion_WP_Sync_Abstract_Model implements \JsonSerializable {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function get_name(): string {
		return isset( $this->data->properties->title->title ) ? Notion_WP_Sync_Rich_Text_Parser::get_instance()->to_plain_text( $this->data->properties->title->title ) : '';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return array(
			'id'               => $this->get_id(),
			'name'             => $this->get_name(),
			'parent_id'        => $this->get_parent_id(),
			'fields'           => array_values( $this->get_fields() ),
			'type'             => 'page',
			'last_edited_time' => $this->data->last_edited_time ?? '',
		);
	}
}
