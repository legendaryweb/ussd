<?php
//Database connection
$server_name = "localhost";
$username = "root";
$password = "9139";
$db_name = "ussd_signups";

try {
    $con = new PDO("mysql:host=$server_name;dbname=$db_name", $username, $password);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    echo $e->getMessage();
}
?>