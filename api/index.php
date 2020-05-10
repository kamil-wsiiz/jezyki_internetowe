<?php


use IdeoTree\Api\Rest;

require_once 'controllers/__class/Rest.php';

$Rest = new Rest($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

$Rest->addRoute('dir', 'GET', [], 'TreeController@Default', null);
$Rest->addRoute('fullDir', 'GET', [], 'TreeController@allDir', null);
$Rest->addRoute('subDir', 'GET', [['id', 'number', [], false]], 'TreeController@childsByParent', null);
$Rest->addRoute('auth', 'POST', [['pass', 'string', [], false]], 'AuthController@Auth', null);
$Rest->addRoute('dir', 'POST', [['parent_id', 'number', [], false]], 'TreeController@addDir', Rest::ONLY_ADMIN, true);
$Rest->addRoute('dir', 'PUT', [['id', 'number', [], false], ['newName', 'pattern', 'name', false]], 'TreeController@editDir', Rest::ONLY_ADMIN, true);
$Rest->addRoute('dir', 'DELETE', [['id', 'number', [], false]], 'TreeController@deleteDir', Rest::ONLY_ADMIN, true);
$Rest->addRoute('setParent', 'POST', [['id', 'number', [], false], ['new_parent', 'number', [], false]], 'TreeController@moveDir', Rest::ONLY_ADMIN, true);

try {

	switch ($Rest->Run()) {

		case 500:
			header('HTTP/1.1 500');
			break;

		case 404:
			header('HTTP/1.1 404');
			break;

		case 401:
			header('HTTP/1.1 401');
			break;

		default:
			header('Content-type: application/json');
			header("x-content-type-options: nosniff");

	};

} catch (Exception $e) {
	exit($e->getMessage());
}
