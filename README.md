# Test Workers

[![Build Status](https://magnum.travis-ci.com/Mikulas/test-workers.svg?token=oqMcqtvnR48QUSpdAyLu&branch=master)](https://magnum.travis-ci.com/Mikulas/test-workers)
[![Coverage Status](https://coveralls.io/repos/Mikulas/test-workers/badge.svg?branch=master&service=github)](https://coveralls.io/github/Mikulas/test-workers?branch=master)
[![Api Documentation](https://img.shields.io/badge/api-master-ff69b4.svg)](https://codedoc.pub/Mikulas/test-workers/master/index.html)

PHP 7 alternative to PHPUnit, Nette Tester and other tools.

## Features

- Parallel test execution
- Tests directly executable
- Minimal footprint: your test files can be as simple as `<?php assert(TRUE);`
- Extremely fast: preload option in combination with forking processes and memory sharing removes overhead of loading dependencies for each test
- Same code coverage output options as PHPUnit


## Requirements

- `PHP >=7.0.0RC7` with phpdbg


## [Documentation](doc/index.md)

See [doc/index.md](doc/index.md) for usage documentation.

[Api documentation](https://codedoc.pub/Mikulas/test-workers/master/index.html)


## License

MIT. See full [license](license.md).
