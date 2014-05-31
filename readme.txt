=== Pages Children ===
Contributors: pmenard
Donate link: http://www.codehooligans.com/donations/
Tags: pages, post types, taxonomies, page management, edit, display, hierarchical 
Requires at least: 3.7
Tested up to: 3.9
Stable tag: 1.5.2.2
License: GPLv2
License URI: http://www.opensource.org/licenses/GPL-2.0

== Description ==

[Plugin Homepage](http://www.codehooligans.com/projects/wordpress/pages-children/ "Pages-Children Plugin for WordPress")

The Pages-Children plugin now works with any post or taxonomy type which is setup as hierarchical. 

Personally I find the WordPress Pages management somewhat lacking. When dealing with a simple list of About Us, Contact Us, etc. pages the interface works fairly well. But when you have a client who has 15+ levels of sub-pages and each level contains some 50+ pages the interface quickly breaks down in it's usefulness. 

So I put together a plugin to help manage this a little better. The new plugin, pages-children, helps to filter the post type or taxonomy list showing only a single level of at a time. That is it really. There is no admin interface or settings. Just through the power of the WordPress actions and filter the page content can be altered. Powerful stuff. 


[Plugin Homepage](http://www.codehooligans.com/projects/wordpress/pages-children/ "Pages-Children Plugin for WordPress")

== Installation ==

1. Upload the extracted plugin folder and contained files to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to any Pages admin screen. The default is to list only top-level pages. Look for the link appended to the page title in column 1 to access lower level pages

== Frequently Asked Questions == 

= Does the plugin change or modify any WordPress core file? =
NO! This is the first rule of plugin development. Never ever change or touch core files, settings or database schema. 

= I didn't like the plugin so I deactivated it. But my Pages listing is still messed up =
Not possible. Double check that you deactivated the 'pages-children' plugin. When active the plugin uses filters to alter the way the page listing is displayed. So once deactivated these filters are no longer used. See first question about changes to core WP code. 

= Does the plugin work on other post types?
YES. As of version 1.4 the plugin now works with any custom post type which is setup as hierarchical. 

= Does the plugin work on Taxonomies?
YES. This was added in version 1.5. now taxonomies defined as hierarchical will be presented similar to post types, one level at a time. 

= I'm using an admin theme and the plugin stopped working
I've only tested the plugin using the default WordPress admin interface. Note the plugin relies on some specific structures of the output tables used for the listing. If the admin theme you are using changes this then my plugin code will no longer work. Sorry. 

= I'm using the WP-ecommerce plugin and Pages-Children stopped working. What can be done?
Not much. For some reason the WP-ecommerce plugin effects the logic of the Pages-Children plugin. I've not been able to figure out the conflict. But continue to investigate. 

== Screenshots ==

1. Example of Page layout
2. Example of taxonomy terms listing layout

== Changelog == 

= 1.5.2.2 =
* 2014-04-22: 
* Corrected small error for undefined var. Thanks Dharmaraj.
* Test for WordPress 3.9

= 1.5.2.1 =
* 2013-12-15: 
* Minor bug. Fixed issue with taxonomy nested items not working on 3.7 and newer. Thanks Euan! 
* Also checked UI functionality for WP 3.8.

= 1.5.2 =
* 2012-03-03: Minor bug fixes when mixed Published and Draft Pages. The bug prevented getting down to or moving up the hierarchy if the child or parent were in draft status.

= 1.5.1 =
*2011-08-03: Minor bug fixes to declare unset variables. Thanks to the many users of my plugin who reported these issues. Note these are Warning not errors. 

Added more logic on the Taxonomy Terms management screen. Have correct the pager logic. In previous versions the pager considered all the terms within the taxonomy. Now the pager is just used for the level of terms displayed. Also, on the Add new term form the dropdown for parent is now set to the parent term being displayed. 

= 1.5 =

*2011-07-01: Added support for any taxonomies which are defined as hierarchical.

= 1.4 =

* 2011-06-29: Added support for any post type which is defined as hierarchical.

= 1.3.1 =

* 2011-06-28: Fixed issues which effected the Media Library listing in WordPress 3.1.3

= 1.3 =

* 2011-01-25: Fixed a bug reported http://wordpress.org/support/topic/plugin-pages-children-wont-return-to-top-level-pages?replies=2 abut not allowing return to top-level pages listing. This was a bug in the cookie logic. This release patches that bug.

= 1.2 =

* 2011-01-23: Set cookie to remember where in the page levels you are. This helps when leaving the Pages section and returning. Also, when adding a new Page, the Parent dropdown is automatically set based on are/where you are in the Page levels. 

= 1.1 =
* 2011-01-16: Fixed bug in WP version compare logic. Basically uploaded the wrong version of the initial file. Apologies. 

= 1.0 =
* 2011-01-14: Initial release

== Upgrade Notice ==

=1.5= 

Added support for taxonomies which are defined as hierarchical.

= 1.4 =

Added support for other custom post types which are defined as hierarchical. Previous versions only supported default Pages. 

= 1.1 =
Basically uploaded the wrong version of the initial file. Apologies. Please try this plugin again. 

= 1.0 =
Use this plugin. You will like it. 

