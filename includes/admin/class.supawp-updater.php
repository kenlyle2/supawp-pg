<?php

/**
 * SupaWP-PG — Auto-updater
 *
 * Replaces the original techcater.com license-based updater with a
 * GitHub-release updater that reads metadata.json from kenlyle2/supawp-pg.
 *
 * Same pattern as the pg-media-vault plugin updater.
 */

if (!defined('ABSPATH')) exit;

class SupaWP_Admin_Updater {
  private static $initiated = false;

  const METADATA_URL = 'https://raw.githubusercontent.com/kenlyle2/supawp-pg/main/metadata.json';
  const PLUGIN_SLUG  = 'supawp';

  private static function plugin_file(): string {
    return plugin_basename(SUPAWP_PLUGIN_FILE);
  }

  public static function init() {
    if (!self::$initiated) {
      self::$initiated = true;
      add_filter('pre_set_site_transient_update_plugins', array(__CLASS__, 'check_update'));
      add_filter('plugins_api',                           array(__CLASS__, 'plugins_api_filter'), 10, 3);
    }
  }

  /**
   * Called directly by the admin settings page to surface an update notice.
   * Returns the metadata object if a newer version exists, false otherwise.
   */
  public static function check_for_update(): object|false {
    $metadata = self::fetch_metadata();
    if (!$metadata || empty($metadata->version)) return false;
    return version_compare($metadata->version, SUPAWP_VERSION, '>') ? $metadata : false;
  }

  public static function check_update($transient) {
    if (empty($transient->checked)) return $transient;

    $metadata = self::fetch_metadata();
    if (!$metadata || empty($metadata->version)) return $transient;

    if (version_compare($metadata->version, SUPAWP_VERSION, '>')) {
      $plugin_file = self::plugin_file();
      $transient->response[$plugin_file] = (object) [
        'slug'         => self::PLUGIN_SLUG,
        'plugin'       => $plugin_file,
        'new_version'  => $metadata->version,
        'url'          => 'https://github.com/kenlyle2/supawp-pg',
        'package'      => $metadata->download_url,
        'requires'     => $metadata->requires     ?? '6.0',
        'requires_php' => $metadata->requires_php ?? '7.4',
        'sections'     => (array) ($metadata->sections ?? new stdClass()),
      ];
    }

    return $transient;
  }

  public static function plugins_api_filter($result, $action, $args) {
    if ($action !== 'plugin_information' || ($args->slug ?? '') !== self::PLUGIN_SLUG) {
      return $result;
    }
    $metadata = self::fetch_metadata();
    if (!$metadata) return $result;
    return (object) [
      'name'          => $metadata->name         ?? 'SupaWP-PG',
      'slug'          => self::PLUGIN_SLUG,
      'version'       => $metadata->version,
      'requires'      => $metadata->requires     ?? '6.0',
      'requires_php'  => $metadata->requires_php ?? '7.4',
      'author'        => '<a href="https://postglider.com">PostGlider</a>',
      'download_link' => $metadata->download_url,
      'sections'      => (array) ($metadata->sections ?? new stdClass()),
    ];
  }

  private static function fetch_metadata(): ?stdClass {
    $response = wp_remote_get(self::METADATA_URL, [
      'timeout'    => 10,
      'user-agent' => 'SupaWP-PG/' . SUPAWP_VERSION . '; ' . get_bloginfo('url'),
    ]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return null;
    $data = json_decode(wp_remote_retrieve_body($response));
    return $data ?: null;
  }
}
