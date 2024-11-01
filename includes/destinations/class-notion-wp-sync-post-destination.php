<?php
/**
 * Manages import as post.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Post_Destination class.
 */
class Notion_WP_Sync_Post_Destination extends Notion_WP_Sync_Abstract_Destination {
	/**
	 * Destination slug.
	 *
	 * @var string
	 */
	protected $slug = 'post';

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();

		add_filter( 'notionwpsync/import_post_data', array( $this, 'add_to_post_data' ), 20, 4 );
	}

	/**
	 * Handle post data importing
	 *
	 * @param array                         $post_data The post data.
	 * @param Notion_WP_Sync_Importer       $importer Importer.
	 * @param array                         $fields Fields.
	 * @param Notion_WP_Sync_Abstract_Model $record The Notion object.
	 */
	public function add_to_post_data( $post_data, $importer, $fields, $record ) {
		$mapped_fields = $this->get_destination_mapping( $importer, $fields );

		foreach ( $mapped_fields as $mapped_field ) {
			$notion_field = $fields[ $mapped_field['notion'] ];
			if ( $notion_field instanceof Notion_WP_Sync_Field_Interface ) {
				$post_data[ $mapped_field['wordpress'] ] = $this->format( $notion_field, $mapped_field, $importer );
			}
		}

		if ( is_post_type_hierarchical( $post_data['post_type'] ) && $record->get_parent_id() !== 'workspace' ) {
			$parent_post_id = $importer->get_object_id_from_record_id( $record->get_parent_id(), $importer->get_post_type() );
			if ( $parent_post_id ) {
				$post_data['post_parent'] = $parent_post_id;
			}
		}

		return $post_data;
	}

	/**
	 * Add field features for each post types
	 *
	 * @param string $post_type Post type.
	 *
	 * @return string[]
	 */
	protected function get_features_by_post_type( $post_type ) {
		$features = array(
			'post_name',
			'post_date',
		);

		if ( post_type_supports( $post_type, 'title' ) ) {
			$features[] = 'post_title';
		}

		if ( post_type_supports( $post_type, 'editor' ) ) {
			$features[] = 'post_content';
		}

		if ( post_type_supports( $post_type, 'excerpt' ) ) {
			$features[] = 'post_excerpt';
		}

		return $features;
	}

	/**
	 * Assign fields to mapping group.
	 */
	protected function get_group() {
		return array(
			'label' => __( 'Post', 'wp-sync-for-notion' ),
			'slug'  => 'post',
		);
	}

	/**
	 * Get mapping fields.
	 *
	 * @return array
	 */
	protected function get_mapping_fields() {
		return array(
			array(
				'value'                 => 'post_title',
				'label'                 => __( 'Title', 'wp-sync-for-notion' ),
				'enabled'               => true,
				'supported_value_types' => array( Notion_WP_Sync_Support_String_Value::class ),
			),
			array(
				'value'                 => 'post_content',
				'label'                 => __( 'Content', 'wp-sync-for-notion' ),
				'enabled'               => true,
				'supported_value_types' => array( Notion_WP_Sync_Support_HTML_Value::class, Notion_WP_Sync_Support_String_Value::class ),
			),
			array(
				'value'                 => 'post_excerpt',
				'label'                 => __( 'Excerpt', 'wp-sync-for-notion' ),
				'enabled'               => true,
				'supported_value_types' => array( Notion_WP_Sync_Support_String_Value::class ),
			),
			array(
				'value'                 => 'post_name',
				'label'                 => __( 'Slug', 'wp-sync-for-notion' ),
				'enabled'               => true,
				'supported_value_types' => array( Notion_WP_Sync_Support_String_Value::class ),
			),
			array(
				'value'                 => 'post_date',
				'label'                 => __( 'Publication Date', 'wp-sync-for-notion' ),
				'enabled'               => true,
				'supported_value_types' => array( Notion_WP_Sync_Support_DateTime_Value::class ),
			),
		);
	}

	/**
	 * Format imported value
	 *
	 * @param Notion_WP_Sync_Field_Interface $notion_field The Notion field.
	 * @param array                          $mapped_field Mapped field config.
	 * @param Notion_WP_Sync_Importer        $importer Importer.
	 *
	 * @return mixed|null
	 */
	protected function format( $notion_field, $mapped_field, $importer ) {
		$destination      = $mapped_field['wordpress'];
		$wp_field_mapping = $this->get_field_mapping( $destination );

		$value                              = '';
		$notion_field_supported_value_types = class_implements( $notion_field );

		if ( $wp_field_mapping ) {
			// Get first supported source value from the field.
			foreach ( $wp_field_mapping['supported_value_types'] as $value_type ) {
				if ( in_array( $value_type, $notion_field_supported_value_types, true ) ) {
					$value = $notion_field->get_value( $value_type, array( 'importer' => $importer ) );
					break;
				}
			}
		}

		if ( 'post_date' === $destination && $value instanceof \DateTimeInterface ) {
			$value = $value->format( 'Y-m-d H:i:s' );
		}

		return $value;
	}
}
