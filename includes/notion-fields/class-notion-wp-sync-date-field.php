<?php
/**
 * Manages date Notion property type.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Date_Field class.
 */
class Notion_WP_Sync_Date_Field extends Notion_WP_Sync_Abstract_Field implements Notion_WP_Sync_Support_String_Value, Notion_WP_Sync_Support_DateTime_Value {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $type = 'date';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $default_value_type = Notion_WP_Sync_Support_DateTime_Value::class;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected $filter_type = 'date';

	/**
	 * {@inheritDoc}
	 */
	public static function register() {

		// Add subfields.
		add_filter( 'notionwpsync/notion-model/fields', self::class . '::add_sub_fields', 10, 2 );

		// Add Object date fields.
		add_filter( 'notionwpsync/notion-model/fields', self::class . '::add_object_date_fields', 20, 2 );
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
				if ( $properties_object instanceof Notion_WP_Sync_Field_Interface && $properties_object->get_type() === 'date' ) {
					$date_raw = $properties_object->get_raw_value();

					// Don't expect values here.
					if ( 'database' === $data->object ) {
						$date_raw = (object) array(
							'start'     => gmdate( 'Y-m-d' ),
							'end'       => gmdate( 'Y-m-d' ),
							'time_zone' => null,
						);
					}

					$result[] = new Notion_WP_Sync_Date_Field(
						(object) array(
							'id'   => $properties_object->get_id() . '.start',
							'type' => 'date',
							/* translators: %s the date field name */
							'name' => sprintf( __( '%s (start)', 'wp-sync-for-notion' ), $properties_object->get_name() ),
							'date' => (object) array(
								'date_string' => $date_raw->start,
								'time_zone'   => $date_raw->time_zone,
							),
						)
					);
					$result[] = new Notion_WP_Sync_Date_Field(
						(object) array(
							'id'   => $properties_object->get_id() . '.end',
							'type' => 'date',
							/* translators: %s the date field name */
							'name' => sprintf( __( '%s (end)', 'wp-sync-for-notion' ), $properties_object->get_name() ),
							'date' => (object) array(
								'date_string' => $date_raw->end,
								'time_zone'   => $date_raw->time_zone,
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
	 * Register fake fields for special date properties like Created time & Last edited time.
	 *
	 * @param array  $properties_objects Field properties.
	 * @param object $data Notion object data (page / database).
	 *
	 * @return array
	 */
	public static function add_object_date_fields( $properties_objects, $data ) {
		array_unshift(
			$properties_objects,
			new Notion_WP_Sync_Date_Field(
				(object) array(
					'id'   => '__notionwpsync_created_time',
					'type' => 'date',
					'name' => __( 'Created time', 'wp-sync-for-notion' ),
					'date' => (object) array(
						'date_string' => $data->created_time,
						'time_zone'   => null,
					),
				)
			),
			new Notion_WP_Sync_Date_Field(
				(object) array(
					'id'   => '__notionwpsync_last_edited_time',
					'type' => 'date',
					'name' => __( 'Last edited time', 'wp-sync-for-notion' ),
					'date' => (object) array(
						'date_string' => $data->last_edited_time,
						'time_zone'   => null,
					),
				)
			)
		);
		return $properties_objects;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array $params Extra params.
	 *
	 * @return \DateTimeInterface|null
	 * @throws \Exception If the date string can't be parsed.
	 */
	public function get_datetime_value( $params ) {
		$date_raw    = $this->get_raw_value();
		$date_string = $date_raw->date_string ?? '';

		if ( empty( $date_string ) ) {
			return null;
		}

		// Only date?
		if ( strlen( $date_string ) === 10 ) {
			$date_string .= 'T00:00:00';
		}

		$date = new \DateTimeImmutable( $date_string );
		if ( $date instanceof \DateTimeInterface ) {
			return $date;
		}
		return null;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 * @param array $params Extra params.
	 * @throws \Exception If the date string can't be parsed.
	 */
	public function get_string_value( $params ): string {
		$date = $this->get_datetime_value( $params );
		if ( $date instanceof \DateTimeInterface ) {
			$date_string = date_i18n( get_option( 'date_format' ), $date->getTimestamp() );
			if ( is_string( $date_string ) ) {
				return $date_string;
			}
		}
		return '';
	}
}
