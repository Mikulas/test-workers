<?php


class SharedMemoryVariable implements ISharedVariable
{

	/** @var int position */
	const SIZE_OFFSET = 0;

	/**
	 * number of bytes in serialize(PHP_INT_MAX)
	 * @var int bytes
	 */
	const SIZE_LENGTH = 22;

	/** @var int position */
	const MEMORY_OFFSET = self::SIZE_LENGTH;


	/** @var Mutex */
	private $mutex;

	/** @var int */
	private $shmId;

	/** @var bool */
	private $destroyed = FALSE;


	/**
	 * @param Mutex $mutex
	 * @param int   $bytesToAllocate
	 * @param NULL  $initValue
	 */
	public function __construct(Mutex $mutex, int $bytesToAllocate, $initValue = NULL)
	{
		$this->mutex = $mutex;

		$shmKey = ftok(__FILE__, 't');
		$this->shmId = shmop_open($shmKey, 'c', 0644, $bytesToAllocate);
		assert($this->shmId, "Could not create shared memory segment");

		$this->set($initValue);
	}


	/**
	 * @return mixed
	 */
	public function get()
	{
		assert(!$this->destroyed);
		return $this->mutex->synchronized([__CLASS__, $this->shmId], function() {
			$memory = shmop_read($this->shmId, self::SIZE_OFFSET, self::SIZE_LENGTH);
			assert($memory !== FALSE, 'Could not read size of shared memory variable');
			$size = unserialize($memory);
			assert($size !== FALSE, 'Could not unserialize memory');

			$memory = shmop_read($this->shmId, self::MEMORY_OFFSET, $size);
			assert($memory !== FALSE, 'Could not read shared memory of variable');
			$variable = unserialize($memory);
			assert($size !== FALSE, 'Could not unserialize memory');

			return $variable;
		});
	}


	/**
	 * @param mixed $variable serializable
	 * @return void
	 */
	public function set($variable)
	{
		assert(!$this->destroyed);
		$this->mutex->synchronized([__CLASS__, $this->shmId], function() use ($variable) {
			$memory = serialize($variable);
			$written = shmop_write($this->shmId, $memory, self::MEMORY_OFFSET);
			assert($written !== FALSE && $written === strlen($memory), 'Could not write to shared memory');

			$size = serialize(strlen($memory));
			$written = shmop_write($this->shmId, $size, self::SIZE_OFFSET);
			assert($written !== FALSE && $written === strlen($size), 'Could not write to shared memory');
		});
	}


	public function destroy()
	{
		assert(!$this->destroyed);
		$this->destroyed = TRUE;
		shmop_delete($this->shmId);
	}

}
