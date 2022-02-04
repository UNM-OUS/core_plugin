<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

class Plugin extends \DigraphCMS\Plugins\AbstractPlugin
{
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
}
