<?php

require_once __DIR__."/ArrayUtil.php";
require_once __DIR__."/DbException.php";

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
	 * Get primary key column given a table.
	 */
	private function getPrimaryKeyColumnsForTable($tableName) {
		$q=$this->pdo->query("SHOW KEYS FROM $tableName WHERE Key_name='PRIMARY'");
		if (!$q)
			throw new DbException($this->pdo);

		$rows=$q->fetchAll(PDO::FETCH_ASSOC);

		if (!sizeof($rows))
			throw new Exception("Expected at least one key column");

		return $rows[0]["Column_name"];
	}

	/**
	 * Returns the current state of the database represented as an array.
	 * The exact format of the array should thought of as being opaque, and
	 * it should only be used to pass as a parameter to the restoreState
	 * function.
	 */
	public function getState() {
		$q=$this->pdo->query("SHOW TABLES");
		if (!$q)
			throw new DbException($this->pdo);

		$rows=$q->fetchAll(PDO::FETCH_NUM);
		$tableDatas=array();

		foreach ($rows as $row) {
			$tableName=$row[0];
			$tableData=array();
			$tableData["name"]=$tableName;

			$q=$this->pdo->query("SHOW CREATE TABLE $tableName");
			if (!$q)
				throw new DbException($this->pdo);

			$row=$q->fetch(PDO::FETCH_NUM);
			$tableData["create"]=$row[1];

			$q=$this->pdo->query("SELECT * FROM $tableName");
			$tableData["data"]=$q->fetchAll(PDO::FETCH_ASSOC);

			$tableDatas[]=$tableData;
		}

		return $tableDatas;
	}

	/**
	 * Insert a record of data.
	 */
	public function insertData($tableName, $data) {
		$set=array();
		foreach ($data as $key=>$value)
			$set[]=$key."=".$this->pdo->quote($value);

		$qs="INSERT INTO $tableName SET ".join(",",$set);
		$res=$this->pdo->exec($qs);
		if ($res===FALSE)
			throw new DbException($this->pdo);
	}

	/**
	 * Update a record of data.
	 */
	public function updateData($tableName, $data) {
		$keyColumn=$this->getPrimaryKeyColumnsForTable($tableName);

		$set=array();
		foreach ($data as $key=>$value)
			if ($key!=$keyColumn)
				$set[]=$key."=".$this->pdo->quote($value);

		$qs="UPDATE $tableName SET ".join(",",$set).
			" WHERE $keyColumn=".$this->pdo->quote($data[$keyColumn]);

		$res=$this->pdo->exec($qs);
		if ($res===FALSE)
			throw new DbException($this->pdo);
	}

	/**
	 * Restore a previously snapshotted state.
	 */
	public function restoreState($expectedState) {
		$currentTables=array();
		$q=$this->pdo->query("SHOW TABLES");
		if (!$q)
			throw new DbException($this->pdo);

		$rows=$q->fetchAll(PDO::FETCH_NUM);
		foreach ($rows as $row)
			$currentTables[]=$row[0];

		foreach ($expectedState as $expectedTableState) {
			$tableName=$expectedTableState["name"];

			if (!in_array($tableName,$currentTables))
				$this->pdo->exec($expectedTableState["create"]);

			$keyColumn=$this->getPrimaryKeyColumnsForTable($tableName);

			$q=$this->pdo->query("SELECT * FROM $tableName");
			$currentData=$q->fetchAll(PDO::FETCH_ASSOC);

			$currentIndexedData=ArrayUtil::indexBy($currentData,$keyColumn);
			$expectedIndexedData=ArrayUtil::indexBy($expectedTableState["data"],$keyColumn);

			$toUpdate=array_intersect(array_keys($expectedIndexedData),array_keys($currentIndexedData));
			foreach ($toUpdate as $id)
				if ($currentIndexedData[$id]!=$expectedIndexedData[$id])
					$this->updateData($tableName,$expectedIndexedData[$id]);

			$toDelete=array_diff(array_keys($currentIndexedData),array_keys($expectedIndexedData));
			foreach ($toDelete as $id) {
				$res=$this->pdo->exec(
					"DELETE FROM $tableName WHERE $keyColumn=".
					$this->pdo->quote($id)
				);

				if ($res===FALSE)
					throw new DbException($this->pdo);
			}

			$toCreate=array_diff(array_keys($expectedIndexedData),array_keys($currentIndexedData));
			foreach ($toCreate as $createId)
				$this->insertData($tableName,$expectedIndexedData[$createId]);
		}
	}
}