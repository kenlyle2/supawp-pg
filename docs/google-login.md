# SupaWP Google Login Setup Guide

This guide will help you set up Google authentication for your WordPress site using the SupaWP plugin and Supabase.

## Prerequisites

- WordPress site with SupaWP plugin installed and activated
- A Supabase account and project (https://supabase.com)
- Google Cloud Platform account for OAuth credentials

## Step 1: Configure Google OAuth in Supabase

1. Log in to your Supabase dashboard and select your project
2. Navigate to **Authentication** > **Providers**
3. Find **Google** in the list of providers and click on it
4. Enable the provider by toggling the switch

### Setting up Google OAuth credentials

1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Navigate to **APIs & Services** > **Credentials**
4. Click **Create Credentials** > **OAuth client ID**
5. Select **Web application** as the application type
6. Enter a name for your OAuth client
7. Add the following Authorized JavaScript origins:
   - Your website URL (e.g., `https://example.com`)
   - Your Supabase project URL (e.g., `https://yourproject.supabase.co`)
8. Add the following Authorized redirect URIs:
   - Your Supabase OAuth callback URL: `https://yourproject.supabase.co/auth/v1/callback`
   - Your site's redirect URL (if you want to redirect users directly to your site after login)
9. Click **Create**
10. Copy the **Client ID** and **Client Secret**

### Adding Google OAuth credentials to Supabase

1. Go back to your Supabase dashboard
2. In the Google provider settings, enter the **Client ID** and **Client Secret** you obtained from Google Cloud Console
3. Save the changes

## Step 2: Configure SupaWP Plugin

1. Log in to your WordPress admin dashboard
2. Navigate to **SupaWP** in the left menu
3. Enter your Supabase Project URL and Anon Key
4. Under **Authentication Methods**, check the **Google** option
5. If you want users to be automatically logged into WordPress after Google authentication, enable **WordPress Auto-Login**
6. Specify a table name in the **Users Table Name** field if you want to sync Google user data to a Supabase table
7. Save your settings

## Step 3: Add Login Form to Your Site

Use the SupaWP shortcode to display a login form with Google authentication:

```
[supawp_login]
```

You can customize the redirect URL after login:

```
[supawp_login redirect="https://example.com/dashboard"]
```

## Troubleshooting

### Google login button doesn't appear
- Ensure you've enabled Google in the SupaWP Authentication Methods settings
- Check that the SupaWP plugin is properly configured with valid Supabase credentials

### "Error during Google login" message
- Verify your Google OAuth credentials are correct in Supabase
- Check that the redirect URIs in Google Cloud Console match your Supabase project

### Debug Mode
For debugging Google login issues, add `?debug=true` to the URL of your login page. This will enable detailed console logging that can help identify the source of problems.

## Security Considerations

- Always use HTTPS for your WordPress site when implementing OAuth
- Review the permissions requested by your Google OAuth application
- Consider enabling additional security features in Supabase, such as email verification

## Need Help?

If you encounter any issues with Google login, please contact our support team at support@techcater.com. 
