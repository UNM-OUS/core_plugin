<?php
include __DIR__.'/cas.php';

if (\phpCAS::getUser()) {
    $this->helper('users')->id(\phpCAS::getUser().'@netid');
} else {
    $this->helper('notifications')->error('Couldn\'t getUser() from phpCAS');
}

//this signin method doesn't use a form
$form = false;
