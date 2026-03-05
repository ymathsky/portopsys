<?php
echo '<h1 style="color:green;font-family:sans-serif;">&#x2705; Deployment successful!</h1>';
echo '<p style="font-family:sans-serif;">Server time: <strong>' . date('Y-m-d H:i:s') . '</strong></p>';
echo '<p style="font-family:sans-serif;">File path: <strong>' . __FILE__ . '</strong></p>';
echo '<p style="font-family:sans-serif;">URL: <strong>' . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '</strong></p>';
