<?php

//php-auth v0.3.1

require './libs/Slim/Slim.php';
require_once 'dbHelper.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app = \Slim\Slim::getInstance();
$db = new dbHelper();

// Register
$app->post('/Mobile/v1_0/Register', function() use ($app) { 
    try {
        $data = json_decode($app->request->getBody());
        require_once 'passwordHash.php';
    
        $userusername = $data->UserName;
        $userpassword = $data->NewPassword;
        $userfullname = $data->FullName;
        $useremail = $data->EmailAddress;
    
        global $db;
        $usernamecheck = $db->select("users","uid",array('username'=>$userusername));
        $emailcheck = $db->select("users","uid",array('email'=>$useremail));
        
        $ir = null;
        if ($userusername == "" ||
            strlen($userusername) < 3 ||
            strlen($userusername) > 20) {
            $ir = new InvalidRequest();
            $ir->ModelState->ErrorCode = array("1");
            $ir->ModelState->ErrorMessage = array("Wrong UserName.");
        }
        else if ($useremail == "" ||
            strlen($useremail) > 254) {
            $ir = new InvalidRequest();
            $ir->ModelState->ErrorCode = array("2");
            $ir->ModelState->ErrorMessage = array("Wrong EmailAddress.");
        }
        else if ($userfullname == "" ||
            strlen($userfullname) > 100) {
            $ir = new InvalidRequest();
            $ir->ModelState->ErrorCode = array("3");
            $ir->ModelState->ErrorMessage = array("Wrong FullName.");
        }
        else if ($userpassword == "" ||
            strlen($userpassword) < 6) {
            $ir = new InvalidRequest();
            $ir->ModelState->ErrorCode = array("4");
            $ir->ModelState->ErrorMessage = array("Wrong Password.");
        }
        else if ($usernamecheck["status"]=="success") {
            $ir = new InvalidRequest();
            $ir->ModelState->ErrorCode = array("6");
            $ir->ModelState->ErrorMessage = array("UserName already exists.");
        }
        else if ($emailcheck["status"]=="success") {
            $ir = new InvalidRequest();
            $ir->ModelState->ErrorCode = array("7");
            $ir->ModelState->ErrorMessage = array("EmailAddress already exists.");
        }
        else if (strpos($userfullname, "<") != false) {
            $ir = new InvalidRequest();
            $ir->ModelState->ErrorCode = array("8");
            $ir->ModelState->ErrorMessage = array("Invalid FullName.");
        }    
        else if (strpos($userusername, "<") != false) {
            $ir = new InvalidRequest();
            $ir->ModelState->ErrorCode = array("9");
            $ir->ModelState->ErrorMessage = array("Invalid UserName.");
        }    
    
        if ($ir !== null) {
            echoResponse(400, array($ir));
        }
        else {
            $user = new user();
            $user->username = $userusername;
            $user->password = passwordHash::hash($userpassword);
            $user->fullname = $userfullname;
            $user->email = $useremail;
    
            $mandatory = array('username','password','fullname','email');
            $rows = $db->insert("users", $user, $mandatory);
            if($rows["status"]=="success"){
                $rows["message"] = "User added successfully.";
                $app->setCookie('.AspNet.ApplicationCookie', sha1('cookie'));
                echoResponse(200, $rows);
            }
            else {
                echoResponse(400, $rows);
            }
        }    
    } catch(Exception $e){
        $ir = new InvalidRequest();
        $ir->ModelState->ErrorCode = array("11");
        $ir->ModelState->ErrorMessage = array($e->getMessage());
        error_log($e->getMessage());
        echoResponse(500, array($ir));
    }
});

$app->post('/Mobile/v1_0/Login', function() use ($app) {
    try {
      require_once 'passwordHash.php';
      $data = json_decode($app->request->getBody());
      $response = array();

      $username = $data->UserName;
      $password = $data->Password;

      global $db;
      $rows = $db->select("users","uid,username,password,fullname,email",array('username'=>$username));

      if ($rows["status"]=="success") {
          if(passwordHash::check_password($rows["data"][0]["password"],$password)){
              $response['status'] = "success";
              $response['message'] = 'Login successful.';
              $app->setCookie('.AspNet.ApplicationCookie', sha1('cookie'));
              echoResponse(200, $response);
          } else {
              $response['status'] = "error";
              $response['message'] = 'Login failed. Incorrect password.';
              echoResponse(401, $response);
          }
      }else {
			//$response['status'] = "error";
			//$response['message'] = 'No such user is registered.';
			//echoResponse(401, $response);

			$user = new user();
			$user->username = $username;
			$user->password = passwordHash::hash($password);
			$user->fullname = $username;
			$user->email = $username;
    
			$mandatory = array('username','password','fullname','email');
			$rows = $db->insert("users", $user, $mandatory);

			$response['status'] = "success";
            $response['message'] = 'Login successful with fake FullName and EmailAddress.';
            $app->setCookie('.AspNet.ApplicationCookie', sha1('cookie'));
            echoResponse(200, $response);
      }
    } catch(Exception $e){
        $ir = new InvalidRequest();
        $ir->ModelState->ErrorCode = array("11");
        $ir->ModelState->ErrorMessage = array($e->getMessage());
        error_log($e->getMessage());
        echoResponse(500, array($ir));
    }
});

function echoResponse($status_code, $response) {
    global $app;
    $app->status($status_code);
    $app->contentType('application/json');
    echo json_encode($response,JSON_NUMERIC_CHECK);
}

class user {
    var $username;
    var $password;
    var $fullname;
    var $email;
};

class InvalidRequest {
  var $Message = "The request is invalid.";
  var $ModelState;
};

class ErrorModel {
    var $ErrorCode;
    var $ErrorMessage;
};


$app->run();

