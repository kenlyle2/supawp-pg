# SupaWP Storage Filter Hook Examples

This document provides practical examples of how to implement and use the new storage filter hooks in SupaWP extensions.

## Available Filter Hooks

### 1. `supawp_upload_image_to_supabase`

**Filter Signature**: `apply_filters('supawp_upload_image_to_supabase', $file, $fileName, $bucket)`

**Parameters**:
- `$file` (array): File array from `$_FILES`
- `$fileName` (string): Generated filename with path
- `$bucket` (string): Storage bucket name

**Return**: Public URL string on success, empty string on failure

**Example Implementation**:
```php
// In your extension plugin
add_filter('supawp_upload_image_to_supabase', function($file, $fileName, $bucket) {
    // Your custom upload implementation here
    // This will be called instead of the default SupaWP implementation
    
    // Example: Custom validation or processing
    if ($file['size'] > 10485760) { // 10MB limit
        error_log('SupaWP Extension: File too large for custom processing');
        return ''; // Let SupaWP handle it with default logic
    }
    
    // Your custom upload logic
    $custom_result = your_custom_upload_function($file, $fileName, $bucket);
    
    if ($custom_result) {
        return $custom_result; // Return custom URL
    }
    
    // Fall back to default SupaWP implementation
    return '';
}, 10, 3);
```

### 2. `supawp_delete_image_from_supabase`

**Filter Signature**: `apply_filters('supawp_delete_image_from_supabase', $image_url, $bucket)`

**Parameters**:
- `$image_url` (string): Public URL of the image to delete
- `$bucket` (string): Storage bucket name

**Return**: Boolean success/failure status

**Example Implementation**:
```php
// In your extension plugin
add_filter('supawp_delete_image_from_supabase', function($image_url, $bucket) {
    // Your custom deletion implementation here
    // This will be called instead of the default SupaWP implementation
    
    // Example: Custom logging or processing
    error_log('SupaWP Extension: Custom deletion for ' . $image_url);
    
    // Your custom deletion logic
    $custom_result = your_custom_delete_function($image_url, $bucket);
    
    if ($custom_result) {
        return true; // Custom deletion successful
    }
    
    // Fall back to default SupaWP implementation
    return false;
}, 10, 2);
```

### 3. `supawp_get_storage_config`

**Filter Signature**: `apply_filters('supawp_get_storage_config')`

**Parameters**: None

**Return**: Configuration array with 'url' and 'auth_key'

**Example Implementation**:
```php
// In your extension plugin
add_filter('supawp_get_storage_config', function() {
    // Your custom configuration here
    // This will be called instead of the default SupaWP configuration
    
    // Example: Custom storage configuration
    $custom_config = array(
        'url' => 'https://your-custom-storage.example.com',
        'auth_key' => 'your-custom-auth-key'
    );
    
    if (!empty($custom_config['url']) && !empty($custom_config['auth_key'])) {
        return $custom_config; // Return custom configuration
    }
    
    // Fall back to default SupaWP configuration
    return array();
}, 10, 0);
```

## Complete Extension Example

Here's a complete example of how to implement storage functionality in a SupaWP extension:

```php
<?php
/**
 * Plugin Name: SupaWP Storage Extension
 * Description: Example extension demonstrating storage filter hooks
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

class SupaWP_Storage_Extension {
    
    public static function init() {
        add_action('supawp_init', array(__CLASS__, 'setup_storage_hooks'));
    }
    
    public static function setup_storage_hooks() {
        // Hook into storage operations
        add_filter('supawp_upload_image_to_supabase', array(__CLASS__, 'handle_image_upload'), 10, 3);
        add_filter('supawp_delete_image_from_supabase', array(__CLASS__, 'handle_image_deletion'), 10, 2);
        add_filter('supawp_get_storage_config', array(__CLASS__, 'handle_storage_config'), 10, 0);
    }
    
    public static function handle_image_upload($file, $fileName, $bucket) {
        // Your custom upload implementation
        error_log('SupaWP Storage Extension: Handling image upload for ' . $fileName);
        
        // Example: Custom processing or validation
        if ($this->should_use_custom_upload($file, $bucket)) {
            return $this->custom_upload($file, $fileName, $bucket);
        }
        
        // Return empty string to let SupaWP handle it with default logic
        return '';
    }
    
    public static function handle_image_deletion($image_url, $bucket) {
        // Your custom deletion implementation
        error_log('SupaWP Storage Extension: Handling image deletion for ' . $image_url);
        
        // Example: Custom processing or logging
        if ($this->should_use_custom_deletion($image_url, $bucket)) {
            return $this->custom_delete($image_url, $bucket);
        }
        
        // Return false to let SupaWP handle it with default logic
        return false;
    }
    
    public static function handle_storage_config() {
        // Your custom configuration
        error_log('SupaWP Storage Extension: Providing custom storage configuration');
        
        // Example: Custom storage configuration
        $custom_config = $this->get_custom_storage_config();
        
        if (!empty($custom_config)) {
            return $custom_config;
        }
        
        // Return empty array to let SupaWP use default configuration
        return array();
    }
    
    private static function should_use_custom_upload($file, $bucket) {
        // Your logic to determine if custom upload should be used
        return false; // Example: always use default
    }
    
    private static function should_use_custom_deletion($image_url, $bucket) {
        // Your logic to determine if custom deletion should be used
        return false; // Example: always use default
    }
    
    private static function custom_upload($file, $fileName, $bucket) {
        // Your custom upload implementation
        // Return public URL on success, empty string on failure
        return '';
    }
    
    private static function custom_delete($image_url, $bucket) {
        // Your custom deletion implementation
        // Return true on success, false on failure
        return false;
    }
    
    private static function get_custom_storage_config() {
        // Your custom storage configuration
        // Return array with 'url' and 'auth_key', or empty array
        return array();
    }
}

// Initialize the extension
add_action('plugins_loaded', array('SupaWP_Storage_Extension', 'init'));
```

## How the Filter Hooks Work

### **Primary Source of Truth**
The SupaWP storage filter hooks are now the **primary source of truth** for storage operations. They are not checking for existing results from other extensions.

### **Extension Override Pattern**
1. **Extension registers filter**: `add_filter('supawp_upload_image_to_supabase', 'your_function', 10, 3)`
2. **Extension function called**: Your function receives the parameters directly
3. **Extension decides**: Return custom result OR return default value to let SupaWP handle it
4. **SupaWP handles**: If extension returns default value, SupaWP uses its built-in logic

### **Default Values for Fallback**
- **Upload**: Return `''` (empty string) to use SupaWP's default upload logic
- **Delete**: Return `false` to use SupaWP's default deletion logic  
- **Config**: Return `array()` (empty array) to use SupaWP's default configuration

## Best Practices

1. **Check if you want to handle the operation** before implementing custom logic
2. **Return appropriate default values** when you don't want to handle the operation
3. **Implement proper error handling** in your custom functions
4. **Log operations** for debugging and monitoring
5. **Test thoroughly** to ensure your custom logic works correctly

## Testing Your Implementation

1. **Enable WordPress debug logging** to see your extension's log messages
2. **Test with various file types and sizes** to ensure robust handling
3. **Verify fallback behavior** when your extension returns default values
4. **Check error handling** with invalid files or network issues

## Troubleshooting

- **"Function not found" errors**: Ensure your filter functions have the correct parameter count
- **Unexpected behavior**: Check that you're returning appropriate default values
- **Performance issues**: Avoid heavy processing in filter functions unless necessary
