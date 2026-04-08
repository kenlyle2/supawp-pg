<?php
if (!defined('ABSPATH')) {
  exit;
}

/**
 * SupaWP Supabase Service Class
 * Handles CRUD operations with Supabase and Storage operations
 */
class SupaWP_Service {
  private $supabase_url;
  private $supabase_anon_key;
  private $supabase_service_role_key;

  /**
   * Constructor
   */
  public function __construct() {
    $options = get_option('supawp_options', array());
    $this->supabase_url = isset($options['supawp_supabase_url']) ? $options['supawp_supabase_url'] : '';
    $this->supabase_anon_key = isset($options['supawp_supabase_anon_key']) ? $options['supawp_supabase_anon_key'] : '';
    $this->supabase_service_role_key = isset($options['supawp_service_role_key']) ? $options['supawp_service_role_key'] : '';
  }

  /**
   * Get authorization headers for Supabase requests
   * Uses service role key for write operations if available, falls back to anon key
   *
   * @param bool $is_write_operation Whether this is a write operation (insert/update/delete)
   * @return array Headers array with authorization
   */
  private function get_auth_headers($is_write_operation = false) {
    $base_headers = array(
      'Content-Type' => 'application/json'
    );

    // Use service role key for write operations if available for better security
    if ($is_write_operation && !empty($this->supabase_service_role_key)) {
      $base_headers['apikey'] = $this->supabase_service_role_key;
      $base_headers['Authorization'] = 'Bearer ' . $this->supabase_service_role_key;
    } else {
      // Fall back to anon key (for read operations or when service key not configured)
      $base_headers['apikey'] = $this->supabase_anon_key;
      $base_headers['Authorization'] = 'Bearer ' . $this->supabase_anon_key;
    }

    return $base_headers;
  }

  /**
   * Get storage authorization headers
   * Storage operations require service role key for full access
   *
   * @return array Headers array with storage authorization
   */
  private function get_storage_auth_headers() {
    $headers = array();

    // Storage operations require service role key for full access
    if (!empty($this->supabase_service_role_key)) {
      $headers['Authorization'] = 'Bearer ' . $this->supabase_service_role_key;
    } else {
      // Fall back to anon key (may be limited by RLS policies)
      $headers['Authorization'] = 'Bearer ' . $this->supabase_anon_key;

      if (empty($this->supabase_anon_key)) {
        error_log('[SupaWP] Error: No authentication key available for storage operations');
        return false;
      }
    }

    return $headers;
  }

  /**
   * Save post to Supabase
   */
  public function save_post_to_supabase($table_name, $post_data) {

    // Allow filtering before saving
    $post_data = apply_filters('supawp_before_saving_to_supabase', $post_data);

    // Get secure headers for write operation
    $headers = $this->get_auth_headers(true);
    $headers['Prefer'] = 'resolution=merge-duplicates';

    // Log warning if using insecure configuration
    if (empty($this->supabase_service_role_key)) {
      error_log('[SupaWP] Security Warning: Using anonymous key for write operations. Consider configuring Service Role Key for better security.');
    }

    $response = wp_remote_post($this->supabase_url . '/rest/v1/' . $table_name, array(
      'headers' => $headers,
      'body' => json_encode($post_data),
      'timeout' => 30
    ));

    if (is_wp_error($response)) {
      error_log('[SupaWP] Error saving post to Supabase: ' . $response->get_error_message());
      return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Allow filtering after saving
    do_action('supawp_after_saving_to_supabase', $data, $post_data);

    return $data;
  }

  /**
   * Save data to specific table
   */
  public function save_to_table($post_data, $table_name) {
    // Allow filtering before saving
    $post_data = apply_filters('supawp_before_saving_to_supabase', $post_data);

    // Get secure headers for write operation
    $headers = $this->get_auth_headers(true);
    $headers['Prefer'] = 'resolution=merge-duplicates';

    // Log warning if using insecure configuration
    if (empty($this->supabase_service_role_key)) {
      error_log('[SupaWP] Security Warning: Using anonymous key for write operations. Consider configuring Service Role Key for better security.');
    }

    $response = wp_remote_post($this->supabase_url . '/rest/v1/' . $table_name, array(
      'headers' => $headers,
      'body' => json_encode($post_data),
      'timeout' => 30
    ));

    if (is_wp_error($response)) {
      error_log('[SupaWP] Error saving data to Supabase table ' . $table_name . ': ' . $response->get_error_message());
      return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Allow filtering after saving
    do_action('supawp_after_saving_to_supabase', $data, $post_data);

    return $data;
  }

  /**
   * Delete post by ID and post type
   */
  public function delete_post_by_id($post_id, $post_type) {
    $table_name = SupaWP_Utils::table_name_generator($post_type);
    return $this->delete_post_from_supabase($table_name, $post_id);
  }

  /**
   * Delete post from Supabase
   */
  public function delete_post_from_supabase($table_name, $post_id) {
    $response = wp_remote_request($this->supabase_url . '/rest/v1/' . $table_name . '?id=eq.' . $post_id, array(
      'method' => 'DELETE',
      'headers' => $this->get_auth_headers(true), // Use secure auth for delete
      'timeout' => 30
    ));

    if (is_wp_error($response)) {
      error_log('[SupaWP] Error deleting post from Supabase: ' . $response->get_error_message());
      return false;
    }

    do_action('supawp_after_deleting_from_supabase', $post_id, $table_name);

    return true;
  }

  /**
   * Get data from Supabase
   */
  public function get_data_from_supabase($table_name, $filters = array()) {
    $url = $this->supabase_url . '/rest/v1/' . $table_name;

    if (!empty($filters)) {
      $url .= '?' . http_build_query($filters);
    }

    $response = wp_remote_get($url, array(
      'headers' => $this->get_auth_headers(true), // Try service key for RLS bypass
      'timeout' => 30
    ));

    if (is_wp_error($response)) {
      error_log('[SupaWP] Error getting data from Supabase: ' . $response->get_error_message());
      return false;
    }

    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
  }

  /**
   * Upload image to Supabase Storage
   *
   * @param array $file File array from $_FILES
   * @param string $fileName Generated filename with path
   * @param string $bucket Storage bucket name
   * @return string Public URL on success, empty string on failure
   */
  public function upload_image_to_supabase($file, $fileName, $bucket = 'images') {
    // Validate file
    if (!$this->validate_image_file($file)) {
      error_log('[SupaWP] Error: Invalid image file for upload');
      return '';
    }

    // Get storage configuration
    $config = $this->get_storage_config();
    if (empty($config)) {
      error_log('[SupaWP] Error: Storage configuration not available');
      return '';
    }

    // Read file content
    $file_content = file_get_contents($file['tmp_name']);
    if ($file_content === false) {
      error_log('[SupaWP] Error: Failed to read file content');
      return '';
    }

    // Get authentication headers
    $auth_headers = $this->get_storage_auth_headers();
    if (!$auth_headers) {
      error_log('[SupaWP] Error: No authentication available for storage operations');
      return '';
    }

    // Upload via REST API
    $upload_url = $config['url'] . '/storage/v1/object/' . $bucket . '/' . $fileName;

    $response = wp_remote_post($upload_url, array(
      'headers' => array_merge($auth_headers, array(
        'Content-Type' => $file['type'],
        'Cache-Control' => 'max-age=3600'
      )),
      'body' => $file_content,
      'timeout' => 30
    ));

    if (is_wp_error($response)) {
      error_log('[SupaWP] Error: Upload request failed: ' . $response->get_error_message());
      return '';
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
      $response_body = wp_remote_retrieve_body($response);
      error_log('[SupaWP] Error: Upload failed with code ' . $response_code . ': ' . $response_body);
      return '';
    }

    // Generate and return public URL
    $public_url = $config['url'] . '/storage/v1/object/public/' . $bucket . '/' . $fileName;

    // Allow filtering after upload
    do_action('supawp_after_uploading_to_storage', $public_url, $file, $fileName, $bucket);

    return $public_url;
  }

  /**
   * Delete image from Supabase Storage
   *
   * @param string $image_url Public URL of the image to delete
   * @param string $bucket Storage bucket name
   * @return bool Success/failure status
   */
  public function delete_image_from_supabase($image_url, $bucket = 'images') {
    if (empty($image_url)) {
      error_log('[SupaWP] Error: Empty image URL provided for deletion');
      return false;
    }

    // Get storage configuration
    $config = $this->get_storage_config();
    if (empty($config)) {
      error_log('[SupaWP] Error: Storage configuration not available');
      return false;
    }

    // Extract file path from public URL
    $file_path = $this->extract_file_path_from_url($image_url, $bucket);
    if (!$file_path) {
      error_log('[SupaWP] Error: Could not extract file path from URL: ' . $image_url);
      return false;
    }

    // Get authentication headers
    $auth_headers = $this->get_storage_auth_headers();
    if (!$auth_headers) {
      error_log('[SupaWP] Error: No authentication available for storage operations');
      return false;
    }

    // Delete via REST API
    $delete_url = $config['url'] . '/storage/v1/object/' . $bucket . '/' . $file_path;

    $response = wp_remote_request($delete_url, array(
      'method' => 'DELETE',
      'headers' => array_merge($auth_headers, array(
        'Content-Type' => 'application/json'
      )),
      'timeout' => 30
    ));

    if (is_wp_error($response)) {
      error_log('[SupaWP] Error: Delete request failed: ' . $response->get_error_message());
      return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    // Success: 200 OK or 204 No Content
    $success = in_array($response_code, [200, 204]);

    if ($success) {
      // Allow filtering after successful deletion
      do_action('supawp_after_deleting_from_storage', $image_url, $bucket, $file_path);
    } else {
      $response_body = wp_remote_retrieve_body($response);
      error_log('[SupaWP] Error: Delete failed with code ' . $response_code . ': ' . $response_body);
    }

    return $success;
  }

  /**
   * Get Supabase Storage configuration
   *
   * @return array Configuration array with 'url' and 'auth_key', empty array if not available
   */
  public function get_storage_config() {
    if (empty($this->supabase_url)) {
      error_log('[SupaWP] Error: Supabase URL not configured');
      return array();
    }

    if (empty($this->supabase_service_role_key) && empty($this->supabase_anon_key)) {
      error_log('[SupaWP] Error: No authentication key configured for storage operations');
      return array();
    }

    // Return configuration for storage operations
    return array(
      'url' => rtrim($this->supabase_url, '/'),
      'auth_key' => $this->supabase_service_role_key ?: $this->supabase_anon_key
    );
  }

  /**
   * Validate image file for upload
   *
   * @param array $file File array from $_FILES
   * @return bool Validation result
   */
  private function validate_image_file($file) {
    // Check upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
      error_log('[SupaWP] Error: File upload error: ' . $file['error']);
      return false;
    }

    // Check file size (5MB limit)
    if ($file['size'] > 5242880) {
      error_log('[SupaWP] Error: File size too large. Maximum size is 5MB.');
      return false;
    }

    // Check MIME type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
      error_log('[SupaWP] Error: Invalid file type. Please use JPG, PNG, or WebP format.');
      return false;
    }

    // Additional MIME type verification
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
      error_log('[SupaWP] Error: Invalid file format detected.');
      return false;
    }

    return true;
  }

  /**
   * Extract file path from public URL
   *
   * @param string $image_url Public URL of the image
   * @param string $bucket Storage bucket name
   * @return string|false File path on success, false on failure
   */
  private function extract_file_path_from_url($image_url, $bucket) {
    // Get storage configuration for URL pattern
    $config = $this->get_storage_config();
    if (empty($config)) {
      return false;
    }

    // Expected URL pattern: {url}/storage/v1/object/public/{bucket}/{filepath}
    $expected_pattern = $config['url'] . '/storage/v1/object/public/' . $bucket . '/';

    if (strpos($image_url, $expected_pattern) !== 0) {
      error_log('[SupaWP] Error: Image URL does not match expected pattern: ' . $image_url);
      return false;
    }

    $file_path = str_replace($expected_pattern, '', $image_url);

    if (empty($file_path)) {
      error_log('[SupaWP] Error: Could not extract file path from URL: ' . $image_url);
      return false;
    }

    return $file_path;
  }
}
