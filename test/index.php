<?php

require_once "../bootstrap.php";

use RobGuida\RgSecureForm\RgSecureForm;

$token = '';
try {
    if (isset($_REQUEST['submit'])) {
        if (RgSecureForm::validateToken($_REQUEST['token'])) {
            echo(__FILE__ . ' ' . __LINE__ . ' $_REQUEST:<pre>' . print_r($_REQUEST, true) . '</pre>');
        } else {
            throw new Exception("The token is not valid<pre>" . print_r($_REQUEST, true) . '</pre>');
        }
    }
    $token = RgSecureForm::getToken();
} catch (Exception $e) {
    echo(__FILE__ . ' ' . __LINE__ . ' $e:<pre>' . print_r($e, true) . '</pre>');
    phpinfo();
}
?>
<html>
<head>
    <title>Test RgSecureForm</title>
</head>
<body>
    <form method="post">
        <input type="text" name="token" id="token" value="<?php echo $token; ?>" style="width:1000px;" /><br />
        <input type="text" name="first_name" value="Rob" /><br />
        <input type="text" name="last_name" value="Guida" /><br />
        <input type="submit" name="submit" value="Submit" />
    </form>
</body>
</html>
