<?php

namespace Viper\ViperLab\Console\Support;

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
        return \Viper\ViperLab\Console\SnippetCommand::API_URL . $uri;
    }
}
