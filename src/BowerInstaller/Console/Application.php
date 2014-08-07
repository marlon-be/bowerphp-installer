<?php


namespace BowerInstaller\Console;

use Bowerphp\Console\Application as BaseApplication;
use BowerInstaller\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends BaseApplication
{
    private $runningCommand;

    private $wantHelps;

    /**
     * {@inheritDoc}
     */
    protected function getDefaultCommands()
    {
        return array(
            new Command\InstallCommand()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if (version_compare(PHP_VERSION, '5.3.2', '<')) {
            $output->writeln('<warning>Bowerphp only officially supports PHP 5.3.2 and above, you will most likely encounter problems with your PHP '.PHP_VERSION.', upgrading is strongly recommended.</warning>');
        }

        if ($input->hasParameterOption('--profile')) {
            $startTime = microtime(true);
        }

        if ($newWorkDir = $this->getNewWorkingDir($input)) {
            $oldWorkingDir = getcwd();
            chdir($newWorkDir);
        }

        $result = $this->symfonyDoRun($input, $output);

        if (isset($oldWorkingDir)) {
            chdir($oldWorkingDir);
        }

        if (isset($startTime)) {
            $output->writeln('<info>Memory usage: '.round(memory_get_usage() / 1024 / 1024, 2).'MB (peak: '.round(memory_get_peak_usage() / 1024 / 1024, 2).'MB), time: '.round(microtime(true) - $startTime, 2).'s');
        }

        return $result;
    }

    /**
     * @param InputInterface $input
     * @return string
     * @throws \RuntimeException
     */
    private function getNewWorkingDir(InputInterface $input)
    {
        $workingDir = $input->getParameterOption(array('--working-dir', '-d'));
        if (false !== $workingDir && !is_dir($workingDir)) {
            throw new \RuntimeException('Invalid working directory specified.');
        }

        return $workingDir;
    }


    /**
     * Copy of original Symfony doRun, to allow a default command
     *
     * @param InputInterface  $input   An Input instance
     * @param OutputInterface $output  An Output instance
     * @param string          $default Default command to execute
     *
     * @return integer 0 if everything went fine, or an error code
     * @codeCoverageIgnore
     */
    private function symfonyDoRun(InputInterface $input, OutputInterface $output, $default = 'install')
    {
        if (true === $input->hasParameterOption(array('--version', '-V'))) {
            $output->writeln($this->getLongVersion());

            return 0;
        }

        $name = $this->getCommandName($input);

        if (true === $input->hasParameterOption(array('--help', '-h'))) {
            if (!$name) {
                $name = 'help';
                $input = new ArrayInput(array('command' => 'help'));
            } else {
                $this->wantHelps = true;
            }
        }

        if (!$name) {
            $name = $default;
            $input = new ArrayInput(array('command' => $name));
        }
        // the command name MUST be the first element of the input
        $command = $this->find($name);


        $this->runningCommand = $command;
        $exitCode = $this->doRunCommand($command, $input, $output);
        $this->runningCommand = null;

        return $exitCode;
    }
} 