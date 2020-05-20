<?php

namespace Viper\ViperLab\Console\Traits;

use Dotenv\Dotenv;
use Viper\ViperLab\Console\Support\Env;

/**
 * ViperLab Clean Concern Trait.
 *
 * @package      ViperEnv
 * @category     Traits
 * @name         Clean
 * @author       Michael Noël <mike@viperframe.work>
 * @copyright    (c) 2020 Viper framework
 * @license      http://viperframe.work/license
 */

trait Clean
{
    protected function finalize($path)
    {
        $variables = Dotenv::create($path, '.env', Env::getFactory())->safeLoad();

        $content = '';

        foreach ($variables as $var => $val) {
            $content .= sprintf('%s=%s', $var, $val) . PHP_EOL;
        }

        $handler = fopen($path . '/.env', 'w');

        fwrite($handler, $content . PHP_EOL);
        fclose($handler);
    }
}
