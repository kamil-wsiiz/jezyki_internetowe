<?php

namespace IdeoTree\Api;

use IdeoTree\Models\Callback;

require_once 'controllers/__model/Callback.php';


class Api
{

	/**
	 * @param $status
	 * @param array $values
	 * @param string $error
	 * Return callback object for API
	 */
	public static function Callback($status, $values = [], $error = null)
	{

		$Model = new Callback();

		$Model->setStatus($status);

		if ($status === Callback::API_STATUS_OK) {

			foreach ($values as $key => $value)
				$Model->addValue($key, $value);

		} elseif ($status === Callback::API_STATUS_FAILED)
			$Model->setError($error);

		echo $Model->build();

	}

}