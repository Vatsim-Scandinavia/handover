<?php

class Auth{

	public static function check(){

		if(empty($_SERVER['HTTP_API_TOKEN'])){
			http_response_code(400);
			die(json_encode(array(
		        "message" => "Authentication token required."
		    )));
		}

		$token = $_SERVER['HTTP_API_TOKEN'];

		if($token == "1234"){
			return array(
				"serviceId" => "1",
				"serviceName" => "Forums"
			);
		} else {
			http_response_code(401);
			die(json_encode(array(
		        "message" => "Authentication failed."
		    )));
		}

	}

}

?>