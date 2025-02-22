## 3.7.1 - 2019-10-24
### Added
- Add support for `:empty:` and `:notempty:` (Fixes #214)

### Fixed
- Fix `embed` and `imgSrcSet` not setting options correctly when outputting from a map field (Fixes #215)

## 3.7.0 - 2019-10-15
### Added
- Add docs
- Add Craft GraphQL support
- Add Pro edition
- Add static map image support
- Add new map field size options
- Add IP based user location lookup
- Add ability to redirect to a specific site based off user location
- Add `coordinate` query argument to CraftQL (Closes #205)
- Add "mini" size for a tiny field footprint (Closes #203)
- Add `address()` method to map value for easy address formatting
- Add galactic address parts

### Changed
- 🍆 New, sexier UI! 💦
- Mapbox, Apple Maps, and Here are now only available in Maps Pro
- Now requires Craft 3.2.1 or newer

### Improved
- Remove Vue from JS bundle to reduce file size
- Removed fly animation when updating map location for snappier UI (Closes #202)

### Fixed
- Fix map not showing when other Vue based plugins interfere (Fixes #196)
- Fix issue when migrating from an older version of Maps (Fixes #195)
- Fix project config migration issue (Fixes #207)
- Fix issues upgrading Maps from Craft 2 to 3 (via [@roelvanhintum](https://github.com/roelvanhintum))

## 3.6.4.3 - 2019-08-30
### Fixed
- Fix issue when trying to save map field on initial draft entry

## 3.6.4.2 - 2019-08-01
### Changed
- Don't update project config unnecessarily during migration (Closes #194)

### Fixed
- Fix migration error when upgrading from 3.3.4 or lower (Fixes #192)

## 3.6.4.1 - 2019-07-30
### Fixed
- Fix error when populating legacy parts server-side from lat/lng
- Fix error when logging invalid legacy part

## 3.6.4 - 2019-07-30
### Fixed
- Remove errant debug code causing migration to run every request (Fixes #190)
- Fix migration trying to change a column type to a table (Fixes #189, #188)

## 3.6.3 - 2019-07-25
### Added
- Add min / max zoom settings to map field (Closes #186)

### Fixed
- Fix migration from Craft 2 (Fixes #153)
- Fix issue when column already exists during migration (Fixes #187)

## 3.6.2.2 - 2019-07-24
### Fixed
- Fix migration issue when matrix / super table blocks don't have any fields (Fixes #184)

## 3.6.2.1 - 2019-07-23
### Fixed
- Fix migration issue when no matrix or SuperTable blocks exist (Fixes #182)
- Fix issue with Google trying to set legacy parts that aren’t supported (Fixes #183)
- Fix getting top-level map value part if no parts exist (Fixes #181)

## 3.6.2 - 2019-07-23
### Added
- Add `postal_code_suffix` to `PartsLegacy` (Fixes #179)

### Fixed
- Fix migration error when upgrading from 3.4.x to 3.6.x (Fixes #178)
- Fix project config content column type being string instead of text (Fixes #180)

## 3.6.1 - 2019-07-19
### Added
- Add support for getting parts without having to go via the `parts` property. 
(i.e. `myMap.parts.number` can be simply `myMap.number`). This _doesn't_ work 
for the `address` part, which is already in use and returns the full address 
as a string (alternatively, use the `streetAddress` alias). (Closes #154)
- Add `streetAddress` alias of `address` to Parts.

### Changed
- `PartsLegacy` will be used when Google is the chosen Geo service, giving 
access to additional Google specific parts (Fixes #167)

### Fixed
- Fix error when normalizing value without an element (Fixes #174)
- Fix JS error when using two different API keys for Google maps services (Fixes #165)
- Fix parts being lost when moving from new to legacy (any other geo service to google)
- Fix issue with Mapbox geo service when country was unrestricted
- Fix JS issues when using Apple or Google Maps in an element edit HUD (Fixes #175)

## 3.6.0 - 2019-07-12

> {warning} This update changes how map data is stored, moving away from an 
element type. This means if you are eager loading the a map field, you'll want 
to remove the `with` from your query and `[0]` when outputting the map (if you 
have it). We also **strongly** recommend taking a backup before updating.

> {tip} If you get a `Column not found` error when upgrading, try running `./craft migrate/all`.

### Changed
- Reformat data structure to remove map element type and need for eager loading

### Fixed
- Fix missing postcode warning (Fixes #169)
- Fix map save DB issue in Craft 3.2 (Fixes #170)
- Fix map not retrieving saved values in Craft 3.2 (Fixes #171)
- Fix DB error on duplicate import via FeedMe (Fixes #168)
- Fix maps not propagating across sites (Fixes #141)

## 3.5.2 - 2019-06-20
### Improved
- FeedMe can now import the individual map parts

## 3.5.1 - 2019-06-20
### Added
- Maps can now populate address and lat/lng data based off only a postcode

## 3.5.0 - 2019-06-13
### Added
- Added FeedMe support!

### Fixed
- Fixed results being duplicated when searching by location when an entry has 
multiple map fields within the search catchment.
- Account for missing Craft 2 API keys
- Fix HERE search not working when no country restriction was set

### Changed
- Add default zoom to map element
- Update preferred country instructions to be clearer
- Support rendering a map field without a value
- Use field handle as table alias suffix, instead of random bytes

## 3.4.11 - 2019-04-05
### Fixed
- Map records are no longer double saved when upgrading to from Craft 2 to 3

## 3.4.10 - 2019-04-04
### Fixed
- Map records are no longer double saved when upgrading to >3.4.x

## 3.4.9 - 2019-04-01
### Added
- Added option to show lat / lng fields

### Fixed
- Fixed map not validating correctly
- Fixed wrong map value being shown on element index with multiple sites
- Fixed missing table prefix in map element query
- Fixed migration issue when upgrading due to duplicate element IDs

### Improved
- Scrolling to zoom disabled on map
- Clearing the map will no longer store the default data

## 3.4.8 - 2019-03-27
### Fixed
- Fix error when migrating a field from Craft 2 when `countryRestriction` isn't set
- Location search excludes elements that have been soft-deleted
- Fixed issue restoring trashed elements that have a map field
- Map field elements are trashed and deleted correctly
- Fixed syntax issue on PHP <7.1.0
- Fixed error during repair migration when element doesn't exist

## 3.4.7 - 2019-03-25
### Fixed
- Fixed JS error when clearing field
- Fixed missing parts when using Google maps for geo-coding

### Improved
- Clear button now translatable

## 3.4.6 - 2019-03-25
### Added
- Added "Clear" button
- Always show full address field even if address block is hidden

### Fixed
- The really shitty element stuff. Is good now. I think.

## 3.4.5 - 2019-03-25
### Fixed
- Fixed maps failing to get value after save

### Changed
- Using Google Maps geo service will result in legacy parts always being used, 
meaning you can access all available address components.

## 3.4.4 - 2019-03-22
### Fixed
- Fixed some issues when upgrading from older versions of Maps. We recommend 
upgrading from 3.3.4 or lower directly to this release or later.

## 3.4.3 - 2019-03-20
### Changed
- You can now pass a map to the location query (fixes #99)

### Fixed
- Fixed issue when `cp-field-inspect` plugin is installed (fixes #127)
- Fixed `elementId cannot be null` error on saving new entries with map fields (fixes #126)

## 3.4.2 - 2019-03-20
### Fixed
- Fixed issue setting old field settings after upgrade.

## 3.4.1 - 2019-03-20
### Fixed
- Fixed an issue where the map field class broke after upgrading.

## 3.4.0 - 2019-03-20

> {warning} This is a major update, we strongly recommend taking a database backup before updating!

### Changed
- SimpleMap is now Maps! We've re-written the plugin from the ground-up while 
keeping it backwards compatible (even back to Craft 2!)
- Maps is now powered by Vue!
- New icon yo

### Added 
- OpenStreetMap Support and map tiles
- Mapbox Support and map tiles
- Apple MapKit Map Tiles
- Here Maps Support and map tiles
- Wikimedia Map Tiles
- Carto Map Tiles
- Address inputs for manually settings address parts data.

### Improved
- We've normalized the map "Parts", so you'll always know what data you have available.
- CraftQL support: you can now query and mutate Maps fields via Graph!
- Field Customization: It's now possible to hide the location search, map, and address inputs.

### Fixed
- Maps are now multi-site aware and can be translated.

### Removed
- Removed lat/lng inputs from field
- Removed restrict by type
- Removed boundary restriction

## 3.3.4 - 2018-09-05
### Fixed
- Fixed a bug where SimpleMap would not validate required fields. (via @samhibberd)

## 3.3.3 - 2018-03-13
### Fixed
- Fixed a bug where SimpleMap would cause the `ResaveElements` job to error when triggered via console.

## 3.3.2 - 2018-03-05
### Added
- Added docs for using a config file to configure the plugin.

### Fixed
- Fixed JOIN alias issue when using the Element API plugin (via @idontmessabout)

## 3.3.1 - 2018-01-30
### Fixed
- Fixed JS bug on settings page

## 3.3.0 - 2018-01-30
### Fixed
- Added a fix for those annoying `Call to a member function getMap() on null` bugs

### Improved
- Map height no longer jumps when page loads
- Vastly improved the map fields settings UI/UX
	- No more nasty text fields!
	- Map height and position is now set by resizing and moving a map
	- Auto-complete search bounds can now be drawn directly onto a map
	- Radio buttons are now drop-downs

### Changed
- Now using the plugins `afterInstall` function instead of the plugin after install event
- The "Hide Lat/Lng" option is now true by default

## 3.2.0 - 2018-01-25
### Fixed
- Fixed bug where pagination would error when querying via a map field. #70

### Improved
- Updated CraftQL support (via @markhuot)
- Removed webonyx/graphql-php dependency #71
- Improved address and lat/lng input sizing on smaller screens and in a HUD #73
- Updated Mapbox example to use latest API #74

## 3.1.3 - 2017-12-18
### Fixed
- Map fields no longer cause global sets to fail to save!

## 3.1.2 - 2017-12-18
### Fixed
- Fixed settings not translating for non-English languages
- Fixed boundary settings fields not accepting decimals

## 3.1.1 - 2017-11-30
### Fixed
- Fixed bug where maps were failing to save.

## 3.1.0 - 2017-11-30
### Added
- [CraftQL](https://github.com/markhuot/craftql) support!
- Added `craft.simpleMap.getLatLngFromAddress($addressString[, $country])`.

### Improved
- The maps `parts` now contains all available options from [here](https://developers.google.com/maps/documentation/geocoding/intro#Types) (including the `_small` variants). Any options without values are returned as empty strings.

## 3.0.4 - 2017-11-28
### Added
- Added ability to restrict location search by country

### Changed
- New icon!

## 3.0.3 - 2017-11-08
### Added
- It's now possible to save the map field with only an address! Useful for populating the field from the front-end. (Requires the Geocoding API).

### Improved
- The address and lat/lng are now validated.

## 3.0.2 - 2017-11-03
### Fixed
- Fixed a bug where location searches would error if `orderBy` was not defined

## 3.0.1 - 2017-11-03
### Fixed
- Fixed maps not rendering

## 3.0.0 - 2017-11-03
### Changed
- Initial Craft 3 Release
