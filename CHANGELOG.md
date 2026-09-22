# Changelog

## 1.3.10

- Added a PayPal donation link to the app store description and to the calendar feed section of the app.

## 1.3.9

- Use PHP attributes instead of legacy docblock annotations for the public calendar feed route.
- Reject planning entries where the end time is before the start time.
- Distinguish plans without a location ("No location") from plans referencing a permanently deleted location.
- Added a database index on `user_id` for planning entries.
- Planning entries now show the user's display name instead of the account login, in both the overview and the calendar feed. The account name remains the fallback if no user is found.

## 1.3.8

- Switched the App Store summary and description to plain metadata fields so the main app page shows the improved text.

## 1.3.7

- Improved the App Store description with clearer user, administrator, calendar, and use-case sections.

## 1.3.6

- Added extended English and German App Store descriptions.
- Added App Store screenshot metadata.
- Added website and repository metadata.

## 1.3.5

- Keep location names visible on existing planning entries after a location is permanently deleted.

## 1.3.4

- Added real screenshots to the README.
- Added restore and permanent delete actions for inactive locations.

## 1.3.3

- Improved responsive layout and modernized styling.
- Added dark mode friendly colors based on Nextcloud design variables.
- Fixed scrolling in the month view and mobile quick entry.
- Fixed German translations in the mobile quick entry.

## 1.3.0

- Added mobile quick entry page.
- Added read-only team calendar feed.

## 1.2.0

- Added tokenized calendar feed for external calendar apps.

## 1.1.0

- Added multiple plans per day.
- Added optional time range and larger notes field.
- Improved daily, weekly, and monthly views.

## 1.0.0

- Initial version with location management and shared work location planning.
