<?php

use DigraphCMS\Context;
use DigraphCMS\UI\ActionMenu;
use DigraphCMS_Plugins\unmous\ous_digraph_module\BulkMail\BulkMail;

$id = Context::url()->actionSuffix();
$mailing = BulkMail::mailing(intval(Context::url()->actionSuffix()));

if ($mailing->sent()) {
    // mailing has been sent
    ActionMenu::addContextAction($mailing->messagesUrl(), 'mailing messages (' . $mailing->messageCount() . ')');
    ActionMenu::addContextAction($mailing->sourceUrl(), 'mailing source');
    ActionMenu::addContextAction($mailing->copyUrl(), 'copy mailing');
} else {
    // mailing has not been sent
    ActionMenu::addContextAction($mailing->editUrl(), 'edit mailing');
    ActionMenu::addContextAction($mailing->previewUrl(), 'preview mailing');
    ActionMenu::addContextAction($mailing->recipientsUrl(), 'mailing recipients');
    ActionMenu::addContextAction($mailing->copyUrl(), 'copy mailing');
    ActionMenu::addContextAction($mailing->sendUrl(), 'schedule mailing');
}
