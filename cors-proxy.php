<?php
// cors-proxy.php
// Simple, safe proxy for a single remote JSON used during development.
// Returns JSON with CORS headers and handles remote errors gracefully.

// Remote file to fetch (keep hard-coded to avoid open proxy risk)
$remote = 'https://invitali.com/wp-content/uploads/2024/04/Animation-1714243899557-1.json';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Respond to preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

if (!function_exists('curl_version')) {
	// Fallback to file_get_contents but suppress warnings
	$context = stream_context_create(['http' => ['timeout' => 10]]);
	$response = @file_get_contents($remote, false, $context);
	if ($response === false) {
		http_response_code(502);
		header('Content-Type: application/json');
		echo json_encode(['error' => 'Failed to fetch remote resource', 'remote' => $remote]);
		exit;
	}
	header('Content-Type: application/json');
	echo $response;
	exit;
}

$ch = curl_init($remote);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'application/json';
$curl_err = curl_error($ch);
curl_close($ch);

if ($response === false || $http_code >= 400) {
	// Try local fallback file (place the JSON here if remote is unavailable)
	$local_path = __DIR__ . '/Animation-1714243899557-1.json';
	if (file_exists($local_path)) {
		header('Content-Type: application/json');
		http_response_code(200);
		readfile($local_path);
		exit;
	}

	http_response_code(502);
	header('Content-Type: application/json');
	echo json_encode([
		'error' => 'Failed to fetch remote resource',
		'remote' => $remote,
		'http_code' => $http_code,
		'curl_error' => $curl_err,
	]);
	exit;
}

header('Content-Type: ' . $content_type);
echo $response;