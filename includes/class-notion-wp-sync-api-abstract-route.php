<?php
/**
 * Base class to manage Rest route.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

use WP_REST_Server, WP_REST_Request;

/**
 * Notion_WP_Sync_Api_Abstract_Route class.
 */
abstract class Notion_WP_Sync_Api_Abstract_Route {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'notionwpsync/v1';

	/**
	 * Route slug
	 *
	 * @var string
	 */
	protected $route;

	/**
	 * Route methods
	 *
	 * @var array|string
	 */
	protected $methods = WP_REST_Server::READABLE;

	/**
	 * List of available importers
	 *
	 * @var Notion_WP_Sync_Importer[]
	 */
	protected $importers = array();

	/**
	 * Set hooks.
	 *
	 * @param Notion_WP_Sync_Importer[] $importers Importers.
	 */
	public function __construct( $importers ) {
		$this->importers = $importers;

		add_action( 'rest_api_init', array( $this, 'register_route' ) );

		$this->set_hooks();
	}

	/**
	 * Register menu route.
	 */
	public function register_route() {
		register_rest_route(
			$this->namespace,
			$this->route,
			array(
				'methods'             => $this->methods,
				'callback'            => array( $this, 'run' ),
				'args'                => $this->get_route_args(),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Set hooks
	 */
	protected function set_hooks() {
	}

	/**
	 * Get route arguments.
	 */
	abstract protected function get_route_args();

	/**
	 * Generate and return the actual data.
	 *
	 * @param WP_REST_Request $request The request.
	 */
	abstract public function run( WP_REST_Request $request );
}
