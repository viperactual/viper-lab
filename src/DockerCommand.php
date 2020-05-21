<?php

namespace Viper\ViperLab\Console;

use Viper\ViperLab\Console\EnvCommand;
use Viper\ViperLab\Console\Support\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ViperLab Env Docker Command Class.
 *
 * @package      ViperEnv
 * @category     Commands
 * @name         DockerCommand
 * @author       Michael Noël <mike@viperframe.work>
 * @copyright    (c) 2020 Viper framework
 * @license      http://viperframe.work/license
 */

class DockerCommand extends EnvCommand 
{
    use Traits\Clean;

    /**
     * Configure the command options.
     *
     * @access protected
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('docker')
            ->setDescription('Grab your Docker environment file from ViperLab.');

        parent::configure();
    }

    /**
     * Execute the command.
     *
     * @access protected
     * @param  \Symfony\Component\Console\Input\InputInterface   $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->file['private_token'] = $input->getOption('private-token') ?? null;
        $this->file['id'] = $input->getOption('id') ?? null;
        $this->file['title'] = $input->getOption('title') ?? null;
        $this->file['path'] = File::path('docker' . DIRECTORY_SEPARATOR . '.env');

        $this->commands($this->install($input, $output), $input);

        if ($input->getOption('update') ?? false) {
            $this->update($input, $output);
        }

        if ($input->getOption('minify') ?? false) {
            $this->finalize($this->file['path']);
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
        if ($input->getOption('dry-run') && ! $input->getOption('force')) {
            $output->writeln('<comment>Dry run updating...</comment>');
        } else {
            $output->writeln('<comment>Updating...</comment>');

            $file = File::path('.env');

            if (File::exists($file)) {
                $this->file['data'] = File::parse($file);

                if (! empty($this->file['data'])) {
                    $current_db_host = $this->file['data']['DB_HOST'];
                    $changed_db_host = $this->pingDocker($input, $output);
                
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
    }

    /**
     * Ping Docker for IP address.
     *
     * @access protected
     * @param  \Symfony\Component\Console\Input\InputInterface   $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return string
     */
    protected function pingDocker(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('debug') ?? false) {
            return '0.0.0.0';
        }

        if (empty($this->file['data'])) {
            $this->file['data'] = File::parse(File::path('.env'));
        }

        $container_name = $this->file['data']['DOCKER_CONTAINER_PREFIX'] . '_mysql_1';

        $container_id = $this->execCommand(sprintf('docker ps -aqf "name=%s"', $container_name));

        $container_ip = $this->execCommand(sprintf("docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' %s", $container_id));

        return $container_ip;
    }
}
