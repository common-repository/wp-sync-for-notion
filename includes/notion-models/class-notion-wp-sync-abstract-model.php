<?php
/**
 * Base class for Notion objects.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Abstract_Model abstract class.
 */
abstract class Notion_WP_Sync_Abstract_Model {

	/**
	 * Data retrieve from the API.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Object properties as fields (all supported Notion properties) or false if not supported.
	 *
	 * @var array
	 */
	protected $properties;

	/**
	 * Complete object fields as defined by the plugin (with sub fields and fake fields).
	 *
	 * @var Notion_WP_Sync_Field_Interface[]
	 */
	protected $fields;


	/**
	 * Constructor
	 *
	 * @param \stdClass $data Notion object datas.
	 */
	public function __construct( $data ) {
		$this->data       = $data;
		$this->properties = $this->populate_properties();
		$this->fields     = $this->populate_fields();
	}

	/**
	 * Returns object name.
	 *
	 * @return string
	 */
	abstract public function get_name(): string;

	/**
	 * Populate properties from data.
	 *
	 * @return array
	 */
	public function populate_properties() {
		if ( ! isset( $this->data->properties ) ) {
			$properties = array();
		} else {
			$properties = array_values( (array) $this->data->properties );
		}

		$properties_objects = array_map(
			function ( $prop ) {
				$field = Notion_WP_Sync_Field_Factory::build( $prop->type, $prop );
				return $field;
			},
			$properties
		);

		return $properties_objects;
	}

	/**
	 * Populate fields from properties.
	 *
	 * @return Notion_WP_Sync_Field_Interface[]
	 */
	public function populate_fields() {
		$properties_objects = $this->get_properties();

		$fields_objects = apply_filters( 'notionwpsync/notion-model/register-fields', $properties_objects, $this->data );

		// Remove falsy values.
		$fields_objects = array_filter( $fields_objects );
		$fields_objects = apply_filters( 'notionwpsync/notion-model/fields', $fields_objects, $this->data );

		// Sort by name.
		usort(
			$fields_objects,
			function ( $item_a, $item_b ) {
				return strcmp( $item_a->get_name(), $item_b->get_name() );
			}
		);

		$fields_objects_ids = array_map(
			function ( $field_object ) {
				return $field_object->get_id();
			},
			$fields_objects
		);

		// re-index.
		return array_combine(
			$fields_objects_ids,
			$fields_objects
		);
	}

	/**
	 * Returns object id.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->data->id;
	}

	/**
	 * Returns parent object id if any (empty string if there is no parent).
	 *
	 * @return string
	 */
	public function get_parent_id() {
		if ( ! isset( $this->data->parent->type ) ) {
			return '';
		}

		$parent_id = 'workspace';
		switch ( $this->data->parent->type ) {
			case 'database_id':
				$parent_id = $this->data->parent->database_id;
				break;
			case 'page_id':
				$parent_id = $this->data->parent->page_id;
				break;
		}
		return $parent_id;
	}

	/**
	 * Returns computed object fields.
	 *
	 * @return Notion_WP_Sync_Field_Interface[]
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Return field defined by $notion_key.
	 *
	 * @param string $notion_key Notion key.
	 *
	 * @return Notion_WP_Sync_Field_Interface|null
	 */
	public function get_field( $notion_key ) {
		$fields = $this->get_fields();
		if ( ! isset( $fields[ $notion_key ] ) ) {
			return null;
		}
		return $fields[ $notion_key ];
	}

	/**
	 * Set object field.
	 *
	 * @param Notion_WP_Sync_Field_Interface[] $fields The fields.
	 *
	 * @return void
	 */
	public function set_fields( $fields ) {
		$this->fields = $fields;
	}

	/**
	 * Returns object property.
	 *
	 * @return array
	 */
	public function get_properties(): array {
		return $this->properties;
	}
}
