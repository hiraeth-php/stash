<?php

namespace Hiraeth\Stash;

use Hiraeth;
use Hiraeth\Caching;
use RuntimeException;
use Stash;

/**
 *
 */
class PoolManagerProvider implements Hiraeth\Provider
{
	/**
	 * {@inheritDoc}
	 */
	static public function getInterfaces(): array
	{
		return [
			Caching\PoolManager::class
		];
	}


	/**
	 * {@inheritDoc}
	 */
	public function __invoke(object $instance, Hiraeth\Application $app): object
	{
		$ephemeral = new Stash\Driver\Ephemeral();

		foreach ($app->getConfig('*', 'cache', []) as $path => $config) {
			if (isset($config['class'])) {
				$name    = basename($path);
				$options = $config['options'] ?? [];
				$drivers = array();

				if ($instance->has($name)) {
					throw new RuntimeException(sprintf(
						'Cannot configure cache "%s", another cache already exists with that name',
						$name
					));
				}

				if (!$config['disabled'] ?? TRUE) {
					if (isset($options['path'])) {
						$options['path'] = $app->getDirectory($options['path'], TRUE)->getRealPath();
					}

					$drivers[] = new $config['class']($options);
				}

				$stack  = new Stash\Driver\Composite(['drivers' => $drivers + [$ephemeral]]);
				$pool   = new Stash\Pool($stack);

				$instance->add($name, $pool);
				$pool->setNamespace($name);
			}
		}
	}
}
