<?php

namespace Viper\ViperLab\Console\Support;

use ReflectionMethod;
use ReflectionFunction;
use Viper\ViperLab\Console\SnippetCommand;

/**
 * ViperLab Debug Support Class.
 *
 * Contains debugging and dumping tools.
 *
 * @package     ViperLab
 * @category    Support
 * @name        Debug
 * @author      Michael Noël <mike@viperframe.work>
 * @copyright   (c) 2020 Viper framework
 * @license     http://viperframe.work/license
 */

class Debug
{
    /**
     * Returns an HTML string of debugging information about any number of
     * variables, each wrapped in a "pre" tag:
     *
     * @example
     *
     *  // Displays the type and value of each variable
     *  echo Debug::vars($foo, $bar, $baz);
     *
     * @static
     * @access public
     * @param  mixed $var  Variable to debug
     * @return null|string
     */
    public static function vars()
    {
        $variables = func_get_args();

        if (func_num_args() === 0) {
            return null;
        }

        $output = [];

        foreach ($variables as $var) {
            $output[] = Debug::_dump($var, 1024);
        }

        return '<pre class="debug">' . implode("\n", $output) . '</pre>';
    }

    /**
     * Returns an HTML string of information about a single variable.
     *
     * Borrows heavily on concepts from the Debug class of [Nette](http://nettephp.com/).
     *
     * @static
     * @access public
     * @param  mixed   $value            Variable to dump
     * @param  integer $length           Maximum length of strings
     * @param  integer $level_recursion  Recursion limit
     * @return string
     * @uses Debug::_dump
     */
    public static function dump($value, $length = 128, $level_recursion = 10)
    {
        return Debug::_dump($value, $length, $level_recursion);
    }

    /**
     * Helper for `Debug::dump()`, handles recursion in arrays and objects.
     *
     * @static
     * @access protected
     * @param  mixed   $var     Variable to dump
     * @param  integer $length  Maximum length of strings
     * @param  integer $limit   Recursion limit
     * @param  integer $level   Current recursion level (internal usage only!)
     * @return string
     */
    protected static function _dump(& $var, $length = 128, $limit = 10, $level = 0)
    {
        if ($var === null) {
            return '<small>null</small>';
        } elseif (is_bool($var)) {
            return '<small>bool</small> ' . ($var ? 'true' : 'false');
        } elseif (is_float($var)) {
            return '<small>float</small> ' . $var;
        } elseif (is_resource($var)) {
            if (($type = get_resource_type($var)) === 'stream' and $meta = stream_get_meta_data($var)) {
                $meta = stream_get_meta_data($var);

                if (isset($meta['uri'])) {
                    $file = $meta['uri'];

                    if (function_exists('stream_is_local')) {
                        // Only exists on PHP >= 5.2.4.
                        if (stream_is_local($file)) {
                            $file = Debug::path($file);
                        }
                    }

                    return '<small>resource</small><span>(' . $type . ')</span> ' . htmlspecialchars($file, ENT_NOQUOTES, SnippetCommand::$charset);
                }
            } else {
                return '<small>resource</small><span>(' . $type . ')</span>';
            }

            return null;
        } elseif (is_string($var)) {
            // Clean invalid multibyte characters. iconv is only invoked
            // if there are non ASCII characters in the string, so this
            // isn't too much of a hit.
            $var = Utf8::clean($var, SnippetCommand::$charset);

            if (Utf8::strLen($var) > $length) {
                // Encode the truncated string.
                $str = htmlspecialchars(Utf8::subStr($var, 0, $length), ENT_NOQUOTES, SnippetCommand::$charset) . '&nbsp;&hellip;';
            } else {
                // Encode the string.
                $str = htmlspecialchars($var, ENT_NOQUOTES, SnippetCommand::$charset);
            }

            return '<small>string</small><span>(' . strlen($var) . ')</span> "' . $str . '"';
        } elseif (is_array($var)) {
            $output = [];

            // Indentation for this variable.
            $space = str_repeat($s = '    ', $level);

            static $marker;

            if ($marker === null) {
                // Make a unique marker - force it to be alphanumeric so that it is always treated as a string array key
                $marker = uniqid("\x00")."x";
            }

            if (empty($var)) {
                // Do nothing.
            } elseif (isset($var[$marker])) {
                $output[] = "(\n$space$s*RECURSION*\n$space)";
            } elseif ($level < $limit) {
                $output[] = "<span>(";

                $var[$marker] = true;

                foreach ($var as $key => & $val) {
                    if ($key === $marker) {
                        continue;
                    }
                    if (! is_int($key)) {
                        $key = '"' . htmlspecialchars($key, ENT_NOQUOTES, SnippetCommand::$charset) . '"';
                    }

                    $output[] = "$space$s$key => ".Debug::_dump($val, $length, $limit, $level + 1);
                }

                unset($var[$marker]);

                $output[] = "$space)</span>";
            } else {
                // Depth too great.
                $output[] = "(\n$space$s...\n$space)";
            }

            return '<small>array</small><span>(' . count($var) . ')</span> ' . implode("\n", $output);
        } elseif (is_object($var)) {
            // Copy the object as an array.
            $array = (array) $var;

            $output = [];

            // Indentation for this variable.
            $space = str_repeat($s = '    ', $level);

            $hash = spl_object_hash($var);

            // Objects that are being dumped.
            static $objects = [];

            if (empty($var)) {
                // Do nothing.
            } elseif (isset($objects[$hash])) {
                $output[] = "{\n$space$s*RECURSION*\n$space}";
            } elseif ($level < $limit) {
                $output[] = "<code>{";

                $objects[$hash] = true;

                foreach ($array as $key => & $val) {
                    $access = '<small>public</small>';

                    if (! is_int($key)) {
                        if ($key[0] === "\x00") {
                            // Determine if the access is protected or protected.
                            $access = '<small>' . (($key[1] === '*') ? 'protected' : 'private') . '</small>';

                            // Remove the access level from the variable name.
                            $key = substr($key, strrpos($key, "\x00") + 1);
                        }
                    }

                    $output[] = "$space$s$access $key => " . Debug::_dump($val, $length, $limit, $level + 1);
                }

                unset($objects[$hash]);

                $output[] = "$space}</code>";
            } else {
                // Depth too great.
                $output[] = "{\n$space$s...\n$space}";
            }

            return '<small>object</small> <span>' . get_class($var) . '(' . count($array) . ')</span> ' . implode("\n", $output);
        } else {
            return '<small>' . gettype($var) . '</small> ' . htmlspecialchars(print_r($var, true), ENT_NOQUOTES, SnippetCommand::$charset);
        }
    }

    /**
     * Removes application, system, pkgpath, or docroot from a filename,
     * replacing them with the plain text equivalents. Useful for debugging
     * when you want to display a shorter path.
     *
     * @example
     *
     *  echo Debug::path('path/to/file');
     *
     * @static
     * @access public
     * @param  string $file  Path to debug
     * @return string
     */
    public static function path($file)
    {
        if (strpos($file, APP_PATH) === 0) {
            $file = 'BIN_BASE' . DIRECTORY_SEPARATOR . substr($file, strlen(BIN_BASE));
        }

        return $file;
    }

    /**
     * Returns an HTML string, highlighting a specific line of a file, with some
     * number of lines padded above and below.
     *
     * @example
     *
     *  // Highlights the current line of the current file
     *  echo Debug::source(__FILE__, __LINE__);
     *
     * @static
     * @access public
     * @param  string  $file         File to open
     * @param  integer $line_number  Line number to highlight
     * @param  integer $padding      Number of padding lines
     * @return string  Source of file
     * @return false  File is unreadable
     */
    public static function source($file, $line_number, $padding = 5)
    {
        if (! $file || ! is_readable($file)) {
            return false;
        }

        // Open the file and set the line position.
        $file = fopen($file, 'r');
        $line = 0;

        // Set the reading range.
        $range = [
            'start' => $line_number - $padding,
            'end' => $line_number + $padding,
        ];

        // Set the zero-padding amount for line numbers.
        $format = '% '.strlen($range['end']).'d';

        $source = '';

        while (($row = fgets($file)) !== false) {
            // Increment the line number.
            if (++$line > $range['end']) {
                break;
            }

            if ($line >= $range['start']) {
                // Make the row safe for output.
                $row = htmlspecialchars($row, ENT_NOQUOTES, SnippetCommand::$charset);

                // Trim whitespace and sanitize the row.
                $row = '<span class="number">' . sprintf($format, $line) . '</span> ' . $row;

                if ($line === $line_number) {
                    // Apply highlighting to this row.
                    $row = sprintf('<span class="line highlight">%s</span>', $row);
                } else {
                    $row = sprintf('<span class="line">%s</span>', $row);
                }

                // Add to the captured source.
                $source .= $row;
            }
        }

        fclose($file);

        return sprintf('<pre class="source"><code>%s</code></pre>', $source);
    }

    /**
     * Returns an array of HTML strings that represent each step in the backtrace.
     *
     * @example
     *
     *  // Displays the entire current backtrace
     *  echo implode('<br/>', Debug::trace());
     *
     * @static
     * @access public
     * @param  array $trace  Trace
     * @return array
     */
    public static function trace(array $trace = null)
    {
        if ($trace === null) {
            $trace = debug_backtrace();
        }

        // Non-standard function calls.
        $statements = ['include', 'include_once', 'require', 'require_once'];

        $output = [];

        foreach ($trace as $step) {
            if (! isset($step['function'])) {
                // Invalid trace step.
                continue;
            }

            if (isset($step['file']) && isset($step['line'])) {
                // Include the source of this step.
                $source = Debug::source($step['file'], $step['line']);
            }

            if (isset($step['file'])) {
                $file = $step['file'];

                if (isset($step['line'])) {
                    $line = $step['line'];
                }
            }

            $function = $step['function'];

            if (in_array($step['function'], $statements)) {
                if (empty($step['args'])) {
                    // No arguments.
                    $args = [];
                } else {
                    // Sanitize the file path.
                    $args = [$step['args'][0]];
                }
            } elseif (isset($step['args'])) {
                if (! function_exists($step['function']) || strpos($step['function'], '{closure}') !== false) {
                    // Introspection on closures or language constructs in a stack trace is impossible.
                    $params = null;
                } else {
                    if (isset($step['class'])) {
                        if (method_exists($step['class'], $step['function'])) {
                            $reflection = new ReflectionMethod($step['class'], $step['function']);
                        } else {
                            $reflection = new ReflectionMethod($step['class'], '__call');
                        }
                    } else {
                        $reflection = new ReflectionFunction($step['function']);
                    }

                    // Get the function parameters.
                    $params = $reflection->getParameters();
                }

                $args = [];

                foreach ($step['args'] as $i => $arg) {
                    if (isset($params[$i])) {
                        // Assign the argument by the parameter name.
                        $args[$params[$i]->name] = $arg;
                    } else {
                        // Assign the argument by number.
                        $args[$i] = $arg;
                    }
                }
            }

            if (isset($step['class'])) {
                // Class->method() or Class::method().
                $function = $step['class'] . $step['type'] . $step['function'];
            }

            $output[] = [
                'function' => $function,
                'args' => isset($args) ? $args : null,
                'file' => isset($file) ? $file : null,
                'line' => isset($line) ? $line : null,
                'source' => isset($source) ? $source : null,
            ];

            unset($function, $args, $file, $line, $source);
        }

        return $output;
    }

    /**
     * Add and End Of Line breaker to a string.
     *
     * @access public
     * @param  mixed $breakers  How many breakers?
     * @return string
     */
    public function breaker($breakers = 1)
    {
        $retval = '';
        $i = 0;

        while ($i < $breakers) {
            $retval .= PHP_EOL;
            $i++;
        }

        return $retval;
    }
}
