<?php
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/ussd/opt.txt', $_SERVER["QUERY_STRING"] . "\n", FILE_APPEND | LOCK_EX);
require_once $_SERVER['DOCUMENT_ROOT'] . "/ussd/control/ussd_class.php";
$ussd_caller = new ussd_callback();
if (isset($_SERVER["QUERY_STRING"])) {
    echo $ussd_caller->request($_SERVER["QUERY_STRING"]);
} else {
    echo('No request recieved!');
};
?>
