<?php

/**
 * Validation class
 */

namespace IdeoTree\Api;

use ReflectionClass;

class Validator {

	public static function isValid($var, $type, $flags = []) {

		switch ($type)
		{

			case 'boolean':
				return (filter_var($var, FILTER_VALIDATE_BOOLEAN));
				break;

			case 'integer':
				return (is_int($var));
				break;

			case 'float':
				return (is_float($var));
				break;

			case 'number':
				if (!ctype_digit(ltrim(strval($var), '-'))) return false;

				if (array_key_exists('min', $flags))
					if ($var < $flags['min']) return false;

				if (array_key_exists('max', $flags))
					if ($var > $flags['max']) return false;

				return true;

				break;

			case 'object':

				//Object builder

				if (!array_key_exists('model', $flags))
					return false;

				$path = "controllers/models/$flags[model].php";
				$class = "Models\\" . $flags['model'];

				if (!file_exists($path))
					return false;

				require_once $path;

				if (!class_exists($class))
					return false;

				$Reflection = new ReflectionClass($class);
				$paramNumber = $Reflection->getConstructor()->getNumberOfParameters();

				$Json = json_decode($var, true);

				if ($paramNumber !== count($Json))
					return false;

				if (array_key_exists('restrict', $flags) || $flags['restrict']) {

					if (array_column($Reflection->getConstructor()->getParameters(), 'name') !== array_keys($Json))
						return false;

				}

				return $Reflection->newInstanceArgs(array_values($Json));

				break;

			case 'string':
				if (!is_string($var)) return false;

				if (array_key_exists('min', $flags))
					if (mb_strlen($var, "UTF-8") < $flags['min']) return false;

				if (array_key_exists('max', $flags))
					if (mb_strlen($var, "UTF-8") > $flags['max']) return false;

				if (array_key_exists('only_letters', $flags))
					if (!preg_match('/^[\p{L}]+$/u', $var)) return false;

				if (array_key_exists('only_lower', $flags))
					if (!preg_match('/^[\p{L}]+$/u', $var) || mb_strtolower($var, "UTF-8") !== $var) return false;

				if (array_key_exists('only_upper', $flags))
					if (!preg_match('/^[\p{L}]+$/u', $var) || mb_strtoupper($var, "UTF-8") !== $var) return false;

				if (array_key_exists('without_digits', $flags))
					if (preg_match('/\\d/', $var)) return false;

				if (array_key_exists('without_special', $flags))
					if (!preg_match('/^[\p{L}0-9]+$/u', $var)) return false;

				if (array_key_exists('without_special_with_space', $flags))
					if (!preg_match('/^[\p{L}0-9 ]+$/u', $var)) return false;

				if (array_key_exists('min_one_upper', $flags))
					if (!preg_match('/\p{Lu}/u', $var)) return false;

				if (array_key_exists('min_one_digit', $flags))
					if (!preg_match('/\\d/', $var)) return false;

				if (array_key_exists('min_one_special', $flags))
					if (preg_match('/^[\p{L}0-9]+$/u', $var)) return false;

				if (array_key_exists('no_whitespaces', $flags))
					if (preg_match('/\s/', $var)) return false;

				if (array_key_exists('bool', $flags))
					if ($var !== 'true' || $var !== 'false') return false;

				return true;

				break;

			default:
				return false;

		}

	}

	public static function isPattern($var, $type)
	{

		switch ($type)
		{

			case 'name':
				return self::isValid($var, 'string', ['min' => 3, 'max' => 50, 'without_special_with_space' => true]);
				break;

			default:
				return false;

		}

	}

}