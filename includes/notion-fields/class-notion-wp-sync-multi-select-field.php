<?php
/**
 * Manages multi select Notion property type.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion field multi select
 */
class Notion_WP_Sync_Multi_Select_Field extends Notion_WP_Sync_Abstract_Field {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $type = 'multi_select';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $default_value_type = '';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected $filter_type = 'multi_select';


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
				if ( $properties_object instanceof Notion_WP_Sync_Field_Interface && $properties_object->get_type() === 'multi_select' ) {
					$date_raw = $properties_object->get_raw_value();

					// Don't expect values here.
					if ( 'database' === $data->object ) {
						$date_raw = array(
							(object) array(
								'name'  => '',
								'id'    => '',
								'color' => '',
							),
						);
					}

					$result[] = new Notion_WP_Sync_Generic_Multi_Text_Field(
						(object) array(
							'id'                     => $properties_object->get_id() . '.name',
							'type'                   => 'nws_generic_multi_text',
							'name'                   => $properties_object->get_name(),
							'nws_generic_multi_text' => array_map(
								function ( $option ) {
									return $option->name ?? ''; },
								$date_raw
							),
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
	 * @return false|object
	 */
	public function get_filter() {
		$filter          = parent::get_filter();
		$filter->options = $this->get_raw_value()->options;
		return $filter;
	}
}
