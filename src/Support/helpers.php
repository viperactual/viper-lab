<?php

if (! function_exists('dd')) {
    /**
     * Debug Dumper.
     *
     * @param
     * @param
     * @return
     */
    function dd(...$args) 
    {
        echo "<pre>";

        $dd = (new \Viper\ViperLab\Console\Support\Debug());

        foreach ($args as $x) {
            echo ($dd)->dump($x);
            echo ($dd)->breaker();
        }

        die(1);
    }
}
