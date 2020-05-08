<?php

namespace Viper\ViperLab\Console\Support;

use Viper\ViperLab\Console\EnvCommand;

/**
 * ViperLab Url Support Class.
 *
 * @package      ViperLab
 * @category     Support
 * @name         Url
 * @author       Michael Noël <mike@viperframe.work>
 * @copyright    (c) 2020 Viper framework
 * @license      http://viperframe.work/license
 */

class Url
{
    /**
     * Return a full qualified API URL.
     *
     * @access protected
     * @param  string $uri
     * @return string
     */
    public static function api($uri)
    {
        return EnvCommand::API_BASE . $uri;
    }
}
