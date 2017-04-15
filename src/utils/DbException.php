<?php

namespace bootstrapper;

use \Exception;

/**
 * Extract error information from a PDO object or a PDOStatement
 * object.
 */
class DbException extends Exception {

	/**
	 * Constructor.
	 */
	public function __construct($pdo) {
		$errorInfo=$pdo->errorInfo();

		parent::__construct($errorInfo[2],$errorInfo[1]);
	}
}