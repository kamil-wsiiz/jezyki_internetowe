<?php

/**
 * Database singleton
 */


namespace IdeoTree\Managers;

use PDO, PDOException, IdeoTree\Configuration\Config;

require_once 'Config.php';

class Database {

	private static $instance;
	private $_db;
	private $_config;

	public static $err;


	private function __construct() {}
	private function __clone() {}
	private function __sleep() {}
	private function __wakeup() {}

	public static function getInstance() {

		if ( !isset ( self::$instance ) ) {

			self::$instance = new self;
			self::$instance->_config = Config::getInstance();
			self::$instance->_db = self::$instance->getConnection();

		}

		return self::$instance->_db;

	}

	private function getConnection() {

		try {

			$Db = new PDO(

				"mysql:host=".$this->_config->DB_HOST.";
				dbname=".$this->_config->DB_NAME.";",
				$this->_config->DB_USER,
				$this->_config->DB_PASS,
				[PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]

			);

			return $Db;

		} catch (PDOException $error) {

			self::$err = $error;

			die('ERROR WITH DATABASE');

		}

	}

}