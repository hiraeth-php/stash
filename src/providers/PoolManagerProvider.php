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
	 *
	 * @param Caching\PoolManager $instance
	 */
	public function __invoke(object $instance, Hiraeth\Application $app): object
	{
		$ephemeral = new Stash\Driver\Ephemeral();
		$defaults  = [
			'class'    => NULL,
			'disabled' => FALSE,
			'options'  => array()
		];

		foreach ($app->getConfig('*', 'cache', $defaults) as $path => $config) {
			if (!$config['class']) {
				continue;
			}

			$name    = basename($path);
			$drivers = array();

			if ($instance->has($name)) {
				throw new RuntimeException(sprintf(
					'Cannot configure cache "%s", another cache already exists with that name',
					$name
				));
			}

			if (!$config['disabled']) {
				$drivers[] = new $config['class']($config['options']);
			}

			$stack  = new Stash\Driver\Composite(['drivers' => $drivers + [$ephemeral]]);
			$pool   = new Stash\Pool($stack);

			$instance->add($name, $pool);
			$pool->setNamespace($name);
		}

		return $instance;
	}
}
