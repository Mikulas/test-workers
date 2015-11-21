<?php

//
// Do not include
//


/**
 * @link https://github.com/php/php-src/blob/master/sapi/phpdbg/phpdbg.c#L415
 * @return void
 */
function phpdbg_start_oplog() {};


/**
 * @link https://github.com/php/php-src/blob/master/sapi/phpdbg/phpdbg.c#L585
 * @param array $options [
 *   functions => bool,
 *   opcodes => bool,
 * ]
 * @return array
 */
function phpdbg_end_oplog(array $options = NULL) {};


/**
 * @link https://github.com/php/php-src/blob/master/sapi/phpdbg/phpdbg.c#L487
 * @param array $options [
 *   functions => bool,
 *   opcodes => bool,
 *   files => string[],
 * ]
 * @return array
 */
function phpdbg_get_executable(array $options = NULL) {};


die('Documentation only, do not include');
