<?php
// Check if GD extension is loaded
if (extension_loaded('gd')) {
    echo "<h2 style='color: green;'>✓ GD Extension is LOADED</h2>";
    echo "<h3>GD Information:</h3>";
    echo "<pre>";
    print_r(gd_info());
    echo "</pre>";
} else {
    echo "<h2 style='color: red;'>✗ GD Extension is NOT LOADED</h2>";
    echo "<p>Please enable the GD extension in your php.ini file and restart Apache.</p>";
}

echo "<hr>";
echo "<h3>All Loaded Extensions:</h3>";
echo "<pre>";
print_r(get_loaded_extensions());
echo "</pre>";

echo "<hr>";
echo "<h3>PHP Info:</h3>";
phpinfo();
?>