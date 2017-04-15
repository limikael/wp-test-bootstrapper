<?php

/**
 * Bootstrap a test instance or WordPress.
 */
class WpTestBootstrapper {

	private $dbName;
	private $dbUser;
	private $dpPass;
	private $dbHost;
	private $projectDir;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->dbHost="localhost";
		$this->dbName="wptest";
		$this->dbUser="";
		$this->dbPass="";
		$this->projectDir=getcwd();
	}

	/**
	 * Get project directory.
	 */
	private function getProjectDir() {
		return $this->projectDir;
	}

	/**
	 * Get prefix for test files.
	 */
	private function getTestDir() {
		return $this->getProjectDir()."/.wptest";
	}

	/**
	 * Download and install required components.
	 */
	private function install() {
		if (!is_dir($this->getTestDir())) {
			if (!mkdir($this->getTestDir(),0755,TRUE))
				throw new Exception("Unable to create directory for test files.");
		}

		if (!is_file($this->getTestDir()."/latest.zip")) {
			echo "Downloading WordPress...\n";

			$f=fopen("https://wordpress.org/latest.zip","r");
			$res=file_put_contents($this->getTestDir()."/latest.zip",$f);

			if (!$res)
				throw new Exception("Unable to download WordPress");
		}

		if (!is_dir($this->getTestDir()."/wordpress")) {
			echo "Extracting WordPress...\n";

			$zip=new ZipArchive();
			if (!$zip->open($this->getTestDir()."/latest.zip"))
				throw new Exception("Can't open zip file.");

			$zip->extractTo($this->getTestDir());
			$zip->close();
		}

		if (!is_dir($this->getTestDir()."/includes")) {
			echo "Downloading WordPress test lib...\n";

			system("svn co --quiet http://develop.svn.wordpress.org/trunk/tests/phpunit/includes/ ".
				$this->getTestDir()."/includes",$res);

			if ($res!=0)
				throw new Exception("Unable to download the test lib, is svn installed?");

			echo "Moving code in the test lib...\n";
			$res=copy(
				$this->getTestDir()."/includes/install.php",
				$this->getTestDir()."/includes/install-original.php"
			);

			if (!$res)
				throw new Exception("Unable to inject code into the test lib");
		}

		$res=copy(
			__DIR__."/src/test-lib-replacements/install.php",
			$this->getTestDir()."/includes/install.php"
		);

		if (!$res)
			throw new Exception("Unable to inject code into the test lib");

		if (!is_dir($this->getTestDir()."/data")) {
			echo "Downloading default theme...\n";

			system("svn co --quiet http://develop.svn.wordpress.org/trunk/tests/phpunit/data/themedir1/default/ ".
				$this->getTestDir()."/data/themedir1",$res); // or /default?

			if ($res!=0)
				throw new Exception("Unable to download default theme, is svn installed?");
		}

		if (!is_file($this->getTestDir()."/wp-tests-config-sample.php")) {
			$f=fopen("https://develop.svn.wordpress.org/trunk/wp-tests-config-sample.php","r");
			$res=file_put_contents($this->getTestDir()."/wp-tests-config-sample.php",$f);

			if (!$res)
				throw new Exception("Unable to download test config");
		}

		if (!is_file($this->getTestDir()."/wp-tests-config-sample.php")) {
			$f=fopen("https://develop.svn.wordpress.org/trunk/wp-tests-config-sample.php","r");
			$res=file_put_contents($this->getTestDir()."/wp-tests-config-sample.php",$f);

			if (!$res)
				throw new Exception("Unable to download test config");
		}
	}

	/**
	 * Generate wp-tests-config.php from wp-tests-config-sample.php
	 */
	private function setupTestConfig() {
		$replace=array(
			"'/src/'"=>"'/wordpress/'",
			"youremptytestdbnamehere"=>$this->dbName,
			"yourusernamehere"=>$this->dbUser,
			"yourpasswordhere"=>$this->dbPass,
		);

		$contents=file_get_contents($this->getTestDir()."/wp-tests-config-sample.php");
		if (!$contents)
			throw new Exception("Unable to load tests config sample");

		foreach ($replace as $key=>$val)
			$contents=str_replace($key,$val,$contents);

		$res=file_put_contents($this->getTestDir()."/wp-tests-config.php",$contents);
		if (!$contents)
			throw new Exception("Unable to save tests config");
	}

	/**
	 * Set database user.
	 */
	public function setDbUser($user) {
		$this->dbUser=$user;
	}

	/**
	 * Set database password.
	 */
	public function setDbPass($pass) {
		$this->dbPass=$pass;
	}

	/**
	 * Set database name.
	 */
	public function setDbName($name) {
		$this->dbName=$name;
	}

	/**
	 * Actually load the plugin file.
	 */
	public function loadPlugin() {
		$files=scandir($this->getProjectDir());

		$defaultHeaders=array(
			'Name' => 'Plugin Name',
        );

		foreach ($files as $file) {
			if (substr($file,-4)==".php") {
				$data=get_file_data($file,$defaultHeaders);
				if ($data["Name"]) {
					echo "Loading plugin: ".$data["Name"]."\n";

					require_once $this->getProjectDir()."/".$file;
				}
			}
		}
	}

	/**
	 * Perform the bootstrap.
	 */
	public function bootstrap() {
		$this->install();
		$this->setupTestConfig();

		require_once $this->getTestDir()."/includes/functions.php";

		echo "Bootstrap...\n";
		tests_add_filter('muplugins_loaded',array($this,"loadPlugin"));

		putenv("WP_TEST_BOOTSTRAPPER_DB_CACHE=".$this->getTestDir()."/database.json");
		putenv("WP_TEST_BOOTSTRAPPER_PATH=".__DIR__);

		require_once $this->getTestDir()."/includes/bootstrap.php";
	}
}