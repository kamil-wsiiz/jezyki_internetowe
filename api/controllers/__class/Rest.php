<?php

namespace IdeoTree\Api;

use Exception, IdeoTree\Models, IdeoTree\Configuration\Config, IdeoTree\Controllers\AuthController;

require_once 'controllers/__model/Route.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/__class/Validator.php';
require_once 'Config.php';

class Rest
{
	const ONLY_ADMIN = 1;

	private $routes = [];
	private $_config;

	private $url;
	private $method;
	private $query;


	public function __construct($url, $method)
	{
		$this->_config = Config::getInstance();

		$parseUrl = parse_url($url);

		//Assign url params
		$split = explode("/", rtrim(preg_replace('/' . preg_quote($this->_config->BASE_URL, '/') . '/i', '', $parseUrl["path"], 1), "/\\"));

		$this->url = strtolower($split[0]);
		$this->query = ($method === 'GET') ? (array_splice($split, 1) ?? NULL) : (($method === 'DELETE') ? $_GET : json_decode(file_get_contents('php://input'), true));
		$this->method = $method;

	}

	public function addRoute($url, $method, $params, $controller, $loginStatus, $verifyCSRF = false)
	{
		//Build route model
		$Route = new Models\Route;

		$Route->url = strtolower($url);
		$Route->method = strtoupper($method);
		$Route->params = $params;
		$Route->controller = $controller;
		$Route->loginStatus = $loginStatus;
		$Route->verifyCSRF = $verifyCSRF;

		array_push($this->routes, $Route);
	}

	private function getFunc(Models\Route $route, $queries)
	{

		//Check controller' function
		$controller = explode('@', $route->controller);
		$path = "controllers/$controller[0].php";
		$class = "IdeoTree\\Controllers\\" . $controller[0];
		$method = $controller[1];

		if (!file_exists($path))
			throw new Exception("File doesn't exist.");

		require_once $path;

		if (!class_exists($class))
			throw new Exception("Class doesn't exist.");

		$classInit = new $class();

		if (!method_exists($classInit, $method))
			throw new Exception("Method doesn't exist.");

		call_user_func_array(array($classInit, $method), $queries);

	}

	private function getRequiredQueryCount($params) {

		//Required count of param

		$count = 0;

		foreach ($params as $param) {

			if (!$param[3])
				$count++;

		}

		return $count;

	}

	public function Run()
	{

		foreach($this->routes as $route)
		{

			if ($route->url === $this->url && $route->method === $this->method)
			{

				if (count($this->query) >= $this->getRequiredQueryCount($route->params)) {

					$queries = [];
					if ($this->method === 'GET') $i = 0;

					foreach ($route->params as $param) {

						$queryIndex = ($this->method === 'GET') ? $i++ : $param[0];

						//Validate params
						if ($param[1] === 'pattern')
							$Validate = Validator::isPattern($this->query[$queryIndex], $param[2]);
						else
							$Validate = Validator::isValid($this->query[$queryIndex], $param[1], $param[2]);

						if (!$Validate)
							return 500;

						array_push($queries, ($param[1] === 'object') ? $Validate : $this->query[$queryIndex]);

					}

				} else return 500;

				//User has privilages for action
				if ($route->loginStatus !== null)
				{

					$Auth = new AuthController();
					$loginStatus = $Auth->isAdmin();

					if ($route->loginStatus === self::ONLY_ADMIN && !$loginStatus)
						return 401;

					//CSRF Token
					if ($route->verifyCSRF && $this->query['csrf'] !== $Auth->getCSRFToken())
						return 401;

				}

				$this->getFunc($route, $queries);

				return 200;
			}

		}

		return 404;

	}

}