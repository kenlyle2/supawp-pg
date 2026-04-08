# Password Reset Testing Guide

This document provides comprehensive testing steps for the OTP password reset feature in both `supawp_login` and `supawp_auth` shortcodes.

## Prerequisites

1. **Enable OTP Password Reset:**
   - Go to WordPress Admin → SupaWP → General tab
   - Find "Password Reset Method"
   - Select "6-Digit OTP Code (Enter code and new password on website)"
   - Click "Save Settings"

2. **Update Supabase Email Template:**
   - Go to Supabase Dashboard → Authentication → Email Templates
   - Edit "Reset Password" / "Recovery" template
   - Replace `{{ .ConfirmationURL }}` with `{{ .Token }}`
   - Example template:
   ```html
   <h2>Reset Your Password</h2>
   <p>Your password reset code is: <strong>{{ .Token }}</strong></p>
   <p>Enter this code on the website to reset your password.</p>
   <p>This code will expire in 60 minutes.</p>
   ```
   - Save the template

## Test 1: Magic Link Method (Default - Existing Flow)

### Setup
- Set "Password Reset Method" to "Magic Link (Click link in email)"
- Save Settings

### Test with supawp_login
1. Visit page with `[supawp_login]` shortcode
2. Click "Forgot Password?" link
3. Enter email address
4. Click "Reset Password"
5. **Expected:** Success message appears
6. Check email for magic link
7. Click the magic link
8. **Expected:** Redirected to password reset page
9. Enter new password
10. **Expected:** Password reset successful

### Test with supawp_auth
1. Visit page with `[supawp_auth]` shortcode
2. Click "Forgot Password?" link
3. Follow steps 3-10 from above
4. **Expected:** Same behavior as supawp_login

## Test 2: OTP Method (New Flow)

### Setup
- Set "Password Reset Method" to "6-Digit OTP Code"
- Save Settings

### Test with supawp_login

#### 2.1 Successful Reset
1. Visit page with `[supawp_login]` shortcode
2. Click "Forgot Password?" link
3. **Expected:** Forgot password form appears
4. Enter email address
5. Click "Reset Password"
6. **Expected:**
   - Success message: "A verification code has been sent to your email..."
   - OTP verification form appears with:
     - 6-digit code input
     - New password input
     - Confirm password input
     - "Reset Password" button
     - "Back" and "Resend Code" links

7. Check email for 6-digit OTP code
8. Enter the OTP code
9. Enter new password
10. Enter same password in confirm field
11. Click "Reset Password"
12. **Expected:**
    - Success message: "Password reset successful! You can now login with your new password."
    - After 2 seconds, redirected to login form
    - Email pre-filled in login form

13. Test login with new password
14. **Expected:** Login successful

#### 2.2 Invalid OTP Code
1. Follow steps 1-6 from 2.1
2. Enter invalid/wrong OTP code
3. Enter new password
4. Click "Reset Password"
5. **Expected:** Error message appears

#### 2.3 Password Mismatch
1. Follow steps 1-8 from 2.1
2. Enter different passwords in new password and confirm password
3. Click "Reset Password"
4. **Expected:** Error message: "Passwords do not match"

#### 2.4 Resend Code
1. Follow steps 1-6 from 2.1
2. Click "Resend Code" link
3. **Expected:**
   - Info message: "A new code has been sent to your email."
   - New OTP code sent to email
4. Enter new code and complete reset
5. **Expected:** Reset successful

#### 2.5 Back Button
1. Follow steps 1-6 from 2.1
2. Click "Back" link
3. **Expected:**
   - Return to forgot password form
   - OTP form hidden
   - Form fields cleared

### Test with supawp_auth

Repeat all tests from 2.1 to 2.5 above using `[supawp_auth]` shortcode instead.

**Expected:** All functionality should work identically to supawp_login.

## Test 3: Form Element IDs

### Verify Element Detection

Both shortcodes should detect their respective elements:

#### supawp_login Elements
- `supawp-forgot-password-link`
- `supawp-back-to-login-link`
- `supawp-forgot-password-form`
- `supawp-forgot-email`
- `supawp-forgot-password-message`
- `supawp-reset-otp-verify-form`
- `supawp-reset-otp-code`
- `supawp-reset-new-password`
- `supawp-reset-confirm-password`
- `supawp-reset-email-hidden`
- `supawp-reset-otp-message`
- `supawp-reset-back-button`
- `supawp-reset-resend-button`

#### supawp_auth Elements
- `supawp-auth-forgot-password-link`
- `supawp-auth-back-to-login-link`
- `supawp-auth-forgot-password-form`
- `supawp-auth-forgot-email`
- `supawp-auth-forgot-password-message`
- `supawp-auth-reset-otp-verify-form`
- `supawp-auth-reset-otp-code`
- `supawp-auth-reset-new-password`
- `supawp-auth-reset-confirm-password`
- `supawp-auth-reset-email-hidden`
- `supawp-auth-reset-otp-message`
- `supawp-auth-reset-back-button`
- `supawp-auth-reset-resend-button`

## Test 4: Browser Console Debugging

Enable browser console and look for debug messages:

1. **On form submission:**
   - "Forgot password form submitted"
   - "Attempting password reset for: [email]"
   - "Password reset method: [magic_link/otp_token]"
   - "Password reset email sent successfully"

2. **On OTP verification:**
   - "OTP reset password verify form submitted"
   - "Verifying OTP for password reset: [email]"
   - "OTP verified successfully, updating password"
   - "Password reset successful"

3. **On resend:**
   - "Resending password reset OTP to: [email]"
   - "OTP resent successfully"

## Test 5: Edge Cases

### 5.1 Empty Email
1. Click "Forgot Password?"
2. Click "Reset Password" without entering email
3. **Expected:** Error message: "Email is required"

### 5.2 Invalid Email Format
1. Enter invalid email (e.g., "notanemail")
2. Click "Reset Password"
3. **Expected:** Browser validation or error message

### 5.3 Empty OTP Code
1. Get to OTP verification form
2. Click "Reset Password" without entering code
3. **Expected:** Error message: "Verification code is required"

### 5.4 Empty Password
1. Get to OTP verification form
2. Enter OTP code but leave password fields empty
3. Click "Reset Password"
4. **Expected:** Error message: "Password is required"

## Test 6: Switching Between Methods

1. Set method to "Magic Link"
2. Test password reset
3. **Expected:** Magic link flow works
4. Change to "6-Digit OTP Code"
5. Test password reset
6. **Expected:** OTP flow works
7. Change back to "Magic Link"
8. Test password reset
9. **Expected:** Magic link flow works again

## Success Criteria

✅ All tests pass for both shortcodes
✅ No JavaScript errors in console
✅ Form submissions work correctly
✅ Error messages display properly
✅ Success messages display properly
✅ UI transitions smoothly between forms
✅ Email pre-fills after successful reset
✅ Both magic link and OTP methods work
✅ Switching between methods works seamlessly

## Files Modified

1. `includes/admin/class.supawp-admin.php` - Added password reset method setting
2. `includes/public/class.supabase.php` - Added passwordResetMethod to config
3. `includes/public/class.shortcodes.php` - Added password reset UI to both shortcodes
4. `src/password-reset.ts` - New file with password reset logic
5. `src/login.ts` - Import password reset functionality
6. `src/supabase.ts` - Initialize password reset for auth form
7. `src/supabase-core.ts` - Added passwordResetMethod to interface
8. `i18n/public_translations.php` - Added new translation keys
