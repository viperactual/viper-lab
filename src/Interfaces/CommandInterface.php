<?php

namespace Viper\ViperLab\Console\Interfaces;

/**
 * ViperLab Env Command Interface Class.
 *
 * @package      ViperLab
 * @category     Interfaces
 * @name         CommandInterface
 * @author       Michael Noël <mike@viperframe.work>
 * @copyright    (c) 2020 Viper framework
 * @license      http://viperframe.work/license
 */

interface CommandInterface
{
    const APP_NAME = 'ViperLab CLI';
    const APP_VERSION = '1.0.0';

    const API_URL = 'https://viper-lab.com/api/v4/snippets/7/raw';
}
