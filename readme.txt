=== Plugin Name ===
Contributors: PopeOnABomb
Donate link: http://www.robotfloss.com/blog/?page_id=279
Tags: xanga, import, importer
Requires at least: 3.1
Tested up to: 3.1
Stable tag: 3.1

This plugin imports posts and comments from Xanga archive files.

== Description ==

This plugin allows you to import your Xanga posts and all comments to your blog from a Xanga archive file.

Please note that currently blog titles are not imported, and rather the date of the post is used. This
feature will come later. This version has been tested with 3.1, but it most likely works with version starting at 3.0 and up. If
you need a version that works for 2.9.2 or earlier, please download the older version from [Robot.Floss](http://www.robotfloss.com/blog/?page_id=243).

NOTE: To get archive files of your Xanga, you will need to have a premium account with Xanga. Once you have
that, you can go to Private -> Settings -> Archives to request and download an archive. You will need to unzip the archive
before importing it.

== Screenshots ==

No screen shots.

== Installation ==

Once you have downloaded your Xanga archive and unzipped it to a folder on your computer:

1. Download the Xanga Importer plugin.
1. Unzip the importer to a folder on your computer.
1. Upload the Xanga-Importer folder to the `/wp-content/plugins` directory.
1. From the WordPress plugins panel, activate the Xanga Importer plugin (Plugins -> Inactive -> Activate Xanga Importer).
1. From the WordPress panel, go to Tools -> Import, and select "Xanga"
1. From the importer, click "Choose File" and select a Xanga archive file to upload (it must be unzipped).
1. Click "Upload and Import"
1. The posts and comments will now be imported, and you will be shown live data regarding the import. If you import the
same file twice, it will not create duplicates of the posts or comments.
1. You may need to repeat step 8 multiple times depending on how many files are in your Xanga archive.

== Frequently Asked Questions ==

= Why doesn't this work on a zipped archive folder? =

As stated in the instructions, you must unzip the Xanga archive in order for this to work.

== Changelog ==

= 3.1 =
* Updated the code to make it work with WordPress 3.1
* Version numbers will now match the supported version of WordPress going forward
* Submitted the plugin to Wordpress as an official plugin.

= 2.8 =
* Updated the code of the defunct Xanga importer
* Added support for the new Xanga url format of http://www.xanga.com/username to http://username.xanga.com

== Upgrade Notice ==

= 3.1 =
The previous version is 100% incompatible with WordPress 3.1, so you'd better grab this update.