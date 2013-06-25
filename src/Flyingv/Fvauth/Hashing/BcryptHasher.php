<?php namespace Flyingv\Fvauth\Hashing;

class BcryptHasher implements HasherInterface {

	/**
	 * Hash strength.
	 *
	 * @var int
	 */
	public $strength = 8;

	/**
	 * Salt length.
	 *
	 * @var int
	 */
	public $saltLength = 22;

	/**
	 * Hash string.
	 *
	 * @param  string  $string
	 * @return string
	 */
	public function hash($string)
	{
		// Format strength
		$strength = str_pad($this->strength, 2, '0', STR_PAD_LEFT);

		// Create salt
		$salt = $this->createSalt();

		return crypt($string, '$2a$'.$strength.'$'.$salt.'$');
	}

	/**
	 * Check string against hashed string.
	 *
	 * @param  string  $string
	 * @param  string  $hashedString
	 * @return bool
	 */
	public function checkhash($string, $hashedString)
	{
		return crypt($string, $hashedString) === $hashedString;
	}

	/**
	 * Create a random string for a salt.
	 *
	 * @return string
	 */
	public function createSalt()
	{
		$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		return substr(str_shuffle(str_repeat($pool, 5)), 0, $this->saltLength);
	}

}
