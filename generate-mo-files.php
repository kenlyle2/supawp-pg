<?php
/**
 * Simple MO file generator for SupaWP translations
 *
 * This script converts .po files to .mo files for WordPress translation support
 * Run with: php generate-mo-files.php
 */

function generateMoFile($poFile, $moFile) {
    if (!file_exists($poFile)) {
        echo "PO file not found: $poFile\n";
        return false;
    }

    // Simple PO to MO conversion
    $po_content = file_get_contents($poFile);
    $entries = array();

    // Parse PO file for msgid and msgstr pairs
    preg_match_all('/msgid "([^"]*)"\s*msgstr "([^"]*)"/', $po_content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $msgid = stripcslashes($match[1]);
        $msgstr = stripcslashes($match[2]);

        if (!empty($msgid) && !empty($msgstr)) {
            $entries[$msgid] = $msgstr;
        }
    }

    if (empty($entries)) {
        echo "No translations found in: $poFile\n";
        return false;
    }

    // Generate MO file content
    $mo_content = generateMoContent($entries);

    if (file_put_contents($moFile, $mo_content) !== false) {
        echo "Generated: $moFile (" . count($entries) . " translations)\n";
        return true;
    } else {
        echo "Failed to write: $moFile\n";
        return false;
    }
}

function generateMoContent($entries) {
    $keys = array_keys($entries);
    $values = array_values($entries);

    // MO file header
    $key_offsets = array();
    $value_offsets = array();
    $key_table = '';
    $value_table = '';

    $offset = 28 + (count($entries) * 16); // Header size + key/value tables

    // Build key and value tables
    foreach ($keys as $i => $key) {
        $key_offsets[] = pack('V', strlen($key)) . pack('V', $offset);
        $key_table .= $key . "\0";
        $offset += strlen($key) + 1;
    }

    foreach ($values as $i => $value) {
        $value_offsets[] = pack('V', strlen($value)) . pack('V', $offset);
        $value_table .= $value . "\0";
        $offset += strlen($value) + 1;
    }

    // MO file structure
    $mo_header = pack('V', 0x950412de); // Magic number
    $mo_header .= pack('V', 0); // File format revision
    $mo_header .= pack('V', count($entries)); // Number of strings
    $mo_header .= pack('V', 28); // Offset of key table
    $mo_header .= pack('V', 28 + (count($entries) * 8)); // Offset of value table
    $mo_header .= pack('V', 0); // Hash table size
    $mo_header .= pack('V', 0); // Hash table offset

    return $mo_header . implode('', $key_offsets) . implode('', $value_offsets) . $key_table . $value_table;
}

// Main execution
$languages_dir = __DIR__ . '/languages/';

if (!is_dir($languages_dir)) {
    echo "Languages directory not found: $languages_dir\n";
    exit(1);
}

echo "Generating MO files for SupaWP translations...\n\n";

$po_files = glob($languages_dir . 'supawp-*.po');

if (empty($po_files)) {
    echo "No PO files found in: $languages_dir\n";
    exit(1);
}

$generated = 0;
foreach ($po_files as $po_file) {
    $mo_file = str_replace('.po', '.mo', $po_file);
    if (generateMoFile($po_file, $mo_file)) {
        $generated++;
    }
}

echo "\nGenerated $generated MO files successfully!\n";
echo "WordPress should now be able to load the translations automatically.\n";
?>