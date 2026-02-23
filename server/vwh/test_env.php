<?php
$env_path = dirname(__DIR__, 2) . '/.env';
echo "Path 2: " . $env_path . " - Exists: " . (file_exists($env_path) ? 'Yes' : 'No') . "\n";

$env_path3 = dirname(__DIR__, 3) . '/.env';
echo "Path 3: " . $env_path3 . " - Exists: " . (file_exists($env_path3) ? 'Yes' : 'No') . "\n";

$env_content = file_get_contents($env_path3);
if ($env_content) {
    if (preg_match('/^CANDIDATE_GOOGLE_CLIENT_ID=[\'"]?([^\'"\r\n]+)[\'"]?/m', $env_content, $matches)) {
        echo "Regex Matched! Client ID: " . $matches[1] . "\n";
    } else {
        echo "Regex Failed to match.\n";
    }
}
