<?php

namespace Mikulas\TestWorkers\Tests;


/**
 * This class only for being covered
 */
class Model
{

	public function a()
	{
		$a = [];
		$a[] = 10;
		return $a;
	}

	public function b()
	{
		$a = [1, 2];
		$b = [2, 3];
		return array_diff($a, $b);
	}

}
