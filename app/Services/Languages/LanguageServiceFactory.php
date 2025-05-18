<?php

namespace App\Services\Languages;

use Illuminate\Contracts\Container\Container;

class LanguageServiceFactory
{
    public function __construct(protected Container $container) {}

    public function make(string $language): LanguageService
    {
        switch ($language) {
            case 'C (-std=c17)':
                return $this->container->make(CService::class);
            case 'C++':
                return $this->container->make(CPPService::class);
            case 'Python3.11':
            case 'Python3.13':
            case 'PyPy3.10':
            case 'PyPy3.11':
                return $this->container->make(PythonService::class, [
                    'language' => $language,
                ]);
            case 'BINARY':
                return $this->container->make(BinaryService::class);
            default:
                throw new \Exception('Language not supported');
        }
    }
}
