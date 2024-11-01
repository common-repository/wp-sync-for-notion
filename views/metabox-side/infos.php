<?php
/**
 * Display connection state: status, last error, last updated date time, next sync.
 *
 * @package Notion_Wp_Sync
 */

/**
 * Connection state infos.
 *
 * @param string $status Status.
 * @param string $last_error Last error.
 * @param int|string $last_updated Last updated date.
 * @param int|string $next_sync Next sync.
 * @param string $status_class Status class.
 */
return function ( $status, $last_error, $last_updated, $next_sync, $status_class ) {
	?>
	<h4><?php esc_html_e( 'Last Sync Status', 'wp-sync-for-notion' ); ?></h4>

	<p class="<?php echo esc_attr( $status_class ); ?>">
		<?php if ( 'success' === $status ) : ?>
			<?php esc_html_e( 'Successful!', 'wp-sync-for-notion' ); ?>
		<?php elseif ( 'error' === $status ) : ?>
			<?php esc_html_e( 'Error', 'wp-sync-for-notion' ); ?>
		<?php else : ?>
			--
		<?php endif; ?>
	</p>

	<?php if ( 'error' === $status && $last_error ) : ?>
		<p class="notionwpsync-last-error"><?php echo esc_html( $last_error ); ?></p>
	<?php endif; ?>

	<h4><?php esc_html_e( 'Last Sync', 'wp-sync-for-notion' ); ?></h4>
	<p>
		<?php if ( $last_updated ) : ?>
			<?php echo esc_html( \Notion_Wp_Sync\Notion_WP_Sync_Helpers::get_formatted_date_time( $last_updated ) ); ?>
		<?php else : ?>
			--
		<?php endif; ?>
	</p>
	<template x-if="config.scheduled_sync.type === 'cron'">
		<div>
			<h4><?php esc_html_e( 'Scheduled Next Sync', 'wp-sync-for-notion' ); ?></h4>
			<p>
				<?php if ( $next_sync ) : ?>
					<?php echo esc_html( \Notion_Wp_Sync\Notion_WP_Sync_Helpers::get_formatted_date_time( $next_sync ) ); ?>
				<?php else : ?>
					--
				<?php endif; ?>
			</p>
		</div>
	</template>
	<?php if ( $last_updated ) : ?>
	<script>
		jQuery(document).trigger('notionwpsync/synchronized');
	</script>
		<?php
	endif;
};
