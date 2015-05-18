=== Move Daily Counters ===
Contributors: easycpmods
Tags: classipress, speed, load, faster, stats, save time
Requires at least: 3.5
Tested up to: 4.1.3
Stable tag: 1.3.5
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Move Daily Counters will make your Classipress site load faster. 

== Description ==

<strong>The development of this plugin is discontinued!
There is a new plugin that does the same thing with some additions.
Please download a new plugin called <a href="https://wordpress.org/plugins/faster-with-stats">Faster with stats<a>.</strong>



There is one table on Classipress installation that can become huge after some time. This table stores daily counters for every ad. Here is a plugin to speed up Classipress called <strong>Move Daily Counters</strong> that will clear this table on daily basis (or manually) with parameters that you specify.

This table is used for showing daily hits per ad, so moving old values has no effect on total statistics, because for that purpose there is another table.
If you are not using history data of daily statistic for some extensive reports, you don't need this data. And this table can get really huge. My table had more than 122.000 records and was slowing my site down.

Why is your site getting slower and slower? This table stores hit counts for every ad on daily basis, which means for every ad a new record is added every day if the ad was seen by anyone on that day. So, if you have many ads on your site, it could mean that even 1000 records will be added to this table daily, so in a few months this table will have more than 100.000 records.

It doesn't sound a lot, but here is what happens when a user visits your web page:<br>
Default theme uses 3 tabs on front page and on every tab there are 10 ads by default. So this means that there will be 30 selects only on this table for every customer and this is a big impact on SQL server.

== Installation ==

1. Extract the folder into your wordpress plugins directory
2. Enable the plugin
3. Config the plugin under ClassiPress->Move Daily Counters
 
== Frequently Asked Questions ==
Waiting for first question.

== Screenshots ==

1. screenshot-1.png

== Changelog ==

= 1.3.0 =
* Added options for moving back the data and deleting settings on plugin deactivation
* 
= 1.2.0 =
* Added new statistical features

= 1.1.0 =
* Added some features and fixed some bugs

= 1.0.0 =
* Initial version

== Upgrade Notice ==
No special care is required for upgrade.

== A brief Markdown Example ==

Feature list:

<strong>Basic version</strong>

* Speed up your Classipress instalation
* Show you how much time you gained with this plugin
* Language files if you would like to translate the plugin
* Moving of data can only be run manually
* Option to move the data back on plugin deactivation

<strong>PRO version</strong>

* Speed up your Classipress instalation
* Show you how much time you gained with this plugin
* Language files if you would like to translate the plugin
* Moving of data can be run manually or <strong>automatically on daily basis - recommended</strong>
* Option to move the data back on plugin deactivation
* Show you some useful <strong>statistics</strong> that you can not see from main Classipress installation
