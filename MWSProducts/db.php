<?php

session_start();

define('HOST', 'localhost');
define('USER', 'mws');
define('PASS', 'mws9lBl88G2uvVtcHw$');
define('DBNM', 'mws');

function mws_mysqlConnect(){
	$mysqli = new mysqli(HOST, USER, PASS);
	if(!($mysqli)){
		die('Connection Failed to DB: '. mysqli_connect_error());
	}
	$mysqli->select_db(DBNM);
	return $mysqli;
}

