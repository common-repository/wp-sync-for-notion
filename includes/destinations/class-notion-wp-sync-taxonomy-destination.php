<?php
/**
 * Manages import as taxonomy.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Taxonomy_Destination class.
 */
class Notion_WP_Sync_Taxonomy_Destination extends Notion_WP_Sync_Abstract_Destination {
	/**
	 * Destination slug.
	 *
	 * @var string
	 */
	protected $slug = 'taxonomy';

	/**
	 * Term formatter dependency.
	 *
	 * @var Notion_WP_Sync_Term_Formatter
	 */
	protected $term_formatter;

	/**
	 * Constructor.
	 *
	 * @param Notion_WP_Sync_Term_Formatter $term_formatter Term formatter dependency.
	 */
	public function __construct( $term_formatter ) {
		parent::__construct();

		$this->term_formatter = $term_formatter;

		add_action( 'notionwpsync/import_record_after', array( $this, 'import' ), 10, 4 );
	}

	/**
	 * Import terms.
	 *
	 * @param Notion_WP_Sync_Importer       $importer Importer.
	 * @param array                         $fields Fields.
	 * @param Notion_WP_Sync_Abstract_Model $record The Notion object.
	 * @param int                           $post_id The post id.
	 */
	public function import( $importer, $fields, $record, $post_id ) {
		$mapped_fields = $this->get_destination_mapping( $importer, $fields );
		foreach ( $mapped_fields as $mapped_field ) {
			$taxonomy = $mapped_field['wordpress'];
			$value    = $this->format( $fields[ $mapped_field['notion'] ], $mapped_field, $importer, $taxonomy );
			wp_set_object_terms( $post_id, $value, $taxonomy );
		}
	}

	/**
	 * Add field features for each post types.
	 *
	 * @param string $post_type Post type.
	 *
	 * @return string[]
	 */
	protected function get_features_by_post_type( $post_type ) {
		return get_object_taxonomies( $post_type );
	}

	/**
	 * Assign fields to mapping group.
	 */
	protected function get_group() {
		return array(
			'label' => __( 'Taxonomies', 'wp-sync-for-notion' ),
			'slug'  => 'taxonomy',
		);
	}

	/**
	 * Get mapping fields
	 */
	protected function get_mapping_fields() {
		$excluded         = array( 'link_category' );
		$taxonomies       = get_taxonomies(
			array(
				'show_ui' => 1,
			),
			'objects'
		);
		$taxonomy_options = array();

		foreach ( $taxonomies as $taxonomy ) {
			if ( ! in_array( $taxonomy->name, $excluded, true ) ) {
				$taxonomy_options[] = array(
					'value'                 => $taxonomy->name,
					'label'                 => sprintf( '%s (%s)', $taxonomy->labels->singular_name, $taxonomy->name ),
					'enabled'               => true,
					'supported_value_types' => array( Notion_WP_Sync_Support_Multi_String_Value::class, Notion_WP_Sync_Support_String_Value::class ),
				);
			}
		}

		return $taxonomy_options;
	}

	/**
	 * Format imported value
	 *
	 * @param Notion_WP_Sync_Field_Interface $notion_field The Notion field.
	 * @param array                          $mapped_field Mapped field config.
	 * @param Notion_WP_Sync_Importer        $importer Importer.
	 * @param string                         $taxonomy The taxonomy.
	 *
	 * @return array
	 */
	protected function format( $notion_field, $mapped_field, $importer, $taxonomy ) {
		$destination      = $mapped_field['wordpress'];
		$wp_field_mapping = $this->get_field_mapping( $destination );

		$value                              = null;
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

		return $this->term_formatter->format( $value, $importer, $taxonomy );
	}
}
