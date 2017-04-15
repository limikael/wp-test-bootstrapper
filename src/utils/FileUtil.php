<?php

/**
 * File utilities.
 */
class FileUtil {

	/**
	 * Copy a directory recursivly.
	 */
	public static function recursiveCopy($src,$dst) { 
		$dir = opendir($src); 
		if (!$dir)
			throw new Exception("Unable to open src dir");

		if (!mkdir($dst,0755,TRUE))
			throw new Exception("Unable to create dest dir");

		while(false !== ( $file = readdir($dir)) ) { 
			if (( $file != '.' ) && ( $file != '..' )) { 
				if ( is_dir($src . '/' . $file) ) { 
					FileUtil::recursiveCopy($src . '/' . $file,$dst . '/' . $file);
				} 
				else { 
					if (!copy($src . '/' . $file,$dst . '/' . $file))
						throw new Exception("Unable to copy file");
				} 
			} 
		} 
		closedir($dir); 
	} 
}