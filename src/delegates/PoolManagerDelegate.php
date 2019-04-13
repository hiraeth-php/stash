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
		$ephemeral = new Stash\Driver\Ephemeral;

		foreach ($app->getConfig('*', 'caching.pools', []) as $collection => $pools) {

			if (!$pools) {
				continue;
			}

			foreach (array_keys($pools) as $name) {
				$caches      = array();
				$collections = $app->getConfig($collection, 'caching.pools.' . $name, []);

				foreach ($collections as $collection) {
					if (!isset($this->caches[$collection])) {
						$this->caches[$collection] = $this->createCache($collection, $app);
					}

					$caches[] = $this->caches[$collection];
				}

				$manager->add($name, new Stash\Pool(new Stash\Driver\Composite([
					'drivers' => $caches + [$ephemeral]
				])));
			}
		}

		return $app->share($manager);
	}


	/**
	 *
	 */
	protected function createCache($cache, $app)
	{
		$config = $app->getConfig($cache, 'cache', NULL);

		if (!$config) {
			//
			// Throw an Exception
			//
		}

		if (empty($config['class'])) {
			//
			// Throw an Exception
			//
		}

		if ($config['disabled'] ?? TRUE) {
			return NULL;
		}

		if (isset($config['path'])) {
			$config['path'] = $app->getDirectory($config['path'], TRUE)->getRealPath();
		}

		return new $config['class']($config);
	}

}
