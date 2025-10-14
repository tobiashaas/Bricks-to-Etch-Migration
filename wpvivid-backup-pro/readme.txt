=== WPvivid Plugins Pro Readme===

Contributors: WPvivid Team
Requires at least: 4.5
Tested up to: 6.8.2
Requires PHP: 5.3
Stable tag: 2.2.37
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 
== Description ==

WPvivid Plugins Pro is a centralized dashboard plugin for managing all WPvivid plugins(addons), including WPvivid Backup Pro addon, WPvivid Image Optimization Pro addon, and the free versions of the two addons.
 
WPvivid Backup Pro addon is an advanced version based on the free version.
It provides more exciting features that are specifically designed for migrating WordPress websites and backing up your money websites.

The free version of the plugin has been trusted by 100,000+ happy users over the past three months with almost 400 five-star ratings, which proves that it has reliable quality.

== Features Highlights==
= WPvivid Plugins Pro =
- One-click installer for all WPvivid plugins/addons
- Access all WPvivid pro features from a centralized dashboard
- One-click update for all WPvivid pro addons.
= WPvivid Backup Pro Addon =
- Customize everything to backup
- Create staging sites and push staging to live site
- Incremental backups
- Database backups encryption
- Auto backup WordPress core, themes, and plugins
- Unused images cleaner
- WordPress Multisite backup
- Create a staging for a WordPress MU
- Create a fresh WP install
- Advanced remote storage
- Advanced backup schedules
- Restore backups from remote storage
- Migrate everything via remote storage
- Migrate a childsite (MU) to a single WordPress install
- White label
- Edit user roles capabilities

== Cloud Storage Supported ==
Dropbox, Google Drive, Microsoft OneDrive, Amazon S3, DigitalOcean Spaces, SFTP, FTP, pCloud, Wasabi, Backblaze and more are coming soon.

== Support ==
We provide 7*24 support with top priority for pro users via our ticket system.
[Submit a ticket](https://wpvivid.com/submit-ticket)

== Installation ==
[How to Install WPvivid Plugins Pro in WordPress](https://docs.wpvivid.com/install-wpvivid-pro-plugins-pro.html)

== Privacy Policy and Term of Service ==
Here are our terms and conditions for the use of WPvivid Backup plugin.

Personal Data Processing and Storing
User Account
For community version user, there’s none of your personal and sensitive data is sent to us. Please see our [Privacy Policy](https://wpvivid.com/privacy-policy) for more information.

For a premium version user, you will need to creating an account on our website, in the process, an email address and a password will required for completing registration. They will be stored on our website server and will be used for the only purpose of your operations to the account. You can ask us to delete your account on our side anytime.

Payment Information
Purchasing any premium subscription plan on our site will not give us any information or access to your payment account. All payments handled by the service providers on their sites, for example, PayPal at www.paypal.com.

Support Terms
WPvivid will provide customers support services including helping solving all the questions and issues regarding the installation and operation of the WPvivid Backup Plugin. Unless otherwise mutually agreed, WPvivid will have no obligation to provide on-site support services at Customer’s location.

Service Suspension
Any abusive behaviors towards our facilities and staff may result in a termination or suspension of access to parts or all of our service without prior notice or refund, under our sole discretion.

Contact Us
If you have any questions about these terms, please feel free to [contact us](https://wpvivid.com/contact-us).

== Frequently Asked Questions ==
1.What are the good things in pro version?
As everybody sees, WPvivid Backup plugin has covered everything for free in the community version, which is more than sufficient for most of people to keep backups of their sites.

For those who expect more customization options and advanced enhancements on top of the free version. You can have a good understanding of them from the full comparison of features in free and pro version - https://pro.wpvivid.com/pricing.

2.How to get WPvivid Backup Pro and use it?
Here is our step-by-step guide to [activate WPvivid Backup Pro](https://wpvivid.com/install-wpvivid-backup-pro.html).

3.How long can I access to updates and support for?
You will have annual access namely one-year or lifetime access to updates and support, depending on which subscription period you choose.

4.How many sites can I use a license for?
You can use a license for 3, 20, 100, or unlimited websites, depending on which subscription plan you choose.

5.Do you provide a trail or refund?
We do not provide a trail. But we do offer 100% refund within 30 days after purchase, no question asked.

6.Can I upgrade or downgrade a purchased plan?
The option is not available yet. But we are working on it and will enable it in a further release.

7.Are there any discounts available for renewal?
Yes. It will be the same discount as the one for your current order for all types of renewals.

8.Can I reuse a licence for different sites within it’s expiration?
For personal plan: It’s limited. In order to avoid abuse of the personal version, we have set 3 days for you to deactivate after you activate a website.

For freelancer or higher plan: Yes. You can easily do this by unbinding the license from the previous websites and reactivating it on new ones, without a limit.

9.What happens if I choose not to renew after a year?
It’s sad to see. But you can still use the pro version, without any barriers. You just will not receive updates and support for the pro.

10. I have more questions…
Please feel free to contact us using the form [here](https://wpvivid.com/contact-us).

== Changelog ==
= 2.2.37 =
- Added 2 optimized backup modes for large Uploads and database.
- Added an option to enable or disable Rollback backups for newly installed plugins.
- Fixed: Backups could not be recognized by new sites when white label was enabled on source site.
- Updated: The plugin list on the Rollback page now displays all plugins.
- Fixed: Backup files became corrupted after being downloaded in some environments.
- Fixed some UI bugs.
= 2.2.36 =
- Added 2 settings to optimize backup process for large databases and uploads.
- Fixed: Backup to OneDrive for some large sites would fail after exceeding an hour.
- Fixed: Downloading backups from S3-compatible storage could get stuck in some environments.
- Fixed: Exporting posts/pages could fail in some environments.
- Fixed some UI bugs.
= 2.2.35 =
- Updated: Restored previous backup file naming to prevent potential backup failures in extreme server environments.
- Fixed: GoogleDrive rollback retention settings were not taking effect.
- Fixed: WebDAV backups could fail in some cases.
- Fixed: OneDrive shared drive backups could encounter an HTTP 401 error.
- Fixed some UI bugs.
= 2.2.34 =
- Fixed: Backup could fail in some cases with version 2.2.33.
= 2.2.33 =
- Added an option to stored backup outside the website directory.
- Updated the backup file naming to indicate the number of files in a backup.
- Fixed: Installing the staging pro addon could fail in some special environments.
- Fixed: Could not clean up incremental backups in B2 storage in some environments.
- Fixed: Backups could fail in some environments with very large databases.
- Fixed: Restoration using PDO database access method could fail in some cases.
- Fixed: Backup to OneDrive and SharedOneDrive could fail in some cases.
- Fixed: Backup to WebDAV could fail when the WebDAV service doesn't support Content-Range.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.32 =
- Added 4 backup performance modes for different scenarios.
- Fixed: backup scanning on some s3 compatible storage would fail.
- Fixed: the staging addon could not be installed in some cases.
- Fixed: Local directories would not be deleted after Rollback backups were uploaded to cloud.
- Fixed: wpvivid_options table would be cleared when 'Empty the database before restoring' was checked.
- Fixed: Restore would fail when WordPress version in the backup was lower than WordPress version of the target site.
- Fixed: custom file permissions set in wp-config.php were not inherited during restoration.
- Fixed a compatibility issue with Wholesale Suite plugin.
- Fixed some UI bugs.
= 2.2.31 =
= WPvivid Backup & Migration Pro =
- Added an option to include symlink folders in a backup.
- Fixed: Could not add WebDAV when path contained special characters.
- Fixed: Downloading specific part in a backup would fail in some cases.
- Fixed: Uploading backups to Wasabi would fail when WP File Download Addon was installed.
- Fixed SFTP connection failure on sites with PHP 8.2.26 and 8.3.14.
- Optimized algorithm of database backup encryption.
- Fixed some UI bugs.
- Optimized the plugin UI.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.30 =
= WPvivid Backup & Migration Pro =
- Added an option to adjust chunk size of GoogleDrive.
- Fixed a warning that would appear with WordPress 6.7.0.
- Optimized the plugin code.
- Successfully tested with WordPress 6.7.0.
= WPvivid Image Optimization Pro =
- Optimized the plugin code.
- Successfully tested with WordPress 6.7.0.
= 2.2.29 =
= WPvivid Backup & Migration Pro =
- Fixed: WP Cerber plugin was excluded from a backup by default.
= WPvivid Image Optimization Pro =
- Fixed: Image optimization failed with an error of 'file size is 0' in some environments.
= 2.2.28 =
= WPvivid Backup & Migration Pro =
- Added an option to delete backup logs when deleting backups.
- Added an option to delete all rollback backups of a plugin.
- Fixed: Debug zip could not be sent if the email address contained a hyphen '-'.
- Fixed: Last backup status showed as successful even it failed in some cases.
- Fixed: Backup to GoogleDrive could fail in some environments.
- Fixed: Backup to s3-compatible storage could fail in some environments.
- Fixed: Rolling back themes would fail in some cases.
- Fixed some UI bugs.
- Fixed a security vulnerability in the plugin code.
= WPvivid Image Optimization Pro =
- Fixed: Optimizing images would get stuck upon upload in some environments.
- Fixed: Some images would not display when lazyloading was enabled.
- Fixed: Excluding some images for lazyloading did not work.
= 2.2.27 =
= WPvivid Backup & Migration Pro =
- Updated the 'autoload' of wpvivid options in the database to NO.
- Added a new FTP cloud storage tab where an existing custom path is allowed.
- Fixed: Activating WPvivid pro license would fail when phpinfo function was disabled on the web server.
- Fixed: Uploading backups to OneDrive failed with a 401 error in some environments.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.26 =
= WPvivid Backup & Migration Pro =
- Fixed: Restore would fail when a backup contained mu-plugins/wp-stack-cache.php.
- Fixed: Image urls in the database were not deleted by default when unused images were deleted.
- Fixed some bugs in the plugin.
- Optimized the plugin code.
- Successfully tested with WordPress 6.6.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
- Successfully tested with WordPress 6.6.
= 2.2.25 =
= WPvivid Backup & Migration Pro =
- Updated: When 'Split a backup every this size' is set to 0. Backups will be split every 4GB.
- Updated: Cloud storage tokens are now encrypted in the database.
- Updated: The 'lotties' folder (if any) will be included in backups by default.
- Fixed: Restoring encrypted database backup would fail in some cases.
- Fixed some bugs in the plugin.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.24 =
= WPvivid Backup & Migration Pro =
- Fixed: Restore might fail when server’s max_allowed_packet was low.
- Fixed: Enabling rollback for Divi theme did not work.
- Fixed: WPvivid was not replaced in some pages when white label was enabled.
- Fixed: Backups to OneDrive would fail in some environments.
- Fixed: Backups to Wasabi would fail in some environments.
- Fixed: Backups to Nextcloud would fail in some environments.
- Fixed: Urls would not be replaced during a migration process in some cases.
- Fixed: '1' would be added as an additional recipient in email report of rollback backups.
- Fixed: Retention settings for Nextcloud and WebDav could not be saved.
- Fixed some UI bugs.
- Fixed some bugs in the plugin code.
- Optimized loading of the plugin’s options.
- Successfully tested with WordPress 6.5.2.
= WPvivid Image Optimization Pro =
- Added an option of automatically cleaning image optimization logs.
- Added an option of disabling animation of lazy loading.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
- Successfully tested with WordPress 6.5.2.
= 2.2.23 =
= WPvivid Backup & Migration Pro =
- Fixed: Downloading incremental backups from some cloud storage would fail.
- Fixed: Users of a subsite in multsite could not be migrated to single WordPress install.
- Fixed: Some special characters could not be properly migrated.
- Fixed: Restore would fail in some cases when using WPDB database access method.
- Fixed: Backups to Dropbox would fail in some cases.
- Fixed: Downloaded backups become corrupted in some environments.
- Fixed: WPvivid brand was not properly replaced on some pages when white label was enabled.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
- Successfully tested with WordPress 6.4.3.
= WPvivid Image Optimization Pro =
- Images pixels would change in some cases when WebP was enabled.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
- Successfully tested with WordPress 6.4.3.
= 2.2.22 =
= WPvivid Backup & Migration Pro =
- Added support for migration of sites without a database prefix.
- Fixed: Prefix of tables with foreign keys would not be replaced in a migration process.
- Fixed: Activating WPvivid pro license would fail in some environments.
- Fixed: Upgrading WPvivid Backup Pro would fail in some environments.
- Fixed: Adding WebDav storage would fail in some environments.
- Fixed: Download and view backups did not work in some environments.
- Fixed some UI bugs.
- Fixed some bugs in the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.21 =
= WPvivid Backup & Migration Pro =
- Added support for Microsoft OneDrive Shared Drives.
- Added cloud storage option for auto backup before update.
- Added rollback settings to exported plugin settings.
- Fixed: OneDrive authentication would get lost in some cases.
- Fixed a compatibility issue between rollback and white label features.
- Fixed: Scanning backups in pCloud would fail in some cases.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed: Image size and pixel were not updated in media library after optimization.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.20 =
= WPvivid Backup & Migration Pro =
- Added an option of backing up database before a plugin/theme/WP update.
- Added an option of sending database rollback backups to cloud storage.
- Fixed: Email reports did not work with rollback backups.
- Fixed: Error logs would not be attached to backup email reports.
- Fixed: Backup retention did not work properly in some environments.
- Fixed a compatibility issue with JetBackup plugin.
- Fixed a compatibility issue with local-google-fonts plugin.
- Fixed: Plugins would be deactivated on the target site after multiste migration.
- Fixed some UI bugs.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.19 =
= WPvivid Backup & Migration Pro =
- Redesigned the rollback (auto backup before update) feature.
- Successfully tested with WordPress 6.3.
- Added an option to exclude folders from unused image scan.
- Fixed backup download failures with S3 compatible storage.
- Fixed: restored would fail when a backup contained zero dates '0000-00-00'.
- Fixed the backup download timeout issue in some cases.
- Fixed a WordPress critical error in some cases caused by the last update.
- Fixed: Restore of incremental backups would fail in some cases.
- Fixed the backup upload failure to Amazon S3 in some cases.
- Fixed: Json files would not be deleted when backup retention was reached.
- Fixed inaccurate backup time in backup list because of timezone.
- Fixed: Customized site icons and logos would be falsely scanned as unused.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.18=
= WPvivid Backup & Migration Pro =
- Redesigned and improved backup managment and the backup list.
- Added breakpoint resume for OneDrive.
- Fixed: Uploading backups to OneDrive would fail in some cases.
- Fixed: Database restoration would fail in some environments.
- Fixed: License activation would fail in some cases.
- Fixed: Installing addons would fail in some cases.
- Fixed: WPvivid slug could not be whitelabeled in MainWP pro reports.
- Fixed some PHP warnings that would appear on sites with PHP 8.2.
- Fixed some UI bugs and optimized the plugin UI.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed: Image optimization would fail when the file name contained special characters.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.17 =
= WPvivid Backup & Migration Pro =
- Fixed: Backup task retry would not execute in some cases.
- Fixed: Auto backup before update would fail to start in some cases.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.16 =
= WPvivid Backup & Migration Pro =
- Added support for Nextcloud storage.
- Optimized the process of uploading files to Dropbox.
- Fixed: Backup email reports did not display properly in Outlook email.
- Fixed: Excluded files were not remembered in the next site export.
- Fixed: Non admin users could see the plugin menus in the top admin bar.
- Fixed: Incremental backups would not triggered correctly in some cases.
- Fixed some PHP warnings.
- Fixed some UI bugs.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Added an option to resize large images to a fixed width or height.
- Optimized the process of image optimization.
- Fixed: Image optimization would fail when there were 0 KB files.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.15 =
= WPvivid Backup & Migration Pro =
- Added support for WebDAV storage.
- Fixed some bugs with the white label feature.
- Fixed: Backup to Google Drive failed in some environments.
- Fixed a library conflict issue with some plugins.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.14 =
= WPvivid Backup & Migration Pro =
- Added an option to choose file compression method.
- Optimized the backup uploading process.
- Updated: Cloud storage directory name cannot be set to '/'.
- Fixed: Incremental backup settings were included in a migration process.
- Fixed: Backup to Google Drive failed in some cases.
- Fixed: Backup schedules could not be triggered properly in some cases.
- Fixed: Backup retention for B2 did not take effect.
- Fixed: Restoring encrypted database failed when the password contain special characters.
- Fixed some warnings on PHP 8.2 sites.
- Fixed some bugs in the plugin code.
- Fixed some UI bugs.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Added a server ping test function.
- Fixed: Restore all optimized images did not work properly.
- Fixed: Image optimization would fail when the file name contained some special characters.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.13 =
= WPvivid Backup & Migration Pro =
- Upgraded and redesigned Roles & Capability addon.
- Added a region field for s3 compatible storage.
- Added a check for link directories in a backup process.
- Fixed: backup would fail when php_uname was disabled on the server.
- Fixed some UI bugs.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Optimized memory consumption in the image optimization process.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.12 =
= WPvivid Backup & Migration Pro =
- Updated: CSS of GenerateBlocks will be automatically regenerated after migration.
- Fixed: Activating WPvivid license failed in some cases.
- Fixed: Wasabi retention for rollback backups did not take effect.
- Fixed: Incorrect backup progress when cache-admin is enabled in Litespeed cache plugin.
- Fixed some UI bugs.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed: WebP would not take effect when CDN is enabled.
- Fixed: Failed to restore optimized images in some cases.
- Fixed: Optimization schedules did not work properly in some cases.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.11 =
= WPvivid Backup & Migration Pro =
- Fixed: Incorrect calculation of next backup date in some cases.
- Fixed: All target pages except for home page showed 404 error in some cases after migration.
- Fixed: White label settings could not be saved in some cases.
- Fixed some broken links on the plugin UI.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.10 =
= WPvivid Backup & Migration Pro =
- Added a check for siteurl and home in a restore process.
- Optimized backup process on Litespeed web server.
- Optimized backup process for large databases.
- Fixed some UI bugs and improved UI of some sections.
- Fixed: Some used images were falsely scanned as unused.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.9 =
= WPvivid Backup & Migration Pro =
- Updated: Transferred files will be deleted when auto migration fails.
- Updated: The website domain is included in email reports.
- Fixed a vulnerability in the plugin code.
- Fixed: 'Auto backup before plugin update' failed in some cases.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.8 =
= WPvivid Backup & Migration Pro =
- Added a check to the integrity of uploaded backups.
- Fixed: Uploading scheduled backups to pCloud failed after editing pCloud settings.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.7 =
= WPvivid Backup & Migration Pro =
- Added an option to restore .htaccess file on Restore page.
- Fixed the 'ERR_INVALID_RESPONSE' error that appeared when downloading backups in some cases.
- Fixed some UI bugs and broken links on the plugin UI.
- Fixed some bugs in the plugin code and optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed: Some paths of custom folder did not work.
- Fixed the 'image not found' error that appeared when running image optimization in some cases.
- Fixed some bugs in the plugin code and optimized the plugin code.
= 2.2.6 =
= WPvivid Backup & Migration Pro =
- Added a checking to available disk space.
- Added a checking to encrypted database backups before restoration.
- Added an option to delete the local copy of a remote backup immediately after the restoration is successful.
- Fixed: Restore logs were not displayed in Logs list.
- Fixed: Bulk update for WPvivid plugins did not work.
- Fixed: WordPress version in a backup was not read and displayed on Restore page.
- Changed time on backup schedule creating and editing page to local time.
- Upgraded and enhanced logging of incremental backups.
- Upgraded: Last backup time will be updated once the backup schedule is triggered.
- Fixed some UI bugs.
- Optimize the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.5 =
= WPvivid Backup & Migration Pro =
- Added an option of excluding database tables for rollback backups in the plugin settings.
- Updated: The directory /mu-plugins now is excluded by default.
- Fixed: When backup retention for localhost was set to 1, it would not take effect.
- Fixed: Cloud storage backup retention settings did not take effect for existing cloud storage.
- Fixed: Could not manually delete WPvivid logs and cache files.
- Fixed: Excluding database tables did not take effect when editing an incremental backup schedule.
- Fixed some bugs in the plugin code and UI.
- Successfully tested with WordPress 6.0.
= WPvivid Image Optimization Pro =
- Fixed: Some images could not be optimized.
- Fixed: Max Optimized Count always remained at 1.
- Fixed some bugs and optimized the plugin code.
- Successfully tested with WordPress 6.0.
= 2.2.4 =
= WPvivid Backup & Migration Pro =
- Updated: Enriched individual backup retention settings for cloud storage.
- Added an option of enabling individual backup retention settings for cloud storage.
- Fixed: Uploading backups to Backblaze storage failed in some cases.
- Fixed: Last incremental backup time was not updated when the backups were stored in localhost.
- Fixed some UI description mistakes.
- Successfully tested with WordPress-6.0-RC2.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.3 =
= WPvivid Backup & Migration Pro =
- Fixed: Some websites got critical error after updating to version 2.2.2.
- Fixed: Excluding files/folders inside /wp-content/ folder did not work.
- Fixed: Additional folders in a backup were not restored.
- Fixed: Uploading backups to Amazon S3 failed in some cases.
- Fixed: Uploading backups to OneDrive failed because of a 'token expired' error in some cases.
- Fixed: Backing up database failed in some cases.
- Fixed: Backup process got stuck in 0% in some cases.
- Fixed: Rollback backups were not triggered when updating themes.
- Fixed: Manual backups was identified as Uploaded backup when white label was enabled.
- Fixed some UI display bugs.
- Added support for using the hyphen mark '-' in white label slug
- Added a field of 'Plugin Author' on white label settings page.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.2 =
= WPvivid Backup & Migration Pro =
- Fixed: Backup retention settings for Google Drive didn't take effect.
- Fixed: Last backup time of child sites was not synced to the WPvivid for MainWP extension.
- Fixed: Connecting to some s3 compatible storage failed.
- Fixed: Failed to sent backups to s3 storage in some cases.
- Fixed some PHP warnings with s3-compatible storage.
- Fixed: Setting remote storage as default backup location did not take effect.
- Fixed: Disabling‘Merge backups’did not take effect when Learning mode was enabled.
- Fixed: The debug zip did not include failed backup logs in some cases.
- Added back the option of locking a backup.
- Fixed: A backup was split into parts even it was set to not split.
- Fixed: Scheduled backups were sent to wrong folder after importing plugin settings from a different site.
- Fixed: Backups failed in some cases.
- Fixed: Additional database could not be backed up.
- Fixed: Encrypted database could not be restored.
- Fixed: Restoring incremental backups failed in some cases.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.2.1 =
= WPvivid Backup & Migration Pro =
- Fixed: Could not install addons when the site language was not English.
- Fixed: Database backup failed in some cases.
- Fixed: Backup sizes were constantly changing in some cases.
- Fixed: Failed to upload backups to Wasabi storage in some cases.
- Fixed: Some remote backups were not displayed in the backup list in some cases.
- Fixed: Incremental backups were uploaded to wrong directory in some cases.
- Fixed: Inactive plugins on MU network were activated after restoration.
- Added support for using path-style as s3-compatible bucket access style.
- Fixed some UI display bugs.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= Version 2.2.0 - A Major Update of WPvivid Backup & Migration Pro =
- Upgraded backup & migration engine.
- Added two new workflows for 'Exporting Site' and 'Importing Site'.
- Added an option of downloading all backup parts in 1-click.
- Updated and enhanced plugin UI.
- Fixed: Adding pCloud EU server failed in some cases.
- Fixed: Last incremental backup time  was incorrect in some cases.
- Fixed: Restore failed because of the error Variable 'time_zone' can't be set to the value of NULL in some cases.
- Fixed some bugs in the plugin code and on UI.
- Optimized the plugin code.
- Successfully tested with WordPress 5.9.2.
= WPvivid Image Optimization Pro =
- Updated: Moved Lazyload, CDN and Image Optimization Settings tabs to the Image Optimization tab.
- Fixed: Enabling CDN integration failed in some cases.
- Fixed some bugs in the plugin code and optimized the plugin code.
- Successfully tested with WordPress 5.9.2.
= 2.1.7 =
= WPvivid Backup & Migration Pro =
- Updated: For security reasons, adding Google Drive, Dropbox, OneDrive now needs to get authentication first.
- Updated: Changed time in a log file to local time.
- Fixed the curl 60 error that could appear when backing up to Google Drive in some cases.
- Fixed: Disabling backup splitting did not take effect on PHP 8 sites.
- Fixed: Uploading backups to Dropbox failed in some cases.
- Fixed: Backup email report was sent even when there was no incremental backup created.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some minor bugs in the plugin code.
- Optimized the plugin code.
= 2.1.6 =
= WPvivid Backup & Migration Pro =
- Added a column to show backup location in backup schedule list.
- Fixed: Backup encryption did not work in PHP 8 environments.
- Updated: Changed timezone in email report title to local time.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some minor bugs in the plugin code.
- Optimized the plugin code.
= 2.1.5 =
= WPvivid Backup & Migration Pro =
- Fixed: The object-cache.php file and protection files generated by Wordfence were not excluded during restore.
- Fixed: Some used images were falsely identified as unused.
- Fixed: Sent backup email reports to only one email address when multiple emails were set.
- Improved: Plugins and Themes in the 'Custom Backup' option are sorted alphabetically now.
- Improved: Elementor CSS will be automatically regenerated after migration.
- Redesigned the UI of 'General Backup Schedule'.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Updated: When WPvivid lazy load is enabled, WordPress lazy load will be disabled.
- Successfully tested with WordPress 5.8.2.
- Optimized the plugin code.
= 2.1.4 =
= WPvivid Backup & Migration Pro =
- Fixed a Dropbox folder bug with incremental backups.
- Fixed a conflict between the unused image cleaner and some themes.
- Fixed a problem that some used images in Elementor were identified as unused.
- Fixed: Downloading backup would failed in some cases.
- Fixed the 'File not found' error when downloading incremental backups from B2.
- Fixed: Themes and plugins were not automatically sorted when migrating a MU subsite to a single site.
- Added the options to 'back up to remote storage' and 'download remote backups' to Role & Capabilities addon.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed: Webp images were not served properly in some environments.
- Fixed: Some CDN settings would not take effect.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.1.3 =
= WPvivid Backup & Migration Pro =
- Fixed: Last backup time of incremental backup was not deleted when deleting the plugin.
- Fixed the ‘File not found’ error when downloading incremental backups from Backblaze B2.
- Fixed some bugs on the plugin UI.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.1.2 =
= WPvivid Backup & Migration Pro =
- Fixed: Adding Drobpbox failed.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.1.1 =
= WPvivid Backup & Migration Pro =
- Major Update: Introduced a new and more reliable restore mechanism(workflow and UI).
- Fixed: Father themes were excluded from backup by default.
- Fixed: Sizes of some backups showed as 0KB in pCloud in some cases.
- Optimized the process of cleaning unused images.
- Added an option in backup manager tab to display all backups.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Optimized the process of converting images to WebP format.
- Optimized the plugin code.
= 2.0.28 =
= WPvivid Backup & Migration Pro =
- Fixed the pclzip 0 error that could occur during restore in PHP 8.
- Fixed: Additional database information was not saved when setting up incremental backup schedule.
- Fixed: Adding Wasabi storage failed in PHP 8.
- Fixed: WPvivid branding was not replaced on the License page when white label was enabled.
- Added an option to reset current white label settings.
- Added an option to not show the plugin update notice on website pages.
- Added an option to not check DeleteObject when adding Wasabi storage.
- Updated: Display logs by date when selecting 'show all logs'.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed: WebP images did not take effect in some environments.
- Fixed: Images in the media library of some subsites in Multisite showed 'not supported'.
- Fixed: WebP images did not take effect when selecting 'use rewrite rule' option.
- Optimized the plugin code.
= 2.0.27 =
= WPvivid Backup & Migration Pro =
- Fixed: Non-admin users could see the plugin update notice.
- Fixed: White label failed to take effect in some pages.
- Optimized the UI of the Manual Backup page.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.26 =
= WPvivid Backup & Migration Pro =
- Added support for Dropbox's new API.
- Fixed: some images used in Elementor would be scanned as unused.
- Fixed some broken links on the plugin UI when the white label option was enabled.
- Fixed: some characters did not display correctly in non-English language websites when WPvivid plugins were activated.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.25 =
= WPvivid Backup & Migration Pro =
- Added an option of re-calculating size of the contents to be backed up after you finish selecting them.
- Fixed: WPvivid menu was visible on the top bar of the non-network admin pages in WordPress Multisite.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
- Successfully tested with WordPress 5.8.1.
= WPvivid Image Optimization Pro =
- Fixed a fatal error that would occur during the optimization process in some environments.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
- Successfully tested with WordPress 5.8.1.
= 2.0.24 =
= WPvivid Backup & Migration Pro =
- Added support for migration of unconventional save of the media paths.
- Added more cycle options for files full backup in an incremental backup schedule.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.23 =
= WPvivid Backup & Migration Pro =
- Added a tool to the plugin dashboard to search and replace urls.
- Added an option to the plugin settings to remove junk files and free up space disk.
- Fixed: White label did not take effect on the WordPress Updates page.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Added support for WordPress Multisite.
- Added an option to the plugin settings to remove image backups.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.22 =
= WPvivid Backup & Migration Pro =
- Refined some descriptions and warning messages on the plugin UI.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Added an option of compressing GIF images.
- Added an option of excluding images from lazy loading.
- Added an option of converting GIF to WebP.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.21 =
= WPvivid Backup & Migration Pro =
- Fixed the 'Unauthorized' error when adding Backblaze as cloud storage.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Added an option of adjusting 'Memory Limit' in the plugin settings.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.20 =
= WPvivid Backup & Migration Pro =
- Added support for Backblaze cloud storage.
- Successfully tested with WordPress 5.8.
- Fixed some bugs on the plugin UI.
- Fixed the error 'WPvivid_Ngg_Image_Optimize' that occurred when the NextGEN Gallery plugin is enabled.
- Fixed some bugs in the plugin code.
= WPvivid Image Optimization Pro =
- Successfully tested with WordPress 5.8.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.19 =
= WPvivid Backup & Migration Pro =
- Fixed a bug in the plugin code that could cause MainWP child sites to disconnect after clicking the 'Sync Dashboard with Child Sites' button.
- Fixed: WPvivid branding in some sections of the plugin UI could not be white labeled.
- Fixed: An error pop-up would flash when saving settings in the FireFox browser.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Beta ended. The stable version of the WPvivid Image Optimization Pro plugin is officially released!
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.18 =
= WPvivid Backup & Migration Pro =
- Updated the API for WPvivid Backup for MainWP extension.
- Fixed a logical issue on the plugin UI.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.17 =
= WPvivid Backup & Migration Pro =
- Fixed the error 'need_reactive' that could occur in some cases when installing addons.
- Updated: For easier management, the features 'White Label' and 'Roles & Capabilities' was split from the plugin to become individual addons. You can install them as needed.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Updated and optimized the workflow of bulk image optimization.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.16 =
= WPvivid Backup & Migration Pro =
- Fixed a critical bug that could occur during backup process in some cases.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Updated: WebP conversion and optimization now happen on WPvivid cloud server rather than your website.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.15 =
= WPvivid Backup & Migration Pro =
- Fixed: WPvivid license status lost after restoration in some cases.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Added support for GIF images optimization.
- Fixed the SyntaxError when optimizing images in some cases.
- Fixed image sizes settings did not take effect in some cases.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.14 =
= WPvivid Backup & Migration Pro =
- Updated and optimized the workflow of selecting backup content.
- Fixed the 'CDN Integration' page broken issue when the plugin is white labeled.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Added a new feature of selecting a directory to optimize images inside it.
- Fixed a bug that can occur in some cases when resizing images.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.13 =
= WPvivid Backup & Migration Pro =
- Fixed: Adding FTP storage failed when the FTP password contains special characters.
- Fixed: Backup schedule cycle did not display properly in the schedule editing page.
- Fixed: After removing folders from the exclusion list, they appeared again for next backup.
- Fixed some display issues in the plugin UI.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= WPvivid Image Optimization Pro =
- Added a new feature of integrating CDN to your WordPress site.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.12 =
- Fixed: Now the plugin can be undefined directly from WordPress plugins list.
- Fixed: Now addons will also be undefined when the plugin is undefined.
- Improved the plugin UI.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.11 =
- Fixed: Updating WPvivid Plugins Pro failed in some cases.
- Fixed the UI broken issue when white label option was enabled.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.10 =
- Fixed some css and js errors when white label option was enabled.
- Fixed: Could not update plugins and themes when auto-backup option was enabled.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.9 =
- WPvivid Backup Pro 2.0 beta ended.
- Redesigned and improved the plugin UI.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.8 =
- Fixed: Failed to delete remote backups in bulk in some cases.
- Fixed: A display issue on 'Edit Schedule' page.
- Fixed: A PHP error that occurred in rollback backups when updating themes.
- Fixed: 'Save Changes' button on 'Edit Incremental Backups' page did not work when there was no cloud storage configured.
- Fixed some bugs in the plugin code and optimized the plugin code.
- Added an option that will allow upgrading from 'WPvivid Backup Pro' to the upcoming 'WPvivid Plugins'.
= 2.0.7 =
- Added a progress bar to restoration process.
- Added: Once a backup is created, the plugin will check whether the zip is good and will prompt you if it is corrupted.
- Added an option to include non-wp prefix tables in a backup.
- Added an option to choose whether to attach log file in a backup email report.
- Fixed: Email report was not sent after a backup was completed in some cases.
-Fixed: Backup retention for cloud storage did not take effect in some cases.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
- Successfully tested with WordPress 5.6.1.
= 2.0.6 =
- Added support for WPvivid Backup Extension for MainWP.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.5 =
Fixed: Failed to activate WPvivid Pro license in some cases.
= 2.0.4 =
- Changed: Email is no longer needed to activate WPvivid plugins, you can use either a father license or a child license.
- Added: Specify remote storage for a backup schedule.
- Added an option to include directories that were excluded by default in the plugin code.
- Fixed: Plugins would not be restored correctly in some cases.
- Fixed: White label settings would not take effect in some pages.
- Fixed some bugs in the plugin code.
= 2.0.3 =
- Added options to set backup retention for each cloud storage.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 2.0.2 =
- 'Auto backup before update' now supports WordPress 'auto update' for plugins.
- Fixed the problem of not being able to switch tabs on the Settings page in some cases.
- The database encryption password is hidden now.
- Improved some text descriptions in the plugin UI.
- Fixed some bugs in the plugin code and optimized the plugin code.
= 2.0.1 =
- Added a check to 'auto backup before plugin update', so when the backup fails, the update won't continue.
- Changed: Password is no longer needed to activate WPvivid plugins, you can use either a father license or a child license.
- Fixed the insufficient permission error that occurred when authenticating FTP in some cases.
- Fixed the incorrect credential error that occurred when authenticating SFTP in some cases.
- Fixed: Could not find files when downloading incremental backups from pCloud in some cases.
- Fixed: When editing an existing backup schedule, the file tree under 'custom Uploads' option did not display properly. 
- Fixed the problem that scheduled tasks could not be created in some environments.
- Fixed the problem that the next backup time of incremental backups sometimes did not display accurately.
- Fixed the problem that sometimes backup on DigitalOcean Spaces could not be listed. 
- Optimized the refresh mechanism of Role & Capabilities settings pages.
- Improved some text descriptions in the plugin UI.
- Optimize the plugin interface.
- Successfully tested with WordPress 5.6.
= 1.9.24 =
- Removed the notice of launch of WPvivid 2.0.0 Beta.
- Improved descriptions of some labels under the Custom Backup option.
- Optimized the plugin code.
= 2.0.0 =
- A new, centralized and simpler plugin UI.
- Separated backup and migration pages.
- A simpler workflow for incremental backups.
- A simpler and more understandable filter option for backup list.
- A redesigned Logs page with a filter option added.
- Numerous minor improvements based on user feedback over the past year.
= 1.9.23 =
- Fixed: WPvivid Pro login status did not remain in some cases.
- Fixed: A folder was automatically excluded from backups when the folder name matches regex: ^uploads.*$.
- Optimized the workflow of restoring incremental backups.
- Optimized the Uploads option and refined the description.
- Optimized the plugin code.
= 1.9.22 =
- Remote storage credentials stored in database are encrypted now.
- Deleting a website from My Account now also deletes WPvivid Pro account from the website.
- DeleteObject checking is now optional when adding S3 remote storage.
- Added support for Amazon S3 Africa region.
- 'Auto backup before update' now also includes database.
- Added the ability to comment the auto backup before updating a single plugin.
- Added an option to uncheck all plugins and themes in the restore interface.
- Added an option to exclude a theme/plugin subdirectory from a backup/migration.
- Fixed: Schedule status showed as 'Disabled' even the existence of an incremental backup schedule.
- Removed the option 'Reset WordPress before restore' when restoring incremental backups.
- Fixed some bugs in the plugin code.
= 1.9.21 =
- New feature Added: Find and clean unused images in your WP media library.
- Added an option to set the default backup location in Settings.
- Included white label settings when exporting plugin settings.
- Added a cancel button to 'uploading backups' process
- Fixed the 'Get pCloud token failed' error when adding pCloud account located on EU servers.
- Fixed: Excluding the /uploads folder from a scheduled backup did not take effect.
- Fixed: Auto backups before update failed in some cases.
- Fixed: The option 'auto backup before update' still appeared when it's disabled.
- Fixed: Some special characters in a database could not be restored properly.
- Fixed: Only 1000 backups stored on Amazon S3 could be displayed.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 1.9.20 =
- Added database backups encryption.
- Added support for migrating a MU child site to a single WP install.
- Added a token system for authenticating pro license with a token.
- Fixed: Backups information did not display when switching from Incremental folder to Common.
- Fixed: After editing remote storage information, changes did not display.
- Fixed: Exported WPVivid Backup Pro settings file was not white labeled.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 1.9.19 =
- Added an option to reset WordPress before a restore process.
- Fixed: Could not edit chunk size when editing pCloud remote storage.
- Fixed: Retention did not work on Wasabi remote storage.
- Fixed: Locking a backup didn't work on remote storage.
- Fixed: The error message 'The free version is required' did not display properly on WordPress Multisite in some cases.
- Optimized the plugin code.
= 1.9.18 =
- Fixed: 'Backup Content' was missing in backup report emails.
- Changed: The white label settings is no longer in the left admin menu. Now you can access it by adding a slug (variable) at the end of the url of your WPvivid plugin page.
- Fixed: WPvivid was not white labeled in the plugins list.
- Fixed: When two remote storage of the same type were configured, backups were only sent to one remote storage.
- Fixed: Backup display issue in the backup list when using Microsoft OneDrive.
- Fixed: 'A root path is required' error that would occur when you edit remote storage.
- Fixed: 'Auto backup before update' did not work on MU.
- Added an option in the plugin settings to delete the WPvivid directory when deleting the plugin.
- Added an option in the plugin settings to auto delete old backup files when the current backup retention is lower than the previous one.
- Optimized the plugin UI.
- Fixed some bugs in the plugin code.
= 1.9.17 =
- Fixed: Backup schedules failed in some cases.
- Added support for MainWP Client Reports.
- Optimized incremental backup schedules.
- Added an option to delete logs.
- Fixed some bugs in the plugin code.
= 1.9.16 =
- Fixed: Could not activate pro.
= 1.9.15 =
- Changed: As requested by many users, we have split the staging feature from WPvivid Backup Pro. It now works as an independent plugin. In this way, users can decide to install or uninstall it conveniently according to their needs.
- Excluded fastcgi session files when creating a backup.
- Excluded the /wphb-cache directory when creating a backup.
- Added an option to set the block size of uploads for Amazon S3 storage.
- Fixed: Error logs were missing in some server environments.
- Fixed: Email report undelivered because a numeric 1 was added to the email address.
- Fixed: Root directory is forbidden to set to '/' when connecting to a FTP server.
- Merged the plugin’s Logs tab with the Debug tab.
= 1.9.14 =
- Added white label feature.
- Successfully tested with WordPress 5.4.
- Finished the beta of incremental backups.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 1.9.13 =
- Updated the API for WPvivid Backup for MainWP extension.
- Optimized the plugin UI.
- Fixed some bugs in the plugin code.
= 1.9.12 =
- Fixed: Some features did not work properly after the last update.
- Fixed: WPvivid Backup Pro would not work properly when the free version was not updated.
- Fixed: Could not retrieve posts list on a multilingual site in an export.
- Changed: Auto backup before update now excludes WPvivid Backup free.
= 1.9.11 =
- Added a domain/url replacement tool.
- Added an option to overwrite existing pages in an import.
- Fixed: Permalinks went back to default after copying staging to live.
- Fixed: The staging site url and admin url were not displayed correctly.
- Fixed: Additional database tables were not displayed when copying staging to live.
- Fixed: Could not retrieve posts list on a multilingual site in an export.
- Fixed some bugs in the plugin code and optimized the plugin code.
= 1.9.10 =
- Fixed: Replacing URLs of the content created by Elementor failed in the process of pushing a staging site to a live site.
- Fixed: Pagination did not work in the remote storage list.
= 1.9.9 =
- Added an option to create staging sites under /wp-content directory.
- Added a log out option on the pro account page.
- Added an option to set PHP Memory Limit for Restoration in the plugin settings.
- Fixed a problem that occurred when selecting Monthly as the incremental backup cycle.
- Added a two-day schedule cycle.
- Fixed: The plugin failed to identify the time for an incremental backup in some cases.
- Fixed: Cronjob events kept being added in some cases.
- Fixed a bug occurred when replacing domain names in the restoration process in some cases.
- Changed the timestamp to WP local time in the exported json settings file 
- Optimized the plugin UI.
= 1.9.8 =
- Added support for Wasabi and pCloud storage.
- Added an option to run a full backup immediately after the new incremental backup schedule is created.
- Updated the API for WPvivid Backup for MainWP extension.
- Fixed some bugs in the plugin code.
= 1.9.7 =
- Added incremental backup.
- Added a column to the backup list to display backup content type.
- Optimized the plugin code.
= 1.9.6 =
- Fixed: Failed to update the last backup information.
- Fixed: Uploading backups to Dropbox failed in some cases.
- Fixed: Restoring backups failed on Nginx servers because of the user.ini file issue.
- Added an option to choose whether to login to access frontend of the staging site
- Optimized the plugin code.
= 1.9.5 =
- Added support for WPvivid Backup Extension for MainWP.
- Added an option to automatically update the plugin in the plugin Pro tab.
- Fixed some bugs in the plugin code.
= 1.9.4 =
- Optimize the algorithm for staging site
- Optimize the codes of uploading to Microsoft OneDrive
- Add locks for backups stored in remote storage to avoid being deleted automatically
- Optimize the compatibility with other plugins.
- Fixed: Unable to access the next page of the backup  list of remote storage.
- Fixed some small bugs in the plugin code.
= 1.9.3 =
- Added a settings tab for the staging feature.
- The plugin now shows local time for backups.
- Fixed some bugs in the plugin code.
= 1.9.2 =
- New feature: Create staging sites and copy a staging site to a live site.
- New feature: Edit user access to WPvivid Backup Pro features
- Successfully tested with WordPress 5.3
- Fixed some bugs in the plugin code.
- Optimized the plugin UI.
= 1.9.1 =
- New feature: Additional Database backup
- Fixed the invalid WPvivid free version download link in the warning message that appears when you install WPvivid Pro without the free version.
- Refined and simplified the plugin menu in admin menu and top admin bar.
- Optimized the plugin code.
= 1.9.0 =
- WPvivid Backup Pro Beta initial release.