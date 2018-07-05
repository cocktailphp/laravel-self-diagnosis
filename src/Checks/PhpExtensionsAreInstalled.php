<?php

namespace BeyondCode\SelfDiagnosis\Checks;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

class PhpExtensionsAreInstalled implements Check
{

    const EXT = 'ext-';

    /** @var Filesystem */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /** @var Collection */
    private $extensions;

    /**
     * The name of the check.
     *
     * @return string
     */
    public function name(): string
    {
        return 'The required PHP extensions are installed';
    }

    /**
     * The error message to display in case the check does not pass.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The following extensions are missing: '.PHP_EOL.$this->extensions->implode(PHP_EOL);
    }

    /**
     * Perform the actual verification of this check.
     *
     * @return bool
     */
    public function check(): bool
    {
        $this->extensions = Collection::make([
            'openssl',
            'PDO',
            'mbstring',
            'tokenizer',
            'xml',
            'ctype',
            'json'
        ]);
        $this->extensions = $this->extensions->merge($this->getExtensionsRequiredInComposerFile());
        $this->extensions = $this->extensions->unique();
        $this->extensions = $this->extensions->reject(function ($ext) {
            return extension_loaded($ext);
        });

        return $this->extensions->isEmpty();
    }

    /**
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getExtensionsRequiredInComposerFile()
    {
        $installedPackages = json_decode($this->filesystem->get(base_path('vendor/composer/installed.json')), true);

        $extensions = [];
        foreach ($installedPackages as $installedPackage) {
            $filtered = array_where(array_keys(array_get($installedPackage, 'require', [])), function ($value, $key) {
                return starts_with($value, self::EXT);
            });
            foreach ($filtered as $extension) {
                $extensions[] = str_replace_first(self::EXT, '', $extension);
            }
        }
        return array_unique($extensions);
    }

}
