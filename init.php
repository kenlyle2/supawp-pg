<?php

/**
 * The initiation loader for SupaWP, and the main plugin file.
 *
 * @category     WordPress_Plugin
 * @package      supawp
 * @author       techcater
 * @link         https://techcater.com
 *
 * Plugin Name:  SupaWP
 * Plugin URI:   https://techcater.com
 * Description:  SupaWP is a plugin that helps to integrate Supabase features to WordPress
 * Author:       techcater
 * Author URI:   https://techcater.com
 * Contributors: Tech Cater (@techcater)
 *
 * Version:      1.13.2
 * Tested up to: 6.8.3
 * Requires PHP: 7.4
 *
 * Text Domain:  supawp
 * Domain Path: /languages/
 *
 *
 *
 * This is an add-on for WordPress
 * https://wordpress.org/
 *
 */

/**
 * *********************************************************************
 *               You should not edit the code below
 *               (or any code in the included files)
 *               or things might explode!
 * ***********************************************************************
 */

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
  echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
  exit;
}

define('SUPAWP_VERSION', '1.13.2');
define('SUPAWP_MINIMUM_WP_VERSION', '5.0.0');
define('SUPAWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SUPAWP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SUPAWP_PLUGIN_FILE', __FILE__);

// Include the Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

add_action('plugins_loaded', 'init_supawp_plugin');

function init_supawp_plugin() {
  $plugin_rel_path = basename(dirname(__FILE__)) . '/languages';
  load_plugin_textdomain('supawp', false, $plugin_rel_path);

  // Load Supabase helper class
  require_once SUPAWP_PLUGIN_DIR . 'includes/public/class.supabase.php';
  if (class_exists('Supabase')) {
    Supabase::init();
  }

  // Register shortcodes
  require_once SUPAWP_PLUGIN_DIR . 'includes/public/class.shortcodes.php';
  if (class_exists('SupaWP_Shortcode')) {
    SupaWP_Shortcode::init();
  }

  // Load REST API handlers
  require_once SUPAWP_PLUGIN_DIR . 'includes/public/class.api-user.php';
  if (class_exists('SupaWP_Rest_Api_User')) {
    SupaWP_Rest_Api_User::init();
  }

  // Load WordPress sync functionality
  require_once SUPAWP_PLUGIN_DIR . 'includes/public/class.supawp-wordpress.php';
  if (class_exists('SupaWP_WordPress')) {
    SupaWP_WordPress::init();
  }

  // Load SupaWP utilities
  require_once SUPAWP_PLUGIN_DIR . 'includes/public/class.supawp-utils.php';

  // Load Supabase service
  require_once SUPAWP_PLUGIN_DIR . 'includes/public/class.supawp-service.php';

  // Load filters
  require_once SUPAWP_PLUGIN_DIR . 'includes/public/class.supawp-filters.php';
  if (class_exists('SupaWP_Filters')) {
    SupaWP_Filters::init();
  }

  // Load app launch handler
  require_once SUPAWP_PLUGIN_DIR . 'includes/public/class.supawp-launch-app.php';
  if (class_exists('SupaWP_Launch_App')) {
    SupaWP_Launch_App::init();
  }

  // Admin configuration
  if (is_admin() || (defined('WP_CLI') && WP_CLI)) {
    require_once SUPAWP_PLUGIN_DIR . 'includes/admin/class.supawp-admin.php';
    if (class_exists('SupaWP_Admin')) {
      SupaWP_Admin::init();
    }

    // Load the updater
    require_once SUPAWP_PLUGIN_DIR . 'includes/admin/class.supawp-updater.php';
    if (class_exists('SupaWP_Admin_Updater')) {
      SupaWP_Admin_Updater::init();
    }
  }

  // Initialize other extensions
  do_action('supawp_init');
}
