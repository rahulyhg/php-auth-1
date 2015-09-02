<?php

//php-auth v0.1.0

require './libs/Slim/Slim.php';
require_once 'dbHelper.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app = \Slim\Slim::getInstance();
$db = new dbHelper();

// Register
$app->post('/Mobile/v1_0/Register', function() use ($app) { 
    $data = json_decode($app->request->getBody());
    require_once 'passwordHash.php';
    $mandatory = array('username');
    $mandatory = array('password');
    $mandatory = array('fullname');
    $mandatory = array('email');
    
    $password = $data->password;
    $data->password = passwordHash::hash($password);
    
    global $db;
    $rows = $db->insert("users", $data, $mandatory);
    if($rows["status"]=="success"){
        $rows["message"] = "User added successfully.";
        $app->setCookie('AspNet.ApplicationCookie', sha1('cookie'));
        echoResponse(200, $rows);
    }
    else {
        echoResponse(400, $rows);
    }
    
});

function echoResponse($status_code, $response) {
    global $app;
    $app->status($status_code);
    $app->contentType('application/json');
    echo json_encode($response,JSON_NUMERIC_CHECK);
}

$app->run();

?>