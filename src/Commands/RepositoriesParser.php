<?php

namespace App\Commands;

use App\Client;
use App\Services\DownloadRepositories;
use App\Services\ExtractZip;
use App\Services\MethodsParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;

class RepositoriesParser extends Command
{
    /** @var string */
    const DOWNLOADS_PATH = 'storage';

    public function configure(): void
    {
        $this->setName('parse-git-repositories:methods-name')
            ->setDescription('Parse names all public and static methods')
            ->addArgument('names', InputArgument::IS_ARRAY, 'Repositories names');
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        $repositories = $input->getArgument('names');

        $client = new Client($output);

        $downloader = new DownloadRepositories($repositories, static::DOWNLOADS_PATH, $client);
        $downloader->download();

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
}