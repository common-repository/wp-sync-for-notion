<?php
/**
 * Plain text Trait.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Plain_Text_Trait Trait.
 */
trait Notion_WP_Sync_Plain_Text_Trait {

	/**
	 * Returns a string value.
	 *
	 * @param array $params Extra params.
	 *
	 * @return string
	 */
	public function get_string_value( $params ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return Notion_WP_Sync_Rich_Text_Parser::get_instance()->to_plain_text( $this->data->{$this->get_type()} );
	}
}
