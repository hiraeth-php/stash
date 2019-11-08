<?php

namespace Hiraeth\Stash;

use Hiraeth;
use Stash;

/**
 *
 */
class PoolManagerDelegate implements Hiraeth\Delegate
{
	/**
	 *
	 */
	protected $caches = array();


	/**
	 * Get the class for which the delegate operates.
	 *
	 * @static
	 * @access public
	 * @return string The class for which the delegate operates
	 */
	static public function getClass(): string
	{
		return Hiraeth\Caching\PoolManager::class;
	}


	/**
	 * Get the instance of the class for which the delegate operates.
	 *
	 * @access public
	 * @param Hiraeth\Application $app The application instance for which the delegate operates
	 * @return object The instance of the class for which the delegate operates
	 */
	public function __invoke(Hiraeth\Application $app): object
	{
		$manager   = new Hiraeth\Caching\PoolManager();
		$ephemeral = new Stash\Driver\Ephemeral();

		foreach ($app->getConfig('*', 'cache', []) as $path => $config) {
			if (isset($config['class'])) {
				$name    = basename($path);
				$drivers = array();

				if ($manager->has($name)) {

				}

				if (!$config['disabled'] ?? TRUE) {
					if (isset($config['path'])) {
						$config['path'] = $app->getDirectory($config['path'], TRUE)->getRealPath();
					}

					$drivers[] = new $config['class']($config['options'] ?? []);
				}

				$stack  = new Stash\Driver\Composite(['drivers' => $drivers + [$ephemeral]]);
				$pool   = new Stash\Pool($stack);

				$manager->add($name, $pool);
				$pool->setNamespace($name);
			}
		}

		return $app->share($manager);
	}
}
