<?php


namespace BowerInstaller\Command;


use BowerInstaller\Installer;
use Bowerphp\Config\Config;
use Bowerphp\Output\BowerphpConsoleOutput;
use Bowerphp\Util\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install specific files from bower packages to dirs')
            ->addOption('remove', null, InputOption::VALUE_NONE, 'Remove bower cache when done')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command reads the bower.json file from
the current directory, processes it, and downloads and installs all the
libraries and dependencies outlined in that file.

  <info>php %command.full_name%</info>

If an optional package name is passed, that package is installed.

  <info>php %command.full_name% packageName[#version]</info>

If an optional flag <comment>-S</comment> is passed, installed package is added
to bower.json file (only if bower.json file already exists).

EOT
            )
        ;
        $this->addOption('bower', null, InputOption::VALUE_OPTIONAL,'Bower Bin', 'bin/bowerphp');
        $this->addOption('token', null, InputOption::VALUE_OPTIONAL, 'Install token');
        $this->addOption('skip-bower', null, InputOption::VALUE_NONE, 'Skip bower install');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initOutputStyles($output);

        if ( !$input->getOption('skip-bower') ) {
            $this->runBower($input, $output);
        }

        $this->doInstallerPass($input, $output);
    }

    protected function initOutputStyles(OutputInterface $output)
    {
        $output->getFormatter()->setStyle('process-name', new OutputFormatterStyle('yellow', null, array('bold')));
    }

    protected function runBower(InputInterface $input, OutputInterface $output)
    {
        $env = array('HOME' => getenv('HOME'));

        if ( $input->getOption('token') ) {
            $env['BOWERPHP_TOKEN'] = $input->getOption('token');
        }
        $process = new Process($input->getOption('bower').' install', null, $env, null, null);
        $output->writeln('<comment>Installing Bower</comment>');
        $process->run(function($type, $buffer) use ($output) {
            if (strpos($buffer, 'install')) {
                $buffer = str_replace('install', '<info>install</info>', $buffer);
                $buffer = str_replace('bower ', '<process-name> bower</process-name> ', $buffer);
                $output->write($buffer);
            }
        });
        if (!$process->isSuccessful()) {
            print($process->getErrorOutput());
            throw new \RuntimeException('Failed to complete bower install');
        }
    }

    public function doInstallerPass(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Copying assets</comment>');
        $filesystem     = new Filesystem();
        $config         = new Config($filesystem);
        $installer      = new Installer($config, new Finder(), $filesystem, $output);

        $installer->installAssets();

        if ( $input->getOption('remove') ) {
            $installer->removeBowerCache();
        }
    }
}