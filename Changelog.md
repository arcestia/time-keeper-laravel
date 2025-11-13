# Changelog
All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning 2.0.0.

## [Unreleased]
### Added
- Initialize changelog file following Keep a Changelog format.
- Laravel Breeze authentication scaffolding (login, register, password reset, email verification, profile).
- Protected dashboard route and view (`/dashboard`) behind `auth` and `verified` middleware.
- Built frontend assets with Vite as part of Breeze installation.
- Centralized inventory cap configuration (`config/inventory.php`).
- Inventory API now includes `cap` in `/api/inventory` response for dynamic UI display.
- Inventory selling endpoint `/api/inventory/sell` to sell items back at 50% of store price.
- Frontend Sell action in Inventory page with credited seconds feedback.
- Inventory UI: client-side search and sorting for inventory and storage lists.
- Inventory UI: per-item "Move all to storage" quick action.
- Inventory UI: display item details (type, description, per-item price).
- Travel feature: new page and API to take a step with random 2–5s delay; rewards XP, time seconds, and random items based on user level; XP and time rewards scale with premium multipliers. Item rewards overflow to storage if inventory cap would be exceeded.
- Expedition scaling config (`config/expeditions.php`) for XP/time per level+hour, variance, and item quantity bands by expedition level.
### Changed
- Switched authentication to username-based login and registration (username + password only).
- Simplified login/register Blade views to use `username` field; removed email inputs and links to password reset on login page.
- Removed `verified` middleware from `/dashboard` route.
- Replaced hardcoded inventory cap with `config('inventory.cap')` in `StoreController@buy` and `InventoryController@moveToInventory`.
- Inventory page now displays the global cap dynamically from API and surfaces server error messages instead of generic failures.
- Inventory page action feedback improved to show credited seconds on successful sale.
- Inventory UI: highlight inventory meta when near capacity (yellow at 75%, red at 90%).
- Travel UI: button is dimmed/disabled during step delay and results are shown via Toastr notifications.
- Travel UI: fixed success detection to treat responses with `{ ok: true }` as success and added `credentials: 'same-origin'` plus Toastr/jQuery includes to avoid CSRF/notification issues.
- Travel UI: removed inline status/log output; results are shown only via notifications.
- Travel UI: added a small progress bar on the "Take a step" button that animates during the delay.
- Travel: Premium tier 20 now gets fast travel with 1 second delay per step.
- Expeditions: XP and time rewards now scale by expedition level and duration (with variance and premium multipliers for XP/time). Item quantities scale by expedition level with duration bonus; loot delivered to storage.
- Expeditions: Increased XP scaling (raised xp_per_level to 15 and xp_per_hour to 6) to better outpace short Travel steps.
- Expeditions: Adopted Option B XP formula: `level*xp_per_level + hours*(xp_per_hour_base + level*xp_per_hour_per_level)`. Added `xp_per_hour_base` and `xp_per_hour_per_level` config keys and updated Expeditions view estimators to match.
- Expeditions: Increased passive XP strength at higher levels (xp_per_hour_base 10→12, xp_per_hour_per_level 1.2→1.5).
- Expeditions: View estimates now auto-apply premium multipliers when active (XP and Time).
### Database
- Added migration to add unique `username` column to `users` table and make `email` nullable.
- Note: Existing users will need a `username` value to log in.
