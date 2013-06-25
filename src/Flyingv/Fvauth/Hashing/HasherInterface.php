<?php namespace Flyingv\Fvauth\Hashing;

interface HasherInterface {

	public function hash($string);

	public function checkhash($string, $hashedString);

}
