=== Merlin Object Browser ===
Tags: media
Tags: merlin
Requires at least: 4.2
Tested up to: 4.9.4
Stable tag: trunk
Contributors: cforber
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Allows selection of images from Merlin Archive for use in WordPress

== Description ==

This plugin allows users to log into their Merlin Archive and select images for upload into the WordPress Media Library and add to posts or pages. It requires access to a Merlin Archive and valid login credentials for that archive.

When a user clicks on the "Add Media" button when editing a post or a page, the Media screen will have an additional option in the left side-bar, "Insert from Merlin". Clicking on that link will open the Merlin Archive. 

If the user has already logged into the Merlin archive via WordPress, they will be taken to the home view, otherwise, they will be asked to log in first. From there, they can search, view collections, run saved searches to find the images that they want to add to the post or page. They can select the image or images they want to upload by clicking on the check mark icon to switch into selection mode, clicking on the images to be selected and then clicking "Save Selected" to complete the selection process. 

Once one or more images are selected (a red checkmark will be shown on the selected images), the user can then click the WordPress icon to upload those images into the Media Library, where they can be used in pages or posts. Depending on the number of images selected and the size of the high resolution files for those images, the upload process may take anywhere from a few seconds to a minute or two.

If a maximum dimension has been configured in the Merlin Object Browser settings, the uploaded images are resampled to that maximum dimension in pixels. Note, small images are not upsampled. Also note, requires Merlin SODA version 1.0.27 or later. If earlier version installed, images will be transferred at full high resolution.

Note that file name (object), headline and caption are transferred from the Merlin archive into the Wordpress image metadata.

== Installation ==

1. Upload 'merlin-object-browser' folder to the '/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Set the Merlin archive url for your archive using WordPress Admin 'Settings', 'Merlin Object Browser'. Can also set optional maximum dimension for transferred images, to speed up transfer into WordPress (as of revision 1.6), if necessary version of Merlin SODA is installed on the Merlin server.

== Frequently Asked Questions ==

= Can I use any login to my Merlin Archive? =

The login must have output permission in order to upload from the Merlin archive into Wordpress.

== Screenshots ==

1. Screen shot of upload media window showing gallery of images in Merlin Archive with one image selected for upload into Wordpress.

== Changelog ==

= 1.7 =
* Updated to resolve issue with some JSON objects being rejected.

= 1.6 =
* Updated to add optional maximum dimension for transferred images. Downsampling high resolution images will speed up the transfer into WordPress. Note, requires Merlin SODA version 1.0.27 or later. If earlier version installed, images will be transferred at full high resolution.

= 1.5 =
* Updated to work with WP accessed via https

= 1.4 =
* Updated to work with MX 5.4

= 1.3 =
* Tidied up code as per feedback from WordPress VIP code review, no functionality changes

= 1.2 =
* Tidied up code for submission to WordPress, no functionality changes

= 1.1 =
* Fixed missing file extension on uploaded file name

= 1.0 =
* Initial version

== Upgrade Notice ==

= 1.7 =
* Mandatory upgrade. Resolves issue with some JSON objects being rejected.

= 1.6 =
* Optional upgrade if using MX 5.4+, allows performance improvement with transfer. If using MX 5.2 DO NOT UPGRADE, contact MerlinOne

= 1.5 =
* Mandatory upgrade if using MX 5.4+, to accommodate code changes in MX required to enable https. If using MX 5.2 DO NOT UPGRADE, contact MerlinOne

= 1.4 =
* Mandatory upgrade if using MX 5.4+. If using MX 5.2 DO NOT UPGRADE, contact MerlinOne

= 1.3 =
* No functionality changes, upgrade not necessary

= 1.2 =
* No functionality changes, upgrade not necessary

= 1.1 =
* Fixed problem with objects without file name extensions, upgrade immediately
