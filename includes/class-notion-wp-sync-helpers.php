<?php
/**
 * Helper functions.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Helpers class.
 */
class Notion_WP_Sync_Helpers {
	/**
	 * Get properly formatted date for WP, with locale and timezone
	 *
	 * @param int|string $datetime A date as string or timestamp.
	 *
	 * @return string|false
	 */
	public static function get_formatted_date_time( $datetime ) {
		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), is_int( $datetime ) ? $datetime : strtotime( $datetime ) );
	}

	/**
	 * Get available post types
	 *
	 * @return array
	 */
	public static function get_post_types() {
		$excluded   = array(
			// WP.
			'attachment',
			// WPC.
			'nwpsync-connection',
			'nwpsync-content',
			'airwpsync-connection',
			// Others known incompatible post types.
			'acf-field-group',
			'acf-field',
			'wpforms',
			'e-landing-page',
			'elementor_library',
			'elementor_snippet',
			'elementor_font',
			'elementor_icons',
			'elementor-hf',
			'wpcf7_contact_form',
			'et_tb_item',
			'et_code_snippet',
			'et_theme_builder',
			'et_template',
			'et_header_layout',
			'et_body_layout',
			'et_pb_layout',
			'et_footer_layout',
			'fl-builder-template',
			'fl-theme-layout',
			'mc4wp-form',
			'polylang_mo',
			'edd_payment',
			'edd_discount',
			'edd_license',
			'edd_license_log',
			'edd_receipt',
			'edd_subscription_log',
			'product_variation',
			'shop_order',
			'shop_order_refund',
			'shop_coupon',
			'shop_order_placehold',
			'nf_sub',
		);
		$post_types = array();

		$wp_post_types = get_post_types( null, 'objects' );

		foreach ( $wp_post_types as $wp_post_type ) {
			// Skip excluded post types.
			if ( in_array( $wp_post_type->name, $excluded, true ) ) {
				continue;
			}
			// Skip WP private post types.
			if ( $wp_post_type->_builtin && ! $wp_post_type->public ) {
				continue;
			}

			$builtin = $wp_post_type->_builtin;

			$post_types[] = array(
				'value'   => $wp_post_type->name,
				/* translators: %s feature name available in pro version */
				'label'   => $builtin ? $wp_post_type->labels->singular_name : sprintf( __( '%s (Pro version)', 'wp-sync-for-notion' ), $wp_post_type->labels->singular_name ),
				'enabled' => $builtin,
				'builtin' => $builtin,
			);
		}

		$post_types[] = array(
			'value'   => 'nwpsync-content',
			'label'   => __( 'Notion content (shortcode)', 'wp-sync-for-notion' ),
			'enabled' => false,
			'builtin' => false,
		);
		$post_types[] = array(
			'value'   => 'custom',
			'label'   => __( 'Create new post type... (Pro version)', 'wp-sync-for-notion' ),
			'enabled' => false,
			'builtin' => false,
			'group'   => 'wordpress',
		);

		return apply_filters( 'notionwpsync/get_post_types', $post_types );
	}

	/**
	 * Get post stati
	 */
	public static function get_post_stati() {
		$post_stati    = array();
		$wp_post_stati = get_post_stati(
			array( 'internal' => false ),
			'objects'
		);

		foreach ( $wp_post_stati as $wp_post_status ) {
			$post_stati[] = array(
				'value'   => $wp_post_status->name,
				'label'   => $wp_post_status->label,
				'enabled' => true,
			);
		}
		return apply_filters( 'notionwpsync/get_post_stati', $post_stati );
	}

	/**
	 * Get post authors
	 */
	public static function get_post_authors() {
		$authors    = array();
		$wp_authors = get_users( array( 'role__in' => array( 'administrator', 'editor', 'author', 'contributor' ) ) );
		foreach ( $wp_authors as $wp_author ) {
			$authors[] = array(
				'value'   => $wp_author->ID,
				'label'   => $wp_author->display_name,
				'enabled' => true,
			);
		}
		return apply_filters( 'notionwpsync/get_post_authors', $authors );
	}

	/**
	 * Get importer instance from id.
	 * Return false if the imported is not found.
	 *
	 * @param Notion_WP_Sync_Importer[] $importers Importers.
	 * @param int                       $id The importet id.
	 *
	 * @return bool|Notion_WP_Sync_Importer
	 */
	public static function get_importer_by_id( $importers, $id ) {
		return array_reduce(
			$importers,
			function ( $result, $importer ) use ( $id ) {
				return $importer->infos()->get( 'id' ) === (int) $id ? $importer : $result;
			},
			false
		);
	}

	/**
	 * Generate hash for given Notion record and config.
	 *
	 * @param Notion_WP_Sync_Abstract_Model $record The Notion object.
	 * @param array                         $config Importer config.
	 *
	 * @return string
	 */
	public static function generate_hash( $record, $config ) {
		// Remove cs & ts query strings from urls in Notion record.
		$record_json = wp_json_encode( $record );
		return md5( $record_json . wp_json_encode( $config ) );
	}

	/**
	 * Convert emoji in $data if required ($db_column in $db_table dot not use utf8mb4)
	 *
	 * @param array|string|object $data The data `wp_encode_emoji` will be applied to.
	 * @param string              $db_table The database table name (without the db prefix).
	 * @param string              $db_column The table column name.
	 *
	 * @return array|mixed
	 */
	public static function maybe_convert_emoji( $data, $db_table, $db_column ) {
		global $wpdb;
		// Get charset for the column where the data will be inserted into.
		$charset = $wpdb->get_col_charset( $wpdb->prefix . $db_table, $db_column );

		// If entry detail value column is not utf8mb4, encode emoji.
		if ( 'utf8mb4' !== $charset ) {
			$data = self::deep_convert_emoji( $data );
		}

		return $data;
	}

	/**
	 * Deep wp_encode_emoji.
	 *
	 * @param array|string|object $data The data `wp_encode_emoji` will be applied to.
	 * @param int                 $max_depth Limit of the recursion.
	 *
	 * @return array|mixed
	 */
	public static function deep_convert_emoji( $data, $max_depth = 512 ) {
		--$max_depth;
		if ( $max_depth <= 0 ) {
			return $data;
		}
		if ( is_string( $data ) ) {
			return wp_encode_emoji( $data );
		} elseif ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = self::deep_convert_emoji( $value, $max_depth );
			}
		} elseif ( is_object( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data->{$key} = self::deep_convert_emoji( $value, $max_depth );
			}
		}
		return $data;
	}
}
