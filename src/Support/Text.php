<?php

namespace Viper\ViperLab\Console\Support;

/**
 * ViperLab Text Support Class.
 *
 * @package      ViperLab
 * @category     Support
 * @name         Text
 * @author       Michael Noël <mike@viperframe.work>
 * @copyright    (c) 2020 Viper framework
 * @license      http://viperframe.work/license
 */

class Text
{
    /**
     * Braces.
     * 
     * @example
     *
     *      $message = Text::braces('Hello {{ first_name }} {{ last_name }}, how are you?', [
     *          'first_name' => 'Jerry',
     *          'last_name' => 'Garcia',
     *      ]);
     *
     * @static
     * @access public
     * @param  string $resource   Resource string
     * @param  array  $variables  Key value array
     * @return string
     */
    public static function braces($resource, array $variables): ?string
    {
        if (is_null($resource)) {
            return null;
        }

        extract($variables);

        preg_match_all('/{{[(\s]?(\w+)[(\s]?}}/', $resource, $matches);

        $string = $resource;

        foreach ($matches[0] as $index => $var_name) {
            if (isset(${$matches[1][$index]})) {
                $string = str_replace($var_name, ${$matches[1][$index]}, $string);
            }
        }

        return $string;
    }
}
