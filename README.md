# Econozel #

Present d' Econozel on your site with articles, editions, and volumes.

## Description ##

> This WordPress plugin requires at least [WordPress](https://wordpress.org) 4.4 and [VGSR](https://github.com/vgsr/vgsr/) 0.1.

Tightly coupled with the VGSR plugin, providing a secured platform to archive and display articles from d' Econozel or any other medium. Articles are clustered by edition, which in turn are clustered in volumes. Since both editions and volumes are stored as WordPress taxonomies, this plugin creates their front-facing pages and paths dynamically.

There are three ways to browse the archived articles:

1. The plugin's root page at `/econozel/` displays a summary of recent article activity
2. The usual article post type archives, which you can visit at `/econozel/articles/`
3. The volume or edition archives, which you can visit at `/econozel/volumes/{volume}/{edition}/`

Several widgets are added to present the plugin's content:

* Recent Articles, optionally displaying an edition's content table
* Article Comments

For delegating article and plugin management to your users, the Econozel Editor role is available.

### Theme compatibility ###

This plugin is developed with no particular design in mind, so the pages should fit nicely in any theme. If you find Econozel having issues adjusting to your theme, simply add your own styles in an `econozel.css` file in your theme's root folder, and you're good to go!

## Installation ##

If you download Econozel manually, make sure it is uploaded to "/wp-content/plugins/econozel/".

Activate Econozel in the "Plugins" admin panel using the "Activate" link. If you're using WordPress Multisite, you can choose to activate Econozel network wide for full integration with all of your sites.

## Updates ##

This plugin is not hosted in the official WordPress repository. Instead, updating is supported through use of the [GitHub Updater](https://github.com/afragen/github-updater/) plugin by @afragen and friends.

## Contributing ##

You can contribute to the development of this plugin by [opening a new issue](https://github.com/vgsr/econozel/issues/) to report a bug or request a feature in the plugin's GitHub repository.
