<?php

/**
 * Get a snapshot and restore a database state.
 */
class DbState {

	private $pdo;

	/**
	 * Construct.
	 */
	public function __construct($pdo) {
		$this->pdo=$pdo;
	}

	/**
	 * 
	 */
	private function getPrimaryKeyColumnForTable($tableName) {
	}

	/**
	 * Returns the current state of the database represented as an array.
	 * The exact format of the array should thought of as being opaque, and
	 * it should only be used to pass as a parameter to the restoreState
	 * function.
	 */
	public function getState() {
		$q=$this->pdo->query("SHOW TABLES");
		$rows=$q->fetchAll(PDO::FETCH_NUM);
		$tableDatas=array();

		foreach ($rows as $row) {
			$tableName=$row[0];
			$tableData=array();
			$tableData["name"]=$tableName;

			$q=$this->pdo->query("SHOW CREATE TABLE $tableName");
			$row=$q->fetch(PDO::FETCH_NUM);
			$tableData["create"]=$row[1];

			$q=$this->pdo->query("SELECT * FROM $tableName");
			$tableData["data"]=$q->fetchAll(PDO::FETCH_ASSOC);

			$tableDatas[]=$tableData;
		}

		return $tableDatas;
	}

	/**
	 * Restore a previously snapshotted state.
	 */
	public function restoreState($state) {
		foreach ($state as $tableState) {
			
		}
	}
}