<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS\Plugins\AbstractPlugin;

class ous_digraph_module extends AbstractPlugin
{
    public function initialConfig(): array
    {
        return json_decode(file_get_contents(
            __DIR__ . '/../config.json'
        ));
    }

    public function isEventSubscriber(): bool
    {
        return false;
    }

    public function mediaFolders(): array
    {
        return [__DIR__ . '/../media'];
    }

    public function routeFolders(): array
    {
        return [__DIR__ . '/../routes'];
    }

    public function templateFolders(): array
    {
        return [__DIR__ . '/../templates'];
    }

    public function phinxFolders(): array
    {
        return [];
    }

    public function postRegistrationCallback()
    {
        // does nothing
    }
}
