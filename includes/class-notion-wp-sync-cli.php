<?php
/**
 * WP Cli.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

use WP_CLI;

/**
 * Notion_WP_Sync_CLI class.
 */
class Notion_WP_Sync_CLI {
	/**
	 * List of available importers
	 *
	 * @var Notion_WP_Sync_Importer[]
	 */
	protected $importers = array();

	/**
	 * Constructor
	 *
	 * @param Notion_WP_Sync_Importer[] $importers Importers.
	 */
	public function __construct( $importers ) {
		$this->importers = $importers;
	}

	/**
	 * List importers
	 *
	 * @param array $args Positional arguments including command name.
	 * @param array $assoc_args  Associative arguments.
	 */
	public function list( $args, $assoc_args ) {
		$formatted_importers = array_map(
			function ( $importer ) {
				return array(
					'slug'  => $importer->infos()->get( 'slug' ),
					'title' => $importer->infos()->get( 'title' ),
				);
			},
			$this->importers
		);

		$formatter = new WP_CLI\Formatter(
			$assoc_args,
			array(
				'slug',
				'title',
			)
		);

		$formatter->display_items( $formatted_importers );
	}

	/**
	 * Run importer by slug
	 *
	 * @param array $args Positional arguments including command name.
	 */
	public function import( $args ) {
		if ( empty( $args[0] ) ) {
			WP_CLI::error( sprintf( 'Missing slug argument.' ) );
			return;
		}
		list ( $slug ) = $args;

		$importer = $this->get_importer_by_slug( $slug );
		if ( $importer ) {
			$start = microtime( true );
			WP_CLI::line( 'Running...' );
			$result            = $importer->run();
			$time_elapsed_secs = number_format( microtime( true ) - $start );

			if ( is_wp_error( $result ) ) {
				WP_CLI::error( sprintf( 'Error: "%s"', $result->get_error_message() ) );
			} else {
				WP_CLI::success( sprintf( 'Done in %ss.', $time_elapsed_secs ) );
			}
		} else {
			WP_CLI::error( sprintf( 'No connection with slug "%s"', $slug ) );
		}
	}

	/**
	 * Get importer instance by slug.
	 * Return false if not found.
	 *
	 * @param string $slug Importer slug.
	 *
	 * @return Notion_WP_Sync_Importer|bool
	 */
	protected function get_importer_by_slug( $slug ) {
		return array_reduce(
			$this->importers,
			function ( $result, $importer ) use ( $slug ) {
				return $importer->infos()->get( 'slug' ) === $slug ? $importer : $result;
			},
			false
		);
	}
}
