<?php

/**
 * Frontend messages
 *
 * Returns an array of languages.
 *
 */

defined('ABSPATH') || exit;

return array(
  "auth" => array(
    "email" => esc_html__('Email', 'supawp'),
    "password" => esc_html__('Password', 'supawp'),
    "confirmPassword" => esc_html__('Confirm Password', 'supawp'),
    "login" => esc_html__('Login', 'supawp'),
    "signUp" => esc_html__('Sign Up', 'supawp'),
    "logout" => esc_html__('Logout', 'supawp'),
    "signInWithGoogle" => esc_html__('Sign in with Google', 'supawp'),
    "signUpWithGoogle" => esc_html__('Sign up with Google', 'supawp'),
    "or" => esc_html__('or', 'supawp'),
    "dontHaveAccount" => esc_html__("Don't have an account?", 'supawp'),
    "alreadyHaveAccount" => esc_html__('Already have an account?', 'supawp'),
    "emailRequired" => esc_html__('Email is required', 'supawp'),
    "passwordRequired" => esc_html__('Password is required', 'supawp'),
    "passwordMismatch" => esc_html__('Passwords do not match', 'supawp'),
    "passwordsMatch" => esc_html__('Passwords match', 'supawp'),
    "invalidEmail" => esc_html__('Please enter a valid email address', 'supawp'),
    "invalidPhoneNumber" => esc_html__('Please enter a valid phone number', 'supawp'),
    "phoneNumberTooShort" => esc_html__('Phone number should be at least 10 digits', 'supawp'),
    "loginSuccess" => esc_html__('Login successful', 'supawp'),
    "signupSuccess" => esc_html__('Account created successfully', 'supawp'),
    "logoutSuccess" => esc_html__('Logged out successfully', 'supawp'),
    "loginError" => esc_html__('Login failed. Please check your credentials.', 'supawp'),
    "signupError" => esc_html__('Account creation failed. Please try again.', 'supawp'),
    "logoutError" => esc_html__('Logout failed. Please try again.', 'supawp'),
    "emailInUse" => esc_html__('This email is already registered', 'supawp'),
    "weakPassword" => esc_html__('Password is too weak. Please choose a stronger password.', 'supawp'),
    "tooManyRequests" => esc_html__('Too many requests. Please try again later.', 'supawp'),
    "userNotFound" => esc_html__('No account found with this email', 'supawp'),
    "wrongPassword" => esc_html__('Incorrect password', 'supawp'),
    "accountDisabled" => esc_html__('This account has been disabled', 'supawp'),
    "emailNotConfirmed" => esc_html__('Please confirm your email address', 'supawp'),
    "networkError" => esc_html__('Network error. Please check your connection.', 'supawp'),
    "unexpectedError" => esc_html__('An unexpected error occurred. Please try again.', 'supawp'),
    "forgotPassword" => esc_html__('Forgot Password?', 'supawp'),
    "resetPassword" => esc_html__('Reset Password', 'supawp'),
    "passwordResetSent" => esc_html__('Password reset email sent. Please check your inbox.', 'supawp'),
    "passwordResetOTPSent" => esc_html__('A verification code has been sent to your email. Please enter it below along with your new password.', 'supawp'),
    "passwordResetSuccess" => esc_html__('Password reset successful! You can now login with your new password.', 'supawp'),
    "resetting" => esc_html__('Resetting...', 'supawp'),
    "enterYourEmail" => esc_html__('Enter your email', 'supawp'),
    "backToLogin" => esc_html__('Back to Login', 'supawp'),
    // OTP related translations
    "sendCode" => esc_html__('Send Code', 'supawp'),
    "sending" => esc_html__('Sending...', 'supawp'),
    "otpSent" => esc_html__('Verification code sent to your email', 'supawp'),
    "otpCodeRequired" => esc_html__('Verification code is required', 'supawp'),
    "verify" => esc_html__('Verify', 'supawp'),
    "verifying" => esc_html__('Verifying...', 'supawp'),
    "otpVerifyFailed" => esc_html__('Verification failed. Please check your code.', 'supawp'),
    "resendCode" => esc_html__('Resend Code', 'supawp'),
    "otpResent" => esc_html__('New code sent to your email', 'supawp'),
    // Email verification translations
    "verificationCodeSent" => esc_html__('Verification code sent to your email', 'supawp'),
    "enterVerificationCode" => esc_html__('Enter 6-digit verification code', 'supawp'),
    "verifyEmail" => esc_html__('Verify Email', 'supawp'),
    "invalidVerificationCode" => esc_html__('Please enter a valid 6-digit code', 'supawp'),
    "verificationFailed" => esc_html__('Email verification failed. Please try again.', 'supawp'),
    "emailVerified" => esc_html__('Email verified successfully!', 'supawp'),
    "verificationCodeResent" => esc_html__('A new verification code has been sent to your email', 'supawp'),
  ),
  "supabase" => array(
    "settingsMissing" => esc_html__("Please configure your Supabase settings!", 'supawp'),
    "connectionError" => esc_html__("Failed to connect to Supabase. Please check your settings.", 'supawp'),
    "sessionExpired" => esc_html__("Your session has expired. Please login again.", 'supawp'),
    "invalidResponse" => esc_html__("Invalid response from Supabase. Please try again.", 'supawp'),
  ),
  "wordpress" => array(
    "autoLoginFailed" => esc_html__("WordPress auto-login failed. Please contact support.", 'supawp'),
    "userCreationFailed" => esc_html__("Failed to create WordPress user account.", 'supawp'),
    "syncFailed" => esc_html__("Failed to sync user data. Please try again.", 'supawp'),
  ),
  "utils" => array(
    "loading" => esc_html__("Loading...", 'supawp'),
    "pleaseWait" => esc_html__("Please wait...", 'supawp'),
    "tryAgain" => esc_html__("Try again", 'supawp'),
    "cancel" => esc_html__("Cancel", 'supawp'),
    "continue" => esc_html__("Continue", 'supawp'),
    "save" => esc_html__("Save", 'supawp'),
    "close" => esc_html__("Close", 'supawp'),
  ),
  "form" => array(
    "requiredField" => esc_html__("This field is required", 'supawp'),
    "invalidField" => esc_html__("Please enter a valid value", 'supawp'),
    "fieldTooShort" => esc_html__("This field is too short", 'supawp'),
    "fieldTooLong" => esc_html__("This field is too long", 'supawp'),
    "submitting" => esc_html__("Submitting...", 'supawp'),
    "submitSuccess" => esc_html__("Form submitted successfully", 'supawp'),
    "submitError" => esc_html__("Form submission failed. Please try again.", 'supawp'),
  )
);