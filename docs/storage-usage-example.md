# SupaWP Storage Usage Example

This document shows a practical example of how to use the new storage filter hooks in your WordPress plugin or theme.

## Basic Usage

### 1. Upload an Image

```php
// Example: Upload a user profile image
function upload_user_profile_image($user_id, $file) {
    // Generate filename with user folder structure
    $timestamp = time();
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = "{$user_id}/{$timestamp}_profile.{$extension}";
    
    // Use the SupaWP storage filter hook
    $image_url = apply_filters('supawp_upload_image_to_supabase', $file, $fileName, 'user-images');
    
    if (!empty($image_url)) {
        // Success - save URL to user meta
        update_user_meta($user_id, 'profile_image_url', $image_url);
        echo "Profile image uploaded successfully: " . $image_url;
    } else {
        // Handle upload failure
        echo "Failed to upload profile image";
    }
}

// Usage
if (isset($_FILES['profile_image'])) {
    upload_user_profile_image(get_current_user_id(), $_FILES['profile_image']);
}
```

### 2. Delete an Image

```php
// Example: Delete a user profile image
function delete_user_profile_image($user_id) {
    // Get current profile image URL
    $image_url = get_user_meta($user_id, 'profile_image_url', true);
    
    if (!empty($image_url)) {
        // Use the SupaWP storage filter hook
        $success = apply_filters('supawp_delete_image_from_supabase', $image_url, 'user-images');
        
        if ($success) {
            // Success - remove from user meta
            delete_user_meta($user_id, 'profile_image_url');
            echo "Profile image deleted successfully";
        } else {
            echo "Failed to delete profile image";
        }
    }
}
```

### 3. Get Storage Configuration

```php
// Example: Check if storage is available
function check_storage_availability() {
    $config = apply_filters('supawp_get_storage_config');
    
    if (!empty($config)) {
        echo "Storage is available:<br>";
        echo "URL: " . $config['url'] . "<br>";
        echo "Auth Key: " . substr($config['auth_key'], 0, 10) . "...<br>";
        return true;
    } else {
        echo "Storage is not available. Check SupaWP configuration.";
        return false;
    }
}
```

## Advanced Usage with Custom Buckets

### Custom Bucket Implementation

```php
// Example: Handle multiple image types with different buckets
function handle_product_images($product_id, $files) {
    $results = array();
    
    // Main product image
    if (isset($files['main_image'])) {
        $timestamp = time();
        $extension = strtolower(pathinfo($files['main_image']['name'], PATHINFO_EXTENSION));
        $fileName = "products/{$product_id}/{$timestamp}_main.{$extension}";
        
        $main_image_url = apply_filters('supawp_upload_image_to_supabase', $files['main_image'], $fileName, 'product-images');
        
        if (!empty($main_image_url)) {
            $results['main_image'] = $main_image_url;
        }
    }
    
    // Additional product images
    if (isset($files['additional_images'])) {
        $additional_urls = array();
        
        foreach ($files['additional_images']['name'] as $index => $name) {
            if (!empty($name)) {
                $timestamp = time() + $index; // Ensure unique timestamps
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $fileName = "products/{$product_id}/{$timestamp}_additional_{$index}.{$extension}";
                
                // Create file array for this specific file
                $file = array(
                    'name' => $name,
                    'type' => $files['additional_images']['type'][$index],
                    'tmp_name' => $files['additional_images']['tmp_name'][$index],
                    'error' => $files['additional_images']['error'][$index],
                    'size' => $files['additional_images']['size'][$index]
                );
                
                $image_url = apply_filters('supawp_upload_image_to_supabase', $file, $fileName, 'product-images');
                
                if (!empty($image_url)) {
                    $additional_urls[] = $image_url;
                }
            }
        }
        
        if (!empty($additional_urls)) {
            $results['additional_images'] = $additional_urls;
        }
    }
    
    return $results;
}
```

## Error Handling

### Comprehensive Error Handling Example

```php
function safe_image_upload($file, $fileName, $bucket) {
    try {
        // Validate file before upload
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Invalid file upload');
        }
        
        // Check file size
        if ($file['size'] > 5242880) { // 5MB
            throw new Exception('File size exceeds 5MB limit');
        }
        
        // Attempt upload using SupaWP
        $image_url = apply_filters('supawp_upload_image_to_supabase', $file, $fileName, $bucket);
        
        if (empty($image_url)) {
            throw new Exception('Upload failed - no URL returned');
        }
        
        return array(
            'success' => true,
            'url' => $image_url,
            'message' => 'Image uploaded successfully'
        );
        
    } catch (Exception $e) {
        error_log('SupaWP Storage Error: ' . $e->getMessage());
        
        return array(
            'success' => false,
            'url' => '',
            'message' => 'Upload failed: ' . $e->getMessage()
        );
    }
}

// Usage with error handling
$result = safe_image_upload($_FILES['image'], 'test.jpg', 'images');

if ($result['success']) {
    echo "Success: " . $result['message'];
    echo "URL: " . $result['url'];
} else {
    echo "Error: " . $result['message'];
}
```

## Integration with WordPress Forms

### Contact Form 7 Integration Example

```php
// Hook into Contact Form 7 submission
add_action('wpcf7_mail_sent', 'handle_cf7_image_upload');

function handle_cf7_image_upload($contact_form) {
    $submission = WPCF7_Submission::get_instance();
    
    if ($submission) {
        $data = $submission->get_posted_data();
        $files = $submission->uploaded_files();
        
        // Handle image upload if present
        if (isset($files['profile_image']) && !empty($files['profile_image'])) {
            $file = $files['profile_image'];
            $user_id = get_current_user_id();
            
            if ($user_id) {
                $timestamp = time();
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $fileName = "users/{$user_id}/{$timestamp}_profile.{$extension}";
                
                $image_url = apply_filters('supawp_upload_image_to_supabase', $file, $fileName, 'user-uploads');
                
                if (!empty($image_url)) {
                    // Save to user meta
                    update_user_meta($user_id, 'profile_image_url', $image_url);
                    
                    // Log success
                    error_log("Profile image uploaded via CF7: {$image_url}");
                }
            }
        }
    }
}
```

## Testing Your Implementation

### 1. Enable Debug Logging

Add to your `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### 2. Test Upload

```php
// Test upload functionality
function test_storage_upload() {
    // Create a test file
    $test_file = array(
        'name' => 'test.jpg',
        'type' => 'image/jpeg',
        'tmp_name' => '/tmp/test.jpg',
        'error' => 0,
        'size' => 1024
    );
    
    $fileName = 'test/' . time() . '_test.jpg';
    
    $result = apply_filters('supawp_upload_image_to_supabase', $test_file, $fileName, 'test-bucket');
    
    if (!empty($result)) {
        echo "Test upload successful: " . $result;
    } else {
        echo "Test upload failed";
    }
}
```

### 3. Check Error Logs

Monitor your WordPress debug log for any storage-related errors:
```bash
tail -f wp-content/debug.log | grep "SupaWP"
```

## Best Practices

1. **Always validate files** before uploading
2. **Use meaningful file paths** (e.g., `users/{user_id}/images/`)
3. **Handle errors gracefully** and provide user feedback
4. **Log operations** for debugging and monitoring
5. **Use appropriate bucket names** for different content types
6. **Implement cleanup** for failed uploads
7. **Test thoroughly** in development before production use

## Troubleshooting

- **"Storage configuration not available"**: Check SupaWP settings for service role key
- **Upload failures**: Verify bucket exists and RLS policies are correct
- **Permission errors**: Ensure authentication keys are properly configured
- **File size issues**: Check file size limits in your PHP configuration
