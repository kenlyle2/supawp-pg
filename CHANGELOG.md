# Changelog

All notable changes to this project will be documented in this file.

> **Tags:**
>
> - :boom: [Breaking Change]
> - :eyeglasses: [Spec Compliancy]
> - :rocket: [New Feature]
> - :bug: [Bug Fix]
> - :memo: [Documentation]
> - :nail_care: [Polish]

## [1.12.0] - 2026-02-24

#### - :rocket: [New Feature]

- Add "No Verification" option for Email Verification Method in signup settings

## [1.11.0] - 2025-12-28

#### - :rocket: [New Feature]

- OTP option for reset password

## [1.10.0] - 2025-11-29

#### - :rocket: [New Feature]

- **Auto-Update Functionality**
  - Added automatic plugin update capability with license validation
  - Integration with techcater.com API for version checking
  - WordPress plugin update API integration for seamless updates
  - Update notification banner on admin settings page
  - Links to plugin update page and user account dashboard

#### - :nail_care: [Polish]

- WordPress 6.8.3 compatibility confirmed

## [1.9.0] - 2025-11-20

#### - :rocket: [New Feature]

- **Email OTP Token Verification for Signup**
  - Added 6-digit token email verification for new user signups
  - Inline verification form displays after signup for unconfirmed emails
  - Automatic code resend functionality
  - Admin setting to choose between Magic Link and OTP Token verification methods
  - Works seamlessly with both `[supawp_signup]` and `[supawp_auth]` shortcodes

- **Unverified Email Login Flow**
  - Automatic detection of unverified emails during login
  - Smart code resend when unverified user attempts to login
  - Inline verification form on login page
  - Auto-login after successful verification with password preservation

- **Comprehensive Test Coverage**
  - Added 118+ unit tests covering all TypeScript functionality
  - Test coverage for signup, login, logout, OTP, auth, translations, database, and Google login
  - Shared test utilities for consistent mocking and test data
  - `npm run test:supawp` command for running SupaWP-specific tests

#### - :memo: [Documentation]

- Email verification template examples for both Magic Link and OTP Token methods
- Email announcement templates for OTP sign-in feature
- Complete testing guide with examples and best practices
- Migration guide for using shared test utilities
- Updated README with test file documentation

#### - :nail_care: [Polish]

- Test files excluded from distribution builds
- Cleaner code with debug logs removed after successful testing
- Improved error handling for email verification flows

## [1.8.0] - 2025-11-16

#### - :rocket: [New Feature]

- Added full Email OTP Token authentication support to `[supawp_auth]` & `[supawp_login]` shortcode
  - Toggle between password and OTP login methods when both are enabled
  - Seamless integration with existing login and signup flows
  - Supports all OTP features: send code, verify, resend, and back navigation

## [1.7.0] - 2025-10-13

#### - :bug: [Bug Fix]

- Fixed error messages not displaying due to missing `style.display = 'block'` property
  - Error messages were being added to DOM but remained invisible
  - Fixed in login form, forgot password form, and all success messages
  - All error/success messages now properly visible to users

## [1.6.0] - 2025-10-07

#### - :rocket: [New Feature]

- Added forgot password functionality to login shortcode
  - Users can now reset their password directly from the login form
  - Integrated with Supabase password reset flow
  - Automatic email delivery of password reset link

## [1.5.0] - 2025-10-03

#### - :rocket: [New Feature]

- Added `company` field support to extra_fields for signup flow
- Added `email_confirmation` parameter to shortcodes (default: true)
  - When set to false, automatically syncs user to Supabase table and enables WordPress auto-login if configured
- Added phone number validation with translated error messages
- Added translation support for password match indicator

#### - :boom: [Breaking Change]

- Changed all form field names from camelCase to snake_case:
  - `firstName` → `first_name`
  - `lastName` → `last_name`
  - `phoneNumber` → `phone`
- Updated field IDs accordingly (e.g., `supawp-signup-phonenumber` → `supawp-signup-phone`)

#### - :bug: [Bug Fix]

- Fixed 409 Conflict error by implementing upsert operation instead of insert for user sync
- Fixed phone and company fields not being saved to Supabase user metadata
- Fixed hardcoded translation strings to use translation system

#### - :nail_care: [Polish]

- Updated all translation files (POT, PO, MO) for Spanish, French, and Korean
- Enhanced debug logging for user sync operations
- Improved error handling for duplicate user insertions

## [1.4.0] - 2025-09-20

#### - :rocket: [New Feature]

- Added comprehensive localization and internationalization support:
  - Implemented multi-language support for Spanish (es_ES), French (fr_FR), and Korean (ko_KR)
  - Created `i18n/public_translations.php` for frontend translations
  - Added TypeScript translation utilities with type-safe interfaces
  - Generated POT template and PO/MO files for all supported languages
  - Automatic Supabase error mapping to translated messages
  - All form elements, buttons, and messages now properly translate based on WordPress language settings

## [1.3.4] - 2025-09-01

#### - :rocket: [New Feature]

- Added storage filter hooks for Supabase Storage integration:
  - `supawp_upload_image_to_supabase` - For image uploads to Supabase Storage
  - `supawp_delete_image_from_supabase` - For image deletion from Supabase Storage
  - `supawp_get_storage_config` - For accessing Supabase Storage configuration
- Implemented real storage logic in SupaWP_Service class using direct REST API calls
- Added comprehensive file validation, error handling, and logging for storage operations
- These hooks enable consistent storage operations across all SupaWP extensions
- Extensions can override behavior or let SupaWP handle operations automatically

#### - :memo: [Documentation]

- Added comprehensive storage integration guide (SUPAWP-STORAGE-INTEGRATION.md)
- Documented recommended filter hook signatures and implementation patterns
- Provided examples for direct REST API implementation and filter hook usage

## [1.3.3] - 2025-08-21

#### - :boom: [Breaking Change]

- Updated `save_post_to_supabase()` function signature to require `$table_name` parameter as first argument
- Function now accepts `save_post_to_supabase($table_name, $post_data)` instead of `save_post_to_supabase($post_data)`
- Removed `get_table_name()` method from SupaWP_Service class
- All table name generation now uses `SupaWP_Utils::table_name_generator($post_type)` directly

#### - :memo: [Documentation]

- Enhanced documentation for `supawp_save_data_to_supabase` filter with new table name parameter examples
- Added advanced usage examples showing explicit table name specification
- Updated all code examples in documentation to reflect new function signatures

## [1.3.1] - 2025-08-05

#### - :rocket: [New Feature]

- Added `supawp_init` action hook for extension plugins to integrate with SupaWP
- This hook is fired after all core SupaWP components are initialized
- Enables third-party plugins to extend SupaWP functionality safely

## [1.3.0] - 2025-05-31

- Add the ability to save data to Supabase


## [ 1.2.0 ] - 2025-05-09

#### - :boom: [Breaking Change]

- Add 'supawp_' prefix to all field names in the admin settings for better namespacing.
- Field names that changed: 'auth_methods', 'supabase_url', 'supabase_anon_key', 'wp_auto_login_enabled', 'redirect_after_login', 'redirect_after_logout'.
- Existing users may need to re-enter their settings after updating.

#### - :rocket: [New Feature]

- Add Google login/signup integration with Supabase.
- Implement social login button in all authentication forms.
- Improve auth form structure to properly handle different authentication methods.
- Add support for toggling between login and signup views in the combined auth form.
- Enhance JavaScript handlers with null checks for better stability.

## [ 1.1.0 ] - 2025-04-26

#### - :rocket: [New Feature]

- Add "Sync Data" tab to admin settings.
- Add setting for Supabase users table name.
- Sync user data (id, email, created_at, updated_at) to specified Supabase table on first login.
- Add database setup documentation (docs/database.md).

## [ 1.0.0 ] - 2025-04-20

#### - :rocket: [New Feature]

- Initial release of the SupaWP plugin. 
