<?php namespace Flyingv\Fvauth;

use Flyingv\Fvauth\Cookies\IlluminateCookie;
use Flyingv\Fvauth\Groups\Eloquent\Provider as GroupProvider;
use Flyingv\Fvauth\Hashing\BcryptHasher;
use Flyingv\Fvauth\Hashing\NativeHasher;
use Flyingv\Fvauth\Hashing\Sha256Hasher;
use Flyingv\Fvauth\Fvauth;
use Flyingv\Fvauth\Sessions\IlluminateSession;
use Flyingv\Fvauth\Throttling\Eloquent\Provider as ThrottleProvider;
use Flyingv\Fvauth\Users\Eloquent\Provider as UserProvider;
use Illuminate\Support\ServiceProvider;

class FvauthServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
        $this->package('flyingv/fvauth', 'flyingv/fvauth');

		$this->observeEvents();
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerHasher();

		$this->registerUserProvider();

		$this->registerGroupProvider();

		$this->registerThrottleProvider();

		$this->registerSession();

		$this->registerCookie();

		$this->registerFvauth();
	}

	protected function registerHasher()
	{
		$this->app['fvauth.hasher'] = $this->app->share(function($app)
		{
			$hasher = $app['config']['flyingv/fvauth::hasher'];

			switch ($hasher)
			{
				case 'native':
					return new NativeHasher;
					break;

				case 'bcrypt':
					return new BcryptHasher;
					break;

				case 'sha256':
					return new Sha256Hasher;
					break;
			}

			throw new \InvalidArgumentException("Invalid hasher [$hasher] chosen for Fvauth.");
		});
	}

	protected function registerUserProvider()
	{
		$this->app['fvauth.user'] = $this->app->share(function($app)
		{
			$model = $app['config']['flyingv/fvauth::users.model'];

			// We will never be accessing a user in fvauth without accessing
			// the user provider first. So, we can lazily setup our user
			// model's login attribute here. If you are manually using the
			// attribute outside of fvauth, you will need to ensure you are
			// overriding at runtime.
			if (method_exists($model, 'setLoginAttribute'))
			{
				$loginAttribute = $app['config']['flyingv/fvauth::users.login_attribute'];

				forward_static_call_array(
					array($model, 'setLoginAttribute'),
					array($loginAttribute)
				);
			}

			return new UserProvider($app['fvauth.hasher'], $model);
		});
	}

	protected function registerGroupProvider()
	{
		$this->app['fvauth.group'] = $this->app->share(function($app)
		{
			$model = $app['config']['flyingv/fvauth::groups.model'];

			return new GroupProvider($model);
		});
	}

	protected function registerThrottleProvider()
	{
		$this->app['fvauth.throttle'] = $this->app->share(function($app)
		{
			$model = $app['config']['flyingv/fvauth::throttling.model'];

			$throttleProvider = new ThrottleProvider($app['fvauth.user'], $model);

			if ($app['config']['flyingv/fvauth::throttling.enabled'] === false)
			{
				$throttleProvider->disable();
			}

			if (method_exists($model, 'setAttemptLimit'))
			{
				$attemptLimit = $app['config']['flyingv/fvauth::throttling.attempt_limit'];

				forward_static_call_array(
					array($model, 'setAttemptLimit'),
					array($attemptLimit)
				);
			}
			if (method_exists($model, 'setSuspensionTime'))
			{
				$suspensionTime = $app['config']['flyingv/fvauth::throttling.suspension_time'];

				forward_static_call_array(
					array($model, 'setSuspensionTime'),
					array($suspensionTime)
				);
			}

			return $throttleProvider;
		});
	}

	protected function registerSession()
	{
		$this->app['fvauth.session'] = $this->app->share(function($app)
		{
			$key = $app['config']['flyingv/fvauth::cookie.key'];
      
			return new IlluminateSession($app['session'], $key);
		});
	}

	protected function registerCookie()
	{
		$this->app['fvauth.cookie'] = $this->app->share(function($app)
		{
			$key = $app['config']['flyingv/fvauth::cookie.key'];
      
			return new IlluminateCookie($app['cookie'], $key);
		});
	}

	protected function registerFvauth()
	{
		$this->app['fvauth'] = $this->app->share(function($app)
		{
			// Once the authentication service has actually been requested by the developer
			// we will set a variable in the application indicating such. This helps us
			// know that we need to set any queued cookies in the after event later.
			$app['fvauth.loaded'] = true;

			return new fvauth(
				$app['fvauth.user'],
				$app['fvauth.group'],
				$app['fvauth.throttle'],
				$app['fvauth.session'],
				$app['fvauth.cookie'],
				$app['request']->getClientIp()
			);
		});
	}

	protected function observeEvents()
	{
		// Set the cookie after the app runs
		$app = $this->app;
		$this->app->after(function($request, $response) use ($app)
		{
			if (isset($app['fvauth.loaded']) and $app['fvauth.loaded'] == true and ($cookie = $app['fvauth.cookie']->getCookie()))
			{
				$response->headers->setCookie($cookie);
			}
		});
	}

}
