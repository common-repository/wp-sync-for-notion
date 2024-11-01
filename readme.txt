=== WP Sync for Notion - Notion to WordPress ===
Author: WP connect
Author URI: https://wpconnect.co/
Contributors: wpconnectco, staurand
Tags: wpconnect, notion, wordpress, api, automation, nocode, synchronization, integration
Tested up to: 6.5
Requires PHP: 7.0
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect Notion and send data to WordPress with the WP Sync for Notion plugin!

== Description ==

With our Notion to WordPress integration, WP Sync for Notion, you’ll be able to create beautiful websites and manage content directly from Notion, the best digital workplace no code tool! Our plugin can export and sync any Notion database, page and content to your website, without Zapier or Make.
Create databases and pages with structured data into Notion: WP Sync for Notion will push and sync data to WordPress, swiftly!
[Pro Version](https://wpconnect.co/notion-wordpress-integration/?utm_source=wp-org&utm_medium=referral&utm_campaign=nwps&utm_content=link1) with multiple advanced features is available.

== Features ==

= Connect Notion databases and pages =
* Connect WordPress to Notion databases & pages (Databases only available in Pro Version)
* Set up and publish unlimited connections (Pro Version)

= Display Notion data in any post type =
* Display Notion data in WordPress in any post type ; Article, Page, Custom Post Types, with Post Status and Author (CPT only available in Pro Version)
* Map Notion database properties to WordPress fields : title, date, category, excerpt… and Custom Fields (Custom Fields only available in Pro Version)
* ACF (Advanced Custom Fields) support in Pro+ version

= Display your content as it is in Notion or customize it =
* Support of major Notion blocks for pages (Paragraph, Title, List, Table, Separator, Image...) but also columns and internal Properties of Notion pages (cover, icon, ...)
* Integrate easily your content with our CPT “Notion Content” (Pro Version only)
* Display it by using our dedicated Gutenberg block or use shortcodes for Divi, Elementor or any Page Builder (Pro Version only)

= Customize synchronization of your data =
* Trigger data sync automatically or manually (Limited in Free Version)
* Select update frequency or instantly via a webhook (Pro Version only)
* Set up synchronization method (add, update, delete)


== Installation ==

1. From your WordPress Dashboard, go to "Plugins > Add New".

2. Look for our plugin into the search bar: WP Sync for Notion.

3. Click on the 'Install Now' button of the plugin, and wait a few seconds.

4. Click on the "Activate" button (also available in "Plugins > Installed Plugins").

5. That's it, WP Sync for Notion is ready to use, find it in the sidebar!


== How to unleash your plugin's full potential? ==

1. Go to the WP Sync for Notion plugin page, click on “Add New” next to “Connections”.

2. Choose a name for your new connection.

3. Enter your Internal integration token (Available in your Notion's integrations [here](https://www.notion.so/my-integrations)).

4. Choose the source of your data. From one or more pages. You also have the possibility to include children's pages.

5. Select the destination of your content.

6. Link the Notion fields with WordPress fields.

7. Choose the Sync Settings (Strategy and Trigger).

8. Publish the connection, and you’re done!

9. Tip: By pressing the "Sync Now" button, you can synchronize your contents for the first time (even if you didn’t choose “Manual only” trigger).


== Frequently Asked Questions ==

= What is Notion? =
Claiming 20 million users worldwide, [Notion.so](http://notion.so/) is an all-in-one digital workplace. It combines various collaborative tools for note-taking, task management, project management (around a kanban board) or even storage and exchange of documents.

= Why do I need a Notion account? =
WP Sync for Notion uses Notion’s API to send data. Creating an account on Notion is free. Once logged in your contact, you can create and get the Internal Integration Token [from this page](https://www.notion.so/my-integrations) (don’t forget to share it with your pages).

= Can I use the plugin with a free Notion plan? =
Yes, [Notion.so](http://notion.so/) offers a free plan, called Notion Individual. It targets small teams of up to 6 people. Allowing the creation of an unlimited number of pages and blocks, Notion Individual gives access to the platform's API.
Depending on your needs, several paid subscriptions allow you to unlock these limitations while giving access to more advanced features ([see prices](https://www.notion.so/fr-fr/pricing)).

= How are my pages synchronized? =
Once you have defined the synchronization frequency and published your connection, relax: everything is automatic. It is also possible to manually synchronize the connection - whenever you want - using the 'Sync Now' button.

= What's the difference between WP Sync for Notion and Notion WP Sync Pro & Notion WP Sync Pro+? =
With WP Sync for Notion, you can effortlessly manage and update your Notion content directly from your WordPress site, while benefiting from extended functionalities to customize and optimize your publishing experience.
Our WP Sync for Notion plugin allows you to synchronize data from your Notion pages to WordPress.
The [Pro Version](https://wpconnect.co/notion-wordpress-integration/?utm_source=wp-org&utm_medium=referral&utm_campaign=nwps&utm_content=link2) enables unlimited connections and database synchronization.
The [Pro+ Version](https://wpconnect.co/notion-wordpress-integration/?utm_source=wp-org&utm_medium=referral&utm_campaign=nwps&utm_content=link3) includes numerous additional features, such as ACF and Yoast SEO support, and upcoming compatibility with other SEO plugins, Pods, and Meta Box.

= I can't see my pages =
To access your data, be sure to share your integration with your pages. To make sure you have shared the connection, follow [these instructions](https://www.notion.so/help/add-and-manage-connections-with-the-api#add-connections-to-pages).

= How can I get support? =
If you need some assistance, open a ticket on the [Support](https://wordpress.org/support/plugin/wp-sync-for-notion/).


== Screenshots ==
1. Edit connection
2. Field Mapping
3. Configure synchronization
4. Notion content block & Shortcode


== Changelog ==

= 1.5.0 =
* Added: support for Embed block
* Fix: missing styles in content (like background color)
* Added: support for Yoast Duplicate Post for connections

= 1.4.0 =
* Added: support for Notion toggle block
* Fix can‘t select more than one page
* Fix emoji for database without utf8mb4 support

= 1.3.0 =
* WordPress 6.4.1 compatibility

= 1.2.1 =
* New branding

= 1.2.0 =
* WordPress 6.3.1 compatibility
* Fix background color

= 1.1.0 =
* Feature: Add Action Scheduler to handle large imports
* Improvement: show sync progress and keep state when reloading
* Improvement: add cancel sync button
* Improvement: remove use of getmypid() function
* Fix page search

= 1.0.1 =
* WordPress 6.2 compatibility

= 1.0.0 =
Initial release


== Support ==
If you need some assistance, open a ticket on the [Support](https://wordpress.org/support/plugin/wp-sync-for-notion/).


== Troubleshooting ==
If you don't see your pages, make sure you have shared it with your integration.
If needed, you can access to logs from a FTP server in this folder: /wp-content/uploads/notionwpsync-logs
