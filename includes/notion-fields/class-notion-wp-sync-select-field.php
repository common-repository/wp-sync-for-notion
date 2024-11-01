<?php
/**
 * Manages select Notion property type.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion field select
 */
class Notion_WP_Sync_Select_Field extends Notion_WP_Sync_Abstract_Field implements Notion_WP_Sync_Support_String_Value {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $type = 'select';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $default_value_type = Notion_WP_Sync_Support_String_Value::class;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected $filter_type = 'select';

	/**
	 * {@inheritDoc}
	 */
	public static function register() {
		// Add subfields.
		add_filter( 'notionwpsync/notion-model/fields', self::class . '::add_sub_fields', 10, 2 );
	}

	/**
	 * Register Notion field sub fields.
	 *
	 * @param array  $properties_objects Field properties.
	 * @param object $data Notion object data (page / database).
	 *
	 * @return array
	 */
	public static function add_sub_fields( $properties_objects, $data ) {
		$properties_objects = array_reduce(
			$properties_objects,
			function ( $result, $properties_object ) use ( $data ) {
				if ( $properties_object instanceof Notion_WP_Sync_Field_Interface && $properties_object->get_type() === 'select' ) {
					$date_raw = $properties_object->get_raw_value();

					// Don't expect values here.
					if ( 'database' === $data->object ) {
						$date_raw = (object) array(
							'name'  => '',
							'id'    => '',
							'color' => '',
						);
					}

					$result[] = new Notion_WP_Sync_Generic_Text_Field(
						(object) array(
							'id'               => $properties_object->get_id() . '.name',
							'type'             => 'nws_generic_text',
							'name'             => $properties_object->get_name(),
							'nws_generic_text' => $date_raw->name ?? '',
						)
					);
				} else {
					$result[] = $properties_object;
				}

				return $result;
			},
			array()
		);

		return $properties_objects;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array $params Extra params.
	 *
	 * @return string
	 */
	public function get_string_value( $params ): string {
		$options = $this->data->select->options ?? array();
		if ( empty( $options ) ) {
			return '';
		}
		return $options[0]->name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_filter() {
		$filter          = parent::get_filter();
		$filter->options = $this->get_raw_value()->options;
		return $filter;
	}
}
