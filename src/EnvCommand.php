<?php

namespace Viper\ViperLab\Console;

use RuntimeException;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;
use Viper\ViperLab\Console\Support\Url;
use Viper\ViperLab\Console\Support\File;
use Viper\ViperLab\Console\Support\Text;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ViperLab Env Base Command Class.
 *
 * @package      ViperEnv
 * @category     Commands
 * @name         EnvCommand
 * @author       Michael Noël <mike@viperframe.work>
 * @copyright    (c) 2020 Viper framework
 * @license      http://viperframe.work/license
 */

class EnvCommand extends Command
{
    const APP_NAME = 'ViperLab CLI';
    const APP_VERSION = '1.0.6';
    const API_BASE = 'https://viper-lab.com/api/v4';
    const API_SNIPPETS_URL = '/snippets';
    const API_SNIPPET_URL = '/snippets/{{ id }}/raw';

    /**
     * @static
     * @access public
     * @var    string $charset  Character set of input and output
     */
    public static $charset = 'utf-8';

    /**
     * @access protected
     * @var    array $file  File data
     */
    protected $file = [
        'data' => [],
        'id' => null,
        'private_token' => null,
        'path' => '.env',
        'title' => null,
    ];

    /**
     * Configure the command options.
     *
     * @access protected
     * @return void
     */
    protected function configure()
    {
        $this
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Debug.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run.')
            ->addOption('minify', 'm', InputOption::VALUE_NONE, 'descr')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces install even if the directory already exists')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'You must provide the ID for the file.')
            ->addOption('private-token', 't', InputOption::VALUE_REQUIRED, 'Add your private token for ViperLab')
            ->addOption('title', null, InputOption::VALUE_OPTIONAL, 'You must provide the title for the file.')
            ->addOption('update', 'u', InputOption::VALUE_NONE, 'Update after install.');
    }

    /**
     * Download and install the file from ViperLab.
     *
     * @access protected
     * @param  \Symfony\Component\Console\Input\InputInterface $input 
     * @param  \Symfony\Component\Console\Output\OutputInterface $output 
     * @return array
     */
    protected function install(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Initializing, please wait...</info>');

        if (File::exists($this->file['path'])) {
            if (! $input->getOption('force')) {
                throw new RuntimeException('The file already exists!');
            }
        }

        $this->get($input, $output);

        return [];
    }

    /**
     * GET request to download the file from ViperLab.
     *
     * @access protected
     * @param  \Symfony\Component\Console\Input\InputInterface   $input 
     * @param  \Symfony\Component\Console\Output\OutputInterface $output 
     * @throws RuntimeException 
     * @return EnvCommand
     */
    protected function get(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Downloading...</comment>');

        $options = [];

        if ($this->file['private_token'] != null) {
            $options = [
                'headers' => [
                    'PRIVATE-TOKEN' => $this->file['private_token'],
                ],
            ];
        }

        if ($this->file['id'] != null) {
            $this->getById($input, $output, $options);
        } else {
            $this->getByTitle($input, $output, $options);
        }

        return $this;
    }

    /**
     * Get by ID.
     *
     * @access protected
     * @param  InputInterface $input 
     * @param  OutputInterface $output
     * @param  array $options
     * @throws RuntimeException 
     * @return EnvCommand
     */
    protected function getById(InputInterface $input, OutputInterface $output, $options)
    {
        $raw_url = Text::braces(Url::api(static::API_SNIPPET_URL), [
            'id' => $this->file['id'],
        ]);

        if ($input->getOption('dry-run') && ! $input->getOption('force')) {
            $output->writeln('<comment>Calling...</comment>');
            $output->writeln('<comment> * ' . $raw_url .'</comment>');
        } else {
            $response = (new Client)->get($raw_url, $options);

            if (! file_put_contents($this->file['path'], $response->getBody())) {
                throw new RuntimeException('Error: Cannot write to file!');
            }
        }

        return $this;
    }

    /**
     * Get by title.
     *
     * @access protected
     * @param  InputInterface  $input 
     * @param  OutputInterface $output
     * @param  array           $options
     * @throws RuntimeException 
     * @return EnvCommand
     */
    protected function getByTitle(InputInterface $input, OutputInterface $output, $options)
    {
        if ($input->getOption('dry-run') && ! $input->getOption('force')) {
            $output->writeln('<comment>Calling...</comment>');
            $output->writeln('<comment> * ' . Url::api(static::API_SNIPPETS_URL) . '</comment>');
        } else {
            $first = (new Client)->get(Url::api(static::API_SNIPPETS_URL), $options);

            $snippets = json_decode($first->getBody()->getContents(), true);

            foreach ($snippets as $snippet) {
                if ($this->file['title'] == $snippet['title']) {
                    $raw_url = Text::braces(Url::api(static::API_SNIPPET_URL), [
                        'id' => $snippet['id'],
                    ]);

                    $second = (new Client)->get($raw_url, $options);

                    if (! file_put_contents($this->file['path'], $second->getBody()->getContents())) {
                        throw new RuntimeException('Error: Cannot write to file!');
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Will run commands sent to it.
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
