<?php
/**
 * TTS Proxy — fetches Google Translate TTS audio server-side to avoid CORS.
 * Usage: /api/tts-proxy.php?text=Hello&lang=fil
 */
require_once __DIR__ . '/../includes/auth.php';

$text = trim($_GET['text'] ?? '');
$lang = trim($_GET['lang'] ?? 'fil');

// Whitelist allowed languages
$allowed = ['fil', 'en', 'tl'];
if (!in_array($lang, $allowed)) $lang = 'fil';

if (empty($text)) {
    http_response_code(400);
    exit('Missing text');
}

// Limit text length for safety
$text = mb_substr($text, 0, 200);

$url = 'https://translate.google.com/translate_tts?ie=UTF-8&client=tw-ob'
     . '&tl=' . urlencode($lang)
     . '&q='  . urlencode($text);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; TTS/1.0)',
    CURLOPT_SSL_VERIFYPEER => false,
]);
$audio = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($audio === false || $httpCode !== 200) {
    http_response_code(502);
    exit('TTS fetch failed');
}

header('Content-Type: audio/mpeg');
header('Cache-Control: public, max-age=3600');
header('Content-Length: ' . strlen($audio));
echo $audio;
