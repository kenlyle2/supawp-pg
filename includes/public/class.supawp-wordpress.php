<?php
if (!defined('ABSPATH')) {
  exit;
}

/**
 * SupaWP WordPress Integration Class
 * Handles WordPress post hooks and data preparation
 */
class SupaWP_WordPress {
  private static $supabase_service;

  /**
   * Initialize the class
   */
  public static function init() {
    add_action('init', array(__CLASS__, 'init_hooks'));
  }

  /**
   * Initialize WordPress hooks
   */
  public static function init_hooks() {
    // Initialize Supabase service
    self::$supabase_service = new SupaWP_Service();

    // WordPress post hooks
    add_action('wp_insert_post', array(__CLASS__, 'listen_to_save_post_event'), 10, 3);
    add_action('trashed_post', array(__CLASS__, 'listen_to_delete_post_event'), 10, 1);
    add_action('before_delete_post', array(__CLASS__, 'listen_to_delete_post_event'), 10, 1);

    // Register delayed sync hook
    add_action('supawp_delayed_post_sync', array(__CLASS__, 'handle_delayed_post_sync'));
  }

  /**
   * Listen to post save events
   */
  public static function listen_to_save_post_event($post_id, $post, $update) {
    // Skip revisions
    if (wp_is_post_revision($post)) {
      return;
    }

    // Check if post type should be synced
    if (self::should_sync_post_type($post->post_type)) {
      // Schedule delayed sync to allow thumbnail processing
      wp_schedule_single_event(time() + 5, 'supawp_delayed_post_sync', array($post_id));
    }
  }

  /**
   * Listen to post delete events
   */
  public static function listen_to_delete_post_event($post_id) {
    $post_type = get_post_type($post_id);

    if (self::should_sync_post_type($post_type)) {
      // Let the service handle table name generation
      self::$supabase_service->delete_post_by_id($post_id, $post_type);
    }
  }

  /**
   * Check if post type should be synced
   */
  private static function should_sync_post_type($post_type) {
    $options = get_option('supawp_options', array());

    // Check built-in post types
    $sync_post_types = isset($options['supawp_sync_post_types']) ? $options['supawp_sync_post_types'] : array();
    if (in_array($post_type, $sync_post_types)) {
      return true;
    }

    // Check custom post types
    $custom_post_types = isset($options['supawp_sync_custom_post_types']) ? $options['supawp_sync_custom_post_types'] : '';
    if (!empty($custom_post_types)) {
      $custom_types_array = array_map('trim', explode(',', $custom_post_types));
      if (in_array($post_type, $custom_types_array)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Prepare post data for Supabase
   */
  private static function prepare_post_data($post_id, $post) {
    $post_data = array(
      'id' => $post_id,
      'post_title' => $post->post_title,
      'post_content' => $post->post_content,
      'post_excerpt' => $post->post_excerpt,
      'post_status' => $post->post_status,
      'post_type' => $post->post_type,
      'post_date' => $post->post_date,
      'post_modified' => $post->post_modified,
      'permalink' => get_permalink($post_id),
      'post_thumbnail' => has_post_thumbnail($post_id) ? get_the_post_thumbnail_url($post_id, 'full') : null,
      'author' => self::get_author_data($post->post_author),
      'taxonomies' => self::get_taxonomies($post_id, $post->post_type),
      'custom_fields' => self::get_custom_fields($post_id)
    );

    // Allow filtering before saving
    return apply_filters('supawp_before_saving_to_supabase', $post_data, $post_id, $post);
  }

  /**
   * Get author data
   */
  private static function get_author_data($author_id) {
    $author = get_userdata($author_id);
    if (!$author) {
      return null;
    }

    return array(
      'id' => $author->ID,
      'display_name' => $author->display_name,
      'user_email' => $author->user_email,
      'user_login' => $author->user_login
    );
  }

  /**
   * Get taxonomies for post
   */
  private static function get_taxonomies($post_id, $post_type) {
    $taxonomies = get_object_taxonomies($post_type, 'objects');
    $taxonomy_data = array();

    foreach ($taxonomies as $taxonomy_slug => $taxonomy) {
      $terms = get_the_terms($post_id, $taxonomy_slug);
      if (!empty($terms) && !is_wp_error($terms)) {
        $taxonomy_data[$taxonomy->label] = $terms;
      }
    }

    return $taxonomy_data;
  }

  /**
   * Handle delayed post sync for thumbnail processing
   */
  public static function handle_delayed_post_sync($post_id) {
    $post = get_post($post_id);
    if ($post && self::should_sync_post_type($post->post_type)) {
      $post_data = self::prepare_post_data($post_id, $post);
      $table_name = SupaWP_Utils::table_name_generator($post->post_type);
      self::$supabase_service->save_post_to_supabase($table_name, $post_data);
    }
  }

  /**
   * Get custom fields for post
   */
  private static function get_custom_fields($post_id) {
    $custom_fields = array();
    $post_meta = get_post_meta($post_id);

    foreach ($post_meta as $key => $value) {
      if (substr($key, 0, 1) !== '_') {
        $data = get_post_meta($post_id, $key, true);
        if (!empty($data)) {
          $custom_fields[$key] = $data;
        }
      }
    }

    return $custom_fields;
  }
}
