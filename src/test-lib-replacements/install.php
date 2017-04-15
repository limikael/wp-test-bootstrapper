<?php

/**
 * This file is copied into the WordPress test lib source tree. It replaces
 * the original install.php file from that library. This file checks for 
 * a file containing cached database contents. If such a file exists, the
 * contents of that file will be used to restore the WordPress database to the
 * state it was just after an installation. If no file with cached database 
 * contents exists, this file will run the original install.php to set up
 * WordPress. After WordPress has been installed, the database contents will
 * be saved for later use so that subsequent rest runs will use the cached
 * contents and run faster.
 */

require_once getenv("WP_TEST_BOOTSTRAPPER_PATH")."/src/utils/DbState.php";
require_once $argv[1];

$dsn="mysql:host=".DB_HOST.";dbname=".DB_NAME;
$pdo=new PDO($dsn,DB_USER,DB_PASSWORD);

if (!$pdo)
	throw new Exception("Can't connect to database");

if (is_file(getenv("WP_TEST_BOOTSTRAPPER_DB_CACHE"))) {
	echo "Restoring saved database contents...\n";

	$fn=getenv("WP_TEST_BOOTSTRAPPER_DB_CACHE");
	$state=json_decode(file_get_contents($fn),TRUE);
	if (!$state)
		throw new Exception("Unable to decode db state to restore");

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
