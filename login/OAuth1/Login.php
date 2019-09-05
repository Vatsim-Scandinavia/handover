<?php

	$config = include("../../config/settings.php");
	include( "vatsim-sso/Authentication.php" );
	include( "../../class/Database.php" );

	if ( session_status() === PHP_SESSION_NONE ){ 
		session_name("Handover"); 
		session_start(); 
	}

	$env = $config["environment"];
	$pdo = new Database($config["database"][$env]["host"], $config["database"][$env]["db"], $config["database"][$env]["username"], $config["database"][$env]["password"]);
	$pdo->getConnection();
	
	$AuthHandler = new Authentication( $config["auth"][$env]["base"], $config["auth"][$env]["key"], $config["auth"][$env]["secret"], $config["auth"][$env]["method"], $config["auth"][$env]["cert"] );

	if( $AuthHandler->shouldCheckLogin() ){

		$AuthHandler->checkLogin();

	}
	if( !$AuthHandler->isLoggedIn() ){

		print_r($AuthHandler->SSO->error());
		die("<br><br>VATSIM login failed. Please go back where you started and try again.");

	}

	// Check for return url
	if(empty($_GET["return"])){
		die("Whoops. You don't seem to have a url, please go back and try again?");
	}

	$vatdata = $AuthHandler->getUserDetails();

	$query = $pdo->conn->prepare("SELECT * FROM core_members WHERE id = ?");
	$query->execute([$vatdata->id]);

	$result = $query->fetchAll(PDO::FETCH_ASSOC);
	
	// Update if found, insert otherwise
	if($result){
		$query = $pdo->conn->prepare("UPDATE `core_members` SET `email` = ?, `firstName` = ?, `lastName` = ?, `rating` = ?, `ratingShort` = ?, `ratingLong` = ?,
			`ratingGRP` = ?, `pilotRating` = ?, `country` = ?, `region` = ?, `division` = ?, `subdivision` = ?, lastLogin = CURRENT_TIMESTAMP WHERE id = ?");
		$query->execute([
			$vatdata->email, $vatdata->name_first, $vatdata->name_last, $vatdata->rating->id, 
			$vatdata->rating->short, $vatdata->rating->long, $vatdata->rating->GRP,
			$vatdata->pilot_rating->rating, $vatdata->country->code, $vatdata->region->code, $vatdata->division->code, $vatdata->subdivision->code, $vatdata->id
		]);
	} else {
		$query = $pdo->conn->prepare("INSERT INTO `core_members` (`id`, `email`, `firstName`, `lastName`, `rating`, `ratingShort`, `ratingLong`, `ratingGRP`, `pilotRating`, `regDate`, `country`, `region`, `division`, `subdivision`, `active`, `acceptedPrivacy`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
		$query->execute([
			$vatdata->id, $vatdata->email, $vatdata->name_first, $vatdata->name_last, $vatdata->rating->id, 
			$vatdata->rating->short, $vatdata->rating->long, $vatdata->rating->GRP,
			$vatdata->pilot_rating->rating, $vatdata->reg_date, $vatdata->country->code, $vatdata->region->code, $vatdata->division->code, $vatdata->subdivision->code,
			0, 0
		]);
	}
	$query = null;

?>

<!DOCTYPE html>
<html>
<head>
	<title>VATSCA Handover</title>
</head>
<body>

	<h1>Please confirm you want us to save this data</h1>
	<p>Bla bla, you accept GDPR. Last updated: 31.08.2019</p>
	<?php

	print('<br><hr><a href="'.$_GET["return"].'?return&oauth_token='.$_GET["oauth_token"].'&oauth_verifier='.$_GET["oauth_verifier"].'">Yes, i accept that you can sell my soul</a>');

	?>
	

</body>
</html>

<?php $_SESSION["AuthHandler"] = serialize( $AuthHandler ); ?>