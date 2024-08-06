# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.13]  
### Modified
- Added additional check for if post already exists
- Fixed coding style issues (PSR12)

## [1.0.12] - 2024-07-17
### Modified
- Changed community lookup from post_title to post_name (slug). This should help solve curly quotes/backtick problem.

## [1.0.11] - 2024-07-15
### Modified
- Updated bump.sh script to ensure changelog is updated before git commit/tagging.

## [1.0.10] - 2024-07-15
### Modified
- Modified categories to include the listing type as the top-level
- Fixed category hierarchy
  
## [1.0.9] - 2024-07-11
### Modified
- Fixed setting ACF field for the "region".

## [1.0.8] - 2024-06-12

### Modified
- Fix category hierarchy

## [1.0.7] - 2024-06-12

### Added
- Tie ACF community field to the community custom post type
  
### Modified
- Fixed delete extra non-causeway listings (or ones that have been removed from Causeway)
- Fix category assignments
- Updated dependencies

## [1.0.6] - 2024-05-08

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
