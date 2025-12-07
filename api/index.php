<?php
if($_SERVER['SERVER_NAME'] != 'localhost')
{
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // CORS headers
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}
// echo 1;exit;

require 'flight/Flight.php';
require 'api.php';
// echo 1;exit;
Flight::route('GET /list', function() {
    $status = EmployeeService::list(); // Optional: pass $_GET or body
    Flight::json([
        'ok' => true,
        'data' => $status
    ]);
});
Flight::route('POST /create', function () {

    $contentType = Flight::request()->headers['Content-Type'] ?? '';

    // JSON (no image)
    if (stripos($contentType, 'application/json') !== false) {
        
        $post  = json_decode(Flight::request()->getBody(), true) ?? [];
        $files = [];
    } else {
       
        // multipart/form-data (with image)
        $post  = $_POST;
        $files = $_FILES;
    }
    // echo json_encode($_POST);exit;
    // echo json_encode($_FILES);exit;
    $status = EmployeeService::create($post, $files);

    Flight::json([
        'ok' => true,
        'id' => $status
    ]);
});

Flight::route('POST /update', function () {

    $contentType = Flight::request()->headers['Content-Type'] ?? '';
    // echo json_encode($contentType);exit;
    if (stripos($contentType, 'application/json') !== false) {
        $post  = json_decode(Flight::request()->getBody(), true) ?? [];
        $files = [];
    } else {
        $post  = $_POST;
        $files = $_FILES;
    }

    $status = EmployeeService::update($post, $files);

    Flight::json([
        'ok' => true,
        'updated' => $status
    ]);
});

Flight::route('DELETE /delete', function () {

    // Delete never has files
    $post = json_decode(Flight::request()->getBody(), true) ?? [];

    $status = EmployeeService::delete($post);

    Flight::json([
        'ok' => true,
        'deleted' => $status
    ]);
});

Flight::route('GET /employeePdf', function () {

    $id = (int)(Flight::request()->query['id'] ?? 0);

    if ($id <= 0) {
        Flight::halt(400, 'Invalid employee id');
    }

    EmployeeService::employeePdf($id);
});


Flight::start();
?>


