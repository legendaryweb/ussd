<?php

//Database connection
//requires below default added
$server_name = "localhost";
$username = "root"; 
$password = "password";
$db_name = "ussd_signups";

try {
    $this->con = new PDO("mysql:host=$server_name;dbname=$db_name", $username, $password);
    $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    echo $e->getMessage();
}
?>