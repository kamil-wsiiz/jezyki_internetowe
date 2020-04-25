<?php

/**
 * Session singleton
 */

namespace IdeoTree\Managers;


/**
 * @property $Auth
 * @property $CSRF_TOKEN
 */
class Session
{
	const SESSION_STARTED = TRUE;
	const SESSION_NOT_STARTED = FALSE;

	private $sessionState = self::SESSION_NOT_STARTED;

	private static $instance;


	private function __construct() {}
	private function __clone() {}
	private function __sleep() {}
	private function __wakeup() {}

	public static function getInstance() {

		if (!isset(self::$instance))
			self::$instance = new self;

		self::$instance->startSession();

		return self::$instance;
	}

	private function startSession() {

		if ($this->sessionState == self::SESSION_NOT_STARTED)
			$this->sessionState = session_start();

		return $this->sessionState;

	}

	public function getSessionId() {

		return ($this->sessionState == self::SESSION_STARTED) ? session_id() : null;

	}

	public function __set($name, $value) {

		$_SESSION[$name] = $value;

	}

	public function __get($name) {

		return $_SESSION[$name] ?? null;

	}

	public function __isset($name) {

		return isset($_SESSION[$name]);

	}

	public function __unset($name) {

		unset($_SESSION[$name]);

	}

	public function regenerateId() {

		if ($this->sessionState == self::SESSION_STARTED) {

			session_regenerate_id(true);
			return true;

		}

		return false;

	}

	public function unset() {

		if ($this->sessionState == self::SESSION_STARTED) {

			session_unset();
			return true;

		}

		return false;

	}

	public function destroy() {

		if ($this->sessionState == self::SESSION_STARTED) {

			$this->sessionState = !session_destroy();
			unset($_SESSION);
			return !$this->sessionState;

		}

		return false;
	}
}