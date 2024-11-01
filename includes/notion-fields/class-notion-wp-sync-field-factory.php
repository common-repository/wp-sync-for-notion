<?php
/**
 * Factory class to register and build Notion fields.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Field_Factory class.
 */
class Notion_WP_Sync_Field_Factory {

	/**
	 * Register managed Notion fields.
	 *
	 * @param string[] $classes Notion fields classes.
	 *
	 * @return void
	 */
	public static function register_fields( $classes ) {
		foreach ( $classes as $class ) {
			$field_type = call_user_func( $class . '::get_object_type' );
			add_filter(
				'notionwpsync/field-objects',
				function ( $fields ) use ( $field_type ) {
					$fields [] = $field_type;
					return $fields;
				}
			);
			add_filter(
				'notionwpsync/field-object/' . sanitize_key( $field_type ),
				function ( $object_class ) use ( $class ) {
					$object_class = $class;
					return $object_class;
				}
			);

			call_user_func( $class . '::register' );
		}
	}

	/**
	 * Build a Notion field from a field type and field props.
	 *
	 * @param string    $field_type Field type.
	 * @param \stdClass $prop_data Field props.
	 *
	 * @return false|Notion_WP_Sync_Field_Interface
	 */
	public static function build( $field_type, $prop_data ) {
		$object_class = apply_filters( 'notionwpsync/field-object/' . sanitize_key( $field_type ), false );

		if ( false === $object_class || ! class_exists( $object_class ) ) {
			return false;
		}

		return new $object_class( $prop_data );
	}

	/**
	 * Get field types based on supported value types.
	 *
	 * @param string[] $support List of value types to support.
	 *
	 * @TODO: throw error instead of return false?
	 * @return array|false
	 */
	public static function get_field_types( $support = array() ) {
		$field_types = array();
		if ( ! empty( $support ) ) {
			$field_types = apply_filters( 'notionwpsync/field-objects', array() );
			$field_types = array_filter(
				$field_types,
				function ( $field_type ) use ( $support ) {
					$field_class = apply_filters( 'notionwpsync/field-object/' . sanitize_key( $field_type ), null );
					if ( ! $field_class ) {
						return false;
					}
					$implements = class_implements( $field_class );
					$intersect  = array_intersect( $support, array_keys( $implements ) );
					return count( $intersect ) > 0;
				}
			);
		}
		return array_values( $field_types );
	}
}
