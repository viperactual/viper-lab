<?php

namespace Viper\ViperLab\Console;

use Viper\ViperLab\Console\EnvCommand;
use Viper\ViperLab\Console\Support\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ViperLab Nginx Command Class.
 *
 * @package      ViperEnv
 * @category     Commands
 * @name         NginxCommand
 * @author       Michael Noël <mike@viperframe.work>
 * @copyright    (c) 2020 Viper framework
 * @license      http://viperframe.work/license
 */

class NginxCommand extends EnvCommand 
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
            ->setName('nginx')
            ->setDescription('Grab your Nginx conf file from ViperLab.');

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
        $this->file['path'] = File::path(
            'docker' . DIRECTORY_SEPARATOR .
            'nginx'  . DIRECTORY_SEPARATOR .
            'vhosts' . DIRECTORY_SEPARATOR .
            $input->getOption('conf') ?? 'default.conf'
        );

        $this->commands($this->install($input, $output), $input);

        $output->writeln('<info>Done!</info>');

        return 0;
    }
}
