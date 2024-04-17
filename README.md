
# Causeway 5.0 WordPress Importer

This plugin is a bridge to connect [NovaStream](https://novastream.ca)'s Causeway app with a WordPress site, mobile application or another solution.

[Causeway](https://beta.causewayapp.ca) is a software-as-a-service allowing the administrators to manage tourism listings located throughout Cape Breton Island which can be distributed to other websites and platforms without the data becoming out of sync. A single source of truth. 

We monitor listing change requests through our support ticketing system and will edit the listing appropriately. Tourism operators are encouraged to [reach out](#listing-updates) if their listing needs to be updated.

These changes will then be reflected on any platform that consumes our feed.

As of April 2024, there are currently eight (8) consumers of our API.

| Site                         | Version      |
|------------------------------|--------------|
| celtic-colours.com           | version 1.0  |
| northerncapebreton.com       | version 1.0  |
| canadasmusicalcoast.com      | version 3.0  |
| experienceingonish.com       | version 3.0  |
| visitbaddeck.com             | version 3.0  |
| victoriacounty.com           | version 3.0  |
| townofporthawkesbury.com     | version 3.0  |
| cbisland.com                 | version 5.0  |


## Requirements
- PHP 7.1 or later (8.0+ recommended)
- WordPress 6.2 or later (Latest version recommended)
- WordPress REST API (optional but recommended)

## Description
There are two ways the plugin saves listing data from our API into WordPress.

1. **REST API**  
WordPress' REST API allows users to create an application password for a user which would be used to import listings. When an edit is made on Causeway, it will contact all the applications who've been granted access to the feed and update/create the listing.

2. **Cronjob**  
This is a alternate method of importing the listings into the WordPress instance in case the REST API is unavailable or disabled.

## Installation
You can manually upload and install the plugin through the "Add New" button on the WordPress plugin section of the dashboard.

The latest zipball archives are available on our [Releases](https://github.com/NovaStreamCA/novastream-wp-causeway/releases) page for download.


## Updating
The plugin can be updated normally through the WordPress plugin dashboard when a new version is released. Releases along with their respective release notes are available on the GitHub repository.

Please note if auto-updates are disabled for any reason, there might be a chance you will not be notified about new updates and will have to manually update the plugin.

## Uninstall
When uninstalling this plugin, it will remove all associated data stored.

This includes any transient caching, schedule cron hooks and any stored API keys and other options.

## Configuration
You can configure the plugin settings by viewing the "Causeway" tab on the WordPress sidebar after logging into the administrative section.

### Primary Setup

**Causeway Backend URL:**  
The backend URL controls where the listing data comes from. Currently there are only two endpoints which serve the JSON data.

The latest version available for Causeway is version **5.0**. 

Previous iterations of Causeway are currently deprecated and when they are fully retired, the live URL may change. We will release a new version if this is the case and any changes regarding URL will be mentioned in the release notes and reflected in this documentation.

- **Causeway 5.0 Live** - https://beta.causewayapp.ca/export (Default)
- **Causeway 5.0 Development** - https://causeway5-api.novastream.dev/export


In most cases, you should only use the **default live** endpoint URL as the development URL could have breaking changes.

**Server API Key**  
API keys are different for each server accessing the endpoint. A server can have multiple secret keys. This allows multiple applications to access the same data (E.g. a mobile app and a WordPress install).

Please [contact us](mailto:support@novastream.ca?subject=Causeway+5.0+API+Key+Request) if you do not currently have an API key setup for your needs and we will have one setup for you.

Complete the configuration of these two settings by clicking the "Save Settings" button.

### Additional Setup
The following steps are optional but are recommended to enable near-instantaneous updating of listings on each platform.

#### Forced Import

Listings can be manually imported by visiting the configuration page and clicking "Import listings now".

**Please note:** This is a time-consuming operation and should be seldomly used. The process can take up to 10 minutes depending on the amount of listings that have been approved.

If you find yourself needing to do this action often, please ensure the REST API is enabled and configured properly. This should eliminate the need of having to forcefully import.

#### Application Password
An application password can be created for a user to allow Causeway to connect to the REST API for quicker updates.

Application passwords can be created in your profile section on the WordPress dashboard.
Once you create a password, please send us the domaibn of the website, the username along with the password and we can add it into Causeway.  

WP-CLI can be used to create an application password for a user by executing the following command in the root of your public htdocs folder:
```bash
wp user application-password create <username> "Causeway Importer" --porcelain
```


## Other Considerations

### Caching
The causeway data is cached in transient data for 1 (one) hour. If you are using the WP REST API and things are not updating near-instantaneously, please verify WordPress' transient cache, object cache, server and client-side caching is cleared before reporting an issue.

Transient cache can be cleared with a plugin through the WordPress admin. Other caching solutions are dependent on the hosting environment.

The transient may be cleared through WP-CLI by executing the following command:
```bash
wp transient delete causeway_data
```

### Cronjob
* The cronjob is only executed once a day at midnight.
* WordPress cron only executes if a visitor interacts with your site or you have disabled **WP_CRON** and used an alternate method of executing cronjobs.

The cronjob can also be executed via WP-CLI.

The cronjob will attempt to load the data from a transient if it was set. Otherwise it will contact the remote server to download the fresh data.
```bash
wp cron event run cron_import_causeway
```

You can also forcefully retrieve the fresh data by deleting the transient and running the import with one of the following flags --major, --minor, --rev.
```bash
composer run-script bump -- --rev
```

### Advanced Custom Fields
This plugin will attempt to update any existing ACF fields based on Verb Interactive's setup. There may be some ACF fields not used or updated by our plugin. It will also use their Events Manager plugin to generate dates for any events. 

### Developer Notes
There is a script included that can be used to bump versions and automatically tag and push them to GitHub.

In the root folder of the plugin, you can execute the following to accomplish that:
```bash
wp transient delete causeway_data; wp cron event run cron_import_causeway
```
## Release Notes / Road Map
Release notes for this version are available in the [CHANGELOG.md](CHANGELOG.md) file.


## Known Issues / Limitations
No known issues as of 2024-04-15.

To raise an issue that is not listed here, please read [how to report an issue](#support--reporting-issues).


## License
By downloading and using the Causeway Importer and its related services, you agree to the product [license terms](LICENSE.md).

License for this repository:

Copyright © NovaStream Inc. All rights reserved.

## Security

Refer to [SECURITY.md](.github/SECURITY.md) for disclosure of potential security vulnerabilities.

## Contributors
* Jason Jardine (jason@novastream.ca)  
Project Manager
* Matt Lewis (matt@novastream.ca)  
Developer - API and Importer WordPress plugin
* Dylan George (dylan@novastream.ca)  
Developer - Frontend

## Support / Reporting Issues


### Listing Updates
If you are a tourism operator and would like to request changes to your listing, please contact [listings@dcba.ca](mailto:listings@dcba.ca).

Please consult the following [document]() on how to properly send changes to us.

### Plugin

For any questions or issues related to this plugin, raise an issue with our ticketing system by sending an email with details to [support@novastream.ca](mailto:support@novastream.ca).

We will reply with a response within 48 hours.


<sub>**README.md last updated:** 2024-04-15</sub>
