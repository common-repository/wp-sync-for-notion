<?php
/**
 * Display the sync strategy options: manual, recurring...
 *
 * @package Notion_Wp_Sync
 */

/**
 * Metabox sync view.
 *
 * @param array $sync_strategies Sync strategies (manual, recurring...)
 * @param array $schedules Frequency schedules
 * @param string|boolean $webhook_url The webhook URL or false if the connection has not been saved yet.
 */
return function ( $sync_strategies, $schedules, $webhook_url ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	?>

<table class="form-table">
	<tr valign="top">
		<th scope="row">
			<label for="sync_strategy">
				<span><?php esc_html_e( 'Strategy', 'wp-sync-for-notion' ); ?></span>
				<span class="notionwpsync-tooltip" aria-label="<?php esc_attr_e( 'Select the method to synchronize your Notion content with WordPress.<br><br><strong>Add:</strong> only adds new content.<br><br><strong>Add & Update:</strong> same + updates content from modified records.<br><br><strong>Add, Update & Delete:</strong> same + deletes content that is no longer in Notion.', 'wp-sync-for-notion' ); ?>">?</span>
			</label>
		</th>
		<td>
			<select class="regular-text ltr" name="notionwpsync::sync_strategy" x-model="config.sync_strategy" x-init="config.sync_strategy = config.sync_strategy || $el.value;">
			<?php foreach ( $sync_strategies as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
			</select>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">
			<label for="sync_type"><?php esc_html_e( 'Trigger', 'wp-sync-for-notion' ); ?></label>
		</th>
		<td>
			<fieldset class="notionwpsync-radiogroup">
				<label>
					<input name="notionwpsync::scheduled_sync::type" type="radio" value="manual" x-model="config.scheduled_sync.type" />
					<span><?php esc_html_e( 'Manual only', 'wp-sync-for-notion' ); ?></span>
					<span class="notionwpsync-tooltip" aria-label="<?php esc_attr_e( 'Disables automatic synchronization of your Notion content. You can still do it manually by clicking on the \'Sync Now\' button in the side panel.', 'wp-sync-for-notion' ); ?>">?</span>
				</label>

				<label>
					<input name="notionwpsync::scheduled_sync::type" type="radio" value="cron" x-model="config.scheduled_sync.type" />
					<span><?php esc_html_e( 'Recurring', 'wp-sync-for-notion' ); ?></span>
					<span class="notionwpsync-tooltip" aria-label="<?php esc_attr_e( 'Enables recurring synchronization of your Notion content. Choose a frequency and it will take effect from the date and time the connection is updated.', 'wp-sync-for-notion' ); ?>">?</span>
				</label>
				<div class="notionwpsync-field" x-show="config.scheduled_sync.type === 'cron'">
					<label for="recurrence"><?php esc_html_e( 'Frequency', 'wp-sync-for-notion' ); ?></label>
					<select class="regular-text ltr" name="notionwpsync::scheduled_sync::recurrence" type="text" x-model="config.scheduled_sync.recurrence" x-init="config.scheduled_sync.recurrence = config.scheduled_sync.recurrence || $el.value;">
						<?php foreach ( $schedules as $schedule ) : ?>
							<option value="<?php echo esc_attr( $schedule['value'] ); ?>" <?php echo ! $schedule['enabled'] ? 'disabled="disabled"' : ''; ?>><?php echo esc_html( $schedule['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description">
					<?php
					echo wp_kses(
						__( 'We recommend <a href="https://developer.wordpress.org/plugins/cron/scheduling-wp-cron-events/" target="_blank">setting up WP-Cron</a> as a cron job for better performance.', 'wp-sync-for-notion' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
							),
						)
					);
					?>
					</p>
				</div>
				<div class="notionwpsync-field-group notionwpsync-field-group-inline" x-show="config.scheduled_sync.type === 'cron'">
					<div class="notionwpsync-field" x-show="config.scheduled_sync.type === 'cron' && config.scheduled_sync.recurrence === 'weekly'">
						<label for="notionwpsync::scheduled_sync::weekday"><?php esc_html_e( 'Day of week', 'wp-sync-for-notion' ); ?></label>
						<select class="regular-text ltr" name="notionwpsync::scheduled_sync::weekday" type="text" x-model="config.scheduled_sync.weekday" x-init="config.scheduled_sync.weekday = config.scheduled_sync.weekday || $el.value;">
							<option value=""></option>
							<option value="monday"><?php esc_html_e( 'Monday', 'wp-sync-for-notion' ); ?></option>
							<option value="tuesday"><?php esc_html_e( 'Tuesday', 'wp-sync-for-notion' ); ?></option>
							<option value="wednesday"><?php esc_html_e( 'Wednesday', 'wp-sync-for-notion' ); ?></option>
							<option value="thursday"><?php esc_html_e( 'Thursday', 'wp-sync-for-notion' ); ?></option>
							<option value="friday"><?php esc_html_e( 'Friday', 'wp-sync-for-notion' ); ?></option>
							<option value="saturday"><?php esc_html_e( 'Saturday', 'wp-sync-for-notion' ); ?></option>
							<option value="sunday"><?php esc_html_e( 'Sunday', 'wp-sync-for-notion' ); ?></option>
						</select>
					</div>
					<div class="notionwpsync-field" x-show="config.scheduled_sync.type === 'cron' && Array('weekly', 'daily').indexOf(config.scheduled_sync.recurrence) > -1">
						<label for="notionwpsync::scheduled_sync::time"><?php esc_html_e( 'Time', 'wp-sync-for-notion' ); ?></label>
						<input type="time" name="notionwpsync::scheduled_sync::time" x-model="config.scheduled_sync.time"/>
					</div>
				</div>


				<label class="notionwpsync-disabled">
					<input name="notionwpsync::scheduled_sync::type" type="radio" value="instant" x-model="config.scheduled_sync.type" disabled />
					<span><?php esc_html_e( 'Instant via webhook (Pro version)', 'wp-sync-for-notion' ); ?></span>
					<span class="notionwpsync-tooltip" aria-label="<?php esc_attr_e( 'Enables instant synchronization of your Notion content. Using webhooks, choose your own trigger and the connection will be updated as soon as the chosen event occurs.', 'wp-sync-for-notion' ); ?>">?</span>
				</label>


			</fieldset>
		</td>
	</tr>
</table>
	<?php
};
