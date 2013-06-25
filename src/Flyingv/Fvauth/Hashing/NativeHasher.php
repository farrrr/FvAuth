<?php namespace Flyingv\Fvauth\Hashing;

class NativeHasher implements HasherInterface {

	/**
	 * Hash string.
	 *
	 * @param  string  $string
	 * @return string
	 */
	public function hash($string)
	{
		// Usually caused by an old PHP environment, see
		// https://github.com/cartalyst/sentry/issues/98#issuecomment-12974603
		// and https://github.com/ircmaxell/password_compat/issues/10
		if ( ! function_exists('password_hash'))
		{
			throw new \RuntimeException('The function password_hash() does not exist, your PHP environment is probably incompatible. Try running [vendor/ircmaxell/password-compat/version-test.php] to check compatibility or use an alternative hashing strategy.');
		}

		if (($hash = password_hash($string, PASSWORD_DEFAULT)) === false)
		{
			throw new \RuntimeException('Error generating hash from string, your PHP environment is probably incompatible. Try running [vendor/ircmaxell/password-compat/version-test.php] to check compatibility or use an alternative hashing strategy.');
		}

		return $hash;
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
		return password_verify($string, $hashedString);
	}

}
