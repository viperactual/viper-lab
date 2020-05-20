<?php

namespace Viper\ViperLab\Console\Traits;

use Dotenv\Dotenv;
use Viper\ViperLab\Support\Env;

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
        $content = '';

        $variables = Dotenv::create($path, '.env', Env::getFactory())->safeLoad();

        foreach ($variables as $var => $val) {
            $content .= sprintf('%s=%s', $var, $val);
        }

        $fp = fopen($path . '/.env', 'w');

        fwrite($fp, $content . PHP_EOL);

        fclose($fp);
    }
}
