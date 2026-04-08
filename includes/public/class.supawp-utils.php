<?php
if (!defined('ABSPATH')) {
  exit;
}

/**
 * SupaWP Utilities Class
 * Helper functions for SupaWP plugin
 */
class SupaWP_Utils {

  /**
   * Generate Supabase table name from post type in snake_case format
   * Examples: 'post' -> 'wp_posts', 'custom_book' -> 'wp_custom_books'
   *
   * @param string $post_type WordPress post type
   * @return string Supabase table name in snake_case
   */
  public static function table_name_generator($post_type) {
    $type_object = get_post_type_object($post_type);
    $plural_name = isset($type_object->labels) ? $type_object->labels->name : $post_type;
    
    // First sanitize to ASCII to handle special characters and accents
    $sanitized_name = self::sanitize_ascii($plural_name);
    
    // Convert to lowercase and replace non-alphanumeric chars with underscores
    $table_name = strtolower($sanitized_name);
    $table_name = preg_replace('/[^a-z0-9]+/', '_', $table_name);
    
    // Remove leading/trailing underscores
    $table_name = trim($table_name, '_');
    
    // Ensure we have a valid table name (fallback to post_type if empty)
    if (empty($table_name)) {
      $table_name = preg_replace('/[^a-z0-9]+/', '_', strtolower($post_type));
      $table_name = trim($table_name, '_');
    }
    
    // Final fallback if still empty
    if (empty($table_name)) {
      $table_name = 'unknown';
    }
    
    // Add wp_ prefix
    $table_name = 'wp_' . $table_name;
    
    return $table_name;
  }

  /**
   * Sanitize a string to ASCII, replacing special characters with their closest equivalents.
   * Uses iconv if available, otherwise falls back to replace_accents.
   * 
   * @param string $string Input string
   * @return string Sanitized ASCII string
   */
  public static function sanitize_ascii($string) {
    // Use iconv if available
    if (function_exists('iconv')) {
      $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    } else {
      $string = self::replace_accents($string);
    }
    // Remove any remaining non-ASCII characters
    $string = preg_replace('/[^A-Za-z0-9 ]/', '', $string);
    return $string;
  }

  /**
   * Replace accented characters with their ASCII equivalents
   * 
   * @param string $str Input string with accents
   * @return string String with accents replaced
   */
  private static function replace_accents($str) {
    $unwanted_array = array(
      'Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z',
      'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A',
      'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ễ' => 'E', 'ễ' => 'e', 'Ê' => 'E', 'Ë' => 'E',
      'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N',
      'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
      'Ù' => 'U', 'Ũ' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y',
      'Þ' => 'B', 'ß' => 'Ss',
      'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a',
      'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
      'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n',
      'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
      'ù' => 'u', 'ũ' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y'
    );
    return strtr($str, $unwanted_array);
  }

  /**
   * Generate a nice username from email or display name
   * Similar to Firebase PRO's generate_nice_username
   * 
   * @param array $user_data User data array
   * @return string Generated username
   */
  public static function generate_nice_username($user_data) {
    $nice_username = '';

    if (isset($user_data['email'])) {
      $nice_username = preg_replace('/@.*?$/', '', $user_data['email']);
    }

    if (isset($user_data['displayName']) && empty($nice_username)) {
      $nice_username = self::sanitize_ascii(str_replace(' ', '_', $user_data['displayName']));
    }

    // Replace special characters
    $nice_username = str_replace('+', '_', $nice_username);

    return $nice_username;
  }
}