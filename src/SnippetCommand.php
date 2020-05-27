<?php

namespace Viper\ViperLab\Console;

use RuntimeException;
use GuzzleHttp\Client;
use Viper\ViperLab\Console\Support\Url;
use Viper\ViperLab\Console\Support\File;
use Viper\ViperLab\Console\Support\Text;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ViperLab Snippet Command Class.
 *
 * @package      ViperEnv
 * @category     Commands
 * @name         SnippetCommand
 * @author       Michael Noël <mike@viperframe.work>
 * @copyright    (c) 2020 Viper framework
 * @license      http://viperframe.work/license
 */

class SnippetCommand extends Command
{
    use Traits\Clean,
        Traits\Command;

    const API_URL = 'https://viper-lab.com/api/v4';

    /**
     * @static
     * @access public
     * @var    string $charset  Character set of input and output
     */
    public static $charset = 'utf-8';

    /**
     * Configure the command options.
     *
     * @access protected
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('snippet')
            ->setDescription('Grab your snippet files from ViperLab.')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Debug.')
            ->addOption('private-token', 't', InputOption::VALUE_REQUIRED, 'Add your private token for ViperLab')
            ->addOption('title', null, InputOption::VALUE_REQUIRED, 'You must provide the title for the file.')
            ->addOption('update', 'u', InputOption::VALUE_NONE, 'Update after install.');
    }

    /**
     * Run Like Hell.
     *
     * throw new RuntimeException('custom message');
     *
     * @access protected
     * @param  InputInterface  $input 
     * @param  OutputInterface $output
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Initializing, please wait...</info>');

        if ($input->getOption('title') == null) {
            throw new RuntimeException('Snippet title is required!');
        }

        if ($input->getOption('private-token') == null) {
            throw new RuntimeException('Snippet title is required!');
        }

        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'PRIVATE-TOKEN' => $input->getOption('private-token'),
            ],
        ];

        $first = (new Client)->get(Url::api('/snippets'), $options);

        $snippets = json_decode($first->getBody()->getContents(), true);

        if (empty($snippets)) {
            throw new RuntimeException('Project does not contain any snippets.');
        }

        $created = [];

        foreach ($snippets as $snippet) {
            if ($input->getOption('title') == $snippet['title']) {
                $file = File::path($snippet['file_name']);

                $file_path = dirname($file);
                $file_name = basename($file);

                if (File::exists($file) && File::delete($file)) {
                    $output->writeln('<comment>Deleted previous file...</comment>');                    
                }

                $output->writeln('<info>Downloading...</info>');

                $raw_url = Text::braces(Url::api('/snippets/{{ id }}/raw'), [
                    'id' => $snippet['id'],
                ]);

                $second = (new Client)->get($raw_url, $options);

                if (! is_dir($file_path)) {
                    mkdir($file_path, 0777, true);
                }

                $file = $file_path . DIRECTORY_SEPARATOR . $file_name;

                if (! file_put_contents($file, $second->getBody()->getContents(), FILE_APPEND | LOCK_EX)) {
                    throw new RuntimeException('Cannot write to file!');
                } else {
                    $created[] = $file;
                }
            }
        }

        if (empty($created)) {
            $output->writeln('<comment>Not found! ' . $input->getOption('title') . '</comment>');
        }

        $commands = [];

        $this->commands($commands, $input);

        if ($input->getOption('update') ?? false) {
            $this->update($input, $output);
        }

        $output->writeln('<info>Done!</info>');

        return 0;
    }

    /**
     * Update file.
     *
     * @access protected
     * @param  \Symfony\Component\Console\Input\InputInterface   $input 
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function update(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Updating...</comment>');

        $file = File::path('.env');

        if (File::exists($file)) {
            $data = File::parse($file);

            if (! empty($data)) {
                $current_db_host = $data['DB_HOST'];
                $changed_db_host = $this->pingDocker($data, $input, $output);
                
                if ($current_db_host !== $changed_db_host) {
                    $replacements = [
                        'DB_HOST' => sprintf('DB_HOST=%s', $changed_db_host),
                    ];

                    $content = File::content($file);

                    $fp = fopen($file, 'w');

                    foreach ($replacements as $key => $value) {
                        $content = preg_replace("/.*\b" . $key . "\b.*\n/ui", trim($value) . "\n", $content);
                    }

                    fwrite($fp, trim($content) . PHP_EOL);
                    fclose($fp);

                    $output->writeln('<info>Changes made.</info>');
                } else {
                    $output->writeln('<info>IP addresses are the same, no changes made.</info>');
                }
            } else {
                $output->writeln('<info>File may be empty, no changes made.</info>');
            }
        } else {
            $output->writeln('<info>File is missing!</info>');
        }
    }

    /**
     * Ping Docker for IP address.
     *
     * @access protected
     * @param  array                                             $data
     * @param  \Symfony\Component\Console\Input\InputInterface   $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return string
     */
    protected function pingDocker($data, InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('debug') ?? false) {
            return '0.0.0.0';
        }

        if (empty($data)) {
            $data = File::parse(File::path('.env'));
        }

        $container_name = $data['DOCKER_CONTAINER_PREFIX'] . '_mysql_1';

        $container_id = $this->execCommand(sprintf('sudo docker ps -aqf "name=%s"', $container_name));

        $container_ip = $this->execCommand(sprintf("sudo docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' %s", $container_id));

        return $container_ip;
    }
}
