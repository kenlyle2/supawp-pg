<?php
// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Delete plugin options
delete_option('supawp_options');

// Clear any cached data that's been stored
wp_cache_flush();
