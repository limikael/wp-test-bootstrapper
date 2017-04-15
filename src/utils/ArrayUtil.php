<?php

/**
 * Array utilities.
 */
class ArrayUtil {

	/**
	 * Given an array of arrays, this function creates a new array
	 * containing the original sub-arrays, but indexed on values from
	 * a given field in the sub-arrays.
	 */
	public function indexBy($a, $field) {
		$res=array();

		foreach ($a as $item)
			$res[$item[$field]]=$item;

		return $res;
	}
}