<?php
/**
 * Manages connection to the Notion API.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

use Exception;

/**
 * Notion_WP_Sync_Notion_Api_Client class.
 */
class Notion_WP_Sync_Notion_Api_Client {
	/**
	 * API endpoint.
	 *
	 * @var string
	 */
	protected $base_url = 'https://api.notion.com/v1';

	/**
	 * Notion version (see https://developers.notion.com/reference/versioning).
	 *
	 * @var string
	 */
	protected $notion_version = '2022-06-28';


	/**
	 * Authentication Token.
	 *
	 * @var string
	 */
	protected $token;

	/**
	 * Cache pages locally, it should be the same data within the request.
	 *
	 * @var array
	 */
	protected static $cache_pages = array();

	/**
	 * Constructor
	 *
	 * @param string $token Authentication Token.
	 */
	public function __construct( $token ) {
		$this->token = $token;
	}

	/**
	 * List pages.
	 *
	 * @param array  $options Endpoint options.
	 * @param string $term Search term.
	 *
	 * @return Notion_WP_Sync_Page_Model[]
	 */
	public function list_pages( $options = array( 'page_size' => 50 ), $term = '' ) {
		$args = array_merge(
			array(
				'filter' => array(
					'value'    => 'page',
					'property' => 'object',
				),
			),
			$options
		);

		if ( ! empty( $term ) ) {
			$args['query'] = $term;
		}

		$pages = $this->search( $args );

		$pages = array_map(
			function ( $page_data ) {
				return new Notion_WP_Sync_Page_Model( $page_data );
			},
			$pages
		);

		$pages = array_filter(
			$pages,
			function ( $page ) {
				return $page->get_name() !== '';
			}
		);

		return array_values( $pages );
	}

	/**
	 * Search through Notion databases and pages.
	 *
	 * @param array $args Endpoint args.
	 *
	 * @return array
	 */
	public function search( $args ) {
		return $this->all_results(
			function ( $options ) use ( $args ) {
				return $this->make_api_request( '/search', array_merge( $args, $options ), 'POST' );
			}
		);
	}

	/**
	 * Retrieve all records based on cursor.
	 *
	 * @param callable $api_call API to call.
	 *
	 * @return array
	 */
	protected function all_results( $api_call ) {
		$start_cursor = null;
		$items        = array();
		do {
			$options = array();
			if ( ! is_null( $start_cursor ) ) {
				$options['start_cursor'] = $start_cursor;
			}

			$response = call_user_func( $api_call, $options );

			$items        = array_merge( $items, isset( $response->results ) ? $response->results : array() );
			$start_cursor = isset( $response->next_cursor ) ? $response->next_cursor : '';

			usleep( 500000 );
		} while (
			! is_wp_error( $response )
			&& isset( $response->has_more )
			&& (bool) $response->has_more
		);

		return $items;
	}

	/**
	 * Get specific page.
	 *
	 * @param string $page_id The page id.
	 *
	 * @return Notion_WP_Sync_Page_Model
	 * @throws Exception API Exception.
	 */
	public function get_page( $page_id ) {
		if ( isset( self::$cache_pages[ $page_id ] ) ) {
			return self::$cache_pages[ $page_id ];
		}
		$page                                 = $this->make_api_request( sprintf( '/pages/%s', $page_id ) );
		$page                                 = new Notion_WP_Sync_Page_Model( $page );
		$page                                 = apply_filters( 'notionwpsync/notion-api-client/get-page', $page, $this );
		self::$cache_pages[ $page->get_id() ] = $page;
		return $page;
	}

	/**
	 * Get blocks from page id.
	 *
	 * @param string $page_id Page id.
	 *
	 * @return array
	 */
	public function get_blocks( $page_id ) {
		$args     = array(
			'page_size' => 50,
		);
		$endpoint = sprintf( '/blocks/%s/children', $page_id );
		$blocks   = $this->all_results(
			function ( $options ) use ( $endpoint, $args ) {
				return $this->make_api_request( $endpoint, array_merge( $args, $options ) );
			}
		);

		foreach ( $blocks as $block ) {
			if ( $block->has_children && 'child_page' !== $block->type ) {
				$block->children = $this->get_blocks( $block->id );
			}
		}

		return $blocks;
	}

	/**
	 * Returns Notion users.
	 *
	 * @param array $args Endpoint args.
	 *
	 * @return array
	 */
	public function get_users( $args = array() ) {
		return $this->all_results(
			function ( $options ) use ( $args ) {
				return $this->make_api_request( '/users', array_merge( $args, $options ) );
			}
		);
	}

	/**
	 * Performs API request
	 *
	 * @param string $url API URL.
	 * @param array  $data Data.
	 * @param string $type Method.
	 *
	 * @return mixed
	 * @throws Exception API Exception.
	 */
	protected function make_api_request( $url, $data = array(), $type = 'GET' ) {
		$url = $this->base_url . $url;

		if ( 'POST' === $type ) {
			$data = wp_json_encode( $data );
			if ( false === $data ) {
				throw new Exception( 'Cannot encode body in JSON' );
			}
		}
		$args     = $this->get_request_args( array( 'body' => $data ) );
		$response = 'POST' === $type ? wp_remote_post( $url, $args ) : wp_remote_get( $url, $args );

		return $this->validate_response( $response );
	}

	/**
	 * Build request args.
	 *
	 * @param array $args Request args.
	 *
	 * @return array
	 */
	protected function get_request_args( $args = array() ) {
		return array_merge(
			array(
				'headers' => array(
					'Authorization'  => 'Bearer ' . $this->token,
					'Content-Type'   => 'application/json',
					'Notion-Version' => $this->notion_version,
				),
				'timeout' => 15,
			),
			$args
		);
	}

	/**
	 * Validate HTTP Response and returns data
	 *
	 * @param array|WP_Error $response The API request réponse.
	 *
	 * @return mixed
	 * @throws Exception API Exception.
	 * @throws Exception Notion API: Could not decode JSON response.
	 */
	protected function validate_response( $response ) {
		if ( is_wp_error( $response ) ) {
			throw new Exception( esc_html( sprintf( 'Notion API: %s', $response->get_error_message() ) ) );
		}
		// Check HTTP code.
		$reponse_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $reponse_code ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body );
			if ( ! empty( $data->error ) ) {
				throw new Exception( esc_html( sprintf( 'Notion API: %s', $this->get_error_message( $data ) ) ) );
			}
			throw new Exception( esc_html( sprintf( 'Notion API: Received HTTP Error, code %s', $reponse_code ) ) );
		}
		// Get JSON data from request body.
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );
		if ( is_null( $data ) ) {
			throw new Exception( 'Notion API: Could not decode JSON response' );
		}
		$data = Notion_WP_Sync_Helpers::maybe_convert_emoji( $data, 'options', 'option_value' );
		return $data;
	}

	/**
	 * Get error message from Notion response.
	 *
	 * @TODO: test errors https://developers.notion.com/reference/errors
	 *
	 * @param \stdClass $data Error data.
	 *
	 * @return mixed|string
	 */
	protected function get_error_message( $data ) {
		$message = 'No error message';
		if ( ! empty( $data->message ) ) {
			$message = $data->message;
		}
		return $message;
	}

	/**
	 * Make sure filters structure is fine.
	 * Return false if there is a problem with the structure.
	 *
	 * @param array $group A group of filters.
	 *
	 * @return array|false
	 */
	public function deep_sanitize_filters( $group ) {
		$result   = array();
		$operator = sanitize_text_field( $group['operator'] );
		$operator = in_array( $operator, array( 'and', 'or' ), true ) ? $operator : 'and';

		if ( ! isset( $group['filters'] ) || ! is_array( $group['filters'] ) ) {
			$group['filters'] = array();
		}

		$result[ $operator ] = array_map(
			function ( $filter ) {
				if ( isset( $filter['filters'] ) || isset( $filter['operator'] ) ) {
					if ( ! isset( $filter['filters'] ) || empty( $filter['filters'] ) ) {
						return false;
					}
					return $this->deep_sanitize_filters( $filter );
				} else {
					// TODO: check property exists.
					$property      = $filter['property'];
					$comparison    = sanitize_text_field( $filter['comparison'] );
					$value         = sanitize_text_field( $filter['value'] );
					$type          = sanitize_text_field( $filter['type'] );
					$filter_type   = sanitize_text_field( $filter['filter_type'] );
					$filter_object = array(
						'property' => $property,
					);
					if ( in_array( $comparison, array( 'is_empty', 'is_not_empty' ), true ) || 'checkbox' === $filter_type ) {
						$value = true;
					} elseif ( 'number' === $filter_type ) {
						$value = floatval( $value );
					}
					$filter_object[ $type ]                = array();
					$filter_object[ $type ][ $comparison ] = $value;
					return $filter_object;
				}
			},
			$group['filters']
		);

		$result[ $operator ] = array_filter( $result[ $operator ] );

		return $result;
	}
}
