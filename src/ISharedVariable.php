<?php


interface ISharedVariable
{

	public function get();

	public function set($data);

	public function destroy();

}
