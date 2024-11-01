<?php
/**
 * Manages Notions blocks parsing and transformation to Gutenberg blocks.
 * It also allows finding page children (defined in blocks).
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Notion_WP_Sync_Blocks_Parser class
 */
class Notion_WP_Sync_Blocks_Parser {

	/**
	 * Based on https://github.com/WordPress/gutenberg/blob/9d8420f8cb6bced827c9879c810023aabc84eaef/packages/block-library/src/embed/constants.js#L1C8-L11C3
	 */
	const ASPECT_RATIOS = array(
		// Common video resolutions.
		array(
			'ratio'     => 2.33,
			'className' => 'wp-embed-aspect-21-9',
		),
		array(
			'ratio'     => 2.00,
			'className' => 'wp-embed-aspect-18-9',
		),
		array(
			'ratio'     => 1.78,
			'className' => 'wp-embed-aspect-16-9',
		),
		array(
			'ratio'     => 1.33,
			'className' => 'wp-embed-aspect-4-3',
		),
		// Vertical video and instagram square video support.
		array(
			'ratio'     => 1.00,
			'className' => 'wp-embed-aspect-1-1',
		),
		array(
			'ratio'     => 0.56,
			'className' => 'wp-embed-aspect-9-16',
		),
		array(
			'ratio'     => 0.50,
			'className' => 'wp-embed-aspect-1-2',
		),
	);


	/**
	 * Notion_WP_Sync_Blocks_Parser instance
	 *
	 * @var Notion_WP_Sync_Blocks_Parser $instance
	 */
	private static $instance;

	/**
	 * Returns Notion_WP_Sync_Blocks_Parser instance
	 *
	 * @return Notion_WP_Sync_Blocks_Parser
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Rich text parser.
	 *
	 * @var Notion_WP_Sync_Rich_Text_Parser
	 */
	private $rich_text_parser;

	/**
	 * Attachment manager (file importer).
	 *
	 * @var Notion_WP_Sync_Attachments_Manager
	 */
	private $attachment_manager;

	/**
	 * Notion_WP_Sync_Blocks_Parser constructor.
	 * Manages dependencies and init blocks hooks.
	 */
	public function __construct() {
		$this->rich_text_parser   = Notion_WP_Sync_Rich_Text_Parser::get_instance();
		$this->attachment_manager = Notion_WP_Sync_Attachments_Manager::get_instance();
		$this->init_blocks();
	}

	/**
	 * Init blocks hooks.
	 *
	 * @return void
	 */
	public function init_blocks() {
		add_filter( 'notionwpsync/blocks_parser/paragraph', array( $this, 'parse_paragraph_block' ), 10, 2 );
		add_filter( 'notionwpsync/blocks_parser/heading_1', array( $this, 'parse_heading_block' ), 10, 2 );
		add_filter( 'notionwpsync/blocks_parser/heading_2', array( $this, 'parse_heading_block' ), 10, 2 );
		add_filter( 'notionwpsync/blocks_parser/heading_3', array( $this, 'parse_heading_block' ), 10, 2 );
		add_filter( 'notionwpsync/blocks_parser/bulleted_list_item', array( $this, 'parse_list_block' ), 10, 3 );
		add_filter( 'notionwpsync/blocks_parser/numbered_list_item', array( $this, 'parse_list_block' ), 10, 3 );
		add_filter( 'notionwpsync/blocks_parser/quote', array( $this, 'parse_quote_block' ), 10, 2 );
		add_filter( 'notionwpsync/blocks_parser/table', array( $this, 'parse_table_block' ), 10, 2 );
		add_filter( 'notionwpsync/blocks_parser/divider', array( $this, 'parse_divider_block' ), 10, 2 );
		add_filter( 'notionwpsync/blocks_parser/image', array( $this, 'parse_image_block' ), 10, 3 );
		add_filter( 'notionwpsync/blocks_parser/video', array( $this, 'parse_video_block' ), 10, 3 );
		add_filter( 'notionwpsync/blocks_parser/column_list', array( $this, 'parse_column_list_block' ), 10, 3 );
		add_filter( 'notionwpsync/blocks_parser/callout', array( $this, 'parse_callout_block' ), 10, 3 );
		add_filter( 'notionwpsync/blocks_parser/synced_block', array( $this, 'parse_synced_block_block' ), 10, 3 );
		add_filter( 'notionwpsync/blocks_parser/code', array( $this, 'parse_code_block' ), 10, 2 );
		add_filter( 'notionwpsync/blocks_parser/toggle', array( $this, 'parse_toggle_block' ), 10, 3 );
		add_filter( 'notionwpsync/blocks_parser/embed', array( $this, 'parse_embed_block' ), 10, 2 );
	}

	/**
	 * Parse $blocks. For each Notion block returns a Gutenberg block as HTML or an empty string if the block is not supported.
	 *
	 * @param array  $blocks Notions blocks.
	 * @param array  $params Extra params for the context (importer, post_id).
	 * @param string $result The result as HTML string.
	 *
	 * @return string
	 */
	public function parse_blocks( $blocks, $params, $result = '' ) {
		$to_regroup          = array( 'bulleted_list_item', 'numbered_list_item' );
		$regrouped_item_type = null;
		$regrouped_items     = array();
		$sublist             = isset( $params['sublist'] ) && $params['sublist'];

		foreach ( $blocks as $block ) {
			if ( in_array( $block->type, $to_regroup, true ) ) {
				// Found all siblings items? parse blocks.
				if ( $block->type !== $regrouped_item_type && null !== $regrouped_item_type && ! $sublist ) {
					$regrouped_item_type_key = sanitize_key( $regrouped_item_type );
					$result                 .= apply_filters( "notionwpsync/blocks_parser/{$regrouped_item_type_key}", '', $regrouped_items, $params );
					$regrouped_items         = array();
				}
				$regrouped_items[]   = $block;
				$regrouped_item_type = $block->type;
			} else {
				// If the last item was part of a group, parse blocks.
				if ( count( $regrouped_items ) > 0 ) {
					$result             .= apply_filters( 'notionwpsync/blocks_parser/' . sanitize_key( $regrouped_item_type ), '', $regrouped_items, $params );
					$regrouped_items     = array();
					$regrouped_item_type = null;
				}
				$result .= apply_filters( 'notionwpsync/blocks_parser/' . sanitize_key( $block->type ), '', $block, $params );
			}
		}
		// If the last item was part of a group, parse blocks.
		if ( count( $regrouped_items ) > 0 ) {
			$result .= apply_filters( 'notionwpsync/blocks_parser/' . sanitize_key( $regrouped_item_type ), '', $regrouped_items, $params );
		}
		return $result;
	}

	/**
	 * Parse paragraph block.
	 *
	 * @param string $html HTML.
	 * @param object $block Block.
	 *
	 * @return string
	 */
	public function parse_paragraph_block( $html, $block ) {
		if ( ! isset( $block->paragraph ) ) {
			return $html;
		}

		$paragraph   = $block->paragraph;
		$block_props = $this->init_gut_props( $paragraph );
		$block_html  = '';

		if ( isset( $paragraph->rich_text ) ) {
			$block_html = $this->rich_text_parser->parse_rich_text( $paragraph->rich_text );
		}

		if ( ! empty( $block_html ) ) {
			$html_attributes = $this->generate_attributes_from_props( $block_props );
			$block_html      = "<p$html_attributes>$block_html</p>";
			$block_html      = $this->wrap_gut( $block_html, 'paragraph', $block_props );
		}

		return $html . $block_html;
	}

	/**
	 * Parse heading block.
	 *
	 * @param string $html HTML.
	 * @param object $block Block.
	 *
	 * @return string
	 */
	public function parse_heading_block( $html, $block ) {
		if ( ! preg_match( '`^heading\_([1-6])$`', $block->type, $matches ) ) {
			return $html;
		}
		$heading_level = (int) $matches[1];

		if ( ! isset( $block->{'heading_' . $heading_level} ) ) {
			return $html;
		}

		$heading    = $block->{'heading_' . $heading_level};
		$block_html = '';
		$props      = $this->init_gut_props( $heading );
		if ( 2 !== $heading_level ) {
			$props['level'] = $heading_level;
		}

		if ( isset( $heading->rich_text ) ) {
			$block_html = $this->rich_text_parser->parse_rich_text( $heading->rich_text );
		}

		if ( ! empty( $block_html ) ) {
			$block_html = $this->wrap_gut( "<h$heading_level>$block_html</h$heading_level>", 'heading', $props );
		}

		return $html . $block_html;
	}

	/**
	 * Parse list block.
	 *
	 * @param string   $html HTML.
	 * @param object[] $blocks List of bulleted_list_item or ... blocks.
	 * @param array    $params Extra params.
	 *
	 * @return string
	 */
	public function parse_list_block( $html, $blocks, $params ) {
		if ( ! is_array( $blocks ) || empty( $blocks ) ) {
			return $html;
		}

		$type = $blocks[0]->type;
		if ( ! in_array( $type, array( 'bulleted_list_item', 'numbered_list_item' ), true ) ) {
			return $html;
		}

		$props   = array();
		$tagname = 'ul';
		if ( 'numbered_list_item' === $type ) {
			$props['ordered'] = true;
			$tagname          = 'ol';
		}

		$block_html = '';
		foreach ( $blocks as $block ) {
			$block_html = self::parse_list_item_block( $block_html, $block, $type, $params );
		}

		$block_html = rtrim( $block_html, "\n" );

		if ( ! empty( $block_html ) ) {
			$block_html = $this->wrap_gut( "<$tagname>$block_html</$tagname>", 'list', $props );
		}

		return $html . $block_html;
	}


	/**
	 * Parse bulleted_list_item or ... block
	 *
	 * @param string $html HTML.
	 * @param object $block Block.
	 * @param string $type List item type.
	 * @param array  $params Extra params.
	 *
	 * @return string
	 */
	public function parse_list_item_block( $html, $block, $type, $params ) {
		if ( ! isset( $block->{$type} ) ) {
			return $html;
		}

		$list_item  = $block->{$type};
		$block_html = '';
		if ( isset( $list_item->rich_text ) ) {
			$block_html = $this->rich_text_parser->parse_rich_text( $list_item->rich_text );
		}
		if ( $block->has_children ) {
			$block_html = $this->parse_blocks( $block->children, array_merge( $params, array( 'sublist' => true ) ), $block_html );
			$block_html = rtrim( $block_html, "\n" );
		}

		if ( ! empty( $block_html ) ) {
			$block_html = $this->wrap_gut( "<li>$block_html</li>", 'list-item' );
		}

		return $html . $block_html . ( ! isset( $params['sublist'] ) ? "\n" : '' );
	}

	/**
	 * Parse quote block.
	 *
	 * @param string $html HTML.
	 * @param object $block Block.
	 *
	 * @return string
	 */
	public function parse_quote_block( $html, $block ) {
		if ( ! isset( $block->quote ) ) {
			return $html;
		}
		$quote       = $block->quote;
		$block_props = $this->init_gut_props( $quote );
		$block_html  = '';

		if ( isset( $quote->rich_text ) ) {
			$block_html = $this->parse_paragraph_block(
				$block_html,
				(object) array(
					'type'      => 'paragraph',
					'paragraph' => (object) array(
						'rich_text' => $quote->rich_text,
					),
				)
			);
		}

		if ( ! empty( $block_html ) ) {
			$block_attributes_props = $block_props;
			array_unshift( $block_attributes_props['className'], 'wp-block-quote' );
			$block_html = sprintf(
				"<blockquote%s>$block_html</blockquote>",
				$this->generate_attributes_from_props( $block_attributes_props )
			);
			$block_html = $this->wrap_gut( $block_html, 'quote', $block_props );
		}

		return $html . $block_html;
	}

	/**
	 * Parse table block.
	 *
	 * @param string $html HTML.
	 * @param object $block Block.
	 *
	 * @return string
	 */
	public function parse_table_block( $html, $block ) {
		if ( ! isset( $block->table ) || ! isset( $block->children ) || ! is_array( $block->children ) || empty( $block->children ) ) {
			return $html;
		}

		$table = $block->table;
		// top row is header.
		$has_column_header = $table->has_column_header;
		// first col is header.
		$has_row_header = $table->has_row_header;
		$block_html     = '';
		$children       = $block->children;
		if ( $has_column_header ) {
			$block_html .= '<thead><tr>';
			foreach ( $children[0]->table_row->cells as $cell ) {
				$block_html .= '<th>' . $this->rich_text_parser->parse_rich_text( $cell ) . '</th>';
			}
			$block_html .= '</tr></thead>';
			array_shift( $children );
		}

		$block_html .= '<tbody>';
		foreach ( $children as $child ) {
			$is_first_col = true;
			if ( 'table_row' === $child->type && isset( $child->table_row ) ) {
				$block_html .= '<tr>';
				foreach ( $child->table_row->cells as $cell ) {
					$tagname      = ( $has_row_header && $is_first_col ? 'th' : 'td' );
					$block_html  .= "<$tagname>" . $this->rich_text_parser->parse_rich_text( $cell ) . "</$tagname>";
					$is_first_col = false;
				}
				$block_html .= '</tr>';
			}
		}
		$block_html .= '</tbody>';

		$block_html = $this->wrap_gut( "<figure class=\"wp-block-table\"><table>$block_html</table></figure>", 'table' );

		return $html . $block_html;
	}

	/**
	 * Parse divider block.
	 *
	 * @param string $html HTML.
	 * @param object $block Block.
	 *
	 * @return string
	 */
	public function parse_divider_block( $html, $block ) {
		if ( ! isset( $block->divider ) ) {
			return $html;
		}

		$block_html = '<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/>';
		$html      .= $this->wrap_gut( $block_html, 'separator', array( 'className' => 'is-style-wide' ) );

		return $html;
	}


	/**
	 * Parse image block.
	 *
	 * @param string $html HTML.
	 * @param object $block Block.
	 * @param array  $params Extra params.
	 * @TODO: add test
	 *
	 * @return string
	 */
	public function parse_image_block( $html, $block, $params ) {
		if ( ! isset( $block->image ) ) {
			return $html;
		}

		$block_html  = '';
		$block_props = $this->init_gut_props(
			$block->image,
			array(
				'linkDestination' => 'none',
			)
		);

		$caption = ! empty( $block->image->caption ) ? $this->rich_text_parser->parse_rich_text( $block->image->caption ) : '';

		if ( ! in_array( $block->image->type, array( 'external', 'file' ), true ) ) {
			return $html;
		}

		$attachment_ids = $this->attachment_manager->get_set_files(
			array(
				$this->attachment_manager->notion_file_to_media(
					$block->id,
					! empty( $caption ) ? $this->rich_text_parser->to_plain_text( $block->image->caption ) : $block->type,
					$block->image
				),
			),
			$params['importer'],
			$params['post_id'] ?? null
		);

		if ( ! empty( $attachment_ids ) ) {
			$attachment_id                                  = $attachment_ids[0];
			list( $image_url, $image_width, $image_height ) = wp_get_attachment_image_src( $attachment_id, 'large' );
			$block_props['className'][]                     = 'size-large';
			$block_html                                     = sprintf( '<figure class="wp-block-image size-large"><img src="%s" alt=""/>', $image_url );
			if ( ! empty( $caption ) ) {
				$block_html .= sprintf( '<figcaption class="wp-element-caption">%s</figcaption>', $caption );
			}
			$block_html .= '</figure>';
		}

		if ( ! empty( $block_html ) ) {
			$html .= $this->wrap_gut( $block_html, 'image', $block_props );
		}

		return $html;
	}

	/**
	 * Parse video block.
	 *
	 * @param string $html HTML.
	 * @param object $block Block.
	 * @param array  $params Extra params.
	 * @TODO: add test
	 *
	 * @return string
	 */
	public function parse_video_block( $html, $block, $params ) {
		if ( ! isset( $block->video ) ) {
			return $html;
		}

		$block_props = $this->init_gut_props( $block->video );
		$block_html  = '';
		$caption     = ! empty( $block->video->caption ) ? $this->rich_text_parser->parse_rich_text( $block->video->caption ) : '';

		if ( 'external' === $block->video->type ) {
			$url   = $block->video->external->url;
			$html .= $this->embed( $url, $block_props, $caption );
		} elseif ( 'file' === $block->video->type ) {
			$file           = $this->attachment_manager->notion_file_to_media(
				$block->id,
				'video',
				$block->video,
				'mp4'
			);
			$attachments_id = $this->attachment_manager->get_set_files(
				array(
					$file,
				),
				$params['importer'],
				$params['post_id'] ?? null
			);
			if ( ! empty( $attachments_id ) ) {
				$attachment_id     = $attachments_id[0];
				$block_props['id'] = $attachment_id;
				$block_html        = sprintf(
					'<figure class="wp-block-video"><video controls src="%s"></video>%s</figure>',
					wp_get_attachment_url( $attachment_id ),
					! empty( $caption ) ? sprintf( '<figcaption class="wp-element-caption">%s</figcaption>', $caption ) : ''
				);
			}

			if ( ! empty( $block_html ) ) {
				$html .= $this->wrap_gut( $block_html, 'video', $block_props );
			}
		}

		return $html;
	}

	/**
	 * Parse column_list block.
	 *
	 * @param string $html HTML.
	 * @param object $block Block.
	 * @param array  $params Extra params.
	 *
	 * @return string
	 */
	public function parse_column_list_block( $html, $block, $params ) {
		if ( ! isset( $block->column_list ) || ! isset( $block->children ) || ! is_array( $block->children ) || empty( $block->children ) ) {
			return $html;
		}

		$block_html = '<div class="wp-block-columns">';
		foreach ( $block->children as $child ) {
			if ( 'column' !== $child->type ) {
				continue;
			}
			$column_html  = '<div class="wp-block-column">';
			$column_html  = $this->parse_blocks( $child->children, $params, $column_html );
			$column_html .= '</div>';

			$block_html .= $this->wrap_gut( $column_html, 'column' );
		}
		$block_html .= '</div>';

		$html .= $this->wrap_gut( $block_html, 'columns' );

		return $html;
	}

	/**
	 * Parse callout block.
	 *
	 * @param string $html HTML.
	 * @param object $block Block.
	 * @param array  $params Extra params.
	 *
	 * @return string
	 */
	public function parse_callout_block( $html, $block, $params ) {
		if ( ! isset( $block->callout ) ) {
			return $html;
		}

		$rich_text = '';
		if ( isset( $block->callout->icon ) ) {
			$icon = $block->callout->icon;
			if ( 'emoji' === $icon->type ) {
				$rich_text .= $icon->emoji . ' ';
			} elseif ( 'external' === $icon->type || 'file' === $icon->type ) {
				$url = '';
				if ( 'external' === $icon->type && strpos( $icon->external->url, 'https://www.notion.so/icons/' ) === 0 ) {
					$url = $icon->external->url;
				} else {
					$attachments_id = $this->attachment_manager->get_set_files(
						array(
							$this->attachment_manager->notion_file_to_media(
								$block->id,
								'icon',
								$icon
							),
						),
						$params['importer'],
						$params['post_id'] ?? null
					);
					if ( ! empty( $attachments_id ) ) {
						list( $image_url, $image_width, $image_height ) = wp_get_attachment_image_src( $attachments_id[0], 'thumbnail' );
						$url = $image_url;
					}
				}
				if ( ! empty( $url ) ) {
					$rich_text .= sprintf( '<img style="height: 24px; width: 24px; object-fit: cover; border-radius: 3px; vertical-align: middle; margin-right: 8px;" src="%s" alt=""/>', $url );
				}
			}
		}

		$rich_text .= $this->rich_text_parser->parse_rich_text( $block->callout->rich_text );

		return $this->parse_paragraph_block(
			$html,
			(object) array(
				'type'      => 'paragraph',
				'paragraph' => (object) array(
					'rich_text' => $rich_text,
					'color'     => $block->callout->color ?? null,
				),
			)
		);
	}

	/**
	 * Parse code block.
	 *
	 * @param string $html HTML.
	 * @param object $block Block.
	 *
	 * @return string
	 */
	public function parse_code_block( $html, $block ) {
		if ( ! isset( $block->code ) ) {
			return $html;
		}

		$block_html = $this->rich_text_parser->parse_rich_text( $block->code->rich_text, array( 'nl2br' => false ) );

		if ( ! empty( $block_html ) ) {
			$block_html = "<pre class=\"wp-block-code\"><code>$block_html</code></pre>";
			$html      .= $this->wrap_gut( $block_html, 'code' );
		}

		if ( isset( $block->code->caption ) ) {
			$html = $this->parse_paragraph_block(
				$html,
				(object) array(
					'type'      => 'paragraph',
					'paragraph' => (object) array(
						'rich_text' => $block->code->caption,
					),
				)
			);
		}

		return $html;
	}

	/**
	 * Parse toggle block.
	 *
	 * @param string $html HTML.
	 * @param object $block Block.
	 * @param array  $params Extra params.
	 *
	 * @return string
	 */
	public function parse_toggle_block( $html, $block, $params ) {
		if ( ! is_object( $block ) || 'toggle' !== $block->type ) {
			return $html;
		}

		$lvl   = $params['lvl'] ?? 1;
		$props = array();

		$block_html = '';
		if ( isset( $block->toggle->rich_text ) ) {
			$block_html = '<summary>' . $this->rich_text_parser->parse_rich_text( $block->toggle->rich_text ) . '</summary>';
		}

		$children = $block->has_children ? $block->children : array();

		// We currently support only 2 levels.
		if ( $lvl >= 2 ) {
			$children = array_filter(
				$children,
				function ( $block ) {
					return 'toggle' !== $block->type;
				}
			);
		}

		if ( count( $children ) > 0 ) {
			$block_html = $this->parse_blocks( $children, array_merge( $params, array( 'lvl' => $lvl + 1 ) ), $block_html );
			$block_html = rtrim( $block_html, "\n" );
		} else {
			$block_html .= '<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->';
		}

		if ( ! empty( $block_html ) ) {
			$block_html = $this->wrap_gut( "<details class=\"wp-block-details\">$block_html</details>", 'details', $props );
			if ( $lvl > 1 ) {
				$block_html .= "\n";
			}
		}

		return $html . $block_html;
	}


	/**
	 * Parse synced block.
	 *
	 * @param string $html HTML.
	 * @param object $block Block.
	 * @param array  $params Extra params.
	 *
	 * @return string
	 */
	public function parse_synced_block_block( $html, $block, $params ) {
		if ( ! isset( $block->children ) ) {
			return $html;
		}

		return $this->parse_blocks( $block->children, $params );
	}

	/**
	 * Parse embed block.
	 *
	 * @param string $html HTML.
	 * @param object $block Block.
	 *
	 * @return string
	 */
	public function parse_embed_block( $html, $block ) {
		if ( ! isset( $block->embed ) ) {
			return $html;
		}

		$block_props = $this->init_gut_props( $block->embed );
		$caption     = ! empty( $block->embed->caption ) ? $this->rich_text_parser->parse_rich_text( $block->embed->caption ) : '';

		$block_html = $this->embed( $block->embed->url, $block_props, $caption );

		return $html . $block_html;
	}

	/**
	 * Generates HTML embed code.
	 *
	 * @param string $url The URL to embed.
	 * @param array  $block_props Gutenberg block properties.
	 * @param string $caption Embed caption.
	 *
	 * @return string
	 */
	public function embed( $url, $block_props, $caption = '' ) {
		$request = new \WP_REST_Request( 'GET', '/oembed/1.0/proxy' );
		$request->set_query_params(
			array(
				'url' => $url,
			)
		);
		$oembed_proxy = new \WP_oEmbed_Controller(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- We need to call this to get the oembed proxy endpoint.
		$response     = $oembed_proxy->get_proxy_item( $request );
		$block_html   = '';
		if ( ! is_wp_error( $response ) ) {
			$block_props['url']              = $url;
			$block_props['type']             = $response->type;
			$block_props['providerNameSlug'] = sanitize_title( $response->provider_name );
			$block_props['responsive']       = true;

			$aspect_ratio_class_names = array();
			if ( preg_match( '`width="([0-9%]+)" height="([0-9]+)"`', $response->html, $width_height_matches ) ) {
				$aspect_ratio_class_names = $this->generate_aspect_ratio_class_names( $width_height_matches[1], $width_height_matches[2] );
				$previous_class_names     = $block_props['className'] ?? array();
				if ( 'rich' === $block_props['type'] ) {
					// Allow to move className prop to the end.
					unset( $block_props['className'] );
				}
				$block_props['className'] = array_merge( $previous_class_names, $aspect_ratio_class_names );
			}

			$block_html = sprintf(
				'<figure class="wp-block-embed is-type-%s is-provider-%s wp-block-embed-%s%s"><div class="wp-block-embed__wrapper">
%s
</div>%s</figure>',
				$block_props['type'],
				$block_props['providerNameSlug'],
				$block_props['providerNameSlug'],
				( ! empty( $aspect_ratio_class_names ) ? ' ' . implode( ' ', $aspect_ratio_class_names ) : '' ),
				$url,
				! empty( $caption ) ? sprintf( '<figcaption class="wp-element-caption">%s</figcaption>', $caption ) : ''
			);

			$block_html = $this->wrap_gut( $block_html, 'embed', $block_props, 'rich' === $block_props['type'] ? JSON_UNESCAPED_SLASHES : 0 );
		}

		return $block_html;
	}

	/**
	 * Returns class names list with "wp-has-aspect-ratio" and "wp-embed-aspect-..." if a supported aspect ratio is found.
	 *
	 * @param string|int $width Width from html attribute or width in pixels (int).
	 * @param string|int $height Height from html attribute or height in pixels (int).
	 *
	 * @return string[]
	 */
	public function generate_aspect_ratio_class_names( $width, $height ) {
		$class_names = array();
		if ( '100%' === $width ) {
			// Replicate bug from WordPress code...
			return array( 'wp-embed-aspect-21-9', 'wp-has-aspect-ratio' );
		} else {
			$width = (int) $width;
		}
		$height = (int) $height;

		if ( $height > 0 ) {
			// Based on https://github.com/WordPress/gutenberg/blob/9d8420f8cb6bced827c9879c810023aabc84eaef/packages/block-library/src/embed/util.js#L219.
			$aspect_ratio = round( $width / $height, 2 );
			// Given the actual aspect ratio, find the widest ratio to support it.
			$count_aspect_ratios = count( self::ASPECT_RATIOS );
			for ( $ratio_index = 0; $ratio_index < $count_aspect_ratios; $ratio_index++ ) {
				$potential_ratio = self::ASPECT_RATIOS[ $ratio_index ];
				if ( $aspect_ratio >= $potential_ratio['ratio'] ) {
					// Evaluate the difference between actual aspect ratio and closest match.
					// If the difference is too big, do not scale the embed according to aspect ratio.
					$ratio_diff = $aspect_ratio - $potential_ratio['ratio'];
					if ( $ratio_diff > 0.1 ) {
						break;
					}
					$class_names[] = $potential_ratio['className'];
					$class_names[] = 'wp-has-aspect-ratio';
					break;
				}
			}
		}

		return $class_names;
	}

	/**
	 * Add required comments around the generated HTML for the Gutenberg block to be valid.
	 *
	 * @param string $content HTML.
	 * @param string $block_name Gutenberg block name.
	 * @param array  $props Gutenberg block props.
	 * @param int    $json_flags Same param as https://www.php.net/manual/en/function.json-encode.php.
	 * @return string
	 */
	public function wrap_gut( $content, $block_name, $props = array(), $json_flags = 0 ) {
		if ( isset( $props['className'] ) && is_array( $props['className'] ) ) {
			$props['className'] = array_diff( $props['className'], array( 'has-background', 'has-text-color' ) );
			$props['className'] = implode( ' ', $props['className'] );
		}
		if ( empty( $props['className'] ) ) {
			unset( $props['className'] );
		}

		$wrapped = sprintf( '<!-- wp:%s ', $block_name );
		if ( ! empty( $props ) ) {
			$wrapped .= wp_json_encode( $props, $json_flags ) . ' ';
		}
		$wrapped .= '-->';
		$wrapped .= "\n";
		$wrapped .= $content;
		$wrapped .= sprintf( "\n<!-- /wp:%s -->\n", $block_name );

		return $wrapped;
	}

	/**
	 * Normalize Gutenberg block props from Notion block.
	 *
	 * @param object $block_value Notion block.
	 * @param array  $props Gutenberg block props.
	 *
	 * @return array
	 */
	public function init_gut_props( $block_value, $props = array() ) {
		$props['className'] = array();
		if ( isset( $block_value->color ) && is_string( $block_value->color ) ) {
			$color = $block_value->color;

			if ( strpos( $color, '_background' ) !== false ) {
				$props['className'][]                  = 'has-background';
				$props['style']['color']['background'] = $this->rich_text_parser->bgcolor_to_rgb( $color );
			} else {
				$props['className'][]            = 'has-text-color';
				$props['style']['color']['text'] = $this->rich_text_parser->color_to_rgb( $color );
			}
		}
		return $props;
	}

	/**
	 * Generate HTML attributes from Gutenberg props.
	 *
	 * @param array $props Gutenberg props.
	 *
	 * @return string
	 */
	public function generate_attributes_from_props( $props ) {
		$attributes = '';
		if ( ! empty( $props['className'] ) ) {
			$attributes .= sprintf( ' class="%s"', implode( ' ', $props['className'] ) );
		}
		if ( ! empty( $props['style'] ) ) {
			$styles = array();
			if ( isset( $props['style']['color'] ) ) {
				foreach ( $props['style']['color'] as $color_key => $color ) {
					$color_prop = 'text' === $color_key ? 'color' : 'background-color';
					$styles[]   = $color_prop . ': ' . $color;
				}
			}
			if ( ! empty( $styles ) ) {
				$attributes .= sprintf( ' style="%s"', implode( '; ', $styles ) );
			}
		}
		return $attributes;
	}

	/**
	 * Get page children id from block.
	 * Notion stores page children as "child_page" block type.
	 *
	 * @param array $blocks Notion blocks.
	 *
	 * @return array
	 */
	public function get_page_children_id( $blocks ) {
		$pages_id = array();
		if ( ! is_array( $blocks ) ) {
			return $pages_id;
		}
		foreach ( $blocks as $block ) {
			if ( 'child_page' === $block->type ) {
				$pages_id[] = $block->id;
			}
		}
		return $pages_id;
	}
}
