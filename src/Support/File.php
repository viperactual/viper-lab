<?php

namespace Viper\ViperLab\Console\Support;

/**
 * ViperLab File Support Class.
 *
 * @package      ViperLab
 * @category     Support
 * @name         File
 * @author       Michael Noël <mike@viperframe.work>
 * @copyright    (c) 2020 Viper framework
 * @license      http://viperframe.work/license
 */

class File
{
    /**
     * Get file contents.
     *
     * @static
     * @access public
     * @param  string $file 
     * @return mixed
     */
    public static function content(string $file = '')
    {
        return static::exists($file) ? file_get_contents($file) : $file;
    }

    /**
     * Delete a file.
     *
     * @static
     * @access public
     * @param  string $file 
     * @return bool
     */
    public static function delete(string $file = '')
    {
        return unlink($file);
    }

    /**
     * Checks to see if the file exists or not.
     *
     * @static
     * @access public
     * @param  mixed $file
     * @return bool
     */
    public static function exists($file)
    {
        return is_file($file);
    }

    /**
     * Parse an environment file into an array.
     *
     * @static
     * @access public
     * @param  string $env  Environment file to parse
     * @return array
     */
    public static function parse(string $env)
    {
        $array = [];

        $file = fopen($env, 'r') or exit('Unable to open file!');

        while (! feof($file)) {
            $line = fgets($file);

            if ($line == "\n") {
                continue;
            }

            if (strpos($line, '#') === 0) {
                continue;
            }

            $data = explode('=', trim($line));

            if (! empty($data[0])) {
                $key = $data[0];
                $val = $data[1];

                $array[$key] = isset($val) ? str_replace('"', '', $val) : '';
            }
        }

        fclose($file);

        return $array;
    }

    /**
     * Get file path.
     *
     * @static
     * @access public
     * @param  mixed $extension 
     * @return string
     */
    public static function path($extension = '')
    {
        return getcwd() . DIRECTORY_SEPARATOR . $extension;
    }
}
