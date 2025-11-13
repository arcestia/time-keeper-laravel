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
### Changed
- Switched authentication to username-based login and registration (username + password only).
- Simplified login/register Blade views to use `username` field; removed email inputs and links to password reset on login page.
- Removed `verified` middleware from `/dashboard` route.
- Replaced hardcoded inventory cap with `config('inventory.cap')` in `StoreController@buy` and `InventoryController@moveToInventory`.
- Inventory page now displays the global cap dynamically from API and surfaces server error messages instead of generic failures.
- Inventory page action feedback improved to show credited seconds on successful sale.
- Inventory UI: highlight inventory meta when near capacity (yellow at 75%, red at 90%).
### Database
- Added migration to add unique `username` column to `users` table and make `email` nullable.
- Note: Existing users will need a `username` value to log in.
