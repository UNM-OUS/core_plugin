<?php
namespace Digraph\Modules\ous_digraph_module;

class Module extends \Digraph\Modules\AbstractModule
{
    /**
     * Gets the path to a module.yaml file to load this module from. In this
     * implementation it's expected to be in the same directory as the source
     * file of the module's class.
     */
    public function getYAMLPath() : string
    {
        return __DIR__.'/../module.yaml';
    }

    /**
     * Gets the default config from which modules should begin their initialize
     * and loading processes
     */
    public function getConfig() : array
    {
        return [
            'module.name' => 'ous_digraph_module',
            'module.path' => realpath(__DIR__.'/..')
        ];
    }
}
