<?php
/**
 * Manage Notion page special fields.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Notion_Page class.
 */
class Notion_WP_Sync_Notion_Page {

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'notionwpsync/notion-model/fields', self::class . '::change_title_field', 10, 2 );
		add_filter( 'notionwpsync/notion-model/register-fields', self::class . '::register_icon_field', 10, 2 );
		add_filter( 'notionwpsync/notion-model/register-fields', self::class . '::register_cover_field', 10, 2 );
		add_filter( 'notionwpsync/notion-model/register-fields', self::class . '::register_blocks_field', 10, 2 );
		// Load blocks only when required.
		add_filter( 'notionwpsync/importer/page', self::class . '::populate_pages_blocks', 10, 2 );
	}

	/**
	 * Replace page title in page fields.
	 *
	 * @param Notion_WP_Sync_Field_Interface[] $properties_objects Page properties.
	 * @param \stdClass                        $data Notion page props.
	 *
	 * @return mixed
	 */
	public static function change_title_field( $properties_objects, $data ) {
		if ( 'page' === $data->object ) {
			$properties_objects = array_reduce(
				$properties_objects,
				function ( $result, Notion_WP_Sync_Field_Interface $properties_object ) {
					if ( $properties_object->get_type() === 'title' ) {
						$data       = $properties_object->get_data();
						$data->name = __( 'Page title', 'wp-sync-for-notion' );
						array_unshift( $result, ( new Notion_WP_Sync_Title_Field( $data ) )->set_group( __( 'Page', 'wp-sync-for-notion' ) ) );
					} else {
						$result[] = $properties_object;
					}

					return $result;
				},
				array()
			);
		}
		return $properties_objects;
	}

	/**
	 * Push page icon in page fields.
	 *
	 * @param Notion_WP_Sync_Field_Interface[] $properties_objects Page properties.
	 * @param \stdClass                        $data Notion page props.
	 *
	 * @return array
	 */
	public static function register_icon_field( $properties_objects, $data ) {
		$icon_files = array();
		$icon_emoji = '';
		if ( isset( $data->icon ) ) {
			if ( 'emoji' === $data->icon->type ) {
				$icon_emoji = $data->icon->emoji;
			} elseif ( 'external' === $data->icon->type ) {
				$icon_files = array(
					(object) array(
						'id'   => 'external',
						'type' => 'file',
						'name' => 'icon',
						'file' => (object) array(
							'url' => $data->icon->external->url,
						),
					),
				);
			} elseif ( 'file' === $data->icon->type ) {
				$icon_files = array(
					(object) array(
						'id'   => 'file',
						'type' => 'file',
						'name' => 'icon',
						'file' => (object) array(
							'url' => $data->icon->file->url,
						),
					),
				);
			}
		}

		$properties_objects[] = ( new Notion_WP_Sync_Files_Field(
			(object) array(
				'id'    => '__notionwpsync_icon.file',
				'type'  => 'files',
				'name'  => __( 'Page icon (icon & custom)', 'wp-sync-for-notion' ),
				'files' => $icon_files,
			)
		) )->set_group( __( 'Page', 'wp-sync-for-notion' ) );

		$properties_objects[] = ( new Notion_WP_Sync_Generic_Text_Field(
			(object) array(
				'id'               => '__notionwpsync_icon.emoji',
				'type'             => 'nws_generic_text',
				'name'             => __( 'Page icon (emoji)', 'wp-sync-for-notion' ),
				'nws_generic_text' => $icon_emoji,
			)
		) )->set_group( __( 'Page', 'wp-sync-for-notion' ) );

		return $properties_objects;
	}

	/**
	 * Push page cover in page fields.
	 *
	 * @param Notion_WP_Sync_Field_Interface[] $properties_objects Page properties.
	 * @param \stdClass                        $data Notion page props.
	 *
	 * @return array
	 */
	public static function register_cover_field( $properties_objects, $data ) {
		$cover_files = array();
		if ( isset( $data->cover ) ) {
			if ( 'external' === $data->cover->type ) {
				$cover_files = array(
					(object) array(
						'id'   => 'external',
						'type' => 'file',
						'name' => 'cover',
						'file' => (object) array(
							'url' => $data->cover->external->url,
						),
					),
				);
			} elseif ( 'file' === $data->cover->type ) {
				$cover_files = array(
					(object) array(
						'id'   => 'file',
						'type' => 'file',
						'name' => 'cover',
						'file' => (object) array(
							'url' => $data->cover->file->url,
						),
					),
				);
			}
		}

		$properties_objects[] = ( new Notion_WP_Sync_Files_Field(
			(object) array(
				'id'    => '__notionwpsync_cover',
				'type'  => 'files',
				'name'  => __( 'Page cover', 'wp-sync-for-notion' ),
				'files' => $cover_files,
			)
		) )->set_group( __( 'Page', 'wp-sync-for-notion' ) );

		return $properties_objects;
	}

	/**
	 * Push page blocks in page fields.
	 *
	 * @param Notion_WP_Sync_Field_Interface[] $properties_objects Page properties.
	 * @param \stdClass                        $data Notion page props.
	 *
	 * @return array
	 */
	public static function register_blocks_field( $properties_objects, $data ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$properties_objects[] = ( new Notion_WP_Sync_Blocks_Field(
			(object) array(
				'id'                    => '__notionwpsync_blocks',
				'type'                  => '__notionwpsync_blocks',
				'name'                  => __( 'Page content', 'wp-sync-for-notion' ),
				'__notionwpsync_blocks' => array(),
			)
		) )->set_group( __( 'Page', 'wp-sync-for-notion' ) );

		return $properties_objects;
	}

	/**
	 * Populate page blocks.
	 *
	 * @param Notion_WP_Sync_Page_Model $page Page.
	 * @param Notion_WP_Sync_Importer   $importer Importer.
	 *
	 * @return Notion_WP_Sync_Page_Model
	 */
	public static function populate_pages_blocks( $page, $importer ) {
		$record_fields = $page->get_fields();
		// Get all blocks fields (from the current page + potentially ones from relations fields).
		$block_fields = array_filter(
			$record_fields,
			function ( $field ) {
				return strpos( $field->get_id(), '__notionwpsync_blocks' ) !== false;
			}
		);
		foreach ( $block_fields as $block_field ) {
			$blocks                                  = $importer->get_api_client()->get_blocks( $page->get_id() );
			$record_fields[ $block_field->get_id() ] = ( new Notion_WP_Sync_Blocks_Field(
				(object) array(
					'id'                    => $block_field->get_id(),
					'type'                  => '__notionwpsync_blocks',
					'name'                  => __( 'Content', 'wp-sync-for-notion' ),
					'__notionwpsync_blocks' => $blocks,
				)
			) )->set_group( $block_field->get_group() );
		}
		$page->set_fields( $record_fields );
		return $page;
	}
}
