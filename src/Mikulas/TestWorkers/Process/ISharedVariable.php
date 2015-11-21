<?php

namespace Mikulas\TestWorkers;


interface ISharedVariable
{

	public function get();

	public function set($data);

	public function destroy();

}
