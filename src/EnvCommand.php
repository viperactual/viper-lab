<?php

namespace Viper\ViperLab\Console;

use Symfony\Component\Process\Process;
use Viper\ViperLab\Console\Support\Text;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Viper\ViperLab\Console\Interfaces\CommandInterface;

/**
 * ViperLab Env Command Class.
 *
 * @package      ViperEnv
 * @category     Commands
 * @name         EnvCommand
 * @author       Michael Noël <mike@viperframe.work>
 * @copyright    (c) 2020 Viper framework
 * @license      http://viperframe.work/license
 */

class EnvCommand extends Command implements CommandInterface
{
    /**
     * @access protected
     * @var    array $environments
     */
    protected $environments = [
        'app' => false,
        'docker' => false,
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
            ->setName('env')
            ->setDescription('Sync your .env files from Viper Lab.')


        ; // End Chain


        var_dump(__METHOD__); die;

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
        $output->writeln('<info>Initializing, please wait...</info>');

        $output->writeln('<comment>Syncing...</comment>');


        var_dump(__METHOD__); die;








        $app = $this->getPath('.env');



        $this->envFile('app', $app);










        $docker = $this->getPath('docker' . DIRECTORY_SEPARATOR . '.env');

        $this->envFile('docker', $docker);





        //if ($this->environments['docker'] != false) {
        //    if (! $this->environments['app']) {
        //        $output->writeln('<error>Error! Cannot find app .env file content.</error>');
        //    } else {
        //        $replacements = [
        //            'DB_HOST' => $this->dbHost(),
        //            'DB_PORT' => $this->dbPort(),
        //            'DB_DATABASE' => $this->dbDatabase(),
        //            'DB_USERNAME' => $this->dbUsername(),
        //            'DB_PASSWORD' => $this->dbPassword(),
        //        ];
        //
        //        $content = file_get_contents($app);
        //
        //        $fp = fopen($app, 'w');
        //
        //        foreach ($replacements as $key => $value) {
        //            $content = preg_replace("/.*\b" . $key . "\b.*\n/ui", trim($value) . "\n", $content);
        //        }
        //
        //        fwrite($fp, trim($content) . PHP_EOL);
        //        fclose($fp);
        //    }
        //
        //    $output->writeln('<info>Changes made.</info>');
        //} else {
        //    $output->writeln('<error>Cannot find docker .env file, now what?</error>');
        //}







        $commands = [];

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

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        if ($process->isSuccessful()) {
            $output->writeln('<info>Sync completed!</info>');
        }

        return 0;
    }

    /**
     * Get docker container ip for database.
     *
     * @access protected
     * @return string
     */
    protected function dockerContainerIp()
    {
        $debug = false;

        if ($debug) {
            return '172.19.0.2';
        }

        $container_name = $this->environments['app']['DOCKER_CONTAINER_PREFIX'] . '_mysql_1';

        $container_id = $this->execCommand('docker ps -aqf "name=' . $container_name . '"');

        $container_ip = $this->execCommand("docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' {$container_id}");

        return $container_ip;
    }

    /**
     * Ping the Docker container for its IP address.
     *
     * @access protected
     * @return string
     */
    protected function dbHost()
    {
        return sprintf('DB_HOST=%s', $this->dockerContainerIp());
    }

    /**
     * Get the database user name.
     *
     * @access protected
     * @return mixed
     */
    protected function dbUsername()
    {
        $username = function () {
            return (! empty($this->environments['app']['DB_USERNAME']))
                ? $this->environments['app']['DB_USERNAME']
                : $this->environments['docker']['MYSQL_USER'];
        };

        return sprintf('DB_USERNAME=%s', $username());
    }

    /**
     * Get the database password.
     *
     * @access protected
     * @return mixed
     */
    protected function dbPassword()
    {
        $password = function () {
            return (! empty($this->environments['app']['DB_PASSWORD']))
                ? $this->environments['app']['DB_PASSWORD']
                : $this->environments['docker']['MYSQL_PASSWORD'];
        };

        return sprintf('DB_PASSWORD=%s', $password());
    }

    /**
     * Get the database name.
     *
     * @access protected
     * @return mixed
     */
    protected function dbDatabase()
    {
        $database = function () {
            return (! empty($this->environments['app']['DB_DATABASE']))
                ? $this->environments['app']['DB_DATABASE']
                : $this->environments['docker']['MYSQL_DATABASE'];
        };

        return sprintf('DB_DATABASE="%s"', $database());
    }

    /**
     * Setting the database port.
     *
     * @access protected
     * @return mixed
     */
    protected function dbPort()
    {
        $port = function () {
            return (! empty($this->environments['app']['DB_PORT']))
                ? $this->environments['app']['DB_PORT']
                : $this->environments['docker']['MYSQL_PORT'];
        };

        return sprintf('DB_PORT=%s', $port());
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
     * Env file.
     *
     * @access private
     * @param  mixed $alias 
     * @param  mixed $path
     * @return void
     */
    private function envFile($alias, $path)
    {
        if (file_exists($path)) {
            $this->environments[$alias] = $this->parse($path);
        }
    }

    /**
     * Execute command.
     *
     * @access private
     * @param  mixed $command  Command to execute
     * @return string
     */
    private function execCommand($command)
    {
        return shell_exec($command);
    }

    /**
     * Parse an environment file into an array.
     *
     * @access private
     * @param  string $env  Environment file to parse
     * @return array
     */
    private function parse(string $env)
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
}
