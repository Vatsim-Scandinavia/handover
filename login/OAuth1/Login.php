<?php

	require( "vatsim-sso/Authentication.php" );
	if ( session_status() === PHP_SESSION_NONE ){ session_start(); }

	$http = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https://' : 'http://';
	$AuthHandler = new Authentication( $http.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'] );

	if( $AuthHandler->shouldCheckLogin() ){

		$AuthHandler->checkLogin();

	}
	if( !$AuthHandler->isLoggedIn() ){

		$_SESSION["AuthHandler"] = serialize( $AuthHandler );
		$AuthHandler->Login();

	}

?>

<!DOCTYPE html>
<html>
<head>
	<title>VATSCA Handover</title>
</head>
<body>

	<h1>Please confirm you want us to save this data</h1>
	<?php print_r($_GET); 
	print_r(json_encode($AuthHandler->getUserDetails()));

	print('<br><hr><a href="http://localhost/service/index.php?return&oauth_token='.$_GET["oauth_token"].'&oauth_verifier='.$_GET["oauth_verifier"].'">Yes</a>');

	?>
	

</body>
</html>

<?php $_SESSION["AuthHandler"] = serialize( $AuthHandler ); ?>