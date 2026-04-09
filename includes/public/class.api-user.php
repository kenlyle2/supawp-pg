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

    // Redirect-based session endpoint: browser navigates here after Supabase auth,
    // WordPress sets the auth cookie (SameSite=Lax, same-origin), then bounces back.
    register_rest_route('supawp/v1', 'auth/session', array(
      'methods'             => 'GET',
      'callback'            => array('SupaWP_Rest_Api_User', 'handle_session_redirect'),
      'permission_callback' => '__return_true',
    ));
  }

  public static function handle_auto_login(WP_REST_Request $request) {
    $response = array();
    $parameters = $request->get_json_params();

    // Validate request (origin, timestamp, JWT). Returns decoded JWT object on success, or WP_Error.
    $decoded_jwt_or_error = self::validate_request($request);
    if (is_wp_error($decoded_jwt_or_error)) {
      $error_data   = $decoded_jwt_or_error->get_error_data();
      $inner_detail = isset($error_data['detail']) ? $error_data['detail'] : '';
      $error_msg    = $decoded_jwt_or_error->get_error_code() . ': ' . $decoded_jwt_or_error->get_error_message();
      if ($inner_detail) {
        $error_msg .= ' — ' . $inner_detail;
      }
      self::record_login_attempt('failed', $error_msg);
      return $decoded_jwt_or_error;
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

    // Only set the auth cookie if there is no existing WordPress session,
    // or the existing session already belongs to this same user.
    // This prevents the auto-login from overwriting an admin's session when
    // the SupaWP JS fires on a page the admin is browsing.
    $existing_user_id = false;
    if (!empty($_COOKIE[LOGGED_IN_COOKIE])) {
      $existing_user_id = wp_validate_auth_cookie($_COOKIE[LOGGED_IN_COOKIE], 'logged_in');
    }

    if (!$existing_user_id || (int) $existing_user_id === (int) $user->ID) {
      wp_set_current_user($user->ID);
      wp_set_auth_cookie($user->ID, true, is_ssl());
    }

    self::record_login_attempt('success', '', $email);

    do_action('supawp_after_user_login', $user, $supabase_user_data_from_params);

    // If all checks pass, return the decoded JWT object
    return $decoded_jwt; // <-- Return decoded JWT object
  }

  /**
   * Store the result of the most recent auto-login attempt as a transient
   * so it can be surfaced in the admin Security tab for easy debugging.
   */
  private static function record_login_attempt($result, $error = '', $email = '') {
    set_transient('supawp_last_login_attempt', array(
      'time'   => current_time('mysql'),
      'result' => $result,
      'error'  => $error,
      'email'  => $email,
    ), DAY_IN_SECONDS);
  }

  private static function validate_request(WP_REST_Request $request) {
    // 1. Validate request origin
    $site_url       = get_site_url();
    $site_host      = parse_url($site_url, PHP_URL_HOST);           // e.g. postglider.com
    $request_origin = $request->get_header('origin');
    $origin_host    = $request_origin ? parse_url($request_origin, PHP_URL_HOST) : '';

    // Build the list of trusted origins: same host + any configured app origins.
    $options         = get_option('supawp_options', array());
    $app_callback    = isset($options['supawp_app_callback_url']) ? $options['supawp_app_callback_url'] : '';
    $app_host        = $app_callback ? parse_url($app_callback, PHP_URL_HOST) : '';

    $trusted_hosts   = array_filter(array($site_host, $app_host));
    $trusted_hosts   = apply_filters('supawp_trusted_origins', $trusted_hosts);

    if (empty($origin_host) || !in_array($origin_host, $trusted_hosts, true)) {
      return new WP_Error(
        'invalid_origin',
        __('Invalid request origin.', 'supawp'),
        array('status' => 403)
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

    if (is_string($decoded_jwt)) { // Error string returned instead of decoded object
      error_log("[SupaWP Auto-Login] JWT validation failed: " . $decoded_jwt);
      return new WP_Error(
        'invalid_token',
        __('Invalid authentication token.', 'supawp'),
        array('status' => 401, 'detail' => $decoded_jwt)
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
        // Never call wp_set_password on login — it destroys all active sessions
        // (wp_destroy_all_sessions) which logs the user out everywhere.
        // WordPress passwords are irrelevant when auth is handled by Supabase JWT.
        return $user;
      } else if ($user) {
        error_log("[SupaWP Auto-Login] Potential UID conflict: UID {$supabase_uid} exists but email does not match ({$user->user_email} vs {$email}).");
        return new WP_Error('uid_email_mismatch', __('Authentication conflict.', 'supawp'), array('status' => 409));
      }
    }

    // Priority 2: Find user by email
    $user = get_user_by('email', $email);
    if ($user) {
      // User found by email — associate Supabase UID if not already set.
      // Do NOT call wp_set_password here: it would destroy all active sessions.
      update_user_meta($user->ID, 'supabase_uid', $supabase_uid);
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
   * Automatically handles both HS256 (secret-based) and RS256 (JWKS public key) tokens.
   *
   * @param string|null $auth_header The Authorization header value (e.g., "Bearer <token>").
   * @return object|string The decoded payload object on success, or an error string on failure.
   */
  private static function validate_supabase_jwt($auth_header) {
    if (empty($auth_header)) {
      error_log("[SupaWP Auto-Login] JWT Validation Error: Authorization header missing.");
      return 'Authorization header missing — the server may be stripping it. Check .htaccess or server config.';
    }

    if (!preg_match('/^Bearer\\s+(.+)$/i', $auth_header, $matches)) {
      error_log("[SupaWP Auto-Login] JWT Validation Error: Malformed Authorization header.");
      return 'Malformed Authorization header (expected: Bearer <token>).';
    }

    $token = $matches[1];
    if (empty($token)) {
      error_log("[SupaWP Auto-Login] JWT Validation Error: Token missing from Authorization header.");
      return 'Token is empty inside the Authorization header.';
    }

    // Peek at the token header (no verification) to determine which algorithm was used
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
      return 'Malformed JWT — expected 3 dot-separated parts.';
    }

    $header_data  = strlen($parts[0]) % 4;
    $padded       = $header_data ? $parts[0] . str_repeat('=', 4 - $header_data) : $parts[0];
    $header       = json_decode(base64_decode(strtr($padded, '-_', '+/')), true);
    $alg          = isset($header['alg']) ? $header['alg'] : 'HS256';

    error_log("[SupaWP Auto-Login] Token algorithm detected: " . $alg);

    // RS256, ES256, and other asymmetric algorithms all verify via the JWKS public key endpoint
    if (in_array($alg, ['RS256', 'ES256', 'PS256'], true)) {
      return self::validate_jwt_rs256($token);
    }

    // HS256 path — use the stored JWT secret
    $secret = isset(self::$options_settings['supawp_jwt_secret']) ? trim(self::$options_settings['supawp_jwt_secret']) : '';

    if (empty($secret)) {
      error_log("[SupaWP Auto-Login] JWT Validation Error: Supabase JWT Secret is not configured in settings.");
      return 'JWT Secret is not configured in SupaWP settings.';
    }

    try {
      $decoded = JWT::decode($token, new Key($secret, 'HS256'));
      error_log("[SupaWP Auto-Login] JWT Validation Success (HS256). UID: " . (isset($decoded->sub) ? $decoded->sub : 'N/A'));
      return $decoded;
    } catch (ExpiredException $e) {
      error_log("[SupaWP Auto-Login] JWT Validation Error: Token has expired. " . $e->getMessage());
      return 'Token has expired: ' . $e->getMessage();
    } catch (SignatureInvalidException $e) {
      error_log("[SupaWP Auto-Login] JWT Validation Error: Signature invalid. " . $e->getMessage());
      return 'Signature verification failed — JWT Secret does not match. ' . $e->getMessage();
    } catch (\Exception $e) {
      error_log("[SupaWP Auto-Login] JWT Validation Error: " . $e->getMessage());
      return 'JWT HS256 decode error (token header alg=' . esc_html($alg) . '): ' . $e->getMessage();
    }
  }

  /**
   * GET /wp-json/supawp/v1/auth/session?token={jwt}&return_to={url}
   *
   * The browser navigates here directly after Supabase auth (not a background fetch),
   * so WordPress can set its auth cookie with SameSite=Lax without any cross-origin
   * restrictions. After the cookie is set, the user is redirected to return_to.
   *
   * return_to is validated against the same trusted-host list used by auto-login
   * to prevent open-redirect attacks.
   */
  public static function handle_session_redirect(WP_REST_Request $request) {
    $token     = sanitize_text_field($request->get_param('token'));
    $return_to = $request->get_param('return_to'); // validated below, not sanitized yet

    // Require a token
    if (empty($token)) {
      wp_die(
        __('Missing authentication token.', 'supawp'),
        __('Auth Error', 'supawp'),
        array('response' => 400)
      );
    }

    // Validate return_to host against the trusted list (open-redirect protection)
    $options      = get_option('supawp_options', array());
    $app_callback = isset($options['supawp_app_callback_url']) ? $options['supawp_app_callback_url'] : '';
    $app_host     = $app_callback ? parse_url($app_callback, PHP_URL_HOST) : '';
    $site_host    = parse_url(get_site_url(), PHP_URL_HOST);

    $trusted_hosts = array_filter(array($site_host, $app_host));
    $trusted_hosts = apply_filters('supawp_trusted_origins', $trusted_hosts);

    $return_host = $return_to ? parse_url($return_to, PHP_URL_HOST) : '';
    if (empty($return_host) || !in_array($return_host, $trusted_hosts, true)) {
      $return_to = home_url('/'); // safe fallback
    }

    // Validate the JWT
    $decoded_jwt = self::validate_supabase_jwt('Bearer ' . $token);
    if (is_string($decoded_jwt)) {
      error_log('[SupaWP Session] JWT validation failed: ' . $decoded_jwt);
      self::record_login_attempt('failed', 'invalid_token: ' . $decoded_jwt);
      wp_die(
        sprintf(__('Authentication failed: %s', 'supawp'), esc_html($decoded_jwt)),
        __('Auth Error', 'supawp'),
        array('response' => 401)
      );
    }

    $supabase_uid = isset($decoded_jwt->sub)   ? sanitize_text_field($decoded_jwt->sub)   : null;
    $email        = isset($decoded_jwt->email) ? sanitize_email($decoded_jwt->email)       : null;

    if (empty($supabase_uid) || empty($email)) {
      wp_die(
        __('Invalid token: missing required claims.', 'supawp'),
        __('Auth Error', 'supawp'),
        array('response' => 400)
      );
    }

    $user = self::get_or_create_user($supabase_uid, $email, null);
    if (is_wp_error($user)) {
      wp_die(
        esc_html($user->get_error_message()),
        __('Auth Error', 'supawp'),
        array('response' => 500)
      );
    }

    // Set the WordPress session. Because this is a direct browser navigation to
    // postglider.com (not a cross-origin fetch), SameSite=Lax cookies are stored.
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true, is_ssl());

    // The Supabase client on this WordPress domain will see SIGNED_OUT (the real
    // session lives on the external app's origin). Mark this session so the
    // frontend-logout handler doesn't immediately undo the cookie we just set.
    $ttl = apply_filters('supawp_cross_domain_session_ttl', 8 * HOUR_IN_SECONDS);
    set_transient('supawp_cross_domain_' . $user->ID, 1, $ttl);

    self::record_login_attempt('success', '', $email);
    do_action('supawp_after_user_login', $user, array());

    wp_redirect(esc_url_raw($return_to));
    exit;
  }

  /**
   * Validate an RS256 Supabase JWT using the project's public JWKS endpoint.
   * Keys are cached for one hour to avoid a remote fetch on every login.
   *
   * @param string $token Raw JWT string.
   * @return object|string Decoded payload on success, error string on failure.
   */
  private static function validate_jwt_rs256($token) {
    $supabase_url = isset(self::$options_settings['supawp_supabase_url'])
      ? rtrim(trim(self::$options_settings['supawp_supabase_url']), '/')
      : '';

    if (empty($supabase_url)) {
      return 'RS256 token received but Supabase URL is not configured — cannot fetch public keys.';
    }

    // Attempt to load cached JWKS first
    $cache_key = 'supawp_jwks_' . md5($supabase_url);
    $jwks      = get_transient($cache_key);

    if (empty($jwks)) {
      $jwks_url = $supabase_url . '/auth/v1/.well-known/jwks.json';
      $response = wp_remote_get($jwks_url, array('timeout' => 10));

      if (is_wp_error($response)) {
        return 'Failed to fetch Supabase JWKS: ' . $response->get_error_message();
      }

      $body = json_decode(wp_remote_retrieve_body($response), true);

      if (empty($body['keys'])) {
        return 'Invalid or empty JWKS response from ' . $jwks_url;
      }

      $jwks = $body;
      set_transient($cache_key, $jwks, HOUR_IN_SECONDS);
      error_log("[SupaWP Auto-Login] JWKS fetched and cached from " . $jwks_url);
    }

    try {
      $key_set = \Firebase\JWT\JWK::parseKeySet($jwks);
      $decoded = JWT::decode($token, $key_set);
      error_log("[SupaWP Auto-Login] JWT Validation Success (RS256). UID: " . (isset($decoded->sub) ? $decoded->sub : 'N/A'));
      return $decoded;
    } catch (ExpiredException $e) {
      return 'Token has expired: ' . $e->getMessage();
    } catch (\Exception $e) {
      return 'RS256 JWT decode error: ' . $e->getMessage();
    }
  }
}

// Initialize the class
SupaWP_Rest_Api_User::init();
