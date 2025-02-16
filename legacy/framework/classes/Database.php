<?php
/**
Database
Developed by Noah Negin-Ulster
**/

//Temporary Database Fix
function sql_query($a) { return mysqli_query(Database::getCon(), $a); }
function sql_num_rows($a) { return mysqli_num_rows($a); }
function sql_fetch_array($a) { return mysqli_fetch_array($a); }
function sql_connect($a, $b, $c) { return Database::getCon(); }
function sql_select_db($a) { return mysqli_select_db($a); }
function sql_insert_id() { return sql_insert_id(); }
function sql_real_escape_string($a) { return mysqli_real_escape_string($a); }

class Database
{
	const DB_USERNAME = 'root';
	const DB_PASSWORD = 'root';
	const DB_HOST = 'localhost';
	const DB_DATABASE = 'redreadu_brevada';
	
	private static $Connection = NULL;
	
	public static function getCon()
	{
		if(!isset(self::$Connection))
		{
			self::$Connection = mysqli_connect(self::DB_HOST, self::DB_USERNAME, self::DB_PASSWORD, self::DB_DATABASE);
			if(self::$Connection->connect_error)
			{
				exit("Our servers are being updated. Please wait a couple minutes before refreshing this page.");
			}
		}
		return self::$Connection;
	}
	
	public static function query($q){ return self::getCon()->query($q); }
	public static function prepare($q){ return self::getCon()->prepare($q); }
	
	public static function escape_string($s)
	{
		return empty($s) ? '' : self::getCon()->real_escape_string($s);
	}
}
?>
