<?php 
//terminar la sesión y redirigir al login 
session_start();
$_SESSION = array();
 header('Location: ../index.php');
?>