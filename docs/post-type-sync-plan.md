# SupaWP Post Type Sync Feature Plan

This document outlines the comprehensive plan for implementing post type synchronization functionality in the SupaWP plugin, allowing WordPress posts, pages, and custom post types to be automatically synced to Supabase.

## Table of Contents

1. [Overview](#overview)
2. [Dashboard Configuration](#dashboard-configuration)
3. [WordPress Hooks Implementation](#wordpress-hooks-implementation)
4. [Supabase Service Class](#supabase-service-class)
5. [Filter Hooks Implementation](#filter-hooks-implementation)
6. [Supabase Table Setup Guide](#supabase-table-setup-guide)
7. [Plugin Integration](#plugin-integration)
8. [Usage Examples](#usage-examples)
9. [Security Considerations](#security-considerations)
10. [Implementation Checklist](#implementation-checklist)

## Overview

The post type sync feature will allow users to:
- Configure which post types to sync (posts, pages, custom post types)
- Automatically sync WordPress post data to Supabase when posts are created, updated, or deleted
- Use reusable filter hooks for customizations
- Maintain data integrity with proper security policies

## Dashboard Configuration

### Admin Settings Fields

Add the following settings to the SupaWP admin panel:

```php
// In class.supawp-admin.php - Add to register_settings() method

// Add new section for Post Type Sync
add_settings_section(
  'supawp_section_post_sync',
  __('Post Type Sync Settings', 'supawp'),
  array(__CLASS__, 'print_post_sync_section_info'),
  'supawp'
);

// Add post types field
add_settings_field(
  'supawp_sync_post_types',
  __('Sync Post Types', 'supawp'),
  array(__CLASS__, 'sync_post_types_field_cb'),
  'supawp',
  'supawp_section_post_sync'
);

// Add custom post types field
add_settings_field(
  'supawp_sync_custom_post_types',
  __('Custom Post Types', 'supawp'),
  array(__CLASS__, 'sync_custom_post_types_field_cb'),
  'supawp',
  'supawp_section_post_sync'
);
```

### Field Callback Methods

```php
public static function print_post_sync_section_info() {
  echo '<p>' . __('Configure which post types should be automatically synced to Supabase.', 'supawp') . '</p>';
}

public static function sync_post_types_field_cb() {
  $options = get_option('supawp_options', array());
  $sync_post_types = isset($options['supawp_sync_post_types']) ? $options['supawp_sync_post_types'] : array('post');
  
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
```

## WordPress Hooks Implementation

### Create: `includes/public/class.supawp-wordpress.php`

```php
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
    self::$supabase_service = new SupaWP_Supabase_Service();
    
    // WordPress post hooks
    add_action('wp_insert_post', array(__CLASS__, 'listen_to_save_post_event'), 10, 3);
    add_action('wp_trash_post', array(__CLASS__, 'listen_to_delete_post_event'), 10, 1);
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
      $post_data = self::prepare_post_data($post_id, $post);
      $table_name = SupaWP_Utils::table_name_generator($post->post_type);
      self::$supabase_service->save_post_to_supabase($table_name, $post_data);
    }
  }
  
  /**
   * Listen to post delete events
   */
  public static function listen_to_delete_post_event($post_id) {
    $post_type = get_post_type($post_id);
    
    if (self::should_sync_post_type($post_type)) {
      $table_name = \SupaWP_Utils::table_name_generator($post_type);
      self::$supabase_service->delete_post_from_supabase($table_name, $post_id);
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
   * Get table name for post type
   */
  // Add a function to SupaWP_Utils named table_name_generator
  // that will return table name as snake_case such as wp_posts
  
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
      'post_thumbnail' => get_the_post_thumbnail_url($post_id, 'post-thumbnail'),
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
```

## Supabase Service Class

### Create: `includes/public/class.supawp-service.php`

```php
<?php
if (!defined('ABSPATH')) {
  exit;
}

/**
 * SupaWP Supabase Service Class
 * Handles CRUD operations with Supabase
 */
class SupaWP_Service {
  private $supabase_url;
  private $supabase_anon_key;
  private $jwt_secret;
  
  /**
   * Constructor
   */
  public function __construct() {
    $options = get_option('supawp_options', array());
    $this->supabase_url = isset($options['supawp_supabase_url']) ? $options['supawp_supabase_url'] : '';
    $this->supabase_anon_key = isset($options['supawp_supabase_anon_key']) ? $options['supawp_supabase_anon_key'] : '';
    $this->jwt_secret = isset($options['supawp_jwt_secret']) ? $options['supawp_jwt_secret'] : '';
  }
  
  /**
   * Save post to Supabase
   */
  public function save_post_to_supabase($table_name, $post_data) {
    
    // Allow filtering before saving
    $post_data = apply_filters('supawp_before_saving_to_supabase', $post_data);
    
    $response = wp_remote_post($this->supabase_url . '/rest/v1/' . $table_name, array(
      'headers' => array(
        'apikey' => $this->supabase_anon_key,
        'Authorization' => 'Bearer ' . $this->supabase_anon_key,
        'Content-Type' => 'application/json',
        'Prefer' => 'resolution=merge-duplicates'
      ),
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
   * Delete post from Supabase
   */
  public function delete_post_from_supabase($table_name, $post_id) {
    $response = wp_remote_request($this->supabase_url . '/rest/v1/' . $table_name . '?id=eq.' . $post_id, array(
      'method' => 'DELETE',
      'headers' => array(
        'apikey' => $this->supabase_anon_key,
        'Authorization' => 'Bearer ' . $this->supabase_anon_key,
        'Content-Type' => 'application/json'
      ),
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
      'headers' => array(
        'apikey' => $this->supabase_anon_key,
        'Authorization' => 'Bearer ' . $this->supabase_anon_key,
        'Content-Type' => 'application/json'
      ),
      'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
      error_log('[SupaWP] Error getting data from Supabase: ' . $response->get_error_message());
      return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
  }
}
```

## Filter Hooks Implementation

### Create: `includes/public/class.supawp-filters.php`

```php
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
    self::$supabase_service = new SupaWP_Supabase_Service();
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
    if (!$table_name && isset($data['post_type'])) {
      $table_name = SupaWP_Utils::table_name_generator($data['post_type']);
    }
    
    return self::$supabase_service->save_post_to_supabase($table_name, $data);
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
}
```

## Supabase Table Setup Guide

### Table Structure

For each post type you want to sync, create a table with the naming convention: `wp_{post_type}s`

#### Example: wp_posts table

```sql
-- Create wp_posts table
CREATE TABLE public.wp_posts (
  id integer PRIMARY KEY,
  post_title text,
  post_content text,
  post_excerpt text,
  post_status text,
  post_type text,
  post_date timestamptz,
  post_modified timestamptz,
  permalink text,
  post_thumbnail text,
  author jsonb,
  taxonomies jsonb,
  custom_fields jsonb,
  synced_at timestamptz DEFAULT now()
);

-- Enable RLS
ALTER TABLE public.wp_posts ENABLE ROW LEVEL SECURITY;

-- Create policies for WordPress service
CREATE POLICY "Allow WordPress service full access"
ON public.wp_posts
FOR ALL
TO authenticated
USING (true)
WITH CHECK (true);

-- Create index for better performance
CREATE INDEX idx_wp_posts_status ON public.wp_posts(post_status);
CREATE INDEX idx_wp_posts_type ON public.wp_posts(post_type);
CREATE INDEX idx_wp_posts_date ON public.wp_posts(post_date);
```

#### Example: wp_pages table

```sql
-- Create wp_pages table
CREATE TABLE public.wp_pages (
  id integer PRIMARY KEY,
  post_title text,
  post_content text,
  post_excerpt text,
  post_status text,
  post_type text,
  post_date timestamptz,
  post_modified timestamptz,
  permalink text,
  post_thumbnail text,
  author jsonb,
  taxonomies jsonb,
  custom_fields jsonb,
  synced_at timestamptz DEFAULT now()
);

-- Enable RLS
ALTER TABLE public.wp_pages ENABLE ROW LEVEL SECURITY;

-- Create policies
CREATE POLICY "Allow WordPress service full access"
ON public.wp_pages
FOR ALL
TO authenticated
USING (true)
WITH CHECK (true);
```

### Custom Post Types

For custom post types like 'book', create a table named `wp_books`:

```sql
CREATE TABLE public.wp_books (
  id integer PRIMARY KEY,
  post_title text,
  post_content text,
  post_excerpt text,
  post_status text,
  post_type text,
  post_date timestamptz,
  post_modified timestamptz,
  permalink text,
  post_thumbnail text,
  author jsonb,
  taxonomies jsonb,
  custom_fields jsonb,
  synced_at timestamptz DEFAULT now()
);

ALTER TABLE public.wp_books ENABLE ROW LEVEL SECURITY;

CREATE POLICY "Allow WordPress service full access"
ON public.wp_books
FOR ALL
TO authenticated
USING (true)
WITH CHECK (true);
```

## Plugin Integration

### Update `init.php`

Add the following to the `init_supawp_plugin()` function:

```php
// Load WordPress sync functionality
require_once SUPAWP_PLUGIN_DIR . 'includes/public/class.supawp-wordpress.php';
if (class_exists('SupaWP_WordPress')) {
  SupaWP_WordPress::init();
}

// Load Supabase service
require_once SUPAWP_PLUGIN_DIR . 'includes/public/class.supawp-service.php';

// Load filters
require_once SUPAWP_PLUGIN_DIR . 'includes/public/class.supawp-filters.php';
if (class_exists('SupaWP_Filters')) {
  SupaWP_Filters::init();
}
```

## Usage Examples

### Using the Filter Hooks

```php
// Get data from Supabase
$posts = apply_filters('supawp_get_data_from_supabase', 'wp_posts', array('post_status' => 'eq.publish'));

// Save data to Supabase
$post_data = array(
  'id' => 123,
  'post_title' => 'My Post',
  'post_content' => 'Content here',
  'post_type' => 'post'
);
apply_filters('supawp_save_data_to_supabase', $post_data);

// Delete data from Supabase
apply_filters('supawp_delete_data_from_supabase', 'wp_posts', 123);

// Modify data before saving
add_filter('supawp_before_saving_to_supabase', function($data) {
  $data['custom_field'] = 'additional data';
  return $data;
});
```

### Custom Integration Example

```php
// In your theme or plugin
function my_custom_post_sync($post_id) {
  $post = get_post($post_id);
  
  // Prepare custom data
  $custom_data = array(
    'id' => $post_id,
    'post_title' => $post->post_title,
    'post_content' => $post->post_content,
    'post_type' => $post->post_type,
    'my_custom_field' => get_post_meta($post_id, 'my_custom_field', true)
  );
  
  // Use SupaWP filter to save
  apply_filters('supawp_save_data_to_supabase', $custom_data);
}

// Hook into WordPress
add_action('save_post', 'my_custom_post_sync');
```

## Security Considerations

1. **Row Level Security (RLS)**: Always enable RLS on your tables
2. **Service Role**: Use a service role key for WordPress operations
3. **API Policies**: Create specific policies for your WordPress service
4. **Data Validation**: Validate data before inserting/updating
5. **Error Logging**: Implement proper error logging for debugging
6. **Rate Limiting**: Consider implementing rate limiting for API calls

## Implementation Checklist

- [ ] Add dashboard configuration fields
- [ ] Create WordPress hooks class
- [ ] Create Supabase service class
- [ ] Create filter hooks class
- [ ] Update plugin initialization
- [ ] Create Supabase table setup documentation
- [ ] Test with built-in post types (post, page)
- [ ] Test with custom post types
- [ ] Implement error handling and logging
- [ ] Add security policies to Supabase tables
- [ ] Create usage examples and documentation
- [ ] Test CRUD operations
- [ ] Performance testing with large datasets
- [ ] Security audit of implementation

## Available Filter Hooks

### `supawp_get_data_from_supabase`
- **Purpose**: Get data from Supabase
- **Parameters**: `$table_name`, `$filters`
- **Returns**: Array of data or false on error

### `supawp_save_data_to_supabase`
- **Purpose**: Save data to Supabase
- **Parameters**: `$data`, `$table_name`
- **Returns**: Response data or false on error

### `supawp_delete_data_from_supabase`
- **Purpose**: Delete data from Supabase
- **Parameters**: `$table_name`, `$post_id`
- **Returns**: Boolean success status

### `supawp_before_saving_to_supabase`
- **Purpose**: Modify data before saving
- **Parameters**: `$data`
- **Returns**: Modified data array

### `supawp_after_saving_to_supabase`
- **Purpose**: Action hook after saving
- **Parameters**: `$response_data`, `$original_data`

### `supawp_after_deleting_from_supabase`
- **Purpose**: Action hook after deleting
- **Parameters**: `$post_id`, `$table_name`

This comprehensive plan provides a solid foundation for implementing post type synchronization in the SupaWP plugin, following WordPress best practices and providing extensibility through filter hooks.
