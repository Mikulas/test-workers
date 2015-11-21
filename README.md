Test Workers
============

[![Build Status](https://magnum.travis-ci.com/Mikulas/test-workers.svg?token=oqMcqtvnR48QUSpdAyLu&branch=master)](https://magnum.travis-ci.com/Mikulas/test-workers)

PHP 7 alternative to PHPUnit, Nette Tester and other tools.

Features
========

- Parallel test execution
- Tests directly executable
- Minimal footprint: your test files can be as simple as `<?php assert(TRUE);`
- Extremely fast: preload option in combination with forking processes and memory sharing removes overhead of loading dependencies for each test
