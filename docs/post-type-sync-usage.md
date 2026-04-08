# SupaWP Post Type Sync - Usage Examples

This document provides usage examples for the newly implemented post type sync feature.

## Basic Configuration

### 1. **Security Settings** (Recommended)
Go to **WordPress Admin → SupaWP → Security tab**:
- **Supabase Service Role Key**: Add your service role key for secure write operations
- This key bypasses RLS and should only be used server-side

### 2. **Sync Configuration**
Go to **WordPress Admin → SupaWP → Sync Data tab**:
- **Select Post Types**: Check the post types you want to sync (Posts, Pages)
- **Custom Post Types**: Enter custom post types separated by commas (e.g., `book, product, event`)
- **Save Settings**: Click "Save Settings"

### 🔒 **Security Note**
For production use, **always configure the Service Role Key** for secure post syncing. The RLS policies in this guide ensure that:
- ✅ **Public can read** published content via anonymous key
- ❌ **Public cannot write** - only WordPress backend can modify data
- ✅ **WordPress has full control** via service role key

## Supabase Table Setup

### For built-in post types

#### Posts Table (wp_posts)
```sql
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

-- Enable Row Level Security
ALTER TABLE public.wp_posts ENABLE ROW LEVEL SECURITY;

-- Allow public read access (anonymous users can read published posts)
CREATE POLICY "Allow public read access"
ON public.wp_posts FOR SELECT
TO anon
USING (post_status = 'publish');

-- Allow authenticated users to read all posts
CREATE POLICY "Allow authenticated read access"
ON public.wp_posts FOR SELECT
TO authenticated
USING (true);

-- Only service role can insert/update/delete (WordPress backend only)
CREATE POLICY "Service role can manage posts"
ON public.wp_posts FOR ALL
TO service_role
USING (true) WITH CHECK (true);

-- Deny all write operations for anonymous and authenticated users
CREATE POLICY "Deny public writes"
ON public.wp_posts FOR INSERT
TO anon, authenticated
WITH CHECK (false);

CREATE POLICY "Deny public updates"
ON public.wp_posts FOR UPDATE
TO anon, authenticated
USING (false);

CREATE POLICY "Deny public deletes"
ON public.wp_posts FOR DELETE
TO anon, authenticated
USING (false);
```

#### Pages Table (wp_pages)
```sql
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

-- Enable Row Level Security
ALTER TABLE public.wp_pages ENABLE ROW LEVEL SECURITY;

-- Allow public read access (anonymous users can read published pages)
CREATE POLICY "Allow public read access"
ON public.wp_pages FOR SELECT
TO anon
USING (post_status = 'publish');

-- Allow authenticated users to read all pages
CREATE POLICY "Allow authenticated read access"
ON public.wp_pages FOR SELECT
TO authenticated
USING (true);

-- Only service role can insert/update/delete (WordPress backend only)
CREATE POLICY "Service role can manage pages"
ON public.wp_pages FOR ALL
TO service_role
USING (true) WITH CHECK (true);

-- Deny all write operations for anonymous and authenticated users
CREATE POLICY "Deny public writes"
ON public.wp_pages FOR INSERT
TO anon, authenticated
WITH CHECK (false);

CREATE POLICY "Deny public updates"
ON public.wp_pages FOR UPDATE
TO anon, authenticated
USING (false);

CREATE POLICY "Deny public deletes"
ON public.wp_pages FOR DELETE
TO anon, authenticated
USING (false);
```

### For custom post types

If you have a custom post type `book`, create table `wp_books`:

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

-- Enable Row Level Security
ALTER TABLE public.wp_books ENABLE ROW LEVEL SECURITY;

-- Allow public read access (anonymous users can read published books)
CREATE POLICY "Allow public read access"
ON public.wp_books FOR SELECT
TO anon
USING (post_status = 'publish');

-- Allow authenticated users to read all books
CREATE POLICY "Allow authenticated read access"
ON public.wp_books FOR SELECT
TO authenticated
USING (true);

-- Only service role can insert/update/delete (WordPress backend only)
CREATE POLICY "Service role can manage books"
ON public.wp_books FOR ALL
TO service_role
USING (true) WITH CHECK (true);

-- Deny all write operations for anonymous and authenticated users
CREATE POLICY "Deny public writes"
ON public.wp_books FOR INSERT
TO anon, authenticated
WITH CHECK (false);

CREATE POLICY "Deny public updates"
ON public.wp_books FOR UPDATE
TO anon, authenticated
USING (false);

CREATE POLICY "Deny public deletes"
ON public.wp_books FOR DELETE
TO anon, authenticated
USING (false);
```

## 🔒 Security Model Explanation

The RLS (Row Level Security) policies above implement a secure model where:

### **Read Access (Public)**
- ✅ **Anonymous users** can read **published posts only** (`post_status = 'publish'`)
- ✅ **Authenticated users** can read **all posts** (including drafts, private)
- ✅ **Service role** has **full read access**

### **Write Access (WordPress Only)**  
- ❌ **Anonymous users** cannot write/update/delete
- ❌ **Authenticated users** cannot write/update/delete  
- ✅ **Service role only** can insert/update/delete (WordPress backend)

### **Benefits:**
1. **Public API** - Frontend apps can read published content
2. **Content Security** - No unauthorized modifications
3. **WordPress Control** - Only WordPress can manage content
4. **Draft Privacy** - Unpublished content hidden from public

### **Example API Usage:**

```javascript
// ✅ Public read access (works with anon key)
const { data: publishedPosts } = await supabase
  .from('wp_posts')
  .select('*')
  .eq('post_status', 'publish');

// ❌ Public write access (fails with anon key) 
const { error } = await supabase
  .from('wp_posts')
  .insert({ post_title: 'Hacked!' }); // Returns permission error

// ✅ WordPress write access (works with service role key from backend)
// This happens automatically when you save posts in WordPress
```

## Usage Examples

### Using Filter Hooks

#### Get data from Supabase
```php
// Get all published posts
$posts = apply_filters('supawp_get_data_from_supabase', 'wp_posts', array('post_status' => 'eq.publish'));

// Get posts with limit
$recent_posts = apply_filters('supawp_get_data_from_supabase', 'wp_posts', array(
  'post_status' => 'eq.publish',
  'limit' => '10',
  'order' => 'post_date.desc'
));
```

#### Save custom data to Supabase
```php
// Manually save post data
$post_data = array(
  'id' => 123,
  'post_title' => 'My Custom Post',
  'post_content' => 'Content here',
  'post_type' => 'post',
  'post_status' => 'publish'
);

// Option 1: Let the filter generate table name from post_type
apply_filters('supawp_save_data_to_supabase', $post_data);

// Option 2: Specify the table name explicitly
apply_filters('supawp_save_data_to_supabase', $post_data, 'wp_posts');
```

#### Delete data from Supabase
```php
// Delete a specific post
apply_filters('supawp_delete_data_from_supabase', 'wp_posts', 123);
```

### Custom Integration Example

```php
// In your theme's functions.php or custom plugin
function my_custom_post_sync($post_id) {
  $post = get_post($post_id);
  
  // Only sync specific post types
  if (!in_array($post->post_type, ['post', 'page', 'custom_book'])) {
    return;
  }
  
  // Prepare custom data
  $custom_data = array(
    'id' => $post_id,
    'post_title' => $post->post_title,
    'post_content' => $post->post_content,
    'post_type' => $post->post_type,
    'custom_field' => get_post_meta($post_id, 'my_custom_field', true),
    'special_data' => 'Additional information'
  );
  
  // Use SupaWP filter to save (table name will be generated from post_type)
  apply_filters('supawp_save_data_to_supabase', $custom_data);
  
  // Or specify table name explicitly for custom tables
  // apply_filters('supawp_save_data_to_supabase', $custom_data, 'my_custom_table');
}

// Hook into WordPress save_post action
add_action('save_post', 'my_custom_post_sync');
```

### Advanced Table Name Usage

```php
// Example 1: Save to custom table with explicit table name
$user_data = array(
  'id' => 123,
  'name' => 'John Doe',
  'email' => 'john@example.com',
  'created_at' => date('Y-m-d H:i:s')
);

// Save to a custom user profiles table
apply_filters('supawp_save_data_to_supabase', $user_data, 'user_profiles');

// Example 2: Save data without post_type (requires explicit table name)
$analytics_data = array(
  'page_views' => 1500,
  'unique_visitors' => 350,
  'date' => date('Y-m-d')
);

// Must specify table name since there's no post_type to generate from
apply_filters('supawp_save_data_to_supabase', $analytics_data, 'analytics_daily');

// Example 3: Using table name generator utility
$post_type = 'custom_book';
$table_name = SupaWP_Utils::table_name_generator($post_type); // Returns 'wp_custom_books'

$book_data = array(
  'id' => 456,
  'title' => 'My Book Title',
  'author' => 'Author Name',
  'post_type' => $post_type
);

// Both approaches work the same:
apply_filters('supawp_save_data_to_supabase', $book_data); // Uses post_type to generate table name
apply_filters('supawp_save_data_to_supabase', $book_data, $table_name); // Uses explicit table name
```

### Modify Data Before Saving

```php
// Add custom fields or modify data before saving to Supabase
add_filter('supawp_before_saving_to_supabase', function($data) {
  // Add site information
  $data['site_url'] = get_site_url();
  
  // Add custom timestamp
  $data['custom_timestamp'] = date('Y-m-d H:i:s');
  
  // Modify content if needed
  if (isset($data['post_content'])) {
    $data['post_content'] = wp_strip_all_tags($data['post_content']);
  }
  
  return $data;
});
```

### Hook into Sync Events

```php
// After data is saved to Supabase
add_action('supawp_after_saving_to_supabase', function($response_data, $original_data) {
  error_log('Post synced to Supabase: ' . $original_data['post_title']);
  
  // Send notification, update cache, etc.
}, 10, 2);

// After data is deleted from Supabase
add_action('supawp_after_deleting_from_supabase', function($post_id, $table_name) {
  error_log("Post {$post_id} deleted from {$table_name}");
}, 10, 2);
```

## Available Filter Hooks

### `supawp_get_data_from_supabase`
- **Purpose**: Get data from Supabase
- **Parameters**: `$table_name`, `$filters`
- **Returns**: Array of data or false on error

### `supawp_save_data_to_supabase`
- **Purpose**: Save data to Supabase
- **Parameters**: `$data`, `$table_name` (optional - if not provided, will be generated from post_type)
- **Returns**: Response data or false on error

### `supawp_delete_data_from_supabase`
- **Purpose**: Delete data from Supabase
- **Parameters**: `$table_name`, `$post_id`
- **Returns**: Boolean success status

### `supawp_before_saving_to_supabase`
- **Purpose**: Modify data before saving
- **Parameters**: `$data`
- **Returns**: Modified data array

## Available Action Hooks

### `supawp_after_saving_to_supabase`
- **Purpose**: Triggered after successful save
- **Parameters**: `$response_data`, `$original_data`

### `supawp_after_deleting_from_supabase`
- **Purpose**: Triggered after successful delete
- **Parameters**: `$post_id`, `$table_name`

## Automatic Sync Behavior

Once configured, the following WordPress actions will automatically trigger syncing:

1. **Creating a new post** - Automatically synced to Supabase
2. **Updating an existing post** - Updates the record in Supabase
3. **Deleting/trashing a post** - Removes the record from Supabase

## Table Naming Convention

The plugin uses the following naming convention for Supabase tables:

- `post` → `wp_posts`
- `page` → `wp_pages` 
- `custom_book` → `wp_custom_books`
- `product` → `wp_product`

Table names are generated using `SupaWP_Utils::table_name_generator()` which converts post type names to snake_case with a `wp_` prefix.

## Troubleshooting

### Common Issues

1. **Posts not syncing**: Check that the post type is selected in SupaWP settings
2. **Permission denied errors**: Ensure Service Role Key is configured (not just anon key)
3. **Supabase connection errors**: Verify Supabase URL and service role key in settings
4. **Table not found**: Ensure the table exists in Supabase with correct naming
5. **RLS policy errors**: Verify RLS policies allow service_role to insert/update/delete
6. **Read access issues**: Check that RLS policies allow anon/authenticated to SELECT

### Debug Logging

Enable WordPress debug logging to see sync errors:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Then check `/wp-content/debug.log` for SupaWP error messages.