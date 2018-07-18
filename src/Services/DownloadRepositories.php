<?php

namespace App\Services;

use GuzzleHttp\ClientInterface;

class DownloadRepositories
{
    /** @var array */
    protected $urls;

    /** @var string */
    protected $folder;

    /** @var ClientInterface */
    protected $client;

    public function __construct(array $names, string $folder, ClientInterface $client)
    {
        $this->getUrlsFromRepositoriesNames($names);
        $this->folder = $folder;
        $this->client = $client;
    }

    public function download()
    {
        foreach ($this->urls as $filename => $url) {

            $path = $this->getPath($filename);

            if (!file_exists($this->folder)) {
                mkdir($this->folder);
            }

            if (!file_exists($dir = dirname($path))) {
                mkdir($dir);
            }

            $this->client->request('get', $url, [
                'save_to' => $path,
                'progress' => [$this->client, 'onProgress'],
            ]);
        }
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