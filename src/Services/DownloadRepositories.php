<?php

namespace App\Services;

use GuzzleHttp\Client;

class DownloadRepositories
{
    /** @var array */
    protected $urls;

    /** @var string */
    protected $folder;

    public function __construct(array $names, string $folder)
    {
        $this->getUrlsFromRepositoriesNames($names);
        $this->folder = $folder;
    }

    public function download(callable $progress)
    {
        foreach ($this->urls as $filename => $url) {

            $path = $this->getPath($filename);

            if (!file_exists($this->folder)) {
                mkdir($this->folder);
            }

            if (!file_exists($dir = dirname($path))) {
                mkdir($dir);
            }

            $this->getClient()->request('get', $url, [
                'save_to' => $path,
                'progress' => $progress,
            ]);
        }
    }

    protected function getClient(): Client
    {
        return new Client;
    }

    protected function getUrlsFromRepositoriesNames(array $repositories)
    {
        foreach ($repositories as $repository) {
            $this->urls[$repository] = "github.com/{$repository}/archive/master.zip";
        }
    }

    protected function getPath(string $filename): string
    {
        return $this->folder . DIRECTORY_SEPARATOR . $filename . '.zip';
    }
}