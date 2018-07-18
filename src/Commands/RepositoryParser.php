<?php

namespace App\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Finder\Finder;
use ZipArchive;

class RepositoryParser extends Command
{
    /** @var string */
    const DOWNLOADS_PATH = 'storage';

    /** @var string */
    const PUBLIC_FUNCTION_PATTERN = '/public\s(static\s)?function\s(?P<method>(\w*))/';

    /** @var ProgressBar */
    protected $progressBar;

    /** @var OutputInterface */
    protected $output;

    public function configure(): void
    {
        $this->setName('parse:methods-name')
            ->setDescription('Parse names all public and static methods')
            ->addArgument('names', InputArgument::IS_ARRAY, 'Repositories names');
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;

        $repositories = $input->getArgument('names');

        foreach ($this->getUrlsFromRepositoriesNames($repositories) as $key => $url) {
            $output->writeln(PHP_EOL . "Download {$key}" . PHP_EOL);

            $this->download($url, $key);

            $output->write(PHP_EOL);
        }

        $path = $this->getZipFilesPath();

        $this->extractRepositories($path);

        $files = $this->getFileWithClass();

        $result = $this->getPublicMethods($files);

        $this->printResult($result);

        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion('Delete downloaded repositories?', true);

        if ($helper->ask($input, $output, $question)) {
            system('rm -R ' . static::DOWNLOADS_PATH);
        }
    }

    public function download(string $url, string $filename): void
    {
        $path = $this->getPath($filename);

        if (!file_exists(static::DOWNLOADS_PATH)) {
            mkdir(static::DOWNLOADS_PATH);
        }

        if (!file_exists($dir = dirname($path))) {
            mkdir($dir);
        }

        $this->getClient()->request('get', $url, [
            'save_to' => $path . ".zip",
            'progress' => [$this, 'onProgress'],
        ]);
    }

    public function getClient(): ClientInterface
    {
        return new Client;
    }

    public function getPath(string $filename): string
    {
        return static::DOWNLOADS_PATH . DIRECTORY_SEPARATOR . $filename;
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

    public function createProgressBar(int $max): ProgressBar
    {
        $bar = new ProgressBar($this->output, $max);

        $bar->setBarCharacter('<fg=green>·</>');
        $bar->setEmptyBarCharacter('<fg=red>·</>');
        $bar->setProgressCharacter('<fg=green>ᗧ</>');
        $bar->setFormat("%current:8s%/%max:-8s% %bar% %percent:5s%% %elapsed:7s%/%estimated:-7s% %memory%");

        return $bar;
    }

    public function getFileWithClass(): Finder
    {
        $finder = new Finder();
        $finder->files()
            ->in(static::DOWNLOADS_PATH . '/')
            ->contains('class')
            ->notContains('class=')
            ->name('*.php');

        return $finder;
    }

    public function getPublicMethods(Finder $finder): array
    {
        $result = [];

        foreach ($finder as $file) {
            $content = $file->getContents();
            preg_match_all(static::PUBLIC_FUNCTION_PATTERN, $content, $matches);
            if (count($matches['method'])) {
                $result[$file->getRealPath()] = $matches['method'];
            }
        }

        return $result;
    }

    public function getZipFilesPath(): Finder
    {
        $finder = new Finder();
        $finder->files()->in(static::DOWNLOADS_PATH)->name('*.zip');

        return $finder;
    }

    public function extractRepositories(Finder $finder): void
    {
        foreach ($finder as $file) {
            $zip = new ZipArchive;
            $zip->open($file->getRealPath());
            $zip->extractTo(static::DOWNLOADS_PATH);
            $zip->close();
            unlink($file->getRealPath());
        }
    }

    public function printResult(array $result): void
    {
        foreach ($result as $path => $value) {
            $this->output->writeln($path . PHP_EOL);
            foreach ($value as $item) {
                $this->output->writeln("\t" . $item . PHP_EOL);
            }
        }
    }

    protected function getUrlsFromRepositoriesNames(array $repositories): array
    {
        $urls = [];

        foreach ($repositories as $repository) {
            $urls[$repository] = "github.com/{$repository}/archive/master.zip";
        }

        return $urls;
    }
}