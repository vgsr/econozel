=== Econozel ===
Contributors: Offereins
Tags: vgsr, econozel, magazine, article, edition, volume
Requires at least: 4.4
Tested up to: 4.9.8
Stable tag: 1.0.0-beta2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Present d' Econozel on your site with articles, editions, and volumes.

== Description ==

Tightly coupled with the VGSR plugin, providing a secured platform to archive and display articles from d' Econozel or any other medium. Articles are clustered by edition, which in turn are clustered in volumes. Since both editions and volumes are stored as WordPress taxonomies, this plugin creates their front-facing pages and paths dynamically.

There are three ways to browse the archived articles:

1. The plugin's root page at `/econozel/` displays a summary of recent article activity
2. The usual article post type archives, which you can visit at `/econozel/articles/`
3. The volume or edition archives, which you can visit starting at `/econozel/volumes/` or `/econozel/editions/`

Several widgets are added to present the plugin's content:

* Recent Articles, optionally displaying an edition's content table
* Article Comments

For delegating article and plugin management to your users, the Econozel Editor role is available.

=== Theme compatibility ===

This plugin is developed with no particular design in mind, so the pages should fit nicely in any theme. If you find Econozel having issues adjusting to your theme, simply add your own styles in an `econozel.css` file in your theme's root folder, and you're good to go!

== Installation ==

If you download Econozel manually, make sure it is uploaded to "/wp-content/plugins/econozel/".

Activate Econozel in the "Plugins" admin panel using the "Activate" link. If you're using WordPress Multisite, you can choose to activate Econozel network wide for full integration with all of your sites.

This plugin is not hosted in the official WordPress repository. Instead, updating is supported through use of the [GitHub Updater](https://github.com/afragen/github-updater/) plugin by @afragen and friends.

== Changelog ==

= 1.0.0 =
* Initial release
