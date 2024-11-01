<?php
/**
 * Base class to manage plugin settings.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Abstract_Settings class.
 */
abstract class Notion_WP_Sync_Abstract_Settings {
	/**
	 * Settings.
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Constructor
	 *
	 * @param array $settings Settings.
	 */
	public function __construct( $settings ) {
		$this->settings = is_array( $settings ) ? $settings : array();
	}

	/**
	 * Get a setting
	 *
	 * @param string $key Setting key.
	 *
	 * @return mixed;
	 */
	public function get( $key ) {
		return array_reduce(
			explode( '.', $key ),
			function ( $o, $p ) {
				return is_array( $o ) && array_key_exists( $p, $o ) ? $o[ $p ] : false;
			},
			$this->settings
		);
	}

	/**
	 * Set a setting
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Setting value.
	 */
	public function set( $key, $value ) {
		$this->settings[ $key ] = $value;
	}

	/**
	 * Delete a setting.
	 *
	 * @param string $key Setting key.
	 */
	public function delete( $key ) {
		if ( array_key_exists( $key, $this->settings ) ) {
			unset( $this->settings[ $key ] );
		}
	}

	/**
	 * Get all settings as array
	 */
	public function to_array() {
		return $this->settings;
	}
}
