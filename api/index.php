<?php
require './libs/Slim/Slim.php';
require_once 'dbHelper.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app = \Slim\Slim::getInstance();
$db = new dbHelper();

// Register
$app->post('/Mobile/v1_0/Register', function() use ($app) { 
    $data = json_decode($app->request->getBody());
    $mandatory = array('username');
    $mandatory = array('password');
    $mandatory = array('fullname');
    $mandatory = array('email');
    global $db;
    $rows = $db->insert("users", $data, $mandatory);
    if($rows["status"]=="success"){
        $rows["message"] = "User added successfully.";
    }
    echoResponse(200, $rows);
});

function echoResponse($status_code, $response) {
    global $app;
    $app->status($status_code);
    $app->contentType('application/json');
    echo json_encode($response,JSON_NUMERIC_CHECK);
}

$app->run();

?>