<?php


namespace BowerInstaller;


use Bowerphp\Config\ConfigInterface;
use Bowerphp\Output\BowerphpConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;

class Installer {
    protected $config, $finder, $filesystem, $output, $installDir;

    public function __construct(ConfigInterface $config, Finder $finder, Filesystem $filesystem, OutputInterface $output)
    {
        $this->config = $config->getBowerFileContent();

        $this->installDir = $config->getInstallDir();
        $this->finder = $finder;
        $this->filesystem = $filesystem;
        $this->output = $output;
    }

    public function installAssets()
    {
        foreach ( $this->config['install']['sources'] as $lib => $files ) {
            foreach ( $files as $pattern ) {

                $path = substr($pattern, 0, strrpos($pattern, '/'));
                if ($this->filesystem->exists($path)) {
                    $filename = substr($pattern, strrpos($pattern, '/') + 1);
                    $finder = new Finder();
                    $finder->files()->name($filename)->in($path);

                    foreach ($finder as $file) {
                        $this->copyAsset($file, $lib);
                    }
                }
            }
        }


    }

    public function copyAsset(SplFileInfo $file, $lib)
    {
        if ( isset($this->config['install']['path'][$file->getExtension()]) ) {
            $path = $this->config['install']['path'][$file->getExtension()].'/'.$lib;
            if ( !$this->filesystem->exists($path) ) {
                $this->filesystem->mkdir($path, 0755);
            }

            $target = $path.'/'.$file->getFilename();
            $this->filesystem->copy($file->getRealPath(), $target, true);
            if ( $this->filesystem->exists($target) ) {
                $this->output->writeln(sprintf('<process-name> bower-installer </process-name> Copied %s to %s', str_replace(getcwd() . '/', '', $file->getRealPath()), $path));
            }
        }
    }

    public function removeBowerCache()
    {
        $this->output->writeln(sprintf('<process-name> bower-installer </process-name> Removed %s', $this->installDir));
        $this->filesystem->remove($this->installDir);
    }
} 