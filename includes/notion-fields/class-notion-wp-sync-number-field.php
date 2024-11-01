<?php
/**
 * Manages number Notion property type.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Number_Field class.
 */
class Notion_WP_Sync_Number_Field extends Notion_WP_Sync_Abstract_Field implements Notion_WP_Sync_Support_Float_Value, Notion_WP_Sync_Support_String_Value {

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $type = 'number';

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected static $default_value_type = Notion_WP_Sync_Support_Float_Value::class;

	/**
	 * {@inheritDoc}
	 *
	 * @var string
	 */
	protected $filter_type = 'number';

	/**
	 * {@inheritDoc}
	 *
	 * @param array $params Extra params.
	 *
	 * @return float
	 */
	public function get_float_value( $params ): float {
		return $this->get_raw_value() ?? 0;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array $params Extra params.
	 */
	public function get_string_value( $params ): string {
		$float_string = (string) $this->get_float_value( $params );
		$parts        = explode( '.', $float_string );
		$decimals     = 0;
		if ( count( $parts ) > 1 ) {
			$decimals = strlen( $parts[1] );
		}
		return number_format_i18n( $this->get_float_value( $params ), $decimals );
	}
}
