<?php

class CoverageCollector
{

	/** @var Mutex */
	private $mutex;

	/** @var SharedVariable of [mixed $fileId => array $coverage] */
	private $data;


	public function __construct(Mutex $mutex)
	{
		$this->mutex = $mutex;

		$this->data = new SharedVariable($mutex, 50 * 1024 * 1024, []);
	}


	/**
	 * @param callable $cb
	 * @param string   $testId file name or other unique identificator
	 */
	public function collect(callable $cb, $testId)
	{
		phpdbg_start_oplog();
		try {
			$cb();
		} finally {
			$coverage = phpdbg_end_oplog(['functions' => TRUE]);
			$this->mutex->synchronized(__CLASS__, function() use ($coverage, $testId) {
				$coverages = $this->data->get();

				assert(!isset($coverages[$testId]), "TestId '$testId' is not unique");
				var_dump('COLLECT OF ', $testId, $coverage);
				$coverages[$testId] = $coverage;
				$this->data->save($coverages);
			});
		}
	}


	/**
	 * @return array [testId => coverage]
	 */
	public function getCoverages()
	{
		return $this->data->get();
	}


	/**
	 * @return void
	 */
	public function destroy()
	{
		$this->data->destroy();
	}

}
