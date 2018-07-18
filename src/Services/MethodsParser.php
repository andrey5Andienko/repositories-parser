<?php

namespace App\Services;

use Symfony\Component\Finder\Finder;

class MethodsParser
{
    /** @var string */
    const PUBLIC_FUNCTION_PATTERN = '/public\s(static\s)?function\s(?P<method>(\w*))/';

    /** @var string */
    protected $folder;

    public function __construct(string $folder)
    {
        $this->folder = $folder;
    }

    public function parse(): array
    {
        $result = [];

        foreach ($this->getFileWithClass() as $file) {

            $content = $file->getContents();

            preg_match_all(static::PUBLIC_FUNCTION_PATTERN, $content, $matches);

            if (count($matches['method'])) {
                $result[$file->getRealPath()] = $matches['method'];
            }
        }

        return $result;
    }

    protected function getFileWithClass(): Finder
    {
        $finder = new Finder();

        $finder->files()
            ->in($this->folder . DIRECTORY_SEPARATOR)
            ->contains('class')
            ->notContains('class=')
            ->name('*.php');

        return $finder;
    }
}