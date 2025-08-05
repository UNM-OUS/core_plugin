<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\CustomLists;

use DigraphCMS\Datastore\DatastoreGroup;
use DigraphCMS\Datastore\DatastoreItem;
use DigraphCMS\Digraph;

class CustomLists
{
    public static function data(): DatastoreGroup
    {
        return new DatastoreGroup('bulk_mail', 'custom_lists');
    }

    public static function create(string $name): CustomRecipientList
    {
        $uuid = Digraph::uuid();
        static::data()->set($uuid, $name, ['recipients' => []]);
        return new CustomRecipientList(static::data()->get($uuid));
    }

    public static function get(string $uuid): CustomRecipientList|null
    {
        $data = static::data()->get($uuid);
        if (!$data) return null;
        return new CustomRecipientList($data);
    }

    /**
     * @return array<CustomRecipientList>
     */
    public static function allLists(): array
    {
        return array_map(
            fn(DatastoreItem $item) => new CustomRecipientList($item),
            static::data()->select()->order('created desc')->fetchAll()
        );
    }
}