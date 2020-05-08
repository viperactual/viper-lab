<?php

namespace Viper\ViperLab\Console;

use Viper\ViperLab\Console\EnvCommand;
use Viper\ViperLab\Console\Support\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ViperLab Env Vagrant Command Class.
 *
 * @package      ViperEnv
 * @category     Commands
 * @name         VagrantCommand
 * @author       Michael Noël <mike@viperframe.work>
 * @copyright    (c) 2020 Viper framework
 * @license      http://viperframe.work/license
 */

class VagrantCommand extends EnvCommand 
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
            ->setName('vagrant')
            ->setDescription('Grab your Vagrant environment file from ViperLab.');

        parent::configure();
    }

    /**
     * Execute the command.
     *
     * @access protected
     * @param  \Symfony\Component\Console\Input\InputInterface   $input   User input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output  Output
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->file['private_token'] = $input->getOption('private-token') ?? null;
        $this->file['id'] = $input->getOption('id') ?? null;
        $this->file['title'] = $input->getOption('title') ?? null;
        $this->file['path'] = File::path('virtual' . DIRECTORY_SEPARATOR . 'config.yml');

        $commands = $this->install($input, $output);

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
     * @todo Do not use. Under construction!
     *
     * Then send back the new files to ViperLab with async put/patch/post.
     * 
     * @access protected
     * @param  \Symfony\Component\Console\Input\InputInterface   $input 
     * @param  \Symfony\Component\Console\Output\OutputInterface $output  Output
     * @return void
     */
    protected function update(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Updating...</comment>');

        if ($input->getOption('dry-run') && ! $input->getOption('force')) {
            $output->writeln('<comment>Dry run without forcing.</comment>');

            // ...
        } else {
            $output->writeln('<comment>Without dry run or using force.</comment>');

            // ...
        }
    }
}
