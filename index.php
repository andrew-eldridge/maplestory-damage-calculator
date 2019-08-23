<?php

	include_once "mysql-connect.php";
	session_start();

	// Some db test stuff
	$userAgent = $_SERVER["HTTP_USER_AGENT"];
	$clientIP = $_SERVER["REMOTE_ADDR"];

	// Determine whether the requesting IP already exists
	$sql = "SELECT * FROM visitor WHERE ipAddress='{$clientIP}';";
	$result = mysqli_query($conn, $sql);
	if (mysqli_num_rows($result) > 0) {
		// This user has visited before! Check the blacklist before granting access
		while ($row = mysqli_fetch_assoc($result)) {
			if ($row["blacklisted"]) {
				// The user has been blacklisted - send them away!
				if (!stripos($_SERVER["HTTP_REFERER"], "andrewcentral.com")) {
					if (isset($_SERVER["HTTP_REFERER"])) {
						header("location: " . $_SERVER["HTTP_REFERER"]);
					} else {
						header("location: ../reject?err=blacklist");
					}
					exit;
				} else {
					// The sneaky critter... just send 'em to the rejection page!
					header("location: ../reject?err=blacklist");
					exit;
				}
			}
		}
	} else {
		// This user has never visited before. Let's log their header info
		$sql = "INSERT INTO visitor (userAgent, ipAddress, blacklisted) VALUES ('{$userAgent}', '{$clientIP}', 0);";
		if (!mysqli_query($conn, $sql)) {
			// The user's info couldn't be logged
			// TODO: Implement a failsafe measure
			echo "An error occurred while processing the request.";
		}
	}

	// Initialize global variables
	$banner = "";
	// Initialize constants
	define("CALC_ITERATIONS", 10);
	define("VARIANCE_CALC_ITERATIONS", 5);

	if ($_POST != null) {
		// Session details
		if (isset($_SESSION["visitCount"])) {
			$_SESSION["visitCount"] += 1;
		} else {
			$_SESSION["visitCount"] = 1;
			$banner = "<div class='banner'>Thank your for choosing to visit Andrew Central! <a href='../index.html'>Learn More</a></div>";
		}

		// Meta data
		$calculationMode = mysqli_real_escape_string($conn, $_POST["calculation-mode"]);
		$inputMode = mysqli_real_escape_string($conn, $_POST["input-mode"]);
		// Generic data
		$lowerRange = mysqli_real_escape_string($conn, $_POST["lowerRange"]);
		$upperRange = mysqli_real_escape_string($conn, $_POST["upperRange"]);
		$skillPercent = mysqli_real_escape_string($conn, $_POST["skillPercent"]);
		$damagePercent = mysqli_real_escape_string($conn, $_POST["damagePercent"]);
		$criticalDamage = mysqli_real_escape_string($conn, $_POST["criticalDamage"]);
		$IED = mysqli_real_escape_string($conn, $_POST["IED"]);
		$ignoreElementalResist = mysqli_real_escape_string($conn, $_POST["ignoreElementalResist"]);
		$FDB = mysqli_real_escape_string($conn, $_POST["FDB"]);
		$class = mysqli_real_escape_string($conn, $_POST["class"]);

		// Mobbing specific data
		if ($calculationMode === "mobbing") {
			$bossPercent = 0;
			if (isset($_POST["PDR"]) && ($_POST["PDR"] != "")) {
				if ($inputMode === "decimal") {
					$PDR = mysqli_real_escape_string($conn, $_POST["PDR"]);
				} else if ($inputMode === "percentage") {
					$PDR = mysqli_real_escape_string($conn, $_POST["PDR"]);
					$PDR /= 100;
				} else {
					echo "Error: invalid request parameter for input mode.";
				}
			} else {
				$PDR = 0;
			}
			if (isset($_POST["elementalResist"]) && ($_POST["elementalResist"] != "")) {
				if ($inputMode === "decimal") {
					$elementalResist = mysqli_real_escape_string($conn, $_POST["elementalResist"]);
				} else if ($inputMode === "percentage") {
					$elementalResist = mysqli_real_escape_string($conn, $_POST["elementalResist"]);
					$elementalResist /= 100;
				} else {
					echo "Error: invalid request parameter for input mode.";
				}
			} else {
				$elementalResist = 0;
			}
			if (isset($_POST["enemyHP"]) && ($_POST["enemyHP"] != "")) {
				if ($inputMode === "decimal") {
					$enemyHP = mysqli_real_escape_string($conn, $_POST["enemyHP"]);
				} else if ($inputMode === "percentage") {
					$enemyHP = mysqli_real_escape_string($conn, $_POST["enemyHP"]);
					$enemyHP /= 100;
				} else {
					echo "Error: invalid request parameter for input mode.";
				}
			} else {
				$enemyHP = 0;
			}
		}

		// Bossing specific data
		if ($calculationMode === "bossing") {
			$boss = mysqli_real_escape_string($conn, $_POST["boss"]);
			$bossPercent = mysqli_real_escape_string($conn, $_POST["bossPercent"]);
			switch ($boss) {
				case "Chaos Vellum":
					$PDR = 2;
					$elementalResist = 0.5;
					$enemyHP = 200000000000;
					break;
				case "Hard Magnus":
					$PDR = 1.2;
					$elementalResist = 0.5;
					$enemyHP = 120000000000;
					break;
				case "Lucid":
					$PDR = 3;
					$elementalResist = 0.5;
					$enemyHP = 108000000000000;
					break;
				case "Hell Gollux":
					$PDR = 0.8;
					$elementalResist = 0.5;
					$enemyHP = 85000000000;
					break;
				case "Lotus":
					$PDR = 3;
					$elementalResist = 0.5;
					$enemyHP = 34650000000000;
					break;
				case "Damien":
					$PDR = 3;
					$elementalResist = 0.5;
					$enemyHP = 36000000000000;
					break;
				case "Will":
					$PDR = 3;
					$elementalResist = 0.5;
					$enemyHP = 126000000000000;
					break;
				case "Madman Ranmaru":
					$PDR = 0.55;
					$elementalResist = 0;
					$enemyHP = 10500000000;
					break;
				default:
					echo("Critical error: undefined request");
					break;
			}
		}

		// Modify percentage input to a decimal type
		if ($inputMode == "percentage") {
			$skillPercent /= 100;
			$damagePercent /= 100;
			$bossPercent /= 100;
			$criticalDamage /= 100;
			$IED /= 100;
			$ignoreElementalResist /= 100;
			$FDB /= 100;
		}

		// Final value adjustments
		$minBaseCrit = 1.2;
		$maxBaseCrit = 1.5;
		$minCrit = $minBaseCrit + $criticalDamage;
		$maxCrit = $maxBaseCrit + $criticalDamage;

		$avgDamage = [];

		// Outer casing for variance calculation
		for ($a=0; $a<VARIANCE_CALC_ITERATIONS; $a++) {

			// Initialize counters
			$sumDamage = 0;
			$sumVariance = 0;
			$damageList = [];
			$lowerDamage = [];
			$upperDamage = [];

			// Make several calculations to reduce randomness
			for ($i=0; $i<CALC_ITERATIONS; $i++) {
				$criticalDamage = rand($minCrit*100, $maxCrit*100) / 100;

				$lowerDamage[$i] = (($lowerRange * $skillPercent * (1 + $damagePercent + $bossPercent) * $criticalDamage) * (1 - ($PDR * (1 - $IED))) * (1 - ($elementalResist * (1 - $ignoreElementalResist)))) * (1 + $FDB);
				if ($lowerDamage[$i] < 0) {
					$lowerDamage[$i] = 0;
				}

				$upperDamage[$i] = (($upperRange * $skillPercent * (1 + $damagePercent + $bossPercent) * $criticalDamage) * (1 - ($PDR * (1 - $IED))) * (1 - ($elementalResist * (1 - $ignoreElementalResist)))) * (1 + $FDB);
				if ($upperDamage[$i] < 0) {
					$upperDamage[$i] = 0;
				}

				$damageList[$i] = ($lowerDamage[$i] + $upperDamage[$i]) / 2;
				$sumDamage += $damageList[$i];
			}

			// Log lower/upper damage calculations on first iteration for result stats
			if ($a === 0) {
				$lowerDmg = array_sum($lowerDamage) / count($lowerDamage);
				$upperDmg = array_sum($upperDamage) / count($upperDamage);
			}
			// Now calculate the average of all calculations
			$avgDamage[$a] = intval($sumDamage / CALC_ITERATIONS);

		}

		// Determine variance
		$benchmarkAvgDamage = $avgDamage[0];
		unset($avgDamage[0]);
		$test = array_sum($avgDamage) / count($avgDamage);
		if ($benchmarkAvgDamage !== 0) {
			$variance = round((abs($benchmarkAvgDamage - (array_sum($avgDamage) / count($avgDamage))) / $benchmarkAvgDamage) * 100, 2);
		} else {
			$variance = 0;
		}
		$variance .= "%";

		// Change average damage to number format
		$resultDamage = number_format($benchmarkAvgDamage);

		$incompleteData = false;
		switch ($class) {
			// Magicians
			case "Battle Mage":
				$skillDamage = 3.3;
				$attacksPerSecond = 8.4;
				$overallPercent = 0.4610;
				break;
			case "Beast Tamer":
				$incompleteData = true;
				break;
			case "Blaze Wizard":
				$skillDamage = 3.05;
				$attacksPerSecond = 18.2;
				$overallPercent = 0.9826;
				break;
			// TODO: Evan
			case "Evan":
				$incompleteData = true;
				break;
			case "Kanna":
				$skillDamage = 3.00;
				$attacksPerSecond = 14.9;
				$overallPercent = 0.8440;
				break;
			case "Luminous":
				$skillDamage = 3.85;
				$attacksPerSecond = 10.1;
				$overallPercent = 0.6009;
				break;
			case "Bishop":
				$skillDamage = 2.95;
				$attacksPerSecond = 13.5;
				$overallPercent = 0.9906;
				break;
			case "Ice/Lightning Mage":
				$skillDamage = 2.3;
				$attacksPerSecond = 11.2;
				$overallPercent = 0.6252;
				break;
			// TODO: Fire/Poison Mage
			case "Fire/Poison Mage":
				$incompleteData = true;
				break;
			case "Kinesis":
				$skillDamage = 1.5;
				$attacksPerSecond = 11.1;
				$overallPercent = 0.4195;
				break;
			// TODO: Illium
			case "Illium":
				$incompleteData = true;
				break;
			// Thieves
			case "Dual Blade":
				$skillDamage = 3.15;
				$attacksPerSecond = 28.5;
				$overallPercent = 0.9113;
				break;
			case "Night Walker":
				$skillDamage = 3.4;
				$attacksPerSecond = 42.9;
				$overallPercent = 0.9220;
				break;
			case "Phantom":
				$skillDamage = 1.1;
				$attacksPerSecond = 13.4;
				$overallPercent = 0.4724;
				break;
			case "Shadower":
				$skillDamage = 6.27;
				$attacksPerSecond = 11.1;
				$overallPercent = 0.9824;
				break;
			case "Night Lord":
				$skillDamage = 3.78;
				$attacksPerSecond = 19.8;
				$overallPercent = 0.8215;
				break;
			case "Xenon":
				$skillDamage = 2.6;
				$attacksPerSecond = 16.3;
				$overallPercent = 0.6466;
				break;
			// TODO: Cadena
			case "Cadena":
				$incompleteData = true;
				break;
			// Warriors
            // TODO: Aran
			case "Aran":
				$incompleteData = true;
				break;
			case "Dawn Warrior":
				$skillDamage = 2.95;
				$attacksPerSecond = 21.2;
				$overallPercent = 1;
				break;
			case "Demon Avenger":
				$skillDamage = 5.0;
				$attacksPerSecond = 14.2;
				$overallPercent = 0.6581;
				break;
			case "Demon Slayer":
				$skillDamage = 4.2;
				$attacksPerSecond = 20.8;
				$overallPercent = 0.8965;
				break;
			// TODO: Hayato
			case "Hayato":
				$incompleteData = true;
				break;
			// TODO: Kaiser
			case "Kaiser":
				$incompleteData = true;
				break;
			case "Mihile":
				$skillDamage = 2.80;
				$attacksPerSecond = 13.0;
				$overallPercent = 0.7893;
				break;
			case "Dark Knight":
				$skillDamage = 2.25;
				$attacksPerSecond = 16.8;
				$overallPercent = 0.9555;
				break;
			case "Hero":
				$skillDamage = 2.80;
				$attacksPerSecond = 15.5;
				$overallPercent = 0.9188;
				break;
			case "Paladin":
				$skillDamage = 3.10;
				$attacksPerSecond = 16.5;
				$overallPercent = 0.9893;
				break;
			case "Zero":
				$skillDamage = 7.45;
				$attacksPerSecond = 2.4;
				$overallPercent = 0.16;
				break;
			// TODO: Blaster
			case "Blaster":
				$incompleteData = true;
				break;
			// Bowmen
			case "Marksman":
				$skillDamage = 14.7;
				$attacksPerSecond = 1.7;
				$overallPercent = 0.9917;
				break;
			case "Bowmaster":
				$skillDamage = 3.3;
				$attacksPerSecond = 16.5;
				$overallPercent = 0.4904;
				break;
			case "Wild Hunter":
				$skillDamage = 0.6;
				$attacksPerSecond = 20.5;
				$overallPercent = 0.4998;
				break;
			// TODO: Wind Archer
			case "Wind Archer":
				$incompleteData = true;
				break;
			case "Mercedes":
				$skillDamage = 2.2;
				$attacksPerSecond = 16.5;
				$overallPercent = 0.5812;
				break;
			// TODO: Pathfinder
			case "Pathfinder":
				$incompleteData = true;
				break;
			// Pirates
			case "Angelic Buster":
				$skillDamage = 2.55;
				$attacksPerSecond = 9.8;
				$overallPercent = 0.5323;
				break;
			// TODO: Cannoneer
			case "Cannoneer":
				$incompleteData = true;
				break;
			case "Jett":
				$skillDamage = 3.00;
				$attacksPerSecond = 8.4;
				$overallPercent = 0.7298;
				break;
			case "Mechanic":
				$skillDamage = 8.5;
				$attacksPerSecond = 15.0;
				$overallPercent = 0.5506;
				break;
			case "Buccaneer":
				$skillDamage = 3.20;
				$attacksPerSecond = 15.8;
				$overallPercent = 0.9879;
				break;
			case "Corsair":
				$skillDamage = 3.00;
				$attacksPerSecond = 8.5;
				$overallPercent = 0.4854;
				break;
			case "Shade":
				$skillDamage = 3.80;
				$attacksPerSecond = 14.1;
				$overallPercent = 0.8990;
				break;
			case "Thunder Breaker":
				$skillDamage = 3.5;
				$attacksPerSecond = 17.0;
				$overallPercent = 0.5408;
				break;
			// TODO: Ark
			case "Ark":
				$incompleteData = true;
				break;
			// None of the above..?
			default:
				echo "Critical error: undefined request";
		}
		$estimatedTime = $enemyHP / ($attacksPerSecond * $benchmarkAvgDamage);
		$estimatedMinutes = floor($estimatedTime / 60);
		$estimatedSeconds = $estimatedTime % 60;
		$estimatedTimeString = "{$estimatedMinutes}min {$estimatedSeconds}sec";
		if ($estimatedMinutes <= 10) {
			$mushroomGifUrl = "images/mushroom-neutral.gif";
		} else {
			$mushroomGifUrl = "images/mushroom-sad.gif";
		}
	}

	if (isset($avgDamage)) {
		$success = true;
		$stats = (object) [
			"meta" => (object) [
				"calculationMode" => $calculationMode,
				"inputMode" => $inputMode
			],
			"boss" => (object) [
				"PDR" => round($PDR, 2),
				"elementalResist" => round($elementalResist, 2)
			],
			"generic" => (object) [
				"lowerRange" => round($lowerRange, 2),
				"upperRange" => round($upperRange, 2),
				"skillPercent" => round($skillPercent, 2),
				"damagePercent" => round($damagePercent, 2),
				"bossPercent" => round($bossPercent, 2),
				"criticalDamage" => round($criticalDamage, 2),
				"IED" => round($IED, 2),
				"ignoreElementalResist" => round($ignoreElementalResist, 2),
				"FDB" => round($FDB, 2),
			],
			"calculated" => (object) [
				"lowerDamage" => number_format(intval($lowerDmg, 2)),
				"upperDamage" => number_format(intval($upperDmg, 2)),
				"avgDamage" => $resultDamage
			]
		];
	} else {
		$success = false;
	}

?>
<!DOCTYPE html>
<html>
<head>
	<title>MapleStory Damage Calculator | Andrew's Portfolio</title>
	<link rel="shortcut icon" href="images/maple-leaf.png">
	<link rel="stylesheet" type="text/css" href="styles/form.css">
	<link href='http://fonts.googleapis.com/css?family=Oleo+Script' rel='stylesheet' type='text/css'>
	<meta name="description" content="This comprehensive MapleStory damage calculator will determine your damage to mobs and bosses based on several factors.">
	<meta http-equiv="Cache-Control" content="no-store" />
</head>
<body onload="fadeIn(<?php echo $success; ?>);">
	<?php echo $banner; ?>
	<img class="background-image" src="images/login.png">
	<header>Maplestory Damage Calculator</header>
	<form action="index.php" method="post">
		<fieldset>
			<legend>Meta Configuration</legend>
			<fieldset class="sub-fieldset">
				<legend>Choose a Calculation Mode</legend>
				<div>
					<input onclick="updateCalculationMode();" class="radio" type="radio" id="calculation-mode1" name="calculation-mode" value="mobbing" required>
					<label for="calculation-mode1">Mobbing</label>
				</div>
				<div>
					<input onclick="updateCalculationMode();" class="radio" type="radio" id="calculation-mode2" name="calculation-mode" value="bossing" checked required>
					<label for="calculation-mode2">Bossing</label>
				</div>
			</fieldset>
			<fieldset class="sub-fieldset">
				<legend>Choose an Input Mode</legend>
				<div>
					<input onclick="updateInputMode();validate('all');" class="radio" type="radio" id="input-mode1" name="input-mode" value="decimal" required>
					<label for="input-mode1">Decimal (e.g. 1.23)</label>
				</div>
				<div>
					<input onclick="updateInputMode();validate('all');" class="radio" type="radio" id="input-mode2" name="input-mode" value="percentage" checked required>
					<label for="input-mode2">Percentage (e.g. 123)</label>
				</div>
			</fieldset>
		</fieldset>
		<fieldset class="bossing">
			<legend>Benchmark Boss</legend>
			<div class="radio-container">
				<input class="radio bossing-input" type="radio" id="boss1" name="boss" value="Chaos Vellum" required>
				<label for="boss1" class="boss-label">Chaos Vellum</label>
			</div>
			<div class="radio-container">
				<input class="radio bossing-input" type="radio" id="boss2" name="boss" value="Hell Gollux" required>
				<label for="boss2" class="boss-label">Hell Gollux</label>
			</div>
			<div class="radio-container">
				<input class="radio bossing-input" type="radio" id="boss3" name="boss" value="Hard Magnus" required>
				<label for="boss3" class="boss-label">Hard Magnus</label>
			</div>
			<div class="radio-container">
				<input class="radio bossing-input" type="radio" id="boss4" name="boss" value="Lotus" required>
				<label for="boss4" class="boss-label">Hard Lotus</label>
			</div>
			<div class="radio-container">
				<input class="radio bossing-input" type="radio" id="boss5" name="boss" value="Lucid" required>
				<label for="boss5" class="boss-label">Hard Lucid</label>
			</div>
			<div class="radio-container">
				<input class="radio bossing-input" type="radio" id="boss6" name="boss" value="Damien" required>
				<label for="boss6" class="boss-label">Hard Damien</label>
			</div>
			<div class="radio-container">
				<input class="radio bossing-input" type="radio" id="boss7" name="boss" value="Will" required>
				<label for="boss7" class="boss-label">Hard Will</label>
			</div>
			<div class="radio-container">
				<input class="radio bossing-input" type="radio" id="boss8" name="boss" value="Madman Ranmaru" required>
				<label for="boss8" class="boss-label">Madman Ranmaru</label>
			</div>
		</fieldset>
		<fieldset>
			<legend>Base Damage</legend>
			<input onfocusout="validate('lowerRange');" id="lowerRange" class="range" type="number" name="lowerRange" placeholder="Lower Damage Range" required> ~ 
			<input onfocusout="validate('upperRange');" id="upperRange" class="range" type="number" name="upperRange" placeholder="Upper Damage Range" required><br>
			<select onchange="javascript:enableClassSelect(this.options[this.selectedIndex].value);" class="reg" id="class-type" name="class-type" required>
				<option disabled selected value> -- select a class type -- </option>
				<option value="Magician">Magician</option>
				<option value="Thief">Thief</option>
				<option value="Warrior">Warrior</option>
				<option value="Bowman">Bowman</option>
				<option value="Pirate">Pirate</option>
			</select>
			<select class="disabled-select reg" id="class" name="class" disabled required>
				<option class="Magician Thief Warrior Bowman Pirate" id="class-default-option" disabled selected value></option>
				<!-- Magicians -->
				<option class="Magician" value="Battle Mage">Battle Mage</option>
				<option class="Magician" value="Beast Tamer">Beast Tamer</option>
				<option class="Magician" value="Blaze Wizard">Blaze Wizard</option>
				<option class="Magician" value="Evan">Evan</option>
				<option class="Magician" value="Kanna">Kanna</option>
				<option class="Magician" value="Luminous">Luminous</option>
				<option class="Magician" value="Bishop">Bishop</option>
				<option class="Magician" value="Ice/Lightning Mage">Ice/Lightning Mage</option>
				<option class="Magician" value="Fire/Poison Mage">Fire/Poison Mage</option>
				<option class="Magician" value="Kinesis">Kinesis</option>
				<option class="Magician" value="Illium">Illium</option>
				<!-- Thieves -->
				<option class="Thief" value="Dual Blade">Dual Blade</option>
				<option class="Thief" value="Night Walker">Night Walker</option>
				<option class="Thief" value="Phantom">Phantom</option>
				<option class="Thief" value="Shadower">Shadower</option>
				<option class="Thief" value="Night Lord">Night Lord</option>
				<option class="Thief" value="Xenon">Xenon</option>
				<option class="Thief" value="Cadena">Cadena</option>
				<!-- Warriors -->
				<option class="Warrior" value="Aran">Aran</option>
				<option class="Warrior" value="Dawn Warrior">Dawn Warrior</option>
				<option class="Warrior" value="Demon Avenger">Demon Avenger</option>
				<option class="Warrior" value="Demon Slayer">Demon Slayer</option>
				<option class="Warrior" value="Hayato">Hayato</option>
				<option class="Warrior" value="Kaiser">Kaiser</option>
				<option class="Warrior" value="Mihile">Mihile</option>
				<option class="Warrior" value="Dark Knight">Dark Knight</option>
				<option class="Warrior" value="Hero">Hero</option>
				<option class="Warrior" value="Paladin">Paladin</option>
				<option class="Warrior" value="Zero">Zero</option>
				<option class="Warrior" value="Blaster">Blaster</option>
				<!-- Bowmen -->
				<option class="Bowman" value="Marksman">Marksman</option>
				<option class="Bowman" value="Bowmaster">Bowmaster</option>
				<option class="Bowman" value="Wild Hunter">Wild Hunter</option>
				<option class="Bowman" value="Wind Archer">Wind Archer</option>
				<option class="Bowman" value="Mercedes">Mercedes</option>
				<option class="Bowman" value="Pathfinder">Pathfinder</option>
				<!-- Pirates -->
				<option class="Pirate" value="Angelic Buster">Angelic Buster</option>
				<option class="Pirate" value="Cannoneer">Cannoneer</option>
				<option class="Pirate" value="Jett">Jett</option>
				<option class="Pirate" value="Mechanic">Mechanic</option>
				<option class="Pirate" value="Buccaneer">Buccaneer</option>
				<option class="Pirate" value="Corsair">Corsair</option>
				<option class="Pirate" value="Shade">Shade</option>
				<option class="Pirate" value="Thunder Breaker">Thunder Breaker</option>
				<option class="Pirate" value="Ark">Ark</option>
			</select>
			<input onfocusout="validate('skillPercent');" id="skillPercent" class="reg" type="number" name="skillPercent" placeholder="Skill Damage Percent" required><br>
		</fieldset>
		<fieldset>
			<legend>Damage Modifiers</legend>
			<label for="damagePercent">Damage Percent</label>
			<input onfocusout="validate('damagePercent');" id="damagePercent" class="reg" type="number" name="damagePercent" placeholder="Damage Percent" required><br>
			<input onfocusout="validate('criticalDamage');" id="criticalDamage" class="reg" type="number" name="criticalDamage" placeholder="Bonus Critical Damage Percent" required><br>
			<input onfocusout="validate('IED');" id="IED" class="reg" type="number" name="IED" placeholder="Ignore Enemy Defense Percent" required><br>
			<input onfocusout="validate('ignoreElementalResist');" id="ignoreElementalResist" class="reg" type="number" name="ignoreElementalResist" placeholder="Ignore Elemental Resist Percent" required><br>
			<input onfocusout="validate('FDB');" id="FDB" class="reg" type="number" name="FDB" placeholder="Final Damage Percent" required><br>
		</fieldset>
		<fieldset class="bossing">
			<legend>Boss Exclusive</legend>
			<input onfocusout="validate('bossPercent');" id="bossPercent" class="reg bossing-input" type="number" name="bossPercent" placeholder="Boss Damage Percent" required><br>
		</fieldset>
		<fieldset class="mobbing">
			<legend>Mob Exclusive</legend>
			<input onfocusout="validate('PDR');" id="PDR" class="reg mobbing-input" type="number" name="PDR" placeholder="Mob's Percent Damage Resisted"><br>
			<input onfocusout="validate('elementalResist');" id="elementalResist" class="reg mobbing-input" type="number" name="elementalResist" placeholder="Mob's Elemental Resist"><br>
			<input onfocusout="validate('enemyHP');" id="enemyHP" class="reg mobbing-input" type="number" name="enemyHP" placeholder="Mob's Total HP"><br>
		</fieldset>
		<fieldset class="results">
			<legend>Results</legend>
			<img src=<?php echo $mushroomGifUrl; ?>>
			<h4>Damage per line: <?php echo $resultDamage; ?></h4>
			<h5>Estimated time: <?php echo $estimatedTimeString; ?></h5>
			<h5>Variance factor: <?php echo $variance; ?><span class="tooltip"><img src="../images/help-icon.png" alt="More Info" width="16px" height="16px" /><span class="tooltiptext">Describes the variance between multiple calculation tests. If this exceeds 5%, try increasing test threshold.</span></span></h5>
			<h5><span class="details-toggle" tabindex="0" onclick="toggleDetails();">Show Details</span></h5>
			<pre style="display:none;"><?php echo var_export($stats, true); ?></pre>
		</fieldset>
		<input class="submit" type="submit" value="Calculate Damage!">
		<button onclick="redirect('index.php');" class="submit return">Make Another Calculation</button>
	</form>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	<script src="scripts/main.js"></script>
</body>
</html>
