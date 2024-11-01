<?php
/**
 * Manage webhook Rest API endpoint.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

use WP_REST_Request, WP_REST_Response;
use Exception;
use WP_Error;

/**
 * Notion_WP_Sync_Api_Import_Route class.
 */
class Notion_WP_Sync_Api_Import_Route extends Notion_WP_Sync_Api_Abstract_Route {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $route = 'import/(?P<importer_hash>[^/]+)';

	/**
	 * {@inheritdoc}
	 *
	 * @var array|string
	 */
	protected $methods = array( 'GET', 'POST' );

	/**
	 * Set hooks
	 */
	protected function set_hooks() {
	}

	/**
	 * Get route arguments.
	 */
	protected function get_route_args() {
		return array(
			'importer_hash' => array(
				'required'          => true,
				'validate_callback' => array( $this, 'validate_importer_hash' ),
			),
		);
	}

	/**
	 * Validate importer parameter.
	 *
	 * @param mixed           $value Request value.
	 * @param WP_REST_Request $request Request.
	 * @param string          $key Value key.
	 */
	public function validate_importer_hash( $value, $request, $key ) {
		return false !== $this->get_importer_by_hash( $value );
	}


	/**
	 * Get importer instance from hash
	 *
	 * @param string $hash Importer hash.
	 *
	 * @return Notion_WP_Sync_Importer|bool
	 */
	protected function get_importer_by_hash( $hash ) {
		return array_reduce(
			$this->importers,
			function ( $result, $importer ) use ( $hash ) {
				return $importer->infos()->get( 'hash' ) === $hash ? $importer : $result;
			},
			false
		);
	}

	/**
	 * Run importer
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @throws \Exception No connection found.
	 * @throws \Exception Importer errors.
	 */
	public function run( WP_REST_Request $request ) {
		try {
			$importer_hash = $request->get_param( 'importer_hash' );
			$importer      = $this->get_importer_by_hash( $importer_hash );

			if ( ! $importer ) {
				throw new Exception( 'No connection found.' );
			}

			$result = $importer->run();
			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}

			return new WP_REST_Response(
				array(
					'success' => true,
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'error', $e->getMessage() );
		}
	}
}
