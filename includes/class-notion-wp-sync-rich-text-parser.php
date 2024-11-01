<?php
/**
 * Notion Rich text parser.
 *
 * @see https://developers.notion.com/reference/rich-text
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Rich_Text_Parser class.
 */
class Notion_WP_Sync_Rich_Text_Parser {

	/**
	 * Notion_WP_Sync_Rich_Text_Parser instance
	 *
	 * @var Notion_WP_Sync_Rich_Text_Parser $instance
	 */
	private static $instance;

	/**
	 * Returns Notion_WP_Sync_Rich_Text_Parser instance.
	 *
	 * @return Notion_WP_Sync_Rich_Text_Parser
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Parse rich texts objects.
	 *
	 * @param array $elements Rich texts objects.
	 * @param array $options Additional options {
	 *     Optional. An array of options.
	 *
	 *     @type boolean $nl2br Convert line breaks to br? Default true. Accepts true or false.
	 * }
	 *
	 * @return string
	 */
	public function parse_rich_text( $elements, $options = array() ) {
		$options = wp_parse_args(
			$options,
			array(
				'nl2br' => true,
			)
		);
		$html    = '';
		if ( is_string( $elements ) ) {
			return $elements;
		}
		if ( ! is_array( $elements ) ) {
			return $html;
		}

		foreach ( $elements as $element ) {
			$element_html = '';
			if ( 'text' === $element->type ) {
				$element_html = esc_html( $element->text->content );

				if ( $options['nl2br'] ) {
					// keep the line break as-is, it's the exact character extracted from Notion...
					$element_html = str_replace(
						'
	',
						'<br>',
						$element_html
					);
				}
			}
			if ( isset( $element->annotations ) ) {
				if ( $element->annotations->bold ) {
					$element_html = '<strong>' . $element_html . '</strong>';
				}
				if ( $element->annotations->italic ) {
					$element_html = '<em>' . $element_html . '</em>';
				}
				if ( $element->annotations->strikethrough ) {
					$element_html = '<s>' . $element_html . '</s>';
				}
				if ( $element->annotations->underline ) {
					$element_html = '<u>' . $element_html . '</u>';
				}

				if ( $element->annotations->code ) {
					$element_html = '<code>' . $element_html . '</code>';
				}

				$inline_style = '';
				if ( isset( $element->annotations->color ) && 'default' !== $element->annotations->color && strpos( $element->annotations->color, '_background' ) === false ) {
					$inline_style .= sprintf( 'color: %s;', $this->color_to_rgb( $element->annotations->color ) );
				}
				if ( isset( $element->annotations->color ) && 'default' !== $element->annotations->color && strpos( $element->annotations->color, '_background' ) !== false ) {
					$inline_style .= sprintf( 'background-color: %s;', $this->bgcolor_to_rgb( $element->annotations->color ) );
				}

				if ( ! empty( $inline_style ) ) {
					$element_html = sprintf( '<span style="%s">' . $element_html . '</span>', esc_attr( $inline_style ) );
				}
			}

			if ( isset( $element->text->link->url ) ) {
				$url = $element->text->link->url;
				// Link relative to Notion.
				if ( strpos( $url, '/' ) === 0 ) {
					$url = 'https://www.notion.so' . $url;
				}
				$element_html = sprintf( '<a href="%s">', esc_url( $url ) ) . $element_html . '</a>';
			}

			$html .= $element_html;
		}
		return $html;
	}

	/**
	 * Extract plain text prop from rich text objects.
	 *
	 * @param array $elements Rich texts objects.
	 *
	 * @return string
	 */
	public function to_plain_text( $elements ) {
		$plain_text = '';
		if ( ! is_array( $elements ) ) {
			return $plain_text;
		}
		foreach ( $elements as $element ) {
			$plain_text .= $element->plain_text;
		}

		return $plain_text;
	}

	/**
	 * Notion colors mapping.
	 *
	 * @var string[]
	 */
	protected $color = array(
		'default' => 'rgb(0, 0, 0)',
		'orange'  => 'rgba(217, 115, 13, 1)',
		'blue'    => 'rgba(51, 126, 169, 1)',
		'brown'   => 'rgba(159, 107, 83, 1)',
		'yellow'  => 'rgba(203, 145, 47, 1)',
		'green'   => 'rgba(68, 131, 97, 1)',
		'gray'    => 'rgba(120, 119, 116, 1)',
		'purple'  => 'rgba(144, 101, 176, 1)',
		'pink'    => 'rgba(193, 76, 138, 1)',
		'red'     => 'rgba(212, 76, 71, 1)',
	);

	/**
	 * Converts Notion color name to rgba color.
	 * Returns a default color if the $color_name is not managed.
	 *
	 * @param string $color_name Notion color name.
	 *
	 * @return string
	 */
	public function color_to_rgb( $color_name ) {
		return $this->color[ $color_name ] ?? $this->color['default'];
	}

	/**
	 * Notion background colors mapping.
	 *
	 * @var string[]
	 */
	protected $bg_color = array(
		'default'           => 'transparent',
		'gray_background'   => 'rgb(241, 241, 239)',
		'brown_background'  => 'rgb(244, 238, 238)',
		'orange_background' => 'rgb(251, 236, 221)',
		'yellow_background' => 'rgb(251, 243, 219)',
		'green_background'  => 'rgb(237, 243, 236)',
		'blue_background'   => 'rgb(231, 243, 248)',
		'purple_background' => 'rgba(244, 240, 247, 0.8)',
		'pink_background'   => 'rgba(249, 238, 243, 0.8)',
		'red_background'    => 'rgb(253, 235, 236)',
	);

	/**
	 * Converts Notion background color name to rgba color.
	 * Returns a default color if the $color_name is not managed.
	 *
	 * @param string $color_name Notion color name.
	 *
	 * @return string
	 */
	public function bgcolor_to_rgb( $color_name ) {
		return $this->bg_color[ $color_name ] ?? $this->bg_color['default'];
	}
}
