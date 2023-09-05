<h1>Bypass login</h1>
<p>
    Use this tool to bypass the login system and sign into this site as any NetID you desire.
    A log entry will be visible on the specified user's login history indicating that this was done, as well as by whom.
</p>
<?php

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\NetIDInput;
use DigraphCMS_Plugins\unmous\ous_digraph_module\OUS;

$form = new FormWrapper();

$netid = (new Field('NetID to log in as', new NetIDInput))
    ->setRequired(true)
    ->addForm($form);

if ($form->ready()) {
    $user = OUS::userFromNetId($netid->value(), true);
    $source = Session::authenticate(
        $user->uuid(),
        sprintf(
            'Login system bypassed by administrative user %s (%s)',
            Users::current()->uuid(),
            Users::current()->name()
        )
    );
    Notifications::flashConfirmation('You are now signed in as ' . $user->name());
    throw new RedirectException(new URL('/'));
}

echo $form;