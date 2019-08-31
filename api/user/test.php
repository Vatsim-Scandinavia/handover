<?php

require "../config/database.php";
require "../../vendor/php-jwt/JWT.php";
use \Firebase\JWT\JWT;

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


$secret_key = "YOUR_SECRET_KEY";
$jwt = null;
$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

// Is authentication sent in at all?
if(empty($_SERVER['HTTP_AUTHORIZATION'])){
	http_response_code(400);
	die(json_encode(array(
        "message" => "Authentication token required."
    )));
}

$jwt = $_SERVER['HTTP_AUTHORIZATION'];

if($jwt){

    try {

        $decoded = JWT::decode($jwt, $secret_key, array('HS256'));

        // Access is granted. Add code of the operation here 

        echo json_encode(array(
            "message" => "Access granted:",
            "error" => $e->getMessage()
        ));

    } catch (Exception $e) {

	    http_response_code(401);

	    echo json_encode(array(
	        "message" => "Access denied.",
	        "error" => $e->getMessage()
	    ));
	}

}

?>