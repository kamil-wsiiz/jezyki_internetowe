<?php

namespace IdeoTree\Models;


class Callback
{

	const API_STATUS_UNKNOWN_ERROR = 0;
	const API_STATUS_OK = 1;
	const API_STATUS_FAILED = 2;

	private $status = false;
	private $values = [];

	private $error = null;


	public function setStatus($status)
	{

		if ($status === self::API_STATUS_UNKNOWN_ERROR || $status === self::API_STATUS_OK || $status === self::API_STATUS_FAILED)
		{

			$this->status = $status;
			return true;

		} else return false;

	}

	public function setError($str)
	{

		$this->error = $str;

	}

	public function addValue($name, $value)
	{

		$this->values[$name] = $value;

	}

	public function build()
	{

		switch($this->status)
		{

			case '0':
				return json_encode(['status' => $this->status]);
				break;

			case '1':
				return json_encode(['status' => $this->status, 'values' => $this->values]);
				break;

			case '2':
				return json_encode(['status' => $this->status, 'error' =>  $this->error]);
				break;

			default:
				return false;

		}


	}

}