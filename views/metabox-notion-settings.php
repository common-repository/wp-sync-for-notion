<?php
/**
 * Display Notion settings options: API Key, database / page selection and filters / page scope.
 *
 * @package Notion_Wp_Sync
 */

/**
 * Metabox Notion settings view.
 *
 * @param array $objects Notions objects selected (database or pages)
 */
return function ( $objects ) {
	?>

<table class="form-table">
	<?php wp_nonce_field( 'notion-wp-sync-ajax', 'notion-wp-sync-ajax-nonce' ); ?>
	<tr valign="top">
		<th scope="row">
			<label for="api_key">
				<span><?php esc_html_e( 'Internal integration token', 'wp-sync-for-notion' ); ?></span>
				<span class="notionwpsync-required" aria-hidden="true">*</span>
				<span class="screen-reader-text"><?php esc_html_e( '(required)', 'wp-sync-for-notion' ); ?></span>
				<span class="notionwpsync-tooltip" aria-label="
				<?php
					echo esc_attr(
						wp_kses(
							__( 'To get your secret token and create an internal integration, please follow <a href="https://www.notion.so/help/create-integrations-with-the-notion-api#create-an-internal-integration" target="_blank">these instructions.</a>', 'wp-sync-for-notion' ),
							array(
								'a' => array(
									'href'   => array(),
									'target' => array(),
								),
							)
						)
					);
				?>
				">?</span>
			</label>
		</th>
		<td>
			<div :class="'notionwpsync-field form-required ' + getValidationCssClass('apiKey')">
				<input class="regular-text ltr" name="api_key" type="text" x-model="config.api_key" x-on:change="showNoticeHandler('connection-warning')"/>
				<p x-show="validation.apiKey && !validation.apiKey.valid" x-html="validation.apiKey && validation.apiKey.message"></p>
			</div>
		</td>
	</tr>
	<tr x-cloak x-show="config.api_key && config.notices['connection-warning'] && !hideNoticeTemp['connection-warning'] ">
		<td colspan="2">
			<div class="notice notice-warning inline notionwpsync-connection-warning">
				<p>
				<?php
					echo wp_kses(
						__( 'Have you added the connection you just created to the Notion pages you want to import? If not, you may not see your pages in the following fields. To make sure you have shared the connection, follow <a href="https://www.notion.so/help/add-and-manage-connections-with-the-api#add-connections-to-pages" target="_blank">these instructions.</a>', 'wp-sync-for-notion' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
							),
						)
					);
				?>
				</p>
				<button @click="hideNoticeHandler('connection-warning')" class="button button-primary" type="button"><?php esc_html_e( 'Yes, it’s already done!', 'wp-sync-for-notion' ); ?></button><button @click="tempHideNoticeHandler('connection-warning')" class="button" type="button"><?php esc_html_e( 'Remind me later', 'wp-sync-for-notion' ); ?></button>
			</div>
		</td>
	</tr>
	<tr valign="top" x-show="config.api_key">
		<th scope="row">
			<label for="objects_id">
				<span><?php esc_html_e( 'Choose', 'wp-sync-for-notion' ); ?></span>
				<span class="notionwpsync-required" aria-hidden="true">*</span>
				<span class="screen-reader-text"><?php esc_html_e( '(required)', 'wp-sync-for-notion' ); ?></span>
				<span class="notionwpsync-tooltip" aria-label="<?php esc_attr_e( 'Select a database or pages from the select boxes.', 'wp-sync-for-notion' ); ?>">?</span>
			</label>
		</th>
		<td>
			<div class="notionwpsync-field form-required notionwpsync-object-choices">
				<div class="notionwpsync-object-choice">
					<label for="page_objects_id">
						<span><?php esc_html_e( 'one or more pages', 'wp-sync-for-notion' ); ?></span>
						<span class="notionwpsync-tooltip" aria-label="<?php echo esc_attr__( 'Select one ore more pages to be synchronized.', 'wp-sync-for-notion' ); ?>">?</span>
					</label>
					<select id="page_objects_id" x-cloak class="regular-text ltr" multiple >
						<?php foreach ( $objects['page'] as $object_id => $object ) : ?>
							<option value="<?php echo esc_attr( $object_id ); ?>" selected="selected" data-full-object="<?php echo esc_attr( wp_json_encode( $object ) ); ?>"><?php echo esc_html( $object->get_name() ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="notionwpsync-object-choice-or"><?php esc_html_e( 'or', 'wp-sync-for-notion' ); ?></div>
				<div class="notionwpsync-object-choice notionwpsync-disabled">
					<label for="database_objects_id">
						<span><?php esc_html_e( 'a database (Pro version)', 'wp-sync-for-notion' ); ?></span>
						<span class="notionwpsync-tooltip" aria-label="<?php echo esc_attr__( 'Only one database can be selected for the mapping to work.', 'wp-sync-for-notion' ); ?>">?</span>
					</label>
					<select id="database_objects_id" x-cloak class="regular-text ltr" multiple>
						<?php foreach ( $objects['database'] as $object_id => $object ) : ?>
							<option value="<?php echo esc_attr( $object_id ); ?>" selected="selected" data-full-object="<?php echo esc_attr( wp_json_encode( $object ) ); ?>"><?php echo esc_html( $object->get_name() ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>


				<select id="objects_id" name="objects_id[]" x-model="config.objects_id" class="screen-reader-text" multiple>
					<template x-for="object_id in config.objects_id" :key="object_id">
						<option :value="object_id" :selected="true" x-html="object_index[object_id] ? object_index[object_id].name : ''"></option>
					</template>
				</select>

			</div>
		</td>
	</tr>
	<template x-if="config.object_type === 'page'">
		<tr valign="top">
			<th scope="row">
				<label>
					<span><?php esc_html_e( 'Include children’s pages', 'wp-sync-for-notion' ); ?></span>
					<span class="notionwpsync-tooltip" aria-label="<?php echo esc_attr__( 'During the synchronization, the children linked to the page you have selected will be imported as well.', 'wp-sync-for-notion' ); ?>">?</span>
				</label>
			</th>
			<td class="notionwpsync-page-scope">
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'Include children’s pages', 'wp-sync-for-notion' ); ?></legend>
					<div><input type="radio" id="page_scope_includes_children" name="page_scope" value="includes_children" x-model="config.page_scope"><label for="page_scope_includes_children"><?php esc_html_e( 'Yes', 'wp-sync-for-notion' ); ?></label></div>
					<div><input type="radio" id="page_scope_no" name="page_scope" value="no" x-model="config.page_scope"><label for="page_scope_no"><?php esc_html_e( 'No', 'wp-sync-for-notion' ); ?></label></div>
				</fieldset>
			</td>
		</tr>
	</template>

</table>
<input type="hidden" name="content" :value="JSON.stringify(config)"/>
	<?php
};
