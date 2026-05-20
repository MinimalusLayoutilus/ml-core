<?php

/*
 * Copyright (C) 2013 Michael Hegenbarth (carschrotter) <mnh@mn-hegenbarth.de>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

namespace mnhcc\ml\classes;
{

    /**
     * Default class for classes in mnhcc namespace implement this functions
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     * @copyright (c) 2012, Michael Hegenbarth
     * @license GPL  
     */
    abstract class Filesystem {

	/**
	 * 
	 * @param string $filename
	 * @return bool
	 */
	public static function fileExists($filename) {
	    return \file_exists($filename);
	}

	/**
	 * Given a string containing a directory, this function will return the number of bytes available on the corresponding filesystem or disk partition.
	 * @param string $directory A directory of the filesystem or disk partition.
	 * @return float Returns the number of available bytes as a float or FALSE on failure.
	 */
	public static function diskFreeSpace($directory) {
	    return \disk_free_space($directory);
	}

	/**
	 * Given a string containing a directory, this function will return the total number of bytes on the corresponding filesystem or disk partition.
	 * @param string $directory A directory of the filesystem or disk partition.
	 * @return float Returns the total number of bytes as a float or FALSE on failure.
	 */
	public static function diskTotalSpace($directory) {
	    return disk_total_space($directory);
	}

	/**
	 * Given a string containing a directory, this function will return a arra whit the total number of bytes and number of bytes available on the corresponding filesystem or disk partition.
	 * @param string $directory A directory of the filesystem or disk partition.
	 * @return array Returns a array whit the total number of bytes as a float or FALSE on failure. 
	 */
	public static function diskSpace($directory) {
	    return ['free' => self::diskFreeSpace($directory), 'total' => self::diskTotalSpace($directory),];
	}

	/**
	 * 
	 * @param string $path
	 * @param string $suffix (optional)
	 * @return string
	 */
	public static function basename($path, $suffix, $php4 = false) {
	    if ($php4)
		$path = rtrim($path, $suffix);
	    return \basename($path, $suffix);
	}

//basename
//chgrp
//chmod
//chown
//clearstatcache
//copy
//delete
//dirname
//disk_free_space
//disk_total_space
//diskfreespace
//fclose
//feof
//fflush
//fgetc
//fgetcsv
//fgets
//fgetss
//file_exists
//file_get_contents
//file_put_contents
//file
//fileatime
//filectime
//filegroup
//fileinode
//filemtime
//fileowner
//fileperms
//filesize
//filetype
//flock
//fnmatch
//fopen
//fpassthru
//fputcsv
//fputs
//fread
//fscanf
//fseek
//fstat
//ftell
//ftruncate
//fwrite
//glob
//is_dir
//is_executable
//is_file
//is_link
//is_readable
//is_uploaded_file
//is_writable
//is_writeable
//lchgrp
//lchown
//link
//linkinfo
//lstat
//mkdir
//move_uploaded_file
//parse_ini_file
//parse_ini_string
//pathinfo
//pclose
//popen
//readfile
//readlink
//realpath_cache_get
//realpath_cache_size
//realpath
//rename
//rewind
//rmdir
//set_file_buffer
//stat
//symlink
//tempnam
//tmpfile
//touch
//umask
//unlink
    }

}
