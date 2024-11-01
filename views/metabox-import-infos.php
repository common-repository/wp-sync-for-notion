<?php
/**
 * Display import infos and Sync button.
 *
 * @package Notion_Wp_Sync
 */

/**
 * Metabox import infos.
 *
 * @param Notion_WP_Sync_Importer|bool $importer
 * @param int $importer_id
 * @param boolean $importer_is_running
 * @param Notion_WP_Sync_Metabox_Import_Infos $metabox_import_infos
 */
return function ( $importer, $importer_id, $importer_is_running, $metabox_import_infos ) {
	?>
	<?php wp_nonce_field( 'notion-wp-sync-trigger-update', 'notion-wp-sync-trigger-update-nonce' ); ?>

<div id="notionwpsync-import">
	<button id="notionwpsync-import-button"
			type="button"
			class="button <?php echo esc_attr( $importer_is_running ? 'loading' : '' ); ?>"
			data-importer="<?php echo esc_attr( $importer_id ); ?>"
			x-bind:disabled="<?php echo ( 'publish' === get_post_status() ) ? 'configHasChanged()' : 'true'; ?>">
	<span class="dashicons dashicons-update"></span>
		<span class="label"><?php esc_html_e( 'Sync now', 'wp-sync-for-notion' ); ?></span>
	</button>
	<span id="notionwpsync-import-feedback"></span>
</div>
<button id="notionwpsync-cancel-button" type="button"><?php esc_html_e( 'Cancel', 'wp-sync-for-notion' ); ?></button>
<template x-if="<?php echo ( 'publish' === get_post_status() ) ? 'configHasChanged()' : 'true'; ?>">
	<p><?php esc_html_e( 'You will be able to sync your Notion content once you have saved this connection.', 'wp-sync-for-notion' ); ?></p>
</template>

<div id="notionwpsync-import-stats">
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $metabox_import_infos->get_stats_html( $importer_id );
	?>
</div>
	<?php
};

