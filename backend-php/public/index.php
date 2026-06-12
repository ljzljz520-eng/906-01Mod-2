<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../controllers/ApiController.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$api = new ApiController();

// Simple Router
if ($uri === '/health' && $method === 'GET') {
    $api->healthCheck();
} 
elseif ($uri === '/api/providers' && $method === 'GET') {
    $api->getProviders();
}
elseif (preg_match('#^/api/search/([^/]+)/([^/]+)(?:/([^/]+))?$#', $uri, $matches) && $method === 'GET') {
    $keyword = urldecode($matches[1]);
    $query = urldecode($matches[2]);
    $page = isset($matches[3]) ? $matches[3] : 1;
    $api->search($keyword, $query, $page);
}
elseif ($uri === '/api/history' && $method === 'GET') {
    $api->getHistory();
}
elseif ($uri === '/api/history' && $method === 'DELETE') {
    $api->clearHistory();
}
elseif ($uri === '/api/favorites' && $method === 'GET') {
    $api->getFavorites();
}
elseif ($uri === '/api/favorites' && $method === 'POST') {
    $api->addFavorite();
}
elseif (preg_match('#^/api/favorites/(\d+)$#', $uri, $matches) && $method === 'DELETE') {
    $id = $matches[1];
    $api->deleteFavorite($id);
}
else {
    http_response_code(404);
    echo json_encode(["message" => "Endpoint not found", "uri" => $uri]);
}
