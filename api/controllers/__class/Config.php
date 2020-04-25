<?php

/**
 * Config singleton
 */

namespace IdeoTree\Configuration;

/**
 * @property string BASE_URL
 * @property string DB_HOST
 * @property string DB_USER
 * @property string DB_PASS
 * @property string DB_NAME
 * @property string ADMIN_PASS
 * @property string ROOT_DIRECTORY
 * @property string DEFAULT_NAME
 */
class Config
{

	private static $instance;
	private $file = 'config.ini';
	private $parser;


	private function __construct() {}
	private function __clone() {}
	private function __sleep() {}
	private function __wakeup() {}


	public static function getInstance() {

		if (!isset(self::$instance)) {

			self::$instance = new self;
			self::$instance->Parse();

		}

		return self::$instance;

	}

	private function Parse() {

		if (!file_exists($this->file))
			die("Configuration file not found.");

		$this->parser = parse_ini_file($this->file);

		if (!$this->parser)
			die("Couldn't parse the config.");

		return $this->parser;

	}

	public function __get($option)
	{

		return (key_exists($option, $this->parser)) ?  $this->parser[$option] : null;

	}

}