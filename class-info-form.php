<?php

    include_once "mysql-connect.php";
    session_start();

    // Determine whether post data is present
    if ($_POST != null) {

        // A form submission was made

        // Get class associated with the image
        $class = $_POST["class"];

        // Determine the file extension
        if (strrpos($_FILES["battleAnalysis"]["name"], ".jpg") != null || strrpos($_FILES["profile_pic"]["name"], ".jpeg") != null) {
            $ext = ".jpg";
        };
        if (strrpos($_FILES["battleAnalysis"]["name"], ".png") != null) {
            $ext = ".png";
        };

        // New path for image
        $path = __DIR__ . "uploads\{$class}-" . uniqid() . $ext;

        // Move temp file to new location
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $path)) {
            header("location: index.php");
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
            header("Location: index.php");
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
    <form action="class-info-form.php" method="post" style="opacity: 1;">
        <fieldset id="instructions">
            <legend>Instructions</legend>
            <h3><b>Please perform a 1-minute battle analysis on a straw training dummy and <a href="input">upload a screenshot of the results</a></b></h3>
        </fieldset>
        <fieldset id="input">
            <legend>Input</legend>
            <label for="battleAnalysis">Upload an image of your battle analysis:</label>
            <input id="battleAnalysis" name="battleAnalysis" type="file" accept="image/png, image/jpeg" required>
        </fieldset>
        <input type="submit" class="submit" value="Submit Diagnostic">
    </form>
</body>
</html>
