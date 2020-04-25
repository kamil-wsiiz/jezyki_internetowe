<?php

namespace IdeoTree\Controllers;

use IdeoTree\Configuration\Config, IdeoTree\Models\Callback, IdeoTree\Managers\Session, IdeoTree\Api\Api;

require_once 'controllers/__class/Session.php';
require_once 'controllers/__class/Config.php';
require_once 'controllers/__class/Api.php';

class AuthController
{

	private $Session;
	private $Config;

	public function __construct()
	{

		$this->Session = Session::getInstance();
		$this->Config = Config::getInstance();

	}

	/**
	 * @param $pass
	 * Authorize user' password
	 */
	public function Auth($pass)
	{

		if ($pass === $this->Config->ADMIN_PASS) {

			$this->initAdmin();
			Api::Callback(Callback::API_STATUS_OK, ['status' => true, 'data' => $this->getCSRFToken()]);

		} else Api::Callback(Callback::API_STATUS_OK, ['status' => false, 'data' => 'Incorrect data.']);

	}

	/**
	 * Initialize session
	 */
	public function initAdmin()
	{

		$this->Session->regenerateId();
		$this->Session->Auth = true;

	}

	/**
	 * @return bool
	 * Check if user has privilages
	 */
	public function isAdmin()
	{

		return $this->Session->Auth;

	}

	/**
	 * @param int $bytes
	 * @return string
	 * Generate and returning csrf token
	 */
	public function getCSRFToken($bytes = 32) {

		$Session = Session::getInstance();

		if ($Session->CSRF_TOKEN === NULL) {

			return $Session->CSRF_TOKEN = bin2hex(random_bytes($bytes));

		} else return $Session->CSRF_TOKEN;

	}

}