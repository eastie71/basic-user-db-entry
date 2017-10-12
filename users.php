<?php
	//session_start();

	$valError = $successMsg = $firstName = $surname = $email = $password = $query = "";
	$dblink = mysqli_connect("localhost", "users_admin", "password", "users");

	if (mysqli_connect_error()) {
		echo "Database failed to connect.";
	}

	// This "if" statement checks if the form has been submitted, and hence needs validation.
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if (empty($_POST["firstName"])) {
			$valError = "<strong>Please enter your First Name</strong></br>";
		} else {
			$firstName = clean_input(mysqli_real_escape_string($dblink, $_POST["firstName"]));
		}
		if (empty($_POST["surname"])) {
			$valError .= "<strong>Please enter your Surname</strong></br>";
		} else {
			$surname = clean_input(mysqli_real_escape_string($dblink, $_POST["surname"]));
		}

		if (empty($_POST["email"])) {
			$valError .= "<strong>Please enter a valid email address</strong></br>";
		} else {
			$email = clean_input(mysqli_real_escape_string($dblink, $_POST["email"]));
			// Check whether the email address already exists in the database
			$query = "SELECT `id` FROM user WHERE `email` = '".mysqli_real_escape_string($dblink, $email)."'";
			if ($result = mysqli_query($dblink, $query)) {
				$row = mysqli_fetch_array($result);
				if (sizeof($row) > 0)
					$valError .= "<strong>Email: ".$email." is already in use.</strong></br>";
			}
		}

		if (empty($_POST["password"])) {
			$valError .= "<strong>Please enter a valid password</strong></br>";
		} else {
			$password = clean_input(mysqli_real_escape_string($dblink, $_POST["password"]));
		}

		if (!$valError) {
			$anyError = false;

			// Turn off autocommitting of database updates
			mysqli_autocommit($dblink, FALSE);
			// Initialise the START of a TRANSACTION sequence of updates
			if (mysqli_query($dblink, "START TRANSACTION")) {
				$query = "INSERT into `user` (`firstName`, `surname`, `email`) VALUES('$firstName', '$surname', '$email')";
				// Perform the INSERT transaction update (but do not commit!)
				if (!mysqli_query($dblink, $query)) {
					$anyError = true;
				} else {
					$query = "SELECT `id` FROM user WHERE `email` = '$email'";					
					if ($result = mysqli_query($dblink, $query)) {
						$row = mysqli_fetch_array($result);
						// Use the Row ID as the SALT for the hashing of the password
						$salt = $row['id'];
						$password = md5(md5($salt).$password);
						$query = "UPDATE `user` SET `password` = '$password' WHERE id = $salt LIMIT 1";
						// Perform the transaction UPDATE of the password field
						if (!mysqli_query($dblink, $query)) {
							$anyError = true;
						}
					} else {
						$anyError = true;
					}
				}
				if ($anyError) {
					// Reverse any transaction updates performed
					mysqli_query($dblink, "ROLLBACK");
				} else {
					// Commit all the transaction updates performed. This also turns auto commit back ON
					if (!mysqli_query($dblink, "COMMIT"))
						$anyError = true;
				}
			} else {
				$anyError = true;
			}

			if ($anyError) {
				$valError = "An error occurred trying to add new Email: ".$email;
			} else {
				$successMsg = "Successfully added new Email: ".$email." with Name: ".$firstName." ".$surname;
			}
		}
	}

	function clean_input($data)
	{
		// trim removes whitespace from beginning and end of string
		$data = trim($data);
		// Remove all slashes (\) from the string
  		$data = stripslashes($data);
  		// Convert any special characters (eg. <,>,&,",') to HTML entities (eg. &lt;,&gt;)
  		$data = htmlspecialchars($data);
  		return $data;
	}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>User Creation</title>
	
	<!-- Required meta tags always come first -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

	<style type="text/css">
		body {
			font-family: Arial;
		}
	</style>
</head>

<body>
	<!== In HTML5 you do NOT need to use the "action" attribute to send the form data -->
	<form method="post">
		<p>Enter your First Name: <input type="text" name="firstName" placeholder="Eg. John"></p>
		<p>Enter your Surname: <input type="text" name="surname" placeholder="Eg. Smith"></p>
		<p>Enter your email: <input type="email" name="email"></p>
		<p>Enter your password: <input type="password" name="password"></p>
		<p><input type="submit" name="submit"></p>
	</form>

	<div id="results">
		<?php 
			if ($valError) { 
				echo $valError;
			} else {
				echo $successMsg;
			}
		?>
	</div>
</body>

</html>
