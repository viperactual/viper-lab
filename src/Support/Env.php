<?php

namespace Viper\ViperLab\Console\Support;

use PhpOption\Option;
use Dotenv\Environment\DotenvFactory;
use Dotenv\Environment\Adapter\PutenvAdapter;
use Dotenv\Environment\Adapter\EnvConstAdapter;
use Dotenv\Environment\Adapter\ServerConstAdapter;

/**
 * ViperLab Environment Support Class.
 *
 * @package     ViperLab
 * @category    Support
 * @name        Env
 * @author      Michael Noël <mike@viperframe.work>
 * @copyright   (c) 2020 Viper framework
 * @license     http://viperframe.work/license
 */

class Env
{
    /**
     * Indicates if the putenv adapter is enabled.
     *
     * @static
     * @access protected
     * @var    bool $putenv  Put into the environment
     */
    protected static $putenv = true;

    /**
     * The environment factory instance.
     *
     * @static
     * @access protected
     * @var    \Dotenv\Environment\FactoryInterface|null $factory
     */
    protected static $factory;

    /**
     * The environment variables instance.
     *
     * @static
     * @access protected
     * @var    \Dotenv\Environment\VariablesInterface|null $variables
     */
    protected static $variables;

    /**
     * Enable the putenv adapter.
     *
     * @static
     * @access public
     * @return void
     */
    public static function enablePutenv(): void
    {
        static::$putenv = true;
        static::$factory = null;
        static::$variables = null;
    }

    /**
     * Disable the putenv adapter.
     *
     * @return void
     */
    public static function disablePutenv(): void
    {
        static::$putenv = false;
        static::$factory = null;
        static::$variables = null;
    }

    /**
     * Get the environment factory instance.
     *
     * @static
     * @access public
     * @return \Dotenv\Environment\FactoryInterface
     */
    public static function getFactory()
    {
        if (static::$factory === null) {
            $adapters = array_merge(
                [new EnvConstAdapter, new ServerConstAdapter],
                static::$putenv ? [new PutenvAdapter] : []
            );

            static::$factory = new DotenvFactory($adapters);
        }

        return static::$factory;
    }

    /**
     * Get the environment variables instance.
     *
     * @static
     * @access public
     * @return \Dotenv\Environment\VariablesInterface
     */
    public static function getVariables()
    {
        if (static::$variables === null) {
            static::$variables = static::getFactory()->createImmutable();
        }

        return static::$variables;
    }

    /**
     * Gets the value of an environment variable.
     *
     * @static
     * @access public
     * @param  string $key      Get key
     * @param  mixed  $default  Default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return Option::fromValue(static::getVariables()->get($key))
            ->map(function ($value) {
                switch (strtolower($value)) {
                    case 'true':
                    case '(true)':
                        return true;
                    case 'false':
                    case '(false)':
                        return false;
                    case 'empty':
                    case '(empty)':
                        return '';
                    case 'null':
                    case '(null)':
                        return null;
                }

                if (preg_match('/\A([\'"])(.*)\1\z/', $value, $matches)) {
                    return $matches[2];
                }

                return $value;
            })
            ->getOrCall(function () use ($default) {
                return value($default);
            });
    }
}
