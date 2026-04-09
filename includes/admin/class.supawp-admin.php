<?php

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

/**
 * SupaWP Admin Class
 *
 * @category Class
 * @package  SupaWP
 */
class SupaWP_Admin {

  /**
   * Initialize the class
   *
   * @return void
   */
  public static function init() {
    add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
    add_action('admin_init', array(__CLASS__, 'register_settings'));
    add_action('admin_notices', array(__CLASS__, 'show_update_banner'));
    add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
  }

  /**
   * Add admin menu
   *
   * @return void
   */
  public static function add_admin_menu() {
    add_menu_page(
      __('SupaWP Settings', 'supawp'),
      __('SupaWP', 'supawp'),
      'manage_options',
      'supawp',
      array(__CLASS__, 'admin_page'),
      'dashicons-database',
      55
    );
  }

  /**
   * Register settings
   *
   * @return void
   */
  public static function register_settings() {
    register_setting('supawp_options', 'supawp_options', array(
      'sanitize_callback' => array(__CLASS__, 'sanitize_options'),
    ));

    add_settings_section(
      'supawp_section_general',
      __('General Settings', 'supawp'),
      null,
      'supawp'
    );

    add_settings_field(
      'supawp_supabase_url',
      __('Supabase URL', 'supawp'),
      array(__CLASS__, 'url_field_cb'),
      'supawp',
      'supawp_section_general'
    );

    add_settings_field(
      'supawp_supabase_anon_key',
      __('Supabase Anon Key', 'supawp'),
      array(__CLASS__, 'anon_key_field_cb'),
      'supawp',
      'supawp_section_general'
    );

    add_settings_field(
      'supawp_wp_auto_login_enabled',
      __('Enable WordPress Auto-Login', 'supawp'),
      array(__CLASS__, 'wp_auto_login_enabled_field_cb'),
      'supawp',
      'supawp_section_general'
    );

    add_settings_field(
      'supawp_auth_methods',
      __('Authentication Methods', 'supawp'),
      array(__CLASS__, 'auth_methods_field_cb'),
      'supawp',
      'supawp_section_general'
    );

    add_settings_field(
      'supawp_email_verification_method',
      __('Email Verification Method', 'supawp'),
      array(__CLASS__, 'email_verification_method_field_cb'),
      'supawp',
      'supawp_section_general'
    );

    add_settings_field(
      'supawp_password_reset_method',
      __('Password Reset Method', 'supawp'),
      array(__CLASS__, 'password_reset_method_field_cb'),
      'supawp',
      'supawp_section_general'
    );

    add_settings_field(
      'supawp_app_callback_url',
      __('App Callback URL', 'supawp'),
      array(__CLASS__, 'app_callback_url_field_cb'),
      'supawp',
      'supawp_section_general'
    );

    add_settings_field(
      'supawp_redirect_after_login',
      __('Redirect After Login', 'supawp'),
      array(__CLASS__, 'redirect_login_field_cb'),
      'supawp',
      'supawp_section_general'
    );

    add_settings_field(
      'supawp_redirect_after_logout',
      __('Redirect After Logout', 'supawp'),
      array(__CLASS__, 'redirect_logout_field_cb'),
      'supawp',
      'supawp_section_general'
    );

    add_settings_section(
      'supawp_section_security',
      __('Security Settings', 'supawp'),
      null,
      'supawp'
    );

    add_settings_field(
      'supawp_product_key',
      __('Product Key', 'supawp'),
      array(__CLASS__, 'product_key_field_cb'),
      'supawp',
      'supawp_section_security'
    );

    add_settings_field(
      'supawp_jwt_secret',
      __('Supabase JWT Secret', 'supawp'),
      array(__CLASS__, 'jwt_secret_field_cb'),
      'supawp',
      'supawp_section_security'
    );

    add_settings_field(
      'supawp_service_role_key',
      __('Supabase Service Role Key', 'supawp'),
      array(__CLASS__, 'service_role_key_field_cb'),
      'supawp',
      'supawp_section_security'
    );

    // --- Add Sync Data Section ---
    add_settings_section(
      'supawp_section_sync',
      __('Sync Data Settings', 'supawp'),
      null,
      'supawp'
    );

    add_settings_field(
      'supawp_users_table_name',
      __('Users Table Name', 'supawp'),
      array(__CLASS__, 'users_table_field_cb'),
      'supawp',
      'supawp_section_sync'
    );

    // Add post types field to existing sync section
    add_settings_field(
      'supawp_sync_post_types',
      __('Sync Post Types', 'supawp'),
      array(__CLASS__, 'sync_post_types_field_cb'),
      'supawp',
      'supawp_section_sync'
    );

    // Add custom post types field to existing sync section
    add_settings_field(
      'supawp_sync_custom_post_types',
      __('Custom Post Types', 'supawp'),
      array(__CLASS__, 'sync_custom_post_types_field_cb'),
      'supawp',
      'supawp_section_sync'
    );
    // --- End Sync Data Section ---
  }

  /**
   * Authentication Methods field callback
   *
   * Renders checkboxes for selecting authentication methods.
   */
  public static function auth_methods_field_cb() {
    $options = get_option('supawp_options', array());
    $auth_methods = isset($options['supawp_auth_methods']) ? $options['supawp_auth_methods'] : array('email');

    // Ensure it's an array
    if (!is_array($auth_methods)) {
      $auth_methods = array('email');
    }
?>
    <fieldset>
      <legend class="screen-reader-text"><?php _e('Authentication Methods', 'supawp'); ?></legend>
      <label>
        <input type="checkbox" name="supawp_options[supawp_auth_methods][]" value="email" <?php checked(in_array('email', $auth_methods)); ?> />
        <?php _e('Email/Password', 'supawp'); ?>
      </label>
      <br>
      <label>
        <input type="checkbox" name="supawp_options[supawp_auth_methods][]" value="email_otp_token" <?php checked(in_array('email_otp_token', $auth_methods)); ?> />
        <?php _e('Email OTP Token (Passwordless)', 'supawp'); ?>
      </label>
      <br>
      <label>
        <input type="checkbox" name="supawp_options[supawp_auth_methods][]" value="google" <?php checked(in_array('google', $auth_methods)); ?> />
        <?php _e('Google', 'supawp'); ?>
      </label>
      <p class="description"><?php _e('Select which authentication methods to enable. You can enable multiple methods to give users options.', 'supawp'); ?></p>
    </fieldset>
  <?php
  }

  /**
   * Email verification method field callback
   *
   * @return void
   */
  public static function email_verification_method_field_cb() {
    $options = get_option('supawp_options', array());
    $method = isset($options['supawp_email_verification_method']) ? $options['supawp_email_verification_method'] : 'magic_link';
  ?>
    <fieldset>
      <legend class="screen-reader-text"><?php _e('Email Verification Method', 'supawp'); ?></legend>
      <label>
        <input type="radio" name="supawp_options[supawp_email_verification_method]" value="magic_link" <?php checked($method, 'magic_link'); ?> />
        <?php _e('Magic Link (Click link in email)', 'supawp'); ?>
      </label>
      <br>
      <label>
        <input type="radio" name="supawp_options[supawp_email_verification_method]" value="otp_token" <?php checked($method, 'otp_token'); ?> />
        <?php _e('6-Digit Code (Enter code on website)', 'supawp'); ?>
      </label>
      <br>
      <label>
        <input type="radio" name="supawp_options[supawp_email_verification_method]" value="none" <?php checked($method, 'none'); ?> />
        <?php _e('No Verification (Skip email confirmation)', 'supawp'); ?>
      </label>
      <p class="description">
        <?php _e('Choose how users verify their email after signup. Magic Link: Users click a link in the email. 6-Digit Code: Users enter a code shown in the email on your website. No Verification: Skip email confirmation and proceed directly after signup.', 'supawp'); ?>
      </p>
      <p class="description" style="color: #d63638;">
        <strong><?php _e('Important:', 'supawp'); ?></strong>
        <?php _e('You must update your Supabase email template to match this setting. For 6-digit code, use {{ .Token }} in your email template instead of {{ .ConfirmationURL }}.', 'supawp'); ?>
      </p>
    </fieldset>
  <?php
  }

  /**
   * Password reset method field callback
   *
   * @return void
   */
  public static function password_reset_method_field_cb() {
    $options = get_option('supawp_options', array());
    $method = isset($options['supawp_password_reset_method']) ? $options['supawp_password_reset_method'] : 'magic_link';
  ?>
    <fieldset>
      <legend class="screen-reader-text"><?php _e('Password Reset Method', 'supawp'); ?></legend>
      <label>
        <input type="radio" name="supawp_options[supawp_password_reset_method]" value="magic_link" <?php checked($method, 'magic_link'); ?> />
        <?php _e('Magic Link (Click link in email)', 'supawp'); ?>
      </label>
      <br>
      <label>
        <input type="radio" name="supawp_options[supawp_password_reset_method]" value="otp_token" <?php checked($method, 'otp_token'); ?> />
        <?php _e('6-Digit OTP Code (Enter code and new password on website)', 'supawp'); ?>
      </label>
      <p class="description">
        <?php _e('Choose how users reset their password. Magic Link: Users click a link in the email to be redirected to reset page. 6-Digit OTP Code: Users enter a code and new password directly on your website.', 'supawp'); ?>
      </p>
      <p class="description" style="color: #d63638;">
        <strong><?php _e('Important:', 'supawp'); ?></strong>
        <?php _e('For OTP code method, update your Supabase password recovery email template to use {{ .Token }} instead of {{ .ConfirmationURL }}. The OTP code will be sent via email and verified on your website.', 'supawp'); ?>
      </p>
    </fieldset>
  <?php
  }

  /**
   * URL field callback
   *
   * @return void
   */
  public static function url_field_cb() {
    $options = get_option('supawp_options', array());
    $url = isset($options['supawp_supabase_url']) ? $options['supawp_supabase_url'] : '';
  ?>
    <input type="url" id="supawp_supabase_url" name="supawp_options[supawp_supabase_url]" value="<?php echo esc_attr($url); ?>" class="regular-text" />
    <p class="description">
      <?php _e('Enter your Supabase project URL. Find this in your Supabase project settings.', 'supawp'); ?>
      <br><?php echo self::render_field_status($url, 'check_supabase_url'); ?>
    </p>
  <?php
  }

  /**
   * Anon key field callback
   *
   * @return void
   */
  public static function anon_key_field_cb() {
    $options = get_option('supawp_options', array());
    $key = isset($options['supawp_supabase_anon_key']) ? $options['supawp_supabase_anon_key'] : '';
  ?>
    <input type="text" id="supawp_supabase_anon_key" name="supawp_options[supawp_supabase_anon_key]" value="<?php echo esc_attr($key); ?>" class="regular-text" />
    <p class="description">
      <?php _e('Enter your Supabase anonymous key. Find this in your Supabase project API settings.', 'supawp'); ?>
      <br><?php echo self::render_field_status($key, 'check_supabase_jwt_key', 'anon'); ?>
    </p>
  <?php
  }

  /**
   * WP Auto Login Enabled field callback
   *
   * Renders a checkbox for the wp_auto_login_enabled setting.
   */
  public static function wp_auto_login_enabled_field_cb() {
    $options = get_option('supawp_options', array());
    $enabled = isset($options['supawp_wp_auto_login_enabled']) && $options['supawp_wp_auto_login_enabled'] === 'on';
  ?>
    <label for="supawp_wp_auto_login_enabled">
      <input type="checkbox" id="supawp_wp_auto_login_enabled" name="supawp_options[supawp_wp_auto_login_enabled]" value="on" <?php checked($enabled); ?> />
      <?php _e('Automatically log users into WordPress after successful Supabase authentication.', 'supawp'); ?>
    </label>
    <?php
    $jwt_secret = isset($options['supawp_jwt_secret']) ? $options['supawp_jwt_secret'] : '';
    if ($enabled && empty($jwt_secret)) {
      echo '<p class="description" style="color:#d63638;font-weight:600;">&#9888; Auto-login is ON but the Supabase JWT Secret (Security tab) is not configured &mdash; logins will fail.</p>';
    } else {
      echo '<p class="description">' . __('If checked, users signing in via Supabase will also be logged into their WordPress account. Requires the Supabase JWT Secret to be set in the Security tab.', 'supawp') . '</p>';
    }
    ?>
  <?php
  }

  /**
   * App Callback URL field callback
   */
  public static function app_callback_url_field_cb() {
    $options = get_option('supawp_options', array());
    $url     = isset($options['supawp_app_callback_url']) ? $options['supawp_app_callback_url'] : '';
  ?>
    <input type="url"
      id="supawp_app_callback_url"
      name="supawp_options[supawp_app_callback_url]"
      value="<?php echo esc_attr($url); ?>"
      class="regular-text"
      placeholder="https://app.example.com/auth/callback" />
    <p class="description">
      <?php _e('The Supabase auth callback URL in your app. Used by the <code>?supawp_launch_app=1</code> endpoint to deliver a magic-link session to the app. Must be added to <strong>Supabase Dashboard → Auth → URL Configuration → Redirect URLs</strong>.', 'supawp'); ?>
      <br><?php echo self::render_field_status($url, 'check_supabase_url'); ?>
    </p>
  <?php
  }

  /**
   * Redirect after login field callback
   *
   * @return void
   */
  public static function redirect_login_field_cb() {
    $options = get_option('supawp_options', array());
    $redirect = isset($options['supawp_redirect_after_login']) ? $options['supawp_redirect_after_login'] : home_url();
  ?>
    <input type="url" id="supawp_redirect_after_login" name="supawp_options[supawp_redirect_after_login]" value="<?php echo esc_attr($redirect); ?>" class="regular-text" />
    <p class="description"><?php _e('URL to redirect users after login. Leave blank for current page.', 'supawp'); ?></p>
  <?php
  }

  /**
   * Redirect after logout field callback
   *
   * @return void
   */
  public static function redirect_logout_field_cb() {
    $options = get_option('supawp_options', array());
    $redirect = isset($options['supawp_redirect_after_logout']) ? $options['supawp_redirect_after_logout'] : home_url();
  ?>
    <input type="url" id="supawp_redirect_after_logout" name="supawp_options[supawp_redirect_after_logout]" value="<?php echo esc_attr($redirect); ?>" class="regular-text" />
    <p class="description"><?php _e('URL to redirect users after logout. Leave blank for current page.', 'supawp'); ?></p>
  <?php
  }

  /**
   * JWT Secret field callback
   *
   * Renders a password input for the supawp_jwt_secret setting.
   */
  public static function jwt_secret_field_cb() {
    $options = get_option('supawp_options', array());
    $secret = isset($options['supawp_jwt_secret']) ? $options['supawp_jwt_secret'] : '';
  ?>
    <input type="password"
      id="supawp_jwt_secret"
      name="supawp_options[supawp_jwt_secret]"
      value="<?php echo esc_attr($secret); ?>"
      class="regular-text"
      autocomplete="off" />
    <p class="description">
      <?php _e('Enter your Supabase project JWT Secret. Found in Project Settings > API > JWT Settings. <strong>Required for WordPress auto-login.</strong>', 'supawp'); ?>
      <br><?php echo self::render_field_status($secret, 'check_jwt_secret'); ?>
    </p>
  <?php
  }

  /**
   * Product Key field callback
   *
   * Renders a text input for the supawp_product_key setting.
   */
  public static function product_key_field_cb() {
    $options = get_option('supawp_options', array());
    $product_key = isset($options['supawp_product_key']) ? $options['supawp_product_key'] : '';
  ?>
    <input type="password"
      id="supawp_product_key"
      name="supawp_options[supawp_product_key]"
      value="<?php echo esc_attr($product_key); ?>"
      class="regular-text"
      autocomplete="off" />
    <p class="description">
      <?php
      // Use printf for translatable string with link
      printf(
        wp_kses(
          /* translators: %s: URL to Tech Cater account page */
          __('Enter the product key for SupaWP. You can find your key in your account: %s', 'supawp'),
          array('a' => array('href' => array(), 'target' => array())) // Allow link tag with href and target
        ),
        '<a href="https://techcater.com/shop/my-account" target="_blank">https://techcater.com/shop/my-account</a>'
      );
      ?>
    </p>
  <?php
  }

  /**
   * Service Role Key field callback
   *
   * Renders a password input for the supawp_service_role_key setting.
   */
  public static function service_role_key_field_cb() {
    $options = get_option('supawp_options', array());
    $service_role_key = isset($options['supawp_service_role_key']) ? $options['supawp_service_role_key'] : '';
  ?>
    <input type="password"
      id="supawp_service_role_key"
      name="supawp_options[supawp_service_role_key]"
      value="<?php echo esc_attr($service_role_key); ?>"
      class="regular-text"
      autocomplete="off" />
    <p class="description">
      <?php _e('<strong>Service Role Key</strong> — Used for secure server-side operations like post syncing. Found in Project Settings > API > Project API keys. <strong>Keep this secret and never expose to frontend!</strong>', 'supawp'); ?>
      <br><?php echo self::render_field_status($service_role_key, 'check_supabase_jwt_key', 'service_role'); ?>
    </p>
  <?php
  }

  /**
   * Users Table Name field callback
   *
   * Renders a text input for the supawp_users_table_name setting.
   */
  public static function users_table_field_cb() {
    $options = get_option('supawp_options', array());
    // Use !empty to ensure default is used if the option is set but empty - Changed default to empty string
    $users_table = !empty($options['supawp_users_table_name']) ? $options['supawp_users_table_name'] : '';
  ?>
    <input type="text"
      id="supawp_users_table_name"
      name="supawp_options[supawp_users_table_name]"
      value="<?php echo esc_attr($users_table); ?>"
      class="regular-text" />
    <p class="description"><?php _e('Enter the name of the Supabase table where user data should be synced. E.g. "users".', 'supawp'); ?></p>
    <p class="description"><a href="https://dalenguyen.me/blog/2025-04-28-supabase-wordpress-integration-save-users-data" target="_blank"><?php _e('Learn more about saving user data to Supabase', 'supawp'); ?></a></p>
  <?php
  }


  /**
   * Sync post types field callback
   */
  public static function sync_post_types_field_cb() {
    $options = get_option('supawp_options', array());
    $sync_post_types = isset($options['supawp_sync_post_types']) ? $options['supawp_sync_post_types'] : array();
    
    $post_types = array(
      'post' => 'Posts',
      'page' => 'Pages'
    );
    
    foreach ($post_types as $value => $label) {
      $checked = in_array($value, $sync_post_types) ? 'checked="checked"' : '';
      printf(
        "<label>$label <input type='checkbox' name='supawp_options[supawp_sync_post_types][]' value='%s' $checked/></label> &nbsp;",
        $value
      );
    }
    echo '<p class="description">' . __('Select built-in post types to sync.', 'supawp') . '</p>';
  }

  // -------------------------------------------------------------------------
  // Validation helpers
  // -------------------------------------------------------------------------

  /**
   * Check a Supabase project URL.
   * Returns null on success, or a translated error string.
   */
  private static function check_supabase_url($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
      return __('Not a valid URL format.', 'supawp');
    }
    if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
      return __('Must use HTTPS (e.g. https://xxxx.supabase.co).', 'supawp');
    }
    return null;
  }

  /**
   * Check a Supabase API key (anon or service_role).
   * These are JWTs — validates structure and role claim.
   * Returns null on success, or a translated error string.
   *
   * @param string $token        The JWT string.
   * @param string $expected_role 'anon' or 'service_role'
   */
  private static function check_supabase_jwt_key($token, $expected_role) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
      return __('Does not look like a valid JWT — expected three dot-separated parts (eyJ…).', 'supawp');
    }

    $payload = json_decode(self::base64url_decode($parts[1]), true);
    if (!is_array($payload)) {
      return __('JWT payload could not be decoded. The key may be truncated or corrupted.', 'supawp');
    }

    if (isset($payload['role']) && $payload['role'] !== $expected_role) {
      return sprintf(
        /* translators: 1: role found in the key, 2: role that was expected */
        __('This key has role "%1$s" but "%2$s" is required here — you may have pasted the wrong key.', 'supawp'),
        esc_html($payload['role']),
        esc_html($expected_role)
      );
    }

    return null;
  }

  /**
   * Check a Supabase JWT signing secret (not itself a JWT).
   * Returns null on success, or a translated error string.
   */
  private static function check_jwt_secret($secret) {
    if (strlen($secret) < 32) {
      return sprintf(
        /* translators: %d: character count of the value that was entered */
        __('Too short (%d characters). Supabase JWT secrets are typically 64+ characters. Find it in Project Settings → API → JWT Settings.', 'supawp'),
        strlen($secret)
      );
    }
    return null;
  }

  /**
   * Decode a base64url-encoded string (used by JWT parts).
   */
  private static function base64url_decode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
      $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
  }

  /**
   * Render an inline status badge for a settings field.
   *
   * @param string $value       The currently-saved field value.
   * @param string $check_fn    Name of a private static check_* method.
   * @param mixed  ...$args     Extra args forwarded to $check_fn after $value.
   * @return string             HTML string — safe to echo directly.
   */
  private static function render_field_status($value, $check_fn, ...$args) {
    if (empty($value)) {
      return '<span style="color:#757575;font-style:italic;">— not configured</span>';
    }
    $error = call_user_func_array(
      array(__CLASS__, $check_fn),
      array_merge(array($value), $args)
    );
    if ($error) {
      return '<span style="color:#d63638;">&#10007; ' . esc_html($error) . '</span>';
    }
    return '<span style="color:#00a32a;">&#10003; Valid</span>';
  }

  // -------------------------------------------------------------------------
  // Settings sanitize callback
  // -------------------------------------------------------------------------

  /**
   * Test whether the saved JWT Secret can actually decode the saved Anon Key.
   *
   * The Anon Key is itself a JWT signed with the project's JWT Secret, so this
   * is a reliable live check that the two values belong to the same project.
   *
   * @return true|string|null  true = pass, string = error message, null = can't test (fields empty)
   */
  private static function test_jwt_configuration() {
    $options    = get_option('supawp_options', array());
    $anon_key   = isset($options['supawp_supabase_anon_key']) ? trim($options['supawp_supabase_anon_key']) : '';
    $jwt_secret = isset($options['supawp_jwt_secret'])        ? trim($options['supawp_jwt_secret'])        : '';

    if (empty($anon_key) || empty($jwt_secret)) {
      return null;
    }

    try {
      \Firebase\JWT\JWT::decode($anon_key, new \Firebase\JWT\Key($jwt_secret, 'HS256'));
      return true;
    } catch (\Firebase\JWT\ExpiredException $e) {
      // Anon keys don't normally expire, but if they do the signature was still valid
      return true;
    } catch (\Exception $e) {
      return $e->getMessage();
    }
  }

  /**
   * Validate and sanitize all SupaWP options on save.
   *
   * Invalid values are rejected (the previously-saved value is kept) and a
   * descriptive admin notice is shown for each problem.  Valid fields are
   * updated normally.
   */
  public static function sanitize_options($input) {
    // Start from the currently-saved options so a bad value on one field
    // never wipes out the good value that was already stored.
    $existing = get_option('supawp_options', array());
    $output   = $existing;
    $errors   = array();

    // --- Supabase URL ---
    if (array_key_exists('supawp_supabase_url', $input)) {
      $url = trim($input['supawp_supabase_url']);
      if (empty($url)) {
        $output['supawp_supabase_url'] = '';
      } else {
        $err = self::check_supabase_url($url);
        if ($err) {
          $errors[] = array('id' => 'bad_supabase_url',
            'msg' => sprintf(__('Supabase URL — %s', 'supawp'), $err));
        } else {
          $output['supawp_supabase_url'] = esc_url_raw(rtrim($url, '/'));
        }
      }
    }

    // --- Anon Key ---
    if (array_key_exists('supawp_supabase_anon_key', $input)) {
      $key = trim($input['supawp_supabase_anon_key']);
      if (empty($key)) {
        $output['supawp_supabase_anon_key'] = '';
      } else {
        $err = self::check_supabase_jwt_key($key, 'anon');
        if ($err) {
          $errors[] = array('id' => 'bad_anon_key',
            'msg' => sprintf(__('Supabase Anon Key — %s', 'supawp'), $err));
        } else {
          $output['supawp_supabase_anon_key'] = sanitize_text_field($key);
        }
      }
    }

    // --- JWT Secret ---
    if (array_key_exists('supawp_jwt_secret', $input)) {
      $secret = trim($input['supawp_jwt_secret']);
      if (empty($secret)) {
        $output['supawp_jwt_secret'] = '';
      } else {
        $err = self::check_jwt_secret($secret);
        if ($err) {
          $errors[] = array('id' => 'bad_jwt_secret',
            'msg' => sprintf(__('Supabase JWT Secret — %s', 'supawp'), $err));
        } else {
          $output['supawp_jwt_secret'] = $secret;
        }
      }
    }

    // --- Service Role Key ---
    if (array_key_exists('supawp_service_role_key', $input)) {
      $key = trim($input['supawp_service_role_key']);
      if (empty($key)) {
        $output['supawp_service_role_key'] = '';
      } else {
        $err = self::check_supabase_jwt_key($key, 'service_role');
        if ($err) {
          $errors[] = array('id' => 'bad_service_role_key',
            'msg' => sprintf(__('Service Role Key — %s', 'supawp'), $err));
        } else {
          $output['supawp_service_role_key'] = sanitize_text_field($key);
        }
      }
    }

    // --- Redirect URLs + App Callback URL ---
    $url_fields = array(
      'supawp_redirect_after_login'  => __('Redirect After Login', 'supawp'),
      'supawp_redirect_after_logout' => __('Redirect After Logout', 'supawp'),
      'supawp_app_callback_url'      => __('App Callback URL', 'supawp'),
    );
    $url_defaults = array(
      'supawp_redirect_after_login'  => home_url(),
      'supawp_redirect_after_logout' => home_url(),
      'supawp_app_callback_url'      => '',
    );
    foreach ($url_fields as $field => $label) {
      if (array_key_exists($field, $input)) {
        $url = trim($input[$field]);
        if (empty($url)) {
          $output[$field] = $url_defaults[$field];
        } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
          $errors[] = array('id' => 'bad_' . $field,
            /* translators: %s: field label */
            'msg' => sprintf(__('%s — not a valid URL.', 'supawp'), $label));
        } else {
          $output[$field] = esc_url_raw($url);
        }
      }
    }

    // --- Pass-through fields (no format validation needed) ---
    // wp_auto_login_enabled is a checkbox; absent = unchecked = 'off'
    $output['supawp_wp_auto_login_enabled'] =
      (isset($input['supawp_wp_auto_login_enabled']) && $input['supawp_wp_auto_login_enabled'] === 'on')
      ? 'on' : 'off';

    // auth_methods and sync_post_types are checkbox arrays; absent = all unchecked
    $output['supawp_auth_methods'] =
      isset($input['supawp_auth_methods']) && is_array($input['supawp_auth_methods'])
      ? array_map('sanitize_text_field', $input['supawp_auth_methods'])
      : array();

    $output['supawp_sync_post_types'] =
      isset($input['supawp_sync_post_types']) && is_array($input['supawp_sync_post_types'])
      ? array_map('sanitize_text_field', $input['supawp_sync_post_types'])
      : array();

    $simple_fields = array(
      'supawp_email_verification_method',
      'supawp_password_reset_method',
      'supawp_product_key',
      'supawp_users_table_name',
      'supawp_sync_custom_post_types',
      // Note: supawp_app_callback_url is handled in the URL fields loop above
    );
    foreach ($simple_fields as $field) {
      if (array_key_exists($field, $input)) {
        $output[$field] = sanitize_text_field(trim($input[$field]));
      }
    }

    // --- Cross-field check: auto-login ON but no JWT secret ---
    if ($output['supawp_wp_auto_login_enabled'] === 'on' && empty($output['supawp_jwt_secret'])) {
      add_settings_error(
        'supawp_options',
        'autologin_no_secret',
        __('Warning: WordPress Auto-Login is enabled but the Supabase JWT Secret has not been configured. Every login attempt will fail with "Invalid authentication token." until the secret is saved in the Security tab.', 'supawp'),
        'warning'
      );
    }

    // --- Emit individual field errors ---
    foreach ($errors as $e) {
      add_settings_error('supawp_options', $e['id'], $e['msg'], 'error');
    }

    return $output;
  }

  /**
   * Sync custom post types field callback
   */
  public static function sync_custom_post_types_field_cb() {
    $options = get_option('supawp_options', array());
    $custom_post_types = isset($options['supawp_sync_custom_post_types']) ? $options['supawp_sync_custom_post_types'] : '';
  ?>
    <input type="text" 
      id="supawp_sync_custom_post_types" 
      name="supawp_options[supawp_sync_custom_post_types]" 
      value="<?php echo esc_attr($custom_post_types); ?>" 
      class="regular-text" 
      placeholder="book, product, event (separated by comma)" />
    <p class="description"><?php _e('Enter custom post types to sync, separated by commas.', 'supawp'); ?></p>
  <?php
  }

  /**
   * Enqueue admin scripts and styles
   *
   * @return void
   */
  public static function enqueue_admin_scripts($hook) {
    // Only load on SupaWP settings page
    if ($hook !== 'toplevel_page_supawp') {
      return;
    }

    // Inline CSS for update banner
    wp_add_inline_style('wp-admin', '
      .supawp-update-banner {
        background: #fff;
        border-left: 4px solid #2271b1;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        margin: 20px 0;
        padding: 12px;
        position: relative;
      }
      .supawp-update-banner.is-dismissible {
        padding-right: 38px;
      }
      .supawp-update-banner h3 {
        margin: 0 0 10px 0;
      }
      .supawp-update-banner p {
        margin: 10px 0;
      }
      .supawp-update-banner .button {
        margin-right: 10px;
      }
    ');

    // Inline JavaScript for dismissible banner
    wp_add_inline_script('jquery', '
      jQuery(document).ready(function($) {
        $(".supawp-update-banner .notice-dismiss").on("click", function() {
          var banner = $(this).closest(".supawp-update-banner");
          banner.fadeOut(300, function() {
            // Store dismissal in localStorage
            localStorage.setItem("supawp_update_banner_dismissed_v" + banner.data("version"), "1");
          });
        });

        // Check if previously dismissed (for this specific version)
        var currentVersion = $(".supawp-update-banner").data("version");
        if (currentVersion && localStorage.getItem("supawp_update_banner_dismissed_v" + currentVersion) === "1") {
          $(".supawp-update-banner").hide();
        }
      });
    ');
  }

  /**
   * Show update banner if new version is available
   *
   * @return void
   */
  public static function show_update_banner() {
    // Only show on SupaWP settings page
    $screen = get_current_screen();

    if (!$screen || $screen->id !== 'toplevel_page_supawp') {
      return;
    }

    // Check if updater class exists
    if (!class_exists('SupaWP_Admin_Updater')) {
      return;
    }

    // Initialize updater if not already done
    SupaWP_Admin_Updater::init();

    // Check for updates
    $update_info = SupaWP_Admin_Updater::check_for_update();

    if (!$update_info) {
      return;
    }

    $current_version = SUPAWP_VERSION;
    $new_version = isset($update_info->version) ? $update_info->version : '';

    // Build changelog text
    $changelog_text = '';
    if (isset($update_info->sections) && isset($update_info->sections['changelog'])) {
      $changelog_text = wp_kses_post($update_info->sections['changelog']);
    }

    ?>
    <div class="notice supawp-update-banner is-dismissible" data-version="<?php echo esc_attr($new_version); ?>">
      <h3><?php printf(__('SupaWP Update Available: Version %s', 'supawp'), esc_html($new_version)); ?></h3>

      <?php if ($changelog_text): ?>
        <div><?php echo $changelog_text; ?></div>
      <?php endif; ?>

      <p>
        <a href="<?php echo admin_url('plugins.php'); ?>" class="button button-primary">
          <?php _e('Go to Plugins Page to Update', 'supawp'); ?>
        </a>
        <a href="https://techcater.com/shop/my-account" class="button" target="_blank">
          <?php _e('View Details', 'supawp'); ?>
        </a>
      </p>

      <button type="button" class="notice-dismiss">
        <span class="screen-reader-text"><?php _e('Dismiss this notice.', 'supawp'); ?></span>
      </button>
    </div>
    <?php
  }

  /**
   * Admin page content renderer with Tabs
   *
   * Renders settings fields within tabs, ensuring all fields are
   * included in the form submission to prevent data loss.
   *
   * @return void
   */
  public static function admin_page() {
    // Determine the active tab
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
  ?>
    <style>
      /* Basic CSS for tab content visibility */
      .supawp-settings-tab-content {
        display: none;
      }

      .supawp-settings-tab-content.active {
        display: block;
      }
    </style>
    <div class="wrap">
      <h1><?php echo esc_html(get_admin_page_title()) . ' v' . esc_html(SUPAWP_VERSION); ?></h1>

      <!-- Tab Navigation -->
      <h2 class="nav-tab-wrapper">
        <a href="?page=supawp&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
          <?php _e('General', 'supawp'); ?>
        </a>
        <a href="?page=supawp&tab=sync" class="nav-tab <?php echo $active_tab == 'sync' ? 'nav-tab-active' : ''; ?>">
          <?php _e('Sync Data', 'supawp'); ?>
        </a>
        <a href="?page=supawp&tab=security" class="nav-tab <?php echo $active_tab == 'security' ? 'nav-tab-active' : ''; ?>">
          <?php _e('Security', 'supawp'); ?>
        </a>
      </h2>

      <form action="options.php" method="post">
        <?php
        // Output nonce, action, and option_page fields for the settings group.
        settings_fields('supawp_options');
        ?>

        <!-- General Settings Tab Content -->
        <div id="tab-general" class="supawp-settings-tab-content <?php echo $active_tab == 'general' ? 'active' : ''; ?>">
          <?php
          // Render the general section and its fields
          echo '<h3>' . esc_html__('General Settings', 'supawp') . '</h3>';
          echo '<table class="form-table" role="presentation">';
          do_settings_fields('supawp', 'supawp_section_general');
          echo '</table>';
          ?>
        </div>

        <!-- Security Settings Tab Content -->
        <div id="tab-security" class="supawp-settings-tab-content <?php echo $active_tab == 'security' ? 'active' : ''; ?>">
          <?php
          // Last login attempt diagnostic
          $last = get_transient('supawp_last_login_attempt');
          if ($last) {
            $time  = esc_html($last['time']);
            $email = !empty($last['email']) ? ' (' . esc_html($last['email']) . ')' : '';
            if ($last['result'] === 'success') {
              echo '<div class="notice notice-success inline" style="margin:10px 0;"><p>'
                . '<strong>&#10003; Last login attempt: SUCCESS</strong> &mdash; ' . $time . $email . '</p></div>';
            } else {
              echo '<div class="notice notice-error inline" style="margin:10px 0;"><p>'
                . '<strong>&#10007; Last login attempt: FAILED</strong> &mdash; ' . $time . $email . '<br>'
                . '<code style="display:block;margin-top:6px;white-space:normal;">' . esc_html($last['error']) . '</code></p></div>';
            }
          } else {
            echo '<div class="notice notice-info inline" style="margin:10px 0;"><p>'
              . 'No login attempts recorded yet. Try logging in, then refresh this page.</p></div>';
          }

          $jwt_test = self::test_jwt_configuration();
          if ($jwt_test === true) {
            echo '<div class="notice notice-success inline" style="margin:10px 0;"><p>'
              . '<strong>&#10003; JWT Secret verified</strong> &mdash; successfully decoded the Anon Key. Auto-login is correctly configured.</p></div>';
          } elseif (is_string($jwt_test)) {
            echo '<div class="notice notice-error inline" style="margin:10px 0;"><p>'
              . '<strong>&#10007; JWT Secret mismatch</strong> &mdash; could not decode the Anon Key using the current JWT Secret.<br>'
              . '<em>' . esc_html($jwt_test) . '</em><br><br>'
              . 'This is why logins fail with &ldquo;Invalid authentication token.&rdquo; &mdash; '
              . 'the secret here must match your Supabase project exactly.<br>'
              . 'Find the correct value at: <strong>Supabase Dashboard &rarr; Project Settings &rarr; API &rarr; JWT Settings &rarr; JWT Secret</strong>.</p></div>';
          } elseif ($jwt_test === null) {
            echo '<div class="notice notice-warning inline" style="margin:10px 0;"><p>'
              . '&#9888; Cannot verify JWT Secret &mdash; both the Anon Key and JWT Secret must be saved to run the self-test.</p></div>';
          }
          // Render the security section and its fields
          echo '<h3>' . esc_html__('Security Settings', 'supawp') . '</h3>';
          echo '<table class="form-table" role="presentation">';
          do_settings_fields('supawp', 'supawp_section_security');
          echo '</table>';
          ?>
        </div>

        <!-- Sync Data Settings Tab Content -->
        <div id="tab-sync" class="supawp-settings-tab-content <?php echo $active_tab == 'sync' ? 'active' : ''; ?>">
          <?php
          // Render the sync data section and its fields
          echo '<h3>' . esc_html__('Sync Data Settings', 'supawp') . '</h3>';
          echo '<table class="form-table" role="presentation">';
          do_settings_fields('supawp', 'supawp_section_sync');
          echo '</table>';

          ?>
        </div>

        <?php
        // Save button - outside the conditional tab divs
        submit_button(__('Save Settings', 'supawp'));
        ?>
      </form>

      <!-- Shortcode Info (Keep outside the form) -->
      <div class="supawp-admin-info" style="margin-top: 20px; padding-top: 10px; border-top: 1px solid #ccc;">
        <h2><?php _e('Shortcodes', 'supawp'); ?></h2>
        <p><?php _e('Use these shortcodes in your pages or posts:', 'supawp'); ?></p>
        <ul>
          <li><code>[supawp_login]</code> - <?php _e('Displays a login form', 'supawp'); ?></li>
          <li><code>[supawp_signup]</code> - <?php _e('Displays a signup form', 'supawp'); ?></li>
          <li><code>[supawp_logout]</code> - <?php _e('Displays a logout button', 'supawp'); ?></li>
          <li><code>[supawp_auth]</code> - <?php _e('Displays a combined login/signup form', 'supawp'); ?></li>
          <li><code>[supawp_launch_app]</code> - <?php _e('Shows "Open App" button for logged-in users, "Log In" link for guests', 'supawp'); ?></li>
        </ul>
      </div>
    </div>
<?php
  }
}
