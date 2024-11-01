<?php
/**
 * Display the destination options: post type, shortcode and post properties (status, author...).
 *
 * @package Notion_Wp_Sync
 */

/**
 * Metabox post settings view.
 *
 * @param array $post_types Supported post types.
 * @param array $post_stati List of post status.
 * @param array $post_authors List of post authors.
 *
 * @return void
 */
return function ( $post_types, $post_stati, $post_authors ) {
	// phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen, Squiz.PHP.EmbeddedPhp.ContentAfterEnd
	?>

<table class="form-table">
	<tr valign="top">
		<th scope="row">
			<label for="post_type">
				<span><?php esc_html_e( 'Destination', 'wp-sync-for-notion' ); ?></span>
				<span class="notionwpsync-tooltip" aria-label="<?php esc_attr_e( 'Import your Notion content as Post type (Article, Page or Custom Post Type) or as Shortcode to use is as a block (Pro version).', 'wp-sync-for-notion' ); ?>">?</span>
			</label>
		</th>
		<td>
			<select class="regular-text ltr" name="notionwpsync::post_type" x-model="config.post_type" x-init="config.post_type = config.post_type || $el.value;" @change="updateWordPressOptions();">
				<option value="post" :selected="config.post_type !== 'nwpsync-content'"><?php esc_html_e( 'Post type', 'wp-sync-for-notion' ); ?></option>
				<option value="nwpsync-content" :selected="config.post_type === 'nwpsync-content'" disabled><?php esc_html_e( 'Shortcode (Pro version)', 'wp-sync-for-notion' ); ?></option>
			</select>
		</td>
	</tr>
</table>

<!-- keep empty template below it fixes alpine js bug...?! -->
<template x-if="config.post_type !== 'nwpsync-content'"><div></div></template>
<template x-if="config.post_type !== 'nwpsync-content'">
	<table class="form-table">
		<tr valign="top">
			<th scope="row">
				<label for="post_type">
					<span><?php esc_html_e( 'Post Type', 'wp-sync-for-notion' ); ?></span>
					<span class="notionwpsync-tooltip" aria-label="<?php esc_attr_e( 'Choose the type of post in wich you want to import your Notion content: Articles, Pages, Custom Post Types already created or create a new Custom Post Type for your needs.', 'wp-sync-for-notion' ); ?>">?</span>
				</label>
			</th>
			<td>
				<select class="regular-text ltr" name="notionwpsync::post_type" x-model="config.post_type"
						x-init="config.post_type = config.post_type || $el.value;">
					<?php foreach ( $post_types as $post_type ) : ?>
						<option
							value="<?php echo esc_attr( $post_type['value'] ); ?>" <?php echo ! $post_type['enabled'] ? 'disabled="disabled"' : ''; ?>><?php echo esc_html( $post_type['label'] ); ?></option>
					<?php endforeach; ?>
				</select>


				<template x-if="config.post_type === 'custom'">
					<div class="notionwpsync-field-subgroup">
						<div class="notionwpsync-field form-required">
							<label for="post_type_name">
								<span><?php esc_html_e( 'Name', 'wp-sync-for-notion' ); ?></span>
								<span class="notionwpsync-required" aria-hidden="true">*</span>
								<span class="screen-reader-text"><?php esc_html_e( '(required)', 'wp-sync-for-notion' ); ?></span>
							</label>
							<input 	x-model="config.post_type_name" type="text" name="post_type_name"
									class="regular-text ltr">
							<p class="description"><?php esc_html_e( 'The name of your Custom Post Type.', 'wp-sync-for-notion' ); ?></p>
						</div>
						<div :class="'notionwpsync-field form-required ' + getValidationCssClass('post_type_slug')">
							<label for="post_type_slug">
								<span><?php esc_html_e( 'Url Prefix', 'wp-sync-for-notion' ); ?></span>
								<span class="notionwpsync-required" aria-hidden="true">*</span>
								<span class="screen-reader-text"><?php esc_html_e( '(required)', 'wp-sync-for-notion' ); ?></span>
							</label>
							<input 	x-model="config.post_type_slug" type="text" name="post_type_slug"
									class="regular-text ltr">
							<template x-if="getValidationCssClass('post_type_slug') === 'form-invalid'">
								<p class="notionwpsync-validation-message"><?php esc_html_e( 'This slug is already in use, please choose another.', 'wp-sync-for-notion' ); ?></p>
							</template>
							<template
								x-if="getValidationCssClass('post_type_slug') === 'form-invalid form-invalid-character'">
								<p class="notionwpsync-validation-message"><?php esc_html_e( 'Only lowercase alphanumeric characters, dashes, and underscores are allowed.', 'wp-sync-for-notion' ); ?></p>
							</template>
							<p class="description">
							<?php
								echo wp_kses(
									/* translators: %s Home URL */
									sprintf( __( 'The prefix used in the URL structure as in <code>%s/<b>prefix/</b>post-name/</code>.', 'wp-sync-for-notion' ), home_url() ),
									array(
										'code' => array(),
										'b'    => array(),
									)
								);
							?>
							</p>
						</div>
					</div>
				</template>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row">
				<label for="post_status"><?php esc_html_e( 'Post Status', 'wp-sync-for-notion' ); ?></label>
			</th>
			<td>
				<select class="regular-text ltr" name="notionwpsync::post_status" x-model="config.post_status"
						x-init="config.post_status = config.post_status || $el.value;">
					<?php foreach ( $post_stati as $post_status ) : ?>
						<option
							value="<?php echo esc_attr( $post_status['value'] ); ?>" <?php echo ! $post_status['enabled'] ? 'disabled="disabled"' : ''; ?>><?php echo esc_html( $post_status['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>

		<tr valign="top">
			<th scope="row">
				<label for="post_author"><?php esc_html_e( 'Post Author', 'wp-sync-for-notion' ); ?></label>
			</th>
			<td>
				<select class="regular-text ltr" name="notionwpsync::post_author" x-model="config.post_author"
						x-init="config.post_author = config.post_author || $el.value;">
					<?php foreach ( $post_authors as $post_author ) : ?>
						<option
							value="<?php echo esc_attr( $post_author['value'] ); ?>" <?php echo ! $post_author['enabled'] ? 'disabled="disabled"' : ''; ?>><?php echo esc_html( $post_author['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
	</table>
</template>
	<?php
	// phpcs:enable
};
