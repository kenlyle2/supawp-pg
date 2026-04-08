<?php
if (!defined('ABSPATH')) {
  exit;
}

/**
 * SupaWP Filters Class
 * Provides reusable filter hooks for CRUD operations
 */
class SupaWP_Filters {
  private static $supabase_service;

  /**
   * Initialize the class
   */
  public static function init() {
    self::$supabase_service = new SupaWP_Service();
    self::init_hooks();
  }

  /**
   * Initialize filter hooks
   */
  public static function init_hooks() {
    // Register filter hooks
    add_filter('supawp_get_data_from_supabase', array(__CLASS__, 'get_data_from_supabase'), 10, 2);
    add_filter('supawp_save_data_to_supabase', array(__CLASS__, 'save_data_to_supabase'), 10, 2);
    add_filter('supawp_delete_data_from_supabase', array(__CLASS__, 'delete_data_from_supabase'), 10, 2);
    add_filter('supawp_before_saving_to_supabase', array(__CLASS__, 'before_saving_to_supabase'), 10, 1);

    // Register filter hooks for storage operations
    add_filter('supawp_upload_image_to_supabase', array(__CLASS__, 'upload_image_to_supabase'), 10, 3);
    add_filter('supawp_delete_image_from_supabase', array(__CLASS__, 'delete_image_from_supabase'), 10, 2);
    add_filter('supawp_get_storage_config', array(__CLASS__, 'get_storage_config'), 10, 0);
  }

  /**
   * Get data from Supabase
   */
  public static function get_data_from_supabase($table_name, $filters = array()) {
    return self::$supabase_service->get_data_from_supabase($table_name, $filters);
  }

  /**
   * Save data to Supabase
   */
  public static function save_data_to_supabase($data, $table_name = null) {
    // If no table name provided and we have post_type, generate it
    if (!$table_name && isset($data['post_type'])) {
      $table_name = SupaWP_Utils::table_name_generator($data['post_type']);
      return self::$supabase_service->save_post_to_supabase($table_name, $data);
    }

    // If table name is provided, use it directly
    if ($table_name) {
      return self::$supabase_service->save_to_table($data, $table_name);
    }

    // Fallback - this shouldn't happen but handle gracefully
    if (isset($data['post_type'])) {
      $table_name = SupaWP_Utils::table_name_generator($data['post_type']);
      return self::$supabase_service->save_post_to_supabase($table_name, $data);
    }

    return false;
  }

  /**
   * Delete data from Supabase
   */
  public static function delete_data_from_supabase($table_name, $post_id) {
    return self::$supabase_service->delete_post_from_supabase($table_name, $post_id);
  }

  /**
   * Modify data before saving to Supabase
   */
  public static function before_saving_to_supabase($data) {
    // Add timestamp
    $data['synced_at'] = current_time('mysql');

    return $data;
  }

  /**
   * Upload image to Supabase Storage
   *
   * @param array $file File array from $_FILES
   * @param string $fileName Generated filename with path
   * @param string $bucket Storage bucket name
   * @return string Public URL on success, empty string on failure
   */
  public static function upload_image_to_supabase($file, $fileName, $bucket) {
    // Use the service method to perform the actual upload
    return self::$supabase_service->upload_image_to_supabase($file, $fileName, $bucket);
  }

  /**
   * Delete image from Supabase Storage
   *
   * @param string $image_url Public URL of the image to delete
   * @param string $bucket Storage bucket name
   * @return bool Success/failure status
   */
  public static function delete_image_from_supabase($image_url, $bucket) {
    // Use the service method to perform the actual deletion
    return self::$supabase_service->delete_image_from_supabase($image_url, $bucket);
  }

  /**
   * Get Supabase Storage configuration
   *
   * @return array Configuration array with 'url' and 'auth_key'
   */
  public static function get_storage_config() {
    // Use the service method to get the configuration
    return self::$supabase_service->get_storage_config();
  }
}
