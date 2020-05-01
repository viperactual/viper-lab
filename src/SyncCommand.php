<?php

namespace Viper\Env\Console;

use ZipArchive;
use RuntimeException;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Viper\Env\Console\Interfaces\CommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Viper Env Sync Command Class.
 *
 * @package      ViperEnv
 * @category     Commands
 * @name         SyncCommand
 * @author       Michael Noël <mike@viperframe.work>
 * @copyright    (c) 2020 Viper framework
 * @license      http://viperframe.work/license
 */

class SyncCommand extends Command implements CommandInterface
{
    /**
     * Configure the command options.
     *
     * @access protected
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('sync')
            ->setDescription('a short description of the command...')
        ; // End Chain
    }

    /**
     * Execute the command.
     *
     * - Find auth.json file
     * - 
     *
     * @access protected
     * @param  \Symfony\Component\Console\Input\InputInterface   $input   User input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output  Output
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Initializing, please wait...</info>');
        $output->writeln('<comment>Syncing...</comment>');

        //$file = $this->getPath('.env');

        // ...

        $commands = ['pwd'];

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
            $output->writeln('<comment>Sync completed!</comment>');
        }

        return 0;
    }

    /**
     * Get path.
     *
     * @access protected
     * @param  mixed $extension 
     * @return string
     */
    protected function getPath($extension = '')
    {
        return getcwd() . DIRECTORY_SEPARATOR . $extension;
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
