<?php

assert(TRUE);


function inFunction() {
	assert(TRUE);
}
inFunction();


class Foo {
	public function inClass() {
		assert(TRUE);
	}
}
(new Foo())->inClass();
