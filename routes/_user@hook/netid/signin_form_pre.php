<?php
include __DIR__.'/cas.php';

//fail gracefully if CAS isn't found
if (!class_exists('\\phpCAS')) {
    $package->error('phpCAS not found');
    return;
}

//force authentication
try {
    \phpCAS::forceAuthentication();
} catch (\Exception $e) {
    //it's kludgey, but wrapping this keeps it from breaking because of
    //the way digraph redirects URLs
}

//get user
if (\phpCAS::getUser()) {
    $this->helper('users')->id(\phpCAS::getUser().'@netid');
} else {
    $this->helper('notifications')->error('Couldn\'t getUser() from phpCAS');
}

//this signin method doesn't use a form
$form = false;
