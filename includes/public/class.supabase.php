<?php

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

/**
 * Supabase Helper Class
 *
 * @category Class
 * @package  SupaWP
 */
class Supabase {

  // Define a constant or variable for the Product ID
  const SUPAWP_PRODUCT_ID = 'SUPA_WP'; // Adjust if needed

  /**
   * Initialize the class
   *
   * @return void
   */
  public static function init() {
    add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    // Remove the direct script initialization in wp_head
    // add_action('wp_head', array(__CLASS__, 'supabase_init_script'));

    // Add AJAX action for frontend-triggered WP logout
    add_action('wp_ajax_supawp_frontend_logout', array(__CLASS__, 'handle_frontend_logout'));
    add_action('wp_ajax_nopriv_supawp_frontend_logout', array(__CLASS__, 'handle_frontend_logout')); // Handle even if WP thinks user is logged out

    // Add AJAX action for checking WP login status
    add_action('wp_ajax_supawp_check_wp_login_status', array(__CLASS__, 'handle_check_wp_login_status'));
  }

  /**
   * Enqueue scripts and styles
   *
   * @return void
   */
  public static function enqueue_scripts() {

    // Enqueue our custom styles
    wp_enqueue_style('supawp-styles', SUPAWP_PLUGIN_URL . 'css/supabase.css', array(), SUPAWP_VERSION);

    // Get plugin options
    $options = get_option('supawp_options', array());

    // Get authentication methods
    $auth_methods = isset($options['supawp_auth_methods']) && is_array($options['supawp_auth_methods'])
      ? $options['supawp_auth_methods']
      : array('email'); // Default to email only

    // Only enqueue social login CSS if Google authentication is enabled
    if (in_array('google', $auth_methods)) {
      wp_enqueue_style(
        'supawp-social-login-styles',
        SUPAWP_PLUGIN_URL . 'css/supawp-social-login.css',
        array('supawp-styles'),
        SUPAWP_VERSION
      );
    }

    // Only load the Supabase auth JS on pages that actually contain a SupaWP shortcode.
    // Sites where login is handled by an external app (e.g. a separate Next.js app)
    // never place these shortcodes, so the JS never loads — avoiding auth-state loops
    // on cart, checkout, account pages, and everywhere else.
    global $post;
    $supawp_shortcodes = array('supawp_login', 'supawp_signup', 'supawp_logout', 'supawp_auth', 'supawp_launch_app');
    $has_supawp_shortcode = false;
    if ($post) {
      foreach ($supawp_shortcodes as $tag) {
        if (has_shortcode($post->post_content, $tag)) {
          $has_supawp_shortcode = true;
          break;
        }
      }
    }
    if (!$has_supawp_shortcode) {
      return;
    }

    // --- Dynamic Script Loading Logic ---

    // Define default values including the product key
    $defaults = array(
      'supawp_supabase_url'          => '', // Kept for potential fallback/direct use if needed, but not primary
      'supawp_supabase_anon_key'     => '', // Kept for potential fallback/direct use if needed, but not primary
      'supawp_product_key'           => '', // Add the product key option
      'supawp_wp_auto_login_enabled' => 'off',
      'supawp_redirect_after_login'  => home_url(),
      'supawp_redirect_after_logout' => home_url(),
    );

    // Merge user-defined options with defaults, ensuring all required keys exist
    // wp_parse_args() combines two arrays, with the first array's values taking precedence
    $options = wp_parse_args($options, $defaults);

    // Get required data for the secret
    $site_url   = get_site_url();
    $license    = $options['supawp_product_key'];
    $plugin_id  = self::SUPAWP_PRODUCT_ID; // Use the defined constant

    // Basic check for license key
    if (empty($license)) {
      // Optionally log an error or display an admin notice
      error_log('[SupaWP] Product Key is missing. Cannot load SupaWP script.');
      // For now, just return to prevent enqueueing without a key
      return;
    }

    // Create the secret string
    $secret_data = json_encode(array(
      'productKey' => $license,
      'origin'     => $site_url,
      'pluginId'   => $plugin_id
    ));
    $secret = base64_encode($secret_data);

    // Determine script URL based on environment
    $supawp_script_base_url = '//wp.techcater.com/js/supabase.js'; // Production URL
    // Define local dev script path outside the condition for clarity
    // Use plugins_url() for a dynamic path relative to the current plugin
    // Requires SUPAWP_PLUGIN_FILE constant (or similar) defined in the main plugin file.
    $local_dev_script_path = plugins_url('js/supabase.js', SUPAWP_PLUGIN_FILE);

    if (strpos($site_url, '.local') !== false || strpos($site_url, 'localhost') !== false) { // More robust local check
      // Check if you want to use the *actual* local dev server path or the wp-dev path
      // Using the specific localhost path based on your highlighted line 88
      $supawp_script_base_url = $local_dev_script_path;
      // Uncomment below if you prefer wp-dev.techcater.com for local WP sites
      // $supawp_script_base_url = '//wp-dev.techcater.com/js/supabase.js'; // Development URL
    }

    $supawp_script_url = $supawp_script_base_url . '?secret=' . $secret;

    // Enqueue the remote script
    wp_enqueue_script('supawp-auth', $supawp_script_url, array(), SUPAWP_VERSION, true);

    // --- Localization ---
    // Load frontend translations
    $public_translations = require_once SUPAWP_PLUGIN_DIR . 'i18n/public_translations.php';


    // Localize necessary config data for frontend interactions.
    // Since exposing anon key is acceptable, we include it and the URL here.
    $config_for_js = array(
      'supabaseUrl'          => $options['supawp_supabase_url'],
      'supabaseAnonKey'      => $options['supawp_supabase_anon_key'],
      'redirectAfterLogin'   => $options['supawp_redirect_after_login'],
      'redirectAfterLogout'  => $options['supawp_redirect_after_logout'],
      'ajaxUrl'              => admin_url('admin-ajax.php'),
      'nonce'                => wp_create_nonce('supawp-ajax-nonce'), // Nonce for WP AJAX calls
      'wpAutoLoginEnabled'   => $options['supawp_wp_auto_login_enabled'],
      'isWordPressLoggedIn'  => is_user_logged_in(), // Add WP login status directly
      'usersTableName'       => !empty($options['supawp_users_table_name']) ? $options['supawp_users_table_name'] : '', // Add users table name
      'authMethods'          => isset($options['supawp_auth_methods']) && is_array($options['supawp_auth_methods']) ? $options['supawp_auth_methods'] : array('email'), // Add auth methods
      'emailVerificationMethod' => isset($options['supawp_email_verification_method']) ? $options['supawp_email_verification_method'] : 'magic_link', // Add email verification method
      'passwordResetMethod'  => isset($options['supawp_password_reset_method']) ? $options['supawp_password_reset_method'] : 'magic_link', // Add password reset method
      'translations'         => $public_translations, // Add frontend translations
    );

    // Remove the conditional logic as keys are always included now.
    // if ($is_local_dev_script) { ... }


    wp_localize_script('supawp-auth', 'SupaWPConfig', $config_for_js);
  }

  /**
   * Initialize Supabase in the header - REMOVED
   * This function is no longer needed as initialization is handled by the remote script.
   *
   * @return void
   */
  // public static function supabase_init_script() { ... }


  /**
   * AJAX handler for logging the user out of WordPress from the frontend.
   *
   * Triggered by triggerWordPressLogout() in auth.ts.
   */
  public static function handle_frontend_logout() {
    // Nonce check is less critical here as the trigger is Supabase logout.
    // The main goal is to ensure WP session is cleared if it exists.
    // We will still verify if possible, but won't strictly require it to proceed with logout.
    $nonce_verified = isset($_POST['_ajax_nonce']) && wp_verify_nonce(sanitize_key($_POST['_ajax_nonce']), 'supawp-ajax-nonce');

    if (!$nonce_verified) {
      error_log('[SupaWP Frontend Logout] Nonce verification failed. Proceeding with logout check anyway.');
    }

    if (is_user_logged_in()) {
      // Only log out users whose WordPress account is linked to Supabase.
      // Native WordPress users (admins, editors) have no supabase_uid and must
      // never be logged out by a Supabase auth-state change — they authenticate
      // through WordPress directly and have no Supabase session to sync with.
      $supabase_uid = get_user_meta(get_current_user_id(), 'supabase_uid', true);
      if (empty($supabase_uid)) {
        wp_send_json_success(array('message' => 'Native WordPress user — skipping Supabase-triggered logout.'));
        return;
      }

      // If this user was logged in via the cross-domain session redirect (e.g. from a
      // separate Next.js app), postglider.com's Supabase client will always see SIGNED_OUT
      // because the session lives in the app's localStorage on a different origin.
      // Honouring the logout here would undo the session immediately after it was set.
      if (get_transient('supawp_cross_domain_' . get_current_user_id())) {
        wp_send_json_success(array('message' => 'Cross-domain session active — skipping Supabase-triggered logout.'));
        return;
      }

      wp_logout();
      wp_clear_auth_cookie();
      wp_send_json_success(array('message' => __('WordPress logout successful.', 'supawp')));
    } else {
      wp_send_json_success(array('message' => __('No active WordPress session found to log out.', 'supawp')));
    }
  }

  /**
   * @deprecated - no longer used at the moment, but let's see if we need to utilize this in the future
   *
   * AJAX handler to check if the current user is logged into WordPress.
   */
  public static function handle_check_wp_login_status() {
    // Nonce check is crucial here. Use check_ajax_referer for consistency.
    // TEMP: Remove to bypass cache
    // check_ajax_referer('supawp-ajax-nonce', '_ajax_nonce');

    wp_send_json_success(array('loggedIn' => is_user_logged_in()));
  }
}
