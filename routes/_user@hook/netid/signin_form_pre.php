<?php
include __DIR__.'/cas.php';

//fail gracefully if CAS isn't found
if (!class_exists('\\phpCAS')) {
    $package->error('phpCAS not found');
    return;
}

if (\phpCAS::getUser()) {
    $this->helper('users')->id(\phpCAS::getUser().'@netid');
} else {
    $this->helper('notifications')->error('Couldn\'t getUser() from phpCAS');
}

//this signin method doesn't use a form
$form = false;
