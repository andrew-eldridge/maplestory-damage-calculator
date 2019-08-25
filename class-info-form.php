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
        $class = $_POST["class"];

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
            header("location: index.php?m=class-form-sub");
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
            header("location: index.php");
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
    <form action="class-info-form.php" method="post" enctype="multipart/form-data" style="opacity: 1;">
        <fieldset id="instructions">
            <legend>Instructions</legend>
            <h3><b>Please perform a 1-minute battle analysis on a straw training dummy and upload a screenshot of the results</b></h3>
        </fieldset>
        <fieldset id="input">
            <legend>Input</legend>
            <label for="battleAnalysis"><b>Upload an image of your battle analysis:</b></label>
            <input type="hidden" name="MAX_FILE_SIZE" value="30000">
            <input id="battleAnalysis" name="battleAnalysis" type="file" accept="image/png, image/jpeg" required>
        </fieldset>
        <input type="hidden" name="class" value="<?php echo $class; ?>">
        <input type="submit" name="submit" class="submit" value="Submit Diagnostic">
    </form>
</body>
</html>
