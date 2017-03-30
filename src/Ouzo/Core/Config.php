<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */
namespace Ouzo;

use InvalidArgumentException;
use Ouzo\Config\ConfigOverrideProperty;
use Ouzo\Config\ConfigRepository;
use ReflectionMethod;

class Config
{
    /** @var null|ConfigRepository */
    private static $configInstance = null;

    /**
     * @return boolean
     */
    public static function isLoaded()
    {
        return self::$configInstance ? true : false;
    }

    /**
     * Sample usage:
     *  getValue('system_name') - will return $config['system_name']
     *  getValue('db', 'host') - will return $config['db']['host']
     *
     * If value does not exist it will return empty array.
     *
     * @param array $keys
     * @return mixed
     */
    public static function getValue(...$keys)
    {
        return self::getInstance()->getValue($keys);
    }

    /**
     * @return ConfigRepository
     */
    private static function getInstance()
    {
        if (!self::isLoaded()) {
            self::$configInstance = new ConfigRepository();
            self::$configInstance->reload();
        }
        return self::$configInstance;
    }

    /**
     * @return ConfigRepository
     */
    private static function getInstanceNoReload()
    {
        if (!self::isLoaded()) {
            self::$configInstance = new ConfigRepository();
        }
        return self::$configInstance;
    }

    /**
     * @return string
     */
    public static function getPrefixSystem()
    {
        return self::getValue('global', 'prefix_system');
    }

    /**
     * @return array
     */
    public static function all()
    {
        return self::getInstance()->all();
    }

    /**
     * @param object $customConfig
     * @throws InvalidArgumentException
     * @return ConfigRepository
     */
    public static function registerConfig($customConfig)
    {
        if (!is_object($customConfig)) {
            throw new InvalidArgumentException('Custom config must be a object');
        }
        if (!method_exists($customConfig, 'getConfig')) {
            throw new InvalidArgumentException('Custom config object must have getConfig method');
        }
        $reflection = new ReflectionMethod($customConfig, 'getConfig');
        if (!$reflection->isPublic()) {
            throw new InvalidArgumentException('Custom config method getConfig must be public');
        }
        $config = self::getInstanceNoReload();
        $config->addCustomConfig($customConfig);
        return self::$configInstance;
    }

    /**
     * @param array $keys
     * @return ConfigOverrideProperty
     */
    public static function overrideProperty(...$keys)
    {
        return new ConfigOverrideProperty($keys);
    }

    /**
     * @param array $keys
     * @return void
     */
    public static function clearProperty(...$keys)
    {
        self::overridePropertyArray($keys, null);
    }

    /**
     * @param array $keys
     * @return void
     */
    public static function revertProperty(...$keys)
    {
        self::revertPropertyArray($keys);
    }

    /**
     * @param $keys
     * @return void
     */
    public static function revertPropertyArray($keys)
    {
        self::getInstance()->revertProperty($keys);
    }

    /**
     * @param $keys
     * @param $value
     * @return void
     */
    public static function overridePropertyArray($keys, $value)
    {
        self::getInstance()->overrideProperty($keys, $value);
    }
}
