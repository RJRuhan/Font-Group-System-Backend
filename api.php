<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'vendor/autoload.php';
require_once 'config/database.php';
require_once 'controllers/FontController.php';
require_once 'controllers/FontGroupController.php';

use Phroute\Phroute\RouteCollector;
use Phroute\Phroute\Dispatcher;

// Set headers for CORS and JSON response
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize router
$router = new RouteCollector();
$database = Database::getInstance()->getConnection();
$fontController = new FontController($database);
$fontGroupController = new FontGroupController($database);

// Define Routes

error_log("{$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}");

// Font API
$router->post('/fonts/upload', [$fontController, 'upload']);
$router->get('/fonts', [$fontController, 'getAllFonts']);
$router->delete('/fonts/{fontName}', [$fontController, 'deleteFont']);

// Font Group API
$router->post('/fontgroups', [$fontGroupController, 'createGroup']);
$router->get('/fontgroups', [$fontGroupController, 'getAllGroups']);
$router->delete('/fontgroups/{groupName}', [$fontGroupController, 'deleteGroup']);
$router->put('/fontgroups/{groupName}', [$fontGroupController, 'updateGroup']);

// Dispatch request
$dispatcher = new Dispatcher($router->getData());

try {
    $response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    echo json_encode($response);
} catch (Phroute\Phroute\Exception\HttpRouteNotFoundException $e) {
    http_response_code(404);
    echo json_encode(['status' => false, 'message' => 'Endpoint not found']);
} catch (Phroute\Phroute\Exception\HttpMethodNotAllowedException $e) {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

