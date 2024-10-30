=== Smart Hashtags [#hashtagger] ===
Contributors: petersplugins
Tags: hashtag, hashtags, tag, tags, tag archive, archive, social, twitter, facebook, classicpress
Requires at least: 4.0
Tested up to: 6.3
Stable tag: 7.2.3
Requires PHP: 5.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to use #hashtags, @usernames and $cashtags in your posts. #hashtags are automatically added as post tags. Highly customizable!

== Description ==

The Smart Hashtags Plugin allows you to use #hashtags, @usernames and $cashtags in your posts

This plugin uses the [WordPress Tag system](https://codex.wordpress.org/Posts_Tags_Screen) to automatically convert a #hashtag into a Post Tag. Each #hashtag is added as a "normal" tag (without leading hash) to the post, so it is fully compatible with existing tags!

== Retired Plugin ==

Development, maintenance and support of this plugin has been retired in october 2023. You can use this plugin as long as is works for you. 

There will be no more updates and I won't answer any support questions. Thanks for your understanding.

Feel free to fork this plugin.

== Usage ==

Just type anywhere in a post

**#hashtag** 
This adds "hashtag" as tag to the current post and on links to tag archive page for "hashtag" when showing the post.

**+#hashtag** 
Use +#hashtag to only link to a tag archive page without adding "hashtag" as tag to the post. When showing the post the link is showed as "#hashtag" (without "+"). If the tag does not exist the text remains unchanged and no link is created.

**##hashtag**
Use duplicate ##hashes to tell the plugin that this word should not be converted into a tag. Duplicate hashes are replaced by a single hash when showing the post.

**@username**
This creates a link either to the Profile Page or the Website of User "username". The usage of @usernames can be activated optionally. If the username does not exist the text remains unchanged and no link is created. It is **highly recommended** to use @nicknames instead of @usernames to enhance security.

**@@username**
Use @@username to avoid link creation. When showing the post this is displayed as "@username" without link (@username feature has to be activated).

**$cashtag**
This creates a link to the concerning stock symbol at MarketWatch, Google Finance, Yahoo Finance or StockTwits. The usage of $cashtags can be activated optionally. $cashtags link to the concerning stock symbol at MarketWatch, Google Finance or Yahoo Finance. **Notice**: stock symbols can not be validated, using a invalid stock symbol will cause an not found error on the target site.

**$$chashtag**
Use $$cashtag to avoid link creation. When showing the post this is displayed as "$cashtag" without link ($cashtag feature has to be activated).

== WordPress Security ==

If you activate the @username feature **it is highly recommended to use @nickname instead of @username**!

== Post Types and Section Types ==

It is possible to use #hashtags, @usernames and $cashtags on Posts, on Pages and on Custom Post Types within Content, Title and Excerpt. Activate only the Post Types and Section Types you want to be processed to avoid unnecessary processing for best performance.

== Formatting links ==

Additional CSS Class(es) to add to the #hashtag and @username links can be configured on the plugins setting page.

== Display of links in front end ==

Optionally all symbols (#, @, $) can be removed from the links generated in front end.

== Display of Tags in front end ==

There's an option to automatically add a hash symbol in front of tags (e.g. when using the Tag Cloud Widget).

== Plugin Privacy Information ==

* This plugin does not set cookies
* This plugin does not collect or store any data
* This plugin does not send any data to external servers

== For developers ==

Use `do_hashtagger( $content )` in your theme files to process #hashtags and @usernames in $content.

== Changelog ==

= 7.2.3 (2024-04-16) CLEANUP =
* Cleanup

= 7.2.2 (2022-10-05) FINAL VERSION =
* removed all links to webiste
* removed request for rating
* removed manual

= 7.2.1 (2022-10-23) =
* bugfix regarding default settings
* bugfix to avoid multiple hash symbols
* Settings interface adapted to my other plugins

= 7.2 (2019-04-07) =
* bug fix for version 7.0 and 7.1

= 7.1 (2019-04-04) =
* Ignore Blocks added

= 7 (2019-04-03) =
* Ignore List added
* security vulnerability in AJAX call fixed

= 6 (2019-03-09) =
* make use of $wp_rewrite->get_extra_permastruct() to show current tag base URL
* UI improvements
* code improvement

= 5 (2018-08-03) =
* StockTwits added as link target for $cashtags
* further UI-improvements

= 4 (2018-05-08) =
* option to only process singular posts added
* many code optimizations
* minor UI-improvements

= 3.8 (2017-11-16) =
* faulty display in WP 4.9 fixed

= 3.7 (2017-09-13) =
* Option to automatically add hash symbol in front of tags 

= 3.6 (2017-07-11) =
* Redesigned admin interface
* Code improvement

= 3.5 (2016-09-13) =
* Option to only create tags from #hashtags, but do not show links
* Bug fix Tag Regeneration

= 3.4 (2016-08-18) =
* Enhanced Polylang support to allow same hashtag in several languages
* Option to allow hashtags starting with numbers

= 3.3 (2015-12-22) =
* Uage of cashtags to link to stock symbols
* Optionally remove symbols in front of generated links in front end

= 3.2 (2015-05-28) =
* Works now with [User Submitted Posts](https://wordpress.org/plugins/user-submitted-posts/)
* Works now with [Barley for WordPress](http://getbarley.com/editor/wp)
* Hook filters only when needed

= 3.1 (2015-02-05) =
* Spanish translation added (thanks to [Andrew](http://www.webhostinghub.com) for translating)
* Cosmetics

= 3.0 (2014-12-21) =
* Completely rewritten with a lot of new Settings and a feature to regenerate existing objects

= 2.1 (2014-10-10) =
* Optionally use @nicknames instead of @usernames (thanks to [joeymalek](https://profiles.wordpress.org/joeymalek/) for pointing out)
* Added `do_hashtagger()` Theme Function (thanks to [joinfof](https://profiles.wordpress.org/joinfof/) for the idea)

= 2.0 (2014-09-17) =
* Optional usage of @usernames
* Syntax +#hashtag to create link only without adding tag

= 1.3 (2014-08-15) =
* Solved: do not use hex color codes in css as hashtags (see [this Support topic](https://wordpress.org/support/topic/this-is-really-great-but-it-doesnt-let-me-color-code-anything))

= 1.2 (2014-08-05) =
* hashtags can contain non ASCII characters
* hashtags must not start with a number
* hashtags can start after punctuation marks without whitespace
* hashtags end at punctuation marks

= 1.1 (2014-07-31) =
* Option to specify css class added
* German translation added

= 1.0 (2014-07-09) =
* Initial Release

== Upgrade Notice ==

= 7.2.1 =
bugfixes: default settings, avoid multiple hash symbols

= 7.2 =
bug fix for version 7.0 and 7.1

= 7.1 =
Ignore Blocks added

= 7 =
Ignore List added

= 6 =
some improvements, no functional changes

= 5 =
StockTwits added as link target for $cashtags

= 4 =
New option to only process singular posts

= 3.8 =
faulty display in WP 4.9 fixed

= 3.7 =
Option to automatically add hash symbol in front of tags 

= 3.6 =
Unified admin interface

= 3.5 =
Option to only create tags from #hashtags, but do not show links

= 3.4 =
Enhanced Polylang support to allow same hashtag in several languages, option to allow hashtags starting with numbers

= 3.3 =
Version 3.3 introduces $cashtag feature

= 3.2 =
Works now with "User Submitted Posts" Plugin and "Barley for WordPress" Plugin

= 3.1 =
Spanish translation, Cosmetics.

= 3.0 =
Version 3.0 introduces a lot of new features and settings. Don't miss it!