<?php
/**
 * Interface for Notion field.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Field_Interface interface
 */
interface Notion_WP_Sync_Field_Interface {

	/**
	 * Default value type.
	 *
	 * @var string
	 */
	public static function get_object_type(): string;

	/**
	 * Returns default value type.
	 *
	 * @return string
	 */
	public static function get_default_value_type(): string;

	/**
	 * Returns field's id.
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Returns field's type.
	 *
	 * @return string
	 */
	public function get_type(): string;

	/**
	 * Returns field's name.
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Returns field's value based on a value type.
	 * The params can contain some context like the post_id.
	 *
	 * @param string $value_type The expected value type.
	 * @param array  $params Extra params.
	 *
	 * @return mixed|null
	 */
	public function get_value( $value_type, $params = array() );

	/**
	 * Returns the field's raw data value.
	 *
	 * @return object|null
	 */
	public function get_raw_value();

	/**
	 * Returns the field's raw data.
	 *
	 * @return object|null
	 */
	public function get_data();

	/**
	 * Returns the field's filter.
	 *
	 * @return object|false
	 */
	public function get_filter();

	/**
	 * Returns the field's group.
	 *
	 * @return string
	 */
	public function get_group(): string;

	/**
	 * Set the field's group
	 *
	 * @param string $group The field group.
	 *
	 * @return \Notion_Wp_Sync\Notion_WP_Sync_Field_Interface
	 */
	public function set_group( string $group ): Notion_WP_Sync_Field_Interface;
}
