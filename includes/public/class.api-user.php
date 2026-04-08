<?php

defined('ABSPATH') || exit;

// Add these use statements at the top of the file if not autoloaded
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

/**
 * REST API for SupaWP User Management and Auto-Login
 */
class SupaWP_Rest_Api_User {
  private static $initiated = false;
  private static $options_settings;

  public static function init() {
    if (!self::$initiated) {
      self::init_hooks();
    }
  }

  public static function init_hooks() {
    self::$initiated = true;
    // Load settings using the correct option name 'supawp_options'
    self::$options_settings = get_option("supawp_options", array());
    add_action('rest_api_init', array('SupaWP_Rest_Api_User', 'register_routes'));
  }

  public static function register_routes() {
    register_rest_route('supawp/v1', 'auth/auto-login', array(
      'methods' => 'POST',
      'callback' => array('SupaWP_Rest_Api_User', 'handle_auto_login'),
      'permission_callback' => '__return_true', // Public endpoint, security handled inside
    ));
  }

  public static function handle_auto_login(WP_REST_Request $request) {
    $response = array();
    $parameters = $request->get_json_params();

    // Validate request (origin, timestamp, JWT). Returns decoded JWT object on success, or WP_Error.
    $decoded_jwt_or_error = self::validate_request($request);
    if (is_wp_error($decoded_jwt_or_error)) {
      return $decoded_jwt_or_error; // Return WP_Error directly
    }
    // If validation passed, we have the decoded JWT object
    $decoded_jwt = $decoded_jwt_or_error;

    // Extract necessary data from request params (metadata, password, etc.)
    $supabase_user_data_from_params = isset($parameters['user']) ? $parameters['user'] : array(); // Default to empty array
    $password_from_payload = isset($supabase_user_data_from_params['password']) ? (string) $supabase_user_data_from_params['password'] : null;

    // Get Supabase UID (sub claim) and email from the decoded JWT
    $supabase_uid = isset($decoded_jwt->sub) ? sanitize_text_field($decoded_jwt->sub) : null;
    $email = isset($decoded_jwt->email) ? sanitize_email($decoded_jwt->email) : null;

    // Validate required fields from JWT
    if (empty($supabase_uid) || empty($email)) {
      error_log("[SupaWP Auto-Login] Error: Missing 'sub' or 'email' claim in decoded JWT.");
      return new WP_Error(
        'missing_fields',
        __('Required Supabase user ID or email is missing.', 'supawp'),
        array('status' => 400)
      );
    }

    // Get or create WordPress user based on Supabase UID or Email
    // Pass the password from payload if available, otherwise generate one (keeping previous behavior as fallback)
    $user = self::get_or_create_user($supabase_uid, $email, $password_from_payload);
    if (is_wp_error($user)) {
      return $user;
    }

    // Reconstruct a partial user data array for metadata update function
    $user_data_for_metadata = array(
      'user_metadata' => isset($supabase_user_data_from_params['user_metadata']) ? $supabase_user_data_from_params['user_metadata'] : array()
    );

    // Update user metadata (like display name, etc.)
    self::update_user_metadata($user, $user_data_for_metadata);

    // Perform auto-login using the WP user ID
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true, is_ssl());

    do_action('supawp_after_user_login', $user, $supabase_user_data_from_params);

    // If all checks pass, return the decoded JWT object
    return $decoded_jwt; // <-- Return decoded JWT object
  }

  private static function validate_request(WP_REST_Request $request) {
    // 1. Validate request origin
    $site_url = get_site_url();
    $request_origin = $request->get_header('origin');

    // Allow requests originating from the same domain
    if (empty($request_origin) || parse_url($request_origin, PHP_URL_HOST) !== parse_url($site_url, PHP_URL_HOST)) {
      return new WP_Error(
        'invalid_origin',
        __('Invalid request origin.', 'supawp'),
        array('status' => 403) // Use 403 Forbidden
      );
    }

    // 2. Validate last sign in time
    $parameters = $request->get_json_params();
    $last_sign_in_at = isset($parameters['user']['last_sign_in_at'])
      ? $parameters['user']['last_sign_in_at']
      : null;

    if (empty($last_sign_in_at)) {
      return new WP_Error(
        'invalid_auth_timestamp',
        __('Missing authentication timestamp.', 'supawp'),
        array('status' => 400)
      );
    }

    // Convert ISO 8601 to timestamp using DateTime for better precision handling
    try {
      // Truncate high-precision fractional seconds (e.g., .123456789Z) before parsing
      $timestamp_to_parse = preg_replace('/\.\d+Z$/', 'Z', $last_sign_in_at);
      // Fallback if pattern didn't match (e.g., no fractional seconds or different format)
      if ($timestamp_to_parse === null || $timestamp_to_parse === $last_sign_in_at) {
        // Attempt basic truncation at the decimal point if regex failed
        $parts = explode('.', $last_sign_in_at);
        $base_timestamp = $parts[0];
        // Check if original ended with Z and re-append
        if (isset($parts[1]) && str_ends_with($parts[1], 'Z')) {
          $timestamp_to_parse = $base_timestamp . 'Z';
        } else {
          $timestamp_to_parse = $base_timestamp; // Use as is if no Z or decimal
        }
      }

      $dt = new DateTime($timestamp_to_parse);
      $last_sign_in_timestamp = $dt->getTimestamp();
    } catch (\Exception $e) {
      // Log the ORIGINAL timestamp value for debugging
      error_log("[SupaWP Auto-Login] Timestamp parsing error: " . $e->getMessage() . " for original value: " . $last_sign_in_at);
      return new WP_Error('invalid_timestamp_format', __('Invalid timestamp format.', 'supawp'), array('status' => 400));
    }

    $current_time = time();
    $time_difference = $current_time - $last_sign_in_timestamp;

    // Allow only recent logins (e.g., within last 2 minutes)
    $allowed_time_window = apply_filters('supawp_autologin_time_window', 120); // 2 minutes in seconds
    if ($time_difference < 0 || $time_difference > $allowed_time_window) { // Check if time is in the future too
      error_log("[SupaWP Auto-Login] Timestamp validation failed. Difference: {$time_difference}s");
      return new WP_Error(
        'auth_expired',
        __('Authentication window expired. Please try logging in again.', 'supawp'),
        array('status' => 401) // Use 401 Unauthorized
      );
    }

    // 3. Validate Supabase JWT
    $auth_header = $request->get_header('Authorization');
    // validate_supabase_jwt now returns the decoded object on success, or false on failure.
    $decoded_jwt = self::validate_supabase_jwt($auth_header);

    if ($decoded_jwt === false) { // Check specifically for false, as the object itself is truthy
      error_log("[SupaWP Auto-Login] JWT validation failed (returned false).");
      return new WP_Error(
        'invalid_token',
        __('Invalid authentication token.', 'supawp'),
        array('status' => 401)
      );
    }

    // If all checks pass (including JWT validation which returned the object),
    // return the decoded JWT object.
    return $decoded_jwt;
  }

  /**
   * @param string $email User's email address.
   * @param string|null $password_from_payload Password from payload (if provided), null otherwise.
   * @return WP_User|WP_Error The WP_User object on success, or WP_Error on failure.
   */
  private static function get_or_create_user($supabase_uid, $email, $password_from_payload) {
    // Priority 1: Find user by Supabase UID
    $user_query = new WP_User_Query(array(
      'meta_key' => 'supabase_uid',
      'meta_value' => $supabase_uid,
      'number' => 1,
      'fields' => 'ID'
    ));
    $user_ids = $user_query->get_results();
    if (!empty($user_ids)) {
      $user = get_user_by('id', $user_ids[0]);
      // Ensure user email matches if found by UID (security check)
      if ($user && strtolower($user->user_email) === strtolower($email)) {
        // Update password silently if needed (e.g., if user was created manually before)
        // Only update if a password was sent, otherwise leave existing WP password
        if ($password_from_payload !== null) {
          wp_set_password($password_from_payload, $user->ID);
        }
        return $user;
      } else if ($user) {
        // Log potential UID conflict
        error_log("[SupaWP Auto-Login] Potential UID conflict: UID {$supabase_uid} exists but email does not match ({$user->user_email} vs {$email}).");
        // Decide how to handle: block, update email, etc. For now, block.
        return new WP_Error('uid_email_mismatch', __('Authentication conflict.', 'supawp'), array('status' => 409));
      }
    }

    // Priority 2: Find user by email
    $user = get_user_by('email', $email);
    if ($user) {
      // User found by email, associate Supabase UID
      update_user_meta($user->ID, 'supabase_uid', $supabase_uid);
      // Set password for this user if provided
      if ($password_from_payload !== null) {
        wp_set_password($password_from_payload, $user->ID);
      }
      return $user;
    }

    // Create new user if not found by UID or email
    // Use password from payload if provided, otherwise generate a secure one
    $password_to_use = ($password_from_payload !== null) ? $password_from_payload : wp_generate_password(20, true);

    $username = self::generate_username($email);
    $user_id = wp_create_user($username, $password_to_use, $email);

    if (is_wp_error($user_id)) {
      error_log("[SupaWP Auto-Login] Failed to create WP user '{$username}': " . $user_id->get_error_message());
      return new WP_Error('user_creation_failed', __('Could not create WordPress user.', 'supawp'), array('status' => 500));
    }

    $user = get_user_by('id', $user_id);
    update_user_meta($user_id, 'supabase_uid', $supabase_uid); // Store Supabase UID

    // Set default role (can be customized via filter)
    $default_role = apply_filters('supawp_default_user_role', get_option('default_role', 'subscriber'));
    $user->set_role($default_role);

    do_action('supawp_user_created', $user, $supabase_uid);

    return $user;
  }

  private static function update_user_metadata($user, $supabase_user_data) {
    $user_metadata = isset($supabase_user_data['user_metadata']) ? $supabase_user_data['user_metadata'] : array();

    // Example: Update display name from 'full_name' if available
    if (!empty($user_metadata['full_name']) && empty($user->display_name)) {
      wp_update_user(array(
        'ID' => $user->ID,
        'display_name' => sanitize_text_field($user_metadata['full_name'])
      ));
    }

    // Example: Update first/last name if provided and empty in WP
    $first_name = get_user_meta($user->ID, 'first_name', true);
    $last_name = get_user_meta($user->ID, 'last_name', true);

    if (empty($first_name) && !empty($user_metadata['first_name'])) {
      update_user_meta($user->ID, 'first_name', sanitize_text_field($user_metadata['first_name']));
    }
    if (empty($last_name) && !empty($user_metadata['last_name'])) {
      update_user_meta($user->ID, 'last_name', sanitize_text_field($user_metadata['last_name']));
    }

    // Add filter for custom metadata mapping
    do_action('supawp_update_user_metadata', $user, $supabase_user_data);
  }

  private static function generate_username($email) {
    $username_base = sanitize_user(substr($email, 0, strpos($email, '@')), true);
    if (empty($username_base)) { // Handle case where email might not have '@'
      $username_base = 'user_' . substr(md5($email), 0, 8);
    }

    $username = $username_base;
    $counter = 1;

    // Ensure username uniqueness
    while (username_exists($username)) {
      $username = $username_base . $counter;
      $counter++;
      if ($counter > 100) { // Safety break
        // Fallback to a more unique name
        return 'user_' . time() . '_' . rand(100, 999);
      }
    }
    return $username;
  }

  /**
   * Validates the Supabase JWT from the Authorization header.
   *
   * @param string|null $auth_header The Authorization header value (e.g., "Bearer <token>").
   * @return object|false The decoded payload object on success, false otherwise.
   */
  private static function validate_supabase_jwt($auth_header) {
    if (empty($auth_header)) {
      error_log("[SupaWP Auto-Login] JWT Validation Error: Authorization header missing.");
      return false;
    }

    if (!preg_match('/^Bearer\\s+(.+)$/i', $auth_header, $matches)) {
      error_log("[SupaWP Auto-Login] JWT Validation Error: Malformed Authorization header.");
      return false;
    }

    $token = $matches[1];
    if (empty($token)) {
      error_log("[SupaWP Auto-Login] JWT Validation Error: Token missing from Authorization header.");
      return false;
    }

    // Retrieve the secret from settings
    $secret = isset(self::$options_settings['supawp_jwt_secret']) ? trim(self::$options_settings['supawp_jwt_secret']) : '';

    if (empty($secret)) {
      error_log("[SupaWP Auto-Login] JWT Validation Error: Supabase JWT Secret is not configured in settings.");
      // Consider returning a WP_Error here if more context is needed upstream,
      // but for a simple boolean validation function, false is okay.
      return false;
    }

    try {
      // Attempt to decode the token
      // The Key object takes the secret and the algorithm
      $decoded = JWT::decode($token, new Key($secret, 'HS256'));

      // Optionally, perform additional checks on the decoded payload if needed
      // For example, check issuer (iss), audience (aud), specific claims etc.
      // if ($decoded->iss !== 'expected_issuer') return false;

      // Log the full decoded payload for debugging
      error_log("[SupaWP Auto-Login] JWT Validation Success. UID: " . (isset($decoded->sub) ? $decoded->sub : 'N/A'));
      return $decoded; // <-- Return decoded object
    } catch (ExpiredException $e) {
      error_log("[SupaWP Auto-Login] JWT Validation Error: Token has expired. Message: " . $e->getMessage());
      return false;
    } catch (SignatureInvalidException $e) {
      error_log("[SupaWP Auto-Login] JWT Validation Error: Signature verification failed. Message: " . $e->getMessage());
      return false;
    } catch (\Exception $e) { // Catch any other JWT or general exceptions
      error_log("[SupaWP Auto-Login] JWT Validation Error: An error occurred during decoding. Message: " . $e->getMessage());
      return false;
    }
  }
}

// Initialize the class
SupaWP_Rest_Api_User::init();
