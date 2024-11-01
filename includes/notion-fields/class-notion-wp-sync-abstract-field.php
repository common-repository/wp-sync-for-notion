<?php
/**
 * Base class for Notion fields.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

use Notion_Wp_Sync\Notion_WP_Sync_Field_Interface;

/**
 * Notion_WP_Sync_Abstract_Field class.
 */
abstract class Notion_WP_Sync_Abstract_Field implements Notion_WP_Sync_Field_Interface, \JsonSerializable {

	/**
	 * Field type (should be unique).
	 *
	 * @var string
	 */
	protected static $type = '';

	/**
	 * Returns field type.
	 *
	 * @return string
	 */
	public static function get_object_type(): string {
		return static::$type;
	}

	/**
	 * Default value type.
	 *
	 * @var string
	 */
	protected static $default_value_type = '';

	/**
	 * {@inheritDoc}
	 */
	public static function get_default_value_type(): string {
		return static::$default_value_type;
	}

	/**
	 * Register available Notion fields from fields classes.
	 *
	 * @param string[] $classes Fields classes.
	 *
	 * @return void
	 */
	public static function register_fields( $classes ) {
		foreach ( $classes as $class ) {
			add_filter(
				'notionwpsync/field-object/' . call_user_func( $class . '::get_object_type' ),
				function ( $object_class ) use ( $class ) {
					$object_class = $class;
					return $object_class;
				}
			);
			call_user_func( $class . '::register' );
		}
	}

	/**
	 * Override this function to register sub fields or any special behaviour specific to this field.
	 *
	 * @return void
	 */
	public static function register() {}

	/**
	 * Data retrieve from the Notion API.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Group use in drop down.
	 *
	 * @var string
	 */
	protected $group;

	/**
	 * Filter type or false is unsupported.
	 *
	 * @var string|boolean
	 */
	protected $filter_type = 'text';

	/**
	 * Constructor
	 *
	 * @param \stdClass $data Notion field props.
	 */
	public function __construct( $data ) {
		$this->data  = $data;
		$this->group = __( 'Properties', 'wp-sync-for-notion' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_id(): string {
		return $this->data->id;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_type(): string {
		return $this->data->type;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_name(): string {
		if ( ! isset( $this->data->name ) ) {
			return $this->data->type;
		}
		return $this->data->name ?? '';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $value_type The expected value type.
	 * @param array  $params Extra params.
	 *
	 * @return mixed|null
	 */
	public function get_value( $value_type, $params = array() ) {
		$value = null;

		switch ( $value_type ) {
			case Notion_WP_Sync_Support_String_Value::class:
				$value = $this->get_string_value( $params );
				break;

			case Notion_WP_Sync_Support_HTML_Value::class:
				$value = $this->get_html_value( $params );
				break;

			case Notion_WP_Sync_Support_Files_Value::class:
				$value = $this->get_files_value( $params );
				break;

			case Notion_WP_Sync_Support_DateTime_Value::class:
				$value = $this->get_datetime_value( $params );
				break;

			case Notion_WP_Sync_Support_Multi_String_Value::class:
				$value = $this->get_multi_string_value( $params );
				break;

			case Notion_WP_Sync_Support_Float_Value::class:
				$value = $this->get_float_value( $params );
				break;

			case Notion_WP_Sync_Support_Boolean_Value::class:
				$value = $this->get_boolean_value( $params );
				break;
		}
		return $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_raw_value() {
		return $this->data->{$this->get_type()} ?? null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_filter() {
		if ( false === $this->filter_type ) {
			return false;
		}
		return (object) array(
			'id'          => $this->get_id(),
			'type'        => $this->get_type(),
			'name'        => $this->get_name(),
			'filter_type' => $this->filter_type,
		);
	}

	/**
	 * Returns fields properties that should be serialized.
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		return array(
			'id'    => $this->get_id(),
			'name'  => $this->get_name(),
			'type'  => $this->get_type(),
			'group' => $this->get_group(),
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function get_group(): string {
		return $this->group;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $group The field group.
	 *
	 * @return \Notion_Wp_Sync\Notion_WP_Sync_Field_Interface
	 */
	public function set_group( string $group ): Notion_WP_Sync_Field_Interface {
		$this->group = $group;
		return $this;
	}
}
