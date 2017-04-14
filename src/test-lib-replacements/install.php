<?php

require_once getenv("WP_TEST_BOOTSTRAPPER_PATH")."/src/utils/DbState.php";
require_once $argv[1];

$dsn="mysql:host=".DB_HOST.";dbname=".DB_NAME;
$pdo=new PDO($dsn,DB_USER,DB_PASSWORD,array(
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
));

if (!$pdo)
	throw new Exception("Can't connect to database");

if (is_file(getenv("WP_TEST_BOOTSTRAPPER_DB_CACHE"))) {
	echo "Restoring saved database contents...\n";

	$fn=getenv("WP_TEST_BOOTSTRAPPER_DB_CACHE");
	$state=json_decode(file_get_contents($fn),TRUE);
	$dbState=new DbState($pdo);
	$dbState->restoreState($state);
	exit;
}

require_once __DIR__."/install-original.php";

echo "Saving database contents...\n";

$dbState=new DbState($pdo);
$res=file_put_contents(
	getenv("WP_TEST_BOOTSTRAPPER_DB_CACHE"),
	json_encode($dbState->getState(),JSON_PRETTY_PRINT)
);

if (!$res)
	throw new Exception("Unable to save database contents");
