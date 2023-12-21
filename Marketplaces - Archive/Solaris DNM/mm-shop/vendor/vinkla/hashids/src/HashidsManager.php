<?php

/*
 * This file is part of Laravel Hashids.
 *
 * (c) Vincent Klaiber <hello@vinkla.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Vinkla\Hashids;

use GrahamCampbell\Manager\AbstractManager;
use Hashids\Hashids;
use Illuminate\Contracts\Config\Repository;

/**
 * This is the Hashids manager class.
 *
 * @author Vincent Klaiber <hello@vinkla.com>
 */
class HashidsManager extends AbstractManager
{
    /**
     * The factory instance.
     *
     * @var \Vinkla\Hashids\HashidsFactory
     */
    private $factory;

    /**
     * Create a new Hashids manager instance.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     * @param \Vinkla\Hashids\HashidsFactory $factory
     *
     * @return void
     */
    public function __construct(Repository $config, HashidsFactory $factory)
    {
        parent::__construct($config);

        $this->factory = $factory;
    }

    /**
     * Create the connection instance.
     *
     * @param array $config
     *
     * @return \Hashids\Hashids
     */
    protected function createConnection(array $config): Hashids
    {
        return $this->factory->make($config);
    }

    /**
     * Get the configuration name.
     *
     * @return string
     */
    protected function getConfigName(): string
    {
        return 'hashids';
    }

    /**
     * Get the factory instance.
     *
     * @return \Vinkla\Hashids\HashidsFactory
     */
    public function getFactory(): HashidsFactory
    {
        return $this->factory;
    }
}
