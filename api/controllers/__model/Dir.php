<?php

namespace IdeoTree\Models;


class Dir
{

	public $id;
	public $name;
	public $childVisible = false;
	public $flags = [];
	public $hasChilds = false;
	public $childs = [];
	public $sort = true;

}