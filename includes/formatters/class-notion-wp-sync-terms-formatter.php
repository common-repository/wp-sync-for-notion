<?php
/**
 * Terms Formatter: import a list of string as terms.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Terms_Formatter class.
 */
class Notion_WP_Sync_Terms_Formatter {
	/**
	 * Importer.
	 *
	 * @var Notion_WP_Sync_Importer
	 */
	protected $importer;

	/**
	 * Format source value
	 *
	 * @param array|string|null       $value The list of string.
	 * @param Notion_WP_Sync_Importer $importer The importer.
	 * @param string                  $taxonomy The taxonomy.
	 *
	 * @return array
	 */
	public function format( $value, $importer, $taxonomy ) {
		$this->importer = $importer;

		if ( is_null( $value ) ) {
			return array();
		}

		// Make sure we have an array of terms.
		$values = ! is_array( $value ) ? array( $value ) : $value;

		$terms = array();
		foreach ( $values as $value ) {
			$value = wp_strip_all_tags( $value );
			$term  = term_exists( $value, $taxonomy );
			if ( 0 === $term || null === $term ) {
				$term = wp_insert_term( $value, $taxonomy );
			}

			if ( is_wp_error( $term ) ) {
				$this->log( sprintf( '- Cannot get term \'%s\' (taxonomy: \'%s\'), error: %s', $value, $taxonomy, $term->get_error_message() ) );
			} else {
				$terms[] = (int) $term['term_id'];
			}
		}
		return $terms;
	}

	/**
	 * Log message.
	 *
	 * @param string $message Message to log.
	 * @param string $level Log level.
	 */
	protected function log( $message, $level = 'log' ) {
		if ( $this->importer ) {
			$this->importer->log( $message, $level );
		}
	}
}
