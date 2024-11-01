<?php
/**
 * Manages admin connections pages.
 *
 * @package Notion_Wp_Sync
 */

namespace Notion_Wp_Sync;

/**
 * Admin Connection
 */
class Notion_WP_Sync_Admin_Connection {
	/**
	 * List of available importers
	 *
	 * @var Notion_WP_Sync_Importer[]
	 */
	protected $importers = array();

	/**
	 * Whether we should display max connection notice
	 *
	 * @var bool
	 */
	protected $display_max_connection = false;


	/**
	 * Constructor
	 *
	 * @param Notion_WP_Sync_Importer[] $importers Importers.
	 */
	public function __construct( $importers ) {
		$this->importers = $importers;

		$this->add_meta_boxes();

		add_action( 'edit_form_top', array( $this, 'add_header' ) );
		add_action( 'dbx_post_sidebar', array( $this, 'add_footer' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_styles_scripts' ) );
		add_filter( 'script_loader_tag', array( $this, 'add_alpine_defer_attribute' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
		add_action( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 10, 2 );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ), 10, 2 );
		add_filter( 'redirect_post_location', array( $this, 'redirect_post_location' ), 10, 1 );
		add_action( 'admin_notices', array( $this, 'admin_notices' ), 10 );
	}

	/**
	 * Output wrapper opening for alpinejs x-data
	 *
	 * @param WP_Post $post Post object.
	 */
	public function add_header( $post ) {
		if ( 'nwpsync-connection' === get_post_type() ) {
			$config = json_decode( $post->post_content );
			if ( $config ) {
				$config->synchronized = ! ! get_post_meta( $post->ID, 'last_updated', true );
			}
			$json = $config ? wp_json_encode( $config ) : '{}';
			// Json output is already encoded.
			// phpcs:ignore
			echo '<script>window.notionwpsyncImporterData = ' . $json . ';</script>';
			echo '<div id="notionwpsync-alpine-container" x-data="notionWpSyncSettingsHandler">';
			echo '<div id="notionwpsync-validation-notice" class="notice notice-error" style="display:none"><p><strong>' . esc_html__( 'Error:', 'wp-sync-for-notion' ) . '</strong> ' . esc_html__( 'Some required fields are missing.', 'wp-sync-for-notion' ) . '</p></div>';
		}
	}

	/**
	 * Output wrapper closing for alpinejs x-data
	 */
	public function add_footer() {
		if ( 'nwpsync-connection' === get_post_type() ) {
			echo '</div>';
		}
	}

	/**
	 * Register admin styles and scripts
	 */
	public function register_styles_scripts() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'nwpsync-connection' === $screen->id ) {
			wp_enqueue_script( 'notion-wp-sync-alpine', plugins_url( 'assets/js/alpinejs@3.10.2.min.js', NOTION_WP_SYNC_PLUGIN_FILE ), false, NOTION_WP_SYNC_VERSION, false );
			wp_enqueue_script( 'notion-wp-sync-sortable', plugins_url( 'assets/js/Sortable.min.js', NOTION_WP_SYNC_PLUGIN_FILE ), false, NOTION_WP_SYNC_VERSION, false );
			wp_enqueue_script( 'notion-wp-sync-select2', plugins_url( 'assets/js/select2/select2.full.min.js', NOTION_WP_SYNC_PLUGIN_FILE ), false, NOTION_WP_SYNC_VERSION, false );
			wp_enqueue_script( 'notion-wp-sync-admin', plugins_url( 'assets/js/admin-page.js', NOTION_WP_SYNC_PLUGIN_FILE ), array( 'notion-wp-sync-alpine', 'notion-wp-sync-sortable', 'notion-wp-sync-select2', 'jquery-ui-tooltip', 'wp-i18n' ), NOTION_WP_SYNC_VERSION, false );
			wp_enqueue_script( 'notion-wp-sync-admin-metabox-mapping', plugins_url( 'assets/js/metabox-mapping/main.js', NOTION_WP_SYNC_PLUGIN_FILE ), array( 'notion-wp-sync-admin' ), NOTION_WP_SYNC_VERSION, false );
			wp_add_inline_script( 'notion-wp-sync-admin', 'var notionWpSync = ' . $this->get_json_config(), 'before' );
			wp_localize_script(
				'notion-wp-sync-admin',
				'notionWpSyncI18n',
				array(
					'deleteActionConfirmation' => __( 'You have a Custom Post Type declared using this connection. Are you sure to delete it?', 'wp-sync-for-notion' ),
					'startingUpdate'           => __( 'In progress...', 'wp-sync-for-notion' ),
					'canceling'                => __( 'Canceling...', 'wp-sync-for-notion' ),
				)
			);
		}
	}

	/**
	 * Add defer attribute to AlpineJS script.
	 *
	 * @param string $tag    The `<script>` tag for the enqueued script.
	 * @param string $handle The script's registered handle.
	 *
	 * @return string
	 */
	public function add_alpine_defer_attribute( $tag, $handle ) {
		if ( 'notion-wp-sync-alpine' === $handle ) {
			$tag = str_replace( ' src', ' defer="defer" src', $tag );
		}
		return $tag;
	}

	/**
	 * Add metaboxes for importer post type
	 */
	public function add_meta_boxes() {
		new Notion_WP_Sync_Metabox_Global_Settings();
		new Notion_WP_Sync_Metabox_Post_Settings( $this->importers );
		new Notion_WP_Sync_Metabox_Field_Mapping();
		new Notion_WP_Sync_Metabox_Sync_Settings( $this->importers );
		new Notion_WP_Sync_Metabox_Import_Infos( $this->importers );
	}

	/**
	 * After importer is saved
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 */
	public function save_post( $post_id, $post, $update ) {
		if ( ! $update || wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || 'nwpsync-connection' !== $post->post_type ) {
			return;
		}

		// FLush rewrite rules.
		delete_option( 'rewrite_rules' );

		// Clear schedule.
		wp_clear_scheduled_hook( 'notion_wp_sync_importer_' . $post_id );
	}

	/**
	 * Before importer is saved
	 *
	 * @param array $data                An array of slashed, sanitized, and processed post data.
	 * @param array $postarr             An array of sanitized (and slashed) but otherwise unmodified post data.
	 */
	public function wp_insert_post_data( $data, $postarr ) {
		if ( 'nwpsync-connection' !== $postarr['post_type'] ) {
			return $data;
		}

		// Limit active importers.
		$post_id = $postarr['ID'] ?? 0;

		if ( ! isset( $data['post_status'] ) || 'publish' === $data['post_status'] ) {
			$importers = get_posts(
				array(
					'post_type'      => 'nwpsync-connection',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'post__not_in'   => array( $post_id ),
				)
			);
			if ( count( $importers ) > 0 ) {
				$data['post_status']          = 'draft';
				$this->display_max_connection = true;
			}
		}

		// Update slug.
		if ( array_key_exists( 'post_title', $data ) ) {
			$data['post_name'] = sanitize_title( $data['post_title'] );
		}

		return $data;
	}

	/**
	 * Customize connections update messages.
	 *
	 * @param array[] $messages Post updated messages. For defaults see `$messages` declarations above.
	 */
	public function post_updated_messages( $messages ) {
		$back_link_html = sprintf(
			' <a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'edit.php?post_type=nwpsync-connection' ) ),
			__( 'Back to list', 'wp-sync-for-notion' )
		);

		$messages['nwpsync-connection'] = array_replace(
			$messages['post'],
			array(
				0  => '', // Unused. Messages start at index 1.
				1  => __( 'Connection updated.', 'wp-sync-for-notion' ) . $back_link_html,
				4  => __( 'Connection updated.', 'wp-sync-for-notion' ),
				6  => __( 'Connection published.', 'wp-sync-for-notion' ),
				7  => __( 'Connection saved.', 'wp-sync-for-notion' ),
				10 => __( 'Connection draft updated.', 'wp-sync-for-notion' ),
			)
		);

		return $messages;
	}

	/**
	 * Customize connections bulk update messages
	 *
	 * @param array[] $messages Arrays of messages, each keyed by the corresponding post type. Messages are
	 *                               keyed with 'updated', 'locked', 'deleted', 'trashed', and 'untrashed'.
	 * @param int[]   $bulk_counts   Array of item counts for each message, used to build internationalized strings.
	 */
	public function bulk_post_updated_messages( $messages, $bulk_counts ) {
		$messages['nwpsync-connection'] = array_replace(
			$messages['post'],
			array(
				/* translators: %s: Number of connections. */
				'updated'   => _n( '%s connection updated.', '%s connections updated.', $bulk_counts['updated'], 'wp-sync-for-notion' ),
				'locked'    => ( 1 === $bulk_counts['locked'] ) ? __( '1 connection not updated, somebody is editing it.', 'wp-sync-for-notion' ) :
								/* translators: %s: Number of connections. */
								_n( '%s connection not updated, somebody is editing it.', '%s connections not updated, somebody is editing them.', $bulk_counts['locked'], 'wp-sync-for-notion' ),
				/* translators: %s: Number of connections. */
				'deleted'   => _n( '%s connection permanently deleted.', '%s connections permanently deleted.', $bulk_counts['deleted'], 'wp-sync-for-notion' ),
				/* translators: %s: Number of connections. */
				'trashed'   => _n( '%s connection moved to the Trash.', '%s connections moved to the Trash.', $bulk_counts['trashed'], 'wp-sync-for-notion' ),
				/* translators: %s: Number of connections. */
				'untrashed' => _n( '%s connection restored from the Trash.', '%s connections restored from the Trash.', $bulk_counts['untrashed'], 'wp-sync-for-notion' ),
			)
		);

		return $messages;
	}

	/**
	 * Add display max connection parameter to location url
	 *
	 * @param string $location The destination URL.
	 */
	public function redirect_post_location( $location ) {
		if ( $this->display_max_connection ) {
			$location = add_query_arg( 'notion_wp_sync_display_max_connection', 1, $location );
			$location = remove_query_arg( 'message', $location );
		}
		return $location;
	}

	/**
	 * Display max connection message if parameter was added
	 */
	public function admin_notices() {
		// phpcs:ignore
		if ( ! empty( $_GET['notion_wp_sync_display_max_connection'] ) && '1' === $_GET['notion_wp_sync_display_max_connection'] ) {
			printf(
				'<div class="%1$s"><p>%2$s</p></div>',
				esc_attr( 'notice notice-error' ),
				wp_kses(
					__( 'Thank you for using the Free Version of our plugin! You already have an active connection. To be able to create as many active connections as you want, <a href="https://wpconnect.co/notion-wordpress-integration/#pricing-plan" target="_blank">Upgrade to Pro Version</a>.', 'wp-sync-for-notion' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				)
			);
		}
	}


	/**
	 * Get Json config
	 */
	protected function get_json_config() {
		$config = array(
			'reservedCptSlugs'   => $this->get_reserved_cpt_slugs(),
			'mappingOptions'     => $this->get_mapping_options(),
			'featuresByPostType' => $this->get_features_by_post_type(),
		);

		return wp_json_encode( $config );
	}

	/**
	 * Get reserved CPT slugs
	 */
	protected function get_reserved_cpt_slugs() {
		return array_values( get_post_types() );
	}

	/**
	 * Get mapping options
	 */
	protected function get_mapping_options() {
		$fields = apply_filters( 'notionwpsync/get_wp_fields', array() );
		foreach ( $fields as &$field ) {
			foreach ( $field['options'] as &$option ) {
				if ( isset( $option['supported_value_types'] ) ) {
					$option['supported_sources'] = Notion_WP_Sync_Field_Factory::get_field_types( $option['supported_value_types'] );
				} else {
					$option['supported_sources'] = array();
				}
			}
		}
		return $fields;
	}

	/**
	 * Get available features by post type
	 */
	protected function get_features_by_post_type() {
		$features = array();
		foreach ( Notion_WP_Sync_Helpers::get_post_types() as $post_type ) {
			$features[ $post_type['value'] ] = apply_filters(
				'notionwpsync/features_by_post_type',
				array(),
				$post_type['value']
			);
		}
		// Default features for custom post type.
		$features['custom'] = array(
			'post' => array(
				'post_name',
				'post_date',
				'post_title',
				'post_excerpt',
				'post_content',
			),
			'meta' => array(
				'_thumbnail_id',
				'custom_field',
			),
		);

		// Default features for nwpsync-content post type.
		$features['nwpsync-content'] = array(
			'post' => array(
				'post_title',
				'post_content',
			),
		);
		return $features;
	}
}
