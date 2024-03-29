<?php

    require "../vendor/autoload.php";
    require "../global.php";
    require "../mysql-connect.php";
    session_start();
    $ext = null;

    // Requesting user details
    $userAgent = $_SERVER["HTTP_USER_AGENT"];
    $clientIP = $_SERVER["REMOTE_ADDR"];

    // Verify that the user has permission to access this page
    validateUser($conn, $userAgent, $clientIP);

    // Determine whether post data is present
    if (isset($_POST["submit"])) {

        // A form submission was made

        // Get class associated with the image
        // If the class is FP or IL mage, remove forward slashes from name
        $class = $_POST["class"];
        if ($class == "Fire/Poison Mage") {
            $class = "FP Mage";
        } else if ($class == "Ice/Lightning Mage") {
            $class = "IL Mage";
        }

        // Replace all spaces in class name with underscores (for valid file name)
        $class = str_replace(" ", "_", $class);

        // Determine the file extension
        if (strrpos($_FILES["battleAnalysis"]["name"], ".jpg") != null || strrpos($_FILES["battleAnalysis"]["name"], ".jpeg") != null) {
            $ext = ".jpg";
        };
        if (strrpos($_FILES["battleAnalysis"]["name"], ".png") != null) {
            $ext = ".png";
        };

        // New path for image
        $path = __DIR__ . "\uploads\\{$class}-" . uniqid() . $ext;

        // Move temp file to new location
        if (move_uploaded_file($_FILES["battleAnalysis"]["tmp_name"], $path)) {
            header("location: index?m=class-form-sub");
            exit;
        } else {
            echo("Upload failed");
        };

    } else {

        // No form submission made yet

        // Parse url for the user's class
        $url = $_SERVER["REQUEST_URI"];
        $parts = parse_url($url);
        if (isset($parts["query"])) {
            parse_str($parts["query"], $query);
            if (isset($query["c"])) {
                $class = $query["c"];
            }
        }

        // Invalid request, sending user back to main app
        if (!isset($class)) {
            header("location: index");
            exit;
        }

    }

?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $class; ?> Info Form | MapleStory Damage Calculator</title>
    <link rel="shortcut icon" href="images/maple-leaf.png">
    <link rel="stylesheet" type="text/css" href="styles/form.css">
    <meta name="robots" content="noindex">
</head>
<body>
    <header><?php echo $class; ?> Info Form</header>
    <form action="class-info-form" method="post" enctype="multipart/form-data" style="opacity: 1;">
        <fieldset id="instructions">
            <legend>Instructions</legend>
            <h3><b>Please perform a 1-minute battle analysis on a straw training dummy and upload a screenshot of the results</b></h3>
        </fieldset>
        <fieldset id="input">
            <legend>Input</legend>
            <label for="battleAnalysis"><b>Upload an image of your battle analysis:</b></label>
            <input type="hidden" name="MAX_FILE_SIZE" value="2000000">
            <input id="battleAnalysis" name="battleAnalysis" type="file" accept="image/png, image/jpeg" required><br/>
            <label for="battleAnalysis">Max upload size: 2MB</label>
        </fieldset>
        <input type="hidden" name="class" value="<?php echo $class; ?>">
        <input type="submit" name="submit" class="submit" value="Submit Diagnostic">
    </form>
    <form action="index" style="opacity: 1;">
        <input type="submit" name="submit" class="submit" value="Return" style="margin-top: 0;">
    </form>
</body>
</html>
