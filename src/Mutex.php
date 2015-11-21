<?php

/**
 * @author Jan Tvrdík
 * @author Mikulas Dite
 */
class Mutex
{

	/** @var string */
	private $dir;

	/** @var array (name => handle) */
	private $locks;


	/**
	 * @param string $dir
	 */
	public function __construct($dir)
	{
		$this->dir = $dir;
	}


	/**
	 * @param  callable $callback
	 * @return mixed value returned by callback
	 */
	public function synchronizedStdOut(callable $callback)
	{
		return $this->synchronized('stdout', $callback);
	}


	/**
	 * @param  mixed    $key
	 * @param  callable $callback
	 * @return mixed value returned by callback
	 */
	protected function synchronized($key, callable $callback)
	{
		$this->lock($key);

		try {
			return $callback();
		} finally {
			$this->unlock($key);
		}
	}


	/**
	 * @param  mixed $key
	 * @return void
	 */
	protected function lock($key)
	{
		$key = $this->getKey($key);
		assert(!isset($this->locks[$key]), 'Trying to acquire the same lock multiple times');

		$path = $this->dir . '/lock-' . $key;
		$this->locks[$key] = fopen($path, 'w');
		flock($this->locks[$key], LOCK_EX);
	}


	/**
	 * @param  mixed $key
	 * @return void
	 */
	protected function unlock($key)
	{
		$key = $this->getKey($key);
		assert(isset($this->locks[$key]), 'Trying to release a lock which has been already released');

		flock($this->locks[$key], LOCK_UN);
		fclose($this->locks[$key]);
		unset($this->locks[$key]);
	}


	/**
	 * @param  mixed $key
	 * @return string
	 * @throws InvalidArgumentException
	 */
	protected function getKey($key)
	{
		if (is_string($key)) {
			return md5($key);
		} elseif (is_array($key)) {
			return md5(implode("\x00", $key));
		} else {
			throw new InvalidArgumentException();
		}
	}

}
