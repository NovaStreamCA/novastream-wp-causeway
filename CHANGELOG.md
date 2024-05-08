# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.5] - 2024-05-08

### Added
- Added checks to only get the post ID based on any of the following statuses: pending, draft, publish.
  This eliminates a bug that it could return a similar listing that is in the trash bin and the public one doesn't get updated
- Added contact_name

### Modified
- Changed some field names around and grouped others to simplify.

### Removed
- Removed some commented code and unused code

## [1.0.4] - 2024-04-22

### Removed
- Removed the need to re-run composer on bump script.

## [1.0.3] - 2024-04-22

### Added
- Adding the ability to update taxonomies via REST API by supplying the "terms" key.

### Modified
- Replaced get_posts() for post ID lookup with WP_Query class.

## [1.0.2] - 2024-04-17

### Added
- Method for displaying warning messages to Query Monitor and WP CLI

### Modified
- Changed some magic numbers to their constants

### Removed
- "feed_images" ACF key update. Not sure where I found that field group key but it doesn't look like its in use.

## [1.0.1] - 2024-04-17

### Added
- Uninstall code

### Modified
- Small text changes in regards to LICENSE.md and README.md

### Added
- GitHub WordPress Plugin updater
  
## [1.0.0] - 2024-04-15

First release prepared to release on GitHub for beta-testing.

### Added
- GitHub WordPress Plugin updater

## [0.1.0] - 2024-03-13

### Added

- Project first started in development.
