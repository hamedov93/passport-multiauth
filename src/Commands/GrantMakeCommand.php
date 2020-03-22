<?php

namespace Hamedov\PassportMultiauth\Commands;

use Illuminate\Console\GeneratorCommand;

class GrantMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:grant';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new passport grant class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Grant';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../stubs/grant.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Grants';
    }
}
