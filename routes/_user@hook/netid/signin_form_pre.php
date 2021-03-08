<?php
include __DIR__ . '/cas.php';

//fail gracefully if CAS isn't found
if (!class_exists('\\phpCAS')) {
    $package->error('phpCAS not found');
    return;
}

//force authentication
try {
    if (!phpCAS::isAuthenticated()) {
        \phpCAS::forceAuthentication();
    }
} catch (\Throwable $th) {
    if ($package['url.args.uniqid']) {
        throw $th;
    }
    $url = $package->url();
    $url['args.uniqid'] = uniqid();
    $cms->helper('notifications')->printError(
        '<strong>Sign-in error</strong><br>' .
        "Signing in failed. Usually this is a temporary error caused by an expired token in UNM's central authentication system. Please <a href='$url'>try again</a>."
    );
}

//get user
if (\phpCAS::getUser()) {
    $this->helper('users')->id(\phpCAS::getUser() . '@netid');
} else {
    $this->helper('notifications')->error('Couldn\'t getUser() from phpCAS');
}

//this signin method doesn't use a form
$form = false;
