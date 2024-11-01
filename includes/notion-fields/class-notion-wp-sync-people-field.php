<?php
/**
 * Manages people Notion property type.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_People_Field class.
 */
class Notion_WP_Sync_People_Field extends Notion_WP_Sync_Abstract_Field {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $type = 'people';

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
	protected $filter_type = 'people';

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
				if ( $properties_object instanceof Notion_WP_Sync_Field_Interface && $properties_object->get_type() === 'people' ) {
					$user = null;
					// Don't expect values here.
					if ( 'database' === $data->object ) {
						$user = (object) array(

							'id'         => '',
							'name'       => '',
							'avatar_url' => '',
							'color'      => '',

						);
					} else {
						$raw_value = $properties_object->get_raw_value();
						if ( is_array( $raw_value ) && count( $raw_value ) > 0 ) {
							$user = $raw_value[0];
						}
					}

					$result[] = new Notion_WP_Sync_Generic_Text_Field(
						(object) array(
							'id'               => $properties_object->get_id() . '.user_id',
							'type'             => 'nws_generic_text',
							/* translators: %S the field name */
							'name'             => sprintf( __( '%s (id)', 'wp-sync-for-notion' ), $properties_object->get_name() ),
							'nws_generic_text' => $user->id ?? '',
						)
					);
					$result[] = new Notion_WP_Sync_Generic_Text_Field(
						(object) array(
							'id'               => $properties_object->get_id() . '.user_name',
							'type'             => 'nws_generic_text',
							/* translators: %s the field name */
							'name'             => sprintf( __( '%s (name)', 'wp-sync-for-notion' ), $properties_object->get_name() ),
							'nws_generic_text' => $user->name ?? '',
						)
					);
					$result[] = new Notion_WP_Sync_Generic_Text_Field(
						(object) array(
							'id'               => $properties_object->get_id() . '.user_avatar_url',
							'type'             => 'nws_generic_text',
							/* translators: %s the field name */
							'name'             => sprintf( __( '%s (avatar url)', 'wp-sync-for-notion' ), $properties_object->get_name() ),
							'nws_generic_text' => $user->avatar_url ?? '',
						)
					);
					$result[] = new Notion_WP_Sync_Files_Field(
						(object) array(
							'id'    => $properties_object->get_id() . '.user_avatar_file',
							'type'  => 'files',
							/* translators: the field name */
							'name'  => sprintf( __( '%s (avatar file: will import the file)', 'wp-sync-for-notion' ), $properties_object->get_name() ),
							'files' => array(
								(object) array(
									'id'   => 'user_avatar_file',
									'name' => $user && $user->name ? sanitize_title( $user->name ) : '',
									'file' => (object) array(
										'url' => $user->avatar_url ?? '',
									),
								),
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
}
