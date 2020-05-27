<?php

namespace Viper\ViperLab\Console\Traits;

use Symfony\Component\Process\Process;

/**
 * ViperLab Command Trait.
 *
 * @package      ViperEnv
 * @category     Traits
 * @name         Command
 * @author       Michael Noël <mike@viperframe.work>
 * @copyright    (c) 2020 Viper framework
 * @license      http://viperframe.work/license
 */

trait Command
{
    /**
     * Run commands.
     *
     * @access protected
     * @param  mixed $commands 
     * @param  mixed $input
     * @return void
     */
    protected function commands($commands = [], $input)
    {
        if (! empty($commands)) {
            if ($input->getOption('no-ansi')) {
                $commands = array_map(function ($value) {
                    return $value . ' --no-ansi';
                }, $commands);
            }

            if ($input->getOption('quiet')) {
                $commands = array_map(function ($value) {
                    return $value . ' --quiet';
                }, $commands);
            }

            $process = Process::fromShellCommandline(implode(' && ', $commands), $this->getPath(), null, null, null);

            if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
                $process->setTty(true);
            }

            $process->run(function ($type, $line) use ($output) {
                $output->write($line);
            });

            if ($process->isSuccessful()) {
                $output->writeln('<comment>Success!</comment>');
            }
        }
    }

    /**
     * Execute command.
     *
     * @access protected
     * @param  mixed $command  Command to execute
     * @return string
     */
    protected function execCommand($command)
    {
        return shell_exec($command);
    }
}
