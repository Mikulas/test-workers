<?php


class SharedFileVariable implements ISharedVariable
{

	/** @var Mutex */
	private $mutex;

	/** @var string absolute file path */
	private $file;


	/**
	 * @param Mutex $mutex
	 * @param mixed $initialValue
	 */
	public function __construct(Mutex $mutex, $initialValue = NULL)
	{
		$this->mutex = $mutex;
		$this->file = sys_get_temp_dir() . '/' . bin2hex(random_bytes(24));
	}


	/**
	 * @return mixed
	 */
	public function get()
	{
		return $this->mutex->synchronized([__CLASS__, $this->file], function() {
			if (!file_exists($this->file)) {
				return NULL;
			}
			return unserialize(file_get_contents($this->file));
		});
	}


	/**
	 * @param mixed $data serializable
	 */
	public function set($data)
	{
		$this->mutex->synchronized([__CLASS__, $this->file], function() use ($data) {
			file_put_contents($this->file, serialize($data));
		});
	}


	public function destroy()
	{
		assert(unlink($this->file));
	}

}
