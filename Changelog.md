# Changelog
All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning 2.0.0.

## [Unreleased]
### Added
- Initialize changelog file following Keep a Changelog format.
- Laravel Breeze authentication scaffolding (login, register, password reset, email verification, profile).
- Protected dashboard route and view (`/dashboard`) behind `auth` and `verified` middleware.
- Built frontend assets with Vite as part of Breeze installation.
### Changed
- Switched authentication to username-based login and registration (username + password only).
- Simplified login/register Blade views to use `username` field; removed email inputs and links to password reset on login page.
- Removed `verified` middleware from `/dashboard` route.
### Database
- Added migration to add unique `username` column to `users` table and make `email` nullable.
- Note: Existing users will need a `username` value to log in.
