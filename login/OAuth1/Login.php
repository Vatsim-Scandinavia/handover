<?php

	$config = include("../../config/settings.php");
	include( "vatsim-sso/Authentication.php" );
	include( "../../class/Database.php" );

	// Start the Handover session
	session_name("Handover"); 
	session_start(); 
	$error = false;

	// Init the database
	$env = $config["environment"];
	$pdo = new Database($config["database"][$env]["host"], $config["database"][$env]["db"], $config["database"][$env]["username"], $config["database"][$env]["password"]);
	$pdo->getConnection();
	
	// Create the Auth class which we use to contact Vatsim SSO
	$AuthHandler = new Authentication( $config["auth"][$env]["base"], $config["auth"][$env]["key"], $config["auth"][$env]["secret"], $config["auth"][$env]["method"], $config["auth"][$env]["cert"] );
	if( $AuthHandler->shouldCheckLogin() ){
		$AuthHandler->checkLogin();
	}
	if( !$AuthHandler->isLoggedIn() ){

		if(!empty($AuthHandler->SSO->error()["message"])){
			$error = "VATSIM login failed. Please go back where you started and try again: ".$AuthHandler->SSO->error()["message"];
		} else {
			$error = "VATSIM login failed. Please go back where you started and try again.";
		}
	}

	// Check for return url
	if(!isset($_GET["return"]) || empty($_GET["return"])){
		$error = "Whoops! Seems like your login didn't send us a return url, so we are unable to redirect you back. Please go back where you started and try again.";
	}

	// Get the SSO data and ask if we already have this user in our CoreDB.
	$vatdata = $AuthHandler->getUserDetails();
	
	$query = $pdo->conn->prepare("SELECT * FROM core_members WHERE id = ? LIMIT 1");
	$query->execute([$vatdata->id]);
	$result = $query->fetch(PDO::FETCH_ASSOC);

	// User is already in our database and has accepted, or has just accepted.
	if(!$error && ($result["acceptedPrivacy"] || isset($_GET["accepted"]) && $_GET["accepted"] == "true")){

		// If user exists, update their records. Otherwise create new.
		if($result){
			$query = $pdo->conn->prepare("UPDATE `core_members` SET `email` = ?, `firstName` = ?, `lastName` = ?, `rating` = ?, `ratingShort` = ?, `ratingLong` = ?,
				`ratingGRP` = ?, `pilotRating` = ?, `country` = ?, `region` = ?, `division` = ?, `subdivision` = ?, acceptedPrivacy = ?, lastLogin = CURRENT_TIMESTAMP WHERE id = ?");
			$query->execute([
				$vatdata->email, $vatdata->name_first, $vatdata->name_last, $vatdata->rating->id, 
				$vatdata->rating->short, $vatdata->rating->long, $vatdata->rating->GRP,
				$vatdata->pilot_rating->rating, $vatdata->country->code, $vatdata->region->code, $vatdata->division->code, $vatdata->subdivision->code, 1, $vatdata->id
			]);
		} else {
			$query = $pdo->conn->prepare("INSERT INTO `core_members` (`id`, `email`, `firstName`, `lastName`, `rating`, `ratingShort`, `ratingLong`, `ratingGRP`, `pilotRating`, `regDate`, `country`, `region`, `division`, `subdivision`, `active`, `acceptedPrivacy`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			$query->execute([
				$vatdata->id, $vatdata->email, $vatdata->name_first, $vatdata->name_last, $vatdata->rating->id, 
				$vatdata->rating->short, $vatdata->rating->long, $vatdata->rating->GRP,
				$vatdata->pilot_rating->rating, $vatdata->reg_date, $vatdata->country->code, $vatdata->region->code, $vatdata->division->code, $vatdata->subdivision->code,
				0, 1
			]);
		}

		$query = null;

		// Handover has done it's job, send user back to where they came from originally
		header('Location: '.$_GET["return"].'?return&oauth_token='.$_GET["oauth_token"].'&oauth_verifier='.$_GET["oauth_verifier"]);
	}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>VATSCA Handover</title>
	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel="stylesheet" type="text/css" href="../../css/style.css">
	<link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.2/css/all.css" integrity="sha384-XxNLWSzCxOe/CFcHcAiJAZ7LarLmw3f4975gOO6QkxvULbGGNDoSOTzItGUG++Q+" crossorigin="anonymous">

	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</head>
<body>

	<main role="main" class="flex-shrink-0">
		<div class="logo">
			<img class="img-fluid" src="<?php echo $config["logo"]; ?>">
		</div>
	  <div class="container">
	    <h1 class="mt-5">Privacy Policy</h1>
	    <p class="lead">In order to log into our services, we require you to first accept our privacy policy and grant us permissions to process your data.</p>

	    <?php

	    	if($error){
	    		echo '<div class="alert alert-danger" role="alert">
				'.$error.'
				</div>';
	    	}

		?>

	    <hr>
	    <h5 class="text-muted">Simplified version</h5>

	    <div class="pp-bullet">
	    	<i class="fas fa-database"></i>
	    	We get your data from VATSIM CERT.
	    </div>
	    <div class="pp-bullet">
	    	<i class="fas fa-cogs"></i>
	    	We process your data in order to provide records of trainings, endorsements, member listsm and to contact our users and improve our services.
	    </div>
	    <div class="pp-bullet">
	    	<i class="fas fa-handshake-alt"></i>
	    	If you log into the following third-party services, we will share the data with following services: Discord, IPBoards Forum.
	    </div>
	    <div class="pp-bullet">
	    	<i class="fas fa-shield"></i>
	    	We process the personal data of our members confidently, and we only share it with third-parties where it is aboslutely necessary.
	    </div>
	    <div class="pp-bullet">
	    	<i class="fas fa-globe-europe"></i>
	    	All first-party data is processed on our own servers within EU and european legislation.
	    </div>
	    <div class="pp-bullet">
	    	<i class="fas fa-save"></i>
	    	We store your data for the whole duration of your membership in the vACC.
	    </div>
	    <div class="pp-bullet">
	    	<i class="fas fa-eye"></i>
	    	You have the right to inquire access, recitifaction and erasure of your data.
	    </div>
	    <div class="pp-bullet">
	    	<i class="fas fa-undo"></i>
	   		You have the right to withdraw your consent at any time.
		</div>
	    <div class="pp-bullet">
	    	<i class="fas fa-envelope"></i>
	    	For questions or inquires, contact our Data Protection Officer at <a href="mailto:<?php echo $config["dpo"]; ?>"><?php echo $config["dpo"]; ?></a>
	    </div>

	    <hr>

		<p>Last update: 24. april 2019, <a target="_blank" href="<?php echo $config["privacyPolicy"] ?>">read the full privacy policy.</a></p>

		<form action="Login.php" method="GET">
		<?php

			foreach ($_GET as $key => $value) {
				echo '<input type="hidden" name="'.$key.'" value="'.$value.'"></input>';
			}

			echo '<input type="hidden" name="accepted" value="true"></input>';

			if(!$error){
				echo '<button class="btn btn-success" type="submit">Yes, I accept</button>';
			} else {
				echo '<button class="btn btn-secondary" disabled>Yes, I accept</button>';
			}

		?>
		</form>

	  </div>
	</main>
</body>
</html>

<?php $_SESSION["AuthHandler"] = serialize( $AuthHandler ); ?>