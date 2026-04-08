# Email Verification Templates

This guide shows you how to configure Supabase email templates for different email verification methods in SupaWP.

## Overview

SupaWP supports two email verification methods:

1. **Magic Link** - Users click a link in their email to verify (traditional method)
2. **6-Digit OTP Token** - Users enter a code shown in the email on your website (new method)

## Configuring in Supabase

1. Log into your Supabase Dashboard
2. Go to **Authentication** → **Email Templates**
3. Select **Confirm signup** template
4. Choose the appropriate template below based on your SupaWP setting
5. Click **Save**

---

## Method 1: Magic Link (Default)

### When to Use
- You want users to verify by clicking a link in their email
- Traditional verification flow
- No need for users to return to your website

### Supabase Email Template

```html
<h2>Confirm Your Email</h2>

<p>Hi there,</p>

<p>Thank you for signing up! Please confirm your email address by clicking the button below:</p>

<p>
  <a href="{{ .ConfirmationURL }}"
     style="display: inline-block; padding: 12px 24px; background-color: #4285f4; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: bold;">
    Verify Email Address
  </a>
</p>

<p>Or copy and paste this URL into your browser:</p>
<p>{{ .ConfirmationURL }}</p>

<p>This link will expire in 24 hours.</p>

<p>If you didn't create an account, you can safely ignore this email.</p>

<p>Thanks,<br>
Your Team</p>
```

### What Users See

**Email:**
```
Subject: Confirm Your Email

Hi there,

Thank you for signing up! Please confirm your email address by clicking the button below:

[Verify Email Address] (blue button)

Or copy and paste this URL into your browser:
https://yourproject.supabase.co/auth/v1/verify?token=...

This link will expire in 24 hours.

If you didn't create an account, you can safely ignore this email.

Thanks,
Your Team
```

**User Flow:**
1. User signs up on your website
2. User receives email with verification link
3. User clicks the link
4. User is redirected back to your website
5. Email is verified automatically ✅

---

## Method 2: 6-Digit OTP Token (New)

### When to Use
- You want users to stay on your website during verification
- More secure as tokens are time-limited and single-use
- Better user experience for mobile users
- Modern verification flow

### Supabase Email Template

```html
<h2>Verify Your Email</h2>

<p>Hi there,</p>

<p>Thank you for signing up! To complete your registration, please enter this verification code on our website:</p>

<div style="background-color: #f5f5f5; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;">
  <div style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #333;">
    {{ .Token }}
  </div>
</div>

<p style="color: #666; font-size: 14px;">
  <strong>Important:</strong> This code will expire in 24 hours.
</p>

<p>If you didn't create an account, you can safely ignore this email.</p>

<p>Thanks,<br>
Your Team</p>
```

### Alternative Template (Plain Style)

```html
<h2>Verify Your Email</h2>

<p>Hi there,</p>

<p>Your verification code is:</p>

<h1 style="font-size: 36px; letter-spacing: 5px; text-align: center; margin: 30px 0;">
  {{ .Token }}
</h1>

<p>Enter this code on our website to verify your email address.</p>

<p><strong>Note:</strong> This code expires in 24 hours and can only be used once.</p>

<p>If you didn't request this code, please ignore this email.</p>

<p>Thanks,<br>
Your Team</p>
```

### What Users See

**Email:**
```
Subject: Verify Your Email

Hi there,

Thank you for signing up! To complete your registration, please enter this verification code on our website:

┌─────────────────┐
│                 │
│    123456       │  (large, bold numbers)
│                 │
└─────────────────┘

Important: This code will expire in 24 hours.

If you didn't create an account, you can safely ignore this email.

Thanks,
Your Team
```

**User Flow:**
1. User signs up on your website
2. Signup form hides, verification form appears
3. User receives email with 6-digit code
4. User enters code on your website
5. Email is verified in real-time ✅

---

## Comparison

| Feature | Magic Link | 6-Digit OTP Token |
|---------|------------|-------------------|
| Supabase Variable | `{{ .ConfirmationURL }}` | `{{ .Token }}` |
| User Action | Click link in email | Enter code on website |
| Verification Location | Redirects to website | Stays on website |
| Mobile Friendly | Good | Excellent |
| Copy/Paste Required | Sometimes | Always |
| Security | Good | Excellent |
| Expiration | 24 hours | 24 hours |
| Resend Option | ✅ | ✅ |

---

## SupaWP Configuration

### Setting the Verification Method

1. Go to **WordPress Admin** → **SupaWP** → **Settings**
2. Find **Email Verification Method**
3. Choose your preferred method:
   - **Magic Link (Click link in email)** - Uses `{{ .ConfirmationURL }}`
   - **6-Digit Code (Enter code on website)** - Uses `{{ .Token }}`
4. Click **Save Changes**

### Important Notes

⚠️ **You MUST update your Supabase email template to match your selected method:**

- If you select **Magic Link**, your template must use `{{ .ConfirmationURL }}`
- If you select **6-Digit Code**, your template must use `{{ .Token }}`

Mismatched settings will result in verification failures!

---

## Advanced Customization

### Styling the OTP Code

You can customize how the 6-digit code appears in emails:

**Boxed Style:**
```html
<table style="margin: 20px auto; border: 2px solid #4285f4; border-radius: 8px;">
  <tr>
    <td style="padding: 20px 40px; text-align: center;">
      <span style="font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #4285f4;">
        {{ .Token }}
      </span>
    </td>
  </tr>
</table>
```

**Gradient Background:**
```html
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 12px;">
  <span style="font-size: 40px; font-weight: bold; letter-spacing: 10px; color: #ffffff;">
    {{ .Token }}
  </span>
</div>
```

**Monospace Font:**
```html
<div style="background-color: #1e1e1e; padding: 20px; text-align: center; border-radius: 8px;">
  <code style="font-family: 'Courier New', monospace; font-size: 32px; letter-spacing: 6px; color: #00ff00;">
    {{ .Token }}
  </code>
</div>
```

### Including Both Methods

Some sites prefer to include both options in the email:

```html
<h2>Verify Your Email</h2>

<p>Hi there,</p>

<p>Please verify your email address using one of these methods:</p>

<h3>Option 1: Enter this code on our website</h3>

<div style="background-color: #f5f5f5; padding: 20px; text-align: center; border-radius: 8px;">
  <div style="font-size: 32px; font-weight: bold; letter-spacing: 8px;">
    {{ .Token }}
  </div>
</div>

<h3>Option 2: Click this verification link</h3>

<p>
  <a href="{{ .ConfirmationURL }}"
     style="display: inline-block; padding: 12px 24px; background-color: #4285f4; color: #ffffff; text-decoration: none; border-radius: 4px;">
    Verify Email
  </a>
</p>

<p style="color: #666; font-size: 14px;">
  Both methods will expire in 24 hours.
</p>
```

**Note:** When using both methods, set SupaWP to **6-Digit Code** mode for the best user experience.

---

## Testing Your Template

### Before Going Live

1. **Test Magic Link:**
   - Set SupaWP to "Magic Link" mode
   - Configure Supabase template with `{{ .ConfirmationURL }}`
   - Sign up with a test email
   - Verify you receive the link and it works

2. **Test 6-Digit Code:**
   - Set SupaWP to "6-Digit Code" mode
   - Configure Supabase template with `{{ .Token }}`
   - Sign up with a test email
   - Verify you receive the code and can enter it

3. **Test Expiration:**
   - Request a verification code/link
   - Wait 24+ hours
   - Verify expired codes/links are rejected

4. **Test Resend:**
   - Request verification
   - Use the "Resend Code" button
   - Verify new code works and old code is invalid

---

## Troubleshooting

### Users Not Receiving Codes

1. Check Supabase email settings are configured
2. Verify email template is saved properly
3. Check spam/junk folders
4. Test with different email providers

### Codes Not Working

1. Verify SupaWP setting matches Supabase template
2. Check code hasn't expired (24 hours)
3. Ensure users enter exactly 6 digits
4. Verify no extra spaces in the code

### Magic Links Not Working

1. Check redirect URL is whitelisted in Supabase
2. Verify `{{ .ConfirmationURL }}` is in template
3. Test link hasn't expired
4. Check browser doesn't block redirects

---

## Best Practices

### For Magic Links

✅ **Do:**
- Use clear call-to-action buttons
- Provide plain text URL as backup
- Explain link expiration clearly
- Make buttons mobile-friendly

❌ **Don't:**
- Use vague link text like "Click here"
- Send multiple verification emails quickly
- Use shortened URLs for security

### For 6-Digit Codes

✅ **Do:**
- Make code highly visible (large, bold)
- Use high contrast colors
- Add spacing between digits for readability
- Clearly state expiration time
- Provide "Resend Code" option

❌ **Don't:**
- Use small font sizes
- Use similar-looking characters (0 vs O)
- Send codes too frequently (rate limiting)
- Allow unlimited resend attempts

---

---

## Special Flow: Unverified User Login (OTP Token Only)

### What Happens When Unverified User Tries to Login?

When using **6-Digit OTP Token** verification method, SupaWP handles unverified users gracefully:

#### **Traditional Behavior (Magic Link):**
```
User tries login → Blocked with error → "Please verify your email"
```

#### **Smart Behavior (6-Digit Code):**
```
User tries login → System detects unverified email → Resends new code → Shows verification form on login page → User verifies → Auto-login ✅
```

### Detailed Flow

**Step 1: User Attempts Login**
```
User enters: email@example.com + password
Clicks "Login"
```

**Step 2: System Detects Unverified Email**
```
✓ Credentials are correct
✗ Email not verified
→ System automatically resends verification code
```

**Step 3: Verification Form Appears**
```
Login form hides
Verification form shows:
┌────────────────────────────────────┐
│ Your email is not verified.        │
│ A new verification code has been   │
│ sent to email@example.com          │
│                                    │
│ Enter 6-digit code: [______]      │
│                                    │
│ [Verify Email]                     │
│                                    │
│ [Resend Code] | [Back to Login]   │
└────────────────────────────────────┘
```

**Step 4: User Verifies**
```
User checks email
User enters code: 123456
Clicks "Verify Email"
```

**Step 5: Auto-Login**
```
✓ Email verified successfully
✓ Automatically logged in
→ Redirected to dashboard/specified URL
```

### Why This Is Better

**For Magic Link:**
- ❌ User must leave and check email
- ❌ User clicks link in email
- ❌ User returns to site
- ❌ User must login again

**For 6-Digit Code:**
- ✅ User stays on the site
- ✅ User enters code immediately
- ✅ User is verified and logged in automatically
- ✅ Seamless single-flow experience

### Configuration

This smart flow is **automatic** when you select:
- **Email Verification Method** → 6-Digit Code (Enter code on website)

No additional configuration needed!

### User Experience Examples

#### Example 1: First-Time Login

```
Day 1: User signs up → Forgets to verify email
Day 3: User tries to login

Traditional (Magic Link):
→ Error: "Please verify your email"
→ User confused, doesn't remember verification email
→ User can't login

Smart (6-Digit Code):
→ "Email not verified. New code sent."
→ User checks email, enters code
→ User logged in successfully ✅
```

#### Example 2: Lost Verification Email

```
User signs up → Email goes to spam
User tries to login

Traditional (Magic Link):
→ Blocked, no way to resend easily
→ User might need to contact support

Smart (6-Digit Code):
→ New code automatically sent
→ User finds it in inbox
→ User verifies and logs in ✅
```

### Resend Code Feature

The verification form includes "Resend Code" button:

```javascript
// Automatically invalidates old codes
// Sends fresh code to user's email
// User can request new code if:
// - Old code expired
// - Email was lost
// - Code wasn't received
```

**Rate Limiting:**
- Codes are rate-limited by Supabase
- Prevents spam/abuse
- Typical limit: 1 code per 60 seconds

### Back to Login Option

Users can click "Back to Login" to:
- Return to the login form
- Try different email
- Check if they have another account

This is helpful if:
- User entered wrong email
- User wants to use different account
- User remembers they verified a different email

---

## Support

For more information:
- [SupaWP Documentation](https://techcater.com)
- [Supabase Email Template Docs](https://supabase.com/docs/guides/auth/auth-email-templates)

For issues or questions, please contact support or open an issue on GitHub.
