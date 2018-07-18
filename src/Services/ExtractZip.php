<?php

namespace App\Services;

use Symfony\Component\Finder\Finder;
use ZipArchive;

class ExtractZip
{
    /** @var string */
    protected $folder;

    /** @var Finder */
    protected $finder;

    public function __construct(string $folder)
    {
        $this->finder = Finder::create()->files()->in($folder)->name('*.zip');

        $this->folder = $folder;
    }

    public function extract(): void
    {
        foreach ($this->finder as $file) {
            $zip = new ZipArchive;

            $zip->open($file->getRealPath());
            $zip->extractTo($this->folder);
            $zip->close();

            unlink($file->getRealPath());
        }
    }
}