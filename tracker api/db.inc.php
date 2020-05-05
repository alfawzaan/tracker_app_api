<?php
try{
	$pdo = new PDO('mysql:host=localhost; dbname=trackerdb', 'ijdbuser', 'fawz0803');
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$pdo->exec('SET NAMES "utf8"');
	//echo 'Connect to the database server';
	}
	catch(PDOException $e){
	$output = 'Unable to connect to the database server ' . $e->getMessage();
	include 'error.html.php';
	exit();
	}
?>