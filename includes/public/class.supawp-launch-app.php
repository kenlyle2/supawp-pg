<?php
defined('ABSPATH') || exit;

/**
 * SupaWP Launch App
 *
 * Handles the ?supawp_launch_app=1 endpoint.
 *
 * Flow:
 *   1. User (logged into WordPress) clicks the "Open App" button.
 *   2. WordPress calls the Supabase admin API to generate a single-use magic link
 *      for that user's email, with redirect_to pointing at the Next.js auth callback.
 *   3. WordPress redirects the browser to the Supabase action_link.
 *   4. Supabase verifies the token, creates a session, and redirects to the app
 *      with access_token + refresh_token in the URL fragment.
 *   5. The Next.js Supabase client picks up the session automatically.
 */
class SupaWP_Launch_App {

  public static function init() {
    add_filter('query_vars', array(__CLASS__, 'add_query_var'));
    add_action('template_redirect', array(__CLASS__, 'handle_launch'));
  }

  public static function add_query_var($vars) {
    $vars[] = 'supawp_launch_app';
    return $vars;
  }

  public static function handle_launch() {
    if (is_admin() || !get_query_var('supawp_launch_app')) {
      return;
    }

    // If not logged into WordPress, send to WP login then back here
    if (!is_user_logged_in()) {
      $return_url = add_query_arg('supawp_launch_app', '1', home_url('/'));
      wp_redirect(wp_login_url($return_url));
      exit;
    }

    $options          = get_option('supawp_options', array());
    $supabase_url     = isset($options['supawp_supabase_url'])     ? rtrim(trim($options['supawp_supabase_url']), '/')     : '';
    $service_role_key = isset($options['supawp_service_role_key']) ? trim($options['supawp_service_role_key'])             : '';
    $app_callback_url = isset($options['supawp_app_callback_url']) ? trim($options['supawp_app_callback_url'])             : '';

    if (empty($supabase_url) || empty($service_role_key)) {
      wp_die(
        __('App launch is not configured: Supabase URL or Service Role Key is missing. Please contact the administrator.', 'supawp'),
        __('Configuration Error', 'supawp'),
        array('response' => 500)
      );
    }

    if (empty($app_callback_url)) {
      wp_die(
        __('App launch is not configured: App Callback URL is missing. Please contact the administrator.', 'supawp'),
        __('Configuration Error', 'supawp'),
        array('response' => 500)
      );
    }

    $email = wp_get_current_user()->user_email;

    $response = wp_remote_post(
      $supabase_url . '/auth/v1/admin/generate_link',
      array(
        'headers' => array(
          'Content-Type'  => 'application/json',
          'Authorization' => 'Bearer ' . $service_role_key,
          'apikey'        => $service_role_key,
        ),
        'body'    => json_encode(array(
          'type'    => 'magiclink',
          'email'   => $email,
          'options' => array(
            'redirect_to' => $app_callback_url,
          ),
        )),
        'timeout' => 15,
      )
    );

    if (is_wp_error($response)) {
      wp_die(
        sprintf(__('Failed to generate app link: %s', 'supawp'), esc_html($response->get_error_message())),
        __('App Launch Error', 'supawp'),
        array('response' => 502)
      );
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code !== 200 || empty($body['action_link'])) {
      $msg = isset($body['message']) ? $body['message'] : 'Unexpected response from Supabase (HTTP ' . $code . ')';
      wp_die(
        sprintf(__('Failed to generate app link: %s', 'supawp'), esc_html($msg)),
        __('App Launch Error', 'supawp'),
        array('response' => 502)
      );
    }

    wp_redirect($body['action_link']);
    exit;
  }
}
