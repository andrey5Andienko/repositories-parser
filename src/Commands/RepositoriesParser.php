<?php

namespace App\Commands;

use App\Client;
use App\Services\DownloadRepositories;
use App\Services\ExtractZip;
use App\Services\MethodsParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;

class RepositoriesParser extends Command
{
    /** @var ProgressBar */
    protected $progressBar;
    /** @var string */
    protected $output;
    const DOWNLOADS_PATH = 'storage';

    public function configure(): void
    {
        $this->setName('parse-git-repositories:methods-name')
            ->setDescription('Parse names all public and static methods')
            ->addArgument('names', InputArgument::IS_ARRAY, 'Repositories names');
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;
        $repositories = $input->getArgument('names');

        $downloader = new DownloadRepositories($repositories, static::DOWNLOADS_PATH);
        $downloader->download([$this, 'onProgress']);

        $extract = new ExtractZip(static::DOWNLOADS_PATH);
        $extract->extract();

        $parser = new MethodsParser(static::DOWNLOADS_PATH);

        foreach ($parser->parse() as $path => $value) {
            $output->writeln($path . PHP_EOL);
            foreach ($value as $item) {
                $output->writeln("\t" . $item . PHP_EOL);
            }
        }

        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion('Delete downloaded repositories?', true);

        if ($helper->ask($input, $output, $question)) {
            $filesystem = new Filesystem();
            $filesystem->remove(static::DOWNLOADS_PATH);
        }
    }

    public function onProgress(int $total, int $downloaded): void
    {
        if ($total <= 0) {
            return;
        }

        if (!$this->progressBar) {
            $this->progressBar = $this->createProgressBar(100);
        }

        $this->progressBar->setProgress(100 / $total * $downloaded);
    }

    protected function createProgressBar(int $max): ProgressBar
    {
        $bar = new ProgressBar($this->output, $max);

        $bar->setBarCharacter('<fg=green>·</>');
        $bar->setEmptyBarCharacter('<fg=red>·</>');
        $bar->setProgressCharacter('<fg=green>ᗧ</>');
        $bar->setFormat("%current:8s%/%max:-8s% %bar% %percent:5s%% %elapsed:7s%/%estimated:-7s% %memory%");

        return $bar;
    }
}