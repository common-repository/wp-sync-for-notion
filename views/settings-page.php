<?php
/**
 * Display the plugin settings page.
 *
 * @package Notion_Wp_Sync
 */

/**
 * Plugin settings page.
 *
 * @param string $license_key License key.
 * @param string $license_status License status.
 */
return function ( $license_key, $license_status ) {
	?>
<div class="wrap">

	<h2><?php esc_html_e( 'Settings', 'wp-sync-for-notion' ); ?></h2>

	<form method="post">

		<?php wp_nonce_field( 'notion-wp-sync-settings-form' ); ?>

		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="license_key"><?php esc_html_e( 'License Key', 'wp-sync-for-notion' ); ?></label>
				</th>
				<td>
					<div>
						<input class="regular-text ltr"
							type="text"
							name="license_key"
							value="<?php echo esc_attr( $license_key ); ?>" />
						<?php if ( 'valid' === $license_status ) : ?>
							<button name="notion-wp-sync-license-deactivate" class="button notionwpsync-button-delete"><?php esc_html_e( 'De-activate', 'wp-sync-for-notion' ); ?></button>
							<p class="description notionwpsync-valid"><?php esc_html_e( 'Your license is valid and activated.', 'wp-sync-for-notion' ); ?></p>
						<?php else : ?>
							<button name="notion-wp-sync-license-activate" class="button notionwpsync-button-success"><?php esc_html_e( 'Activate', 'wp-sync-for-notion' ); ?></button>
						<?php endif; ?>
					</div>
				</td>
			</tr>
		</table>
		<div id="poststuff"></div>

		<p class="submit">
			<input	class="button button-primary"
					type="submit"
					name="notion-wp-sync-settings-update"
					value="<?php esc_attr_e( 'Update settings', 'wp-sync-for-notion' ); ?>"/>
		</p>
	</form>
</div>
	<?php
};
