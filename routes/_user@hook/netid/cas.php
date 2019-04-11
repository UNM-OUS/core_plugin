<?php
//fail gracefully if CAS isn't found
if (!class_exists('\\phpCAS')) {
    $package->error('phpCAS not found');
    return;
}
//set up CAS
if (!defined(PHPCAS_CONFIGURED)) {
    //remember that we've configured CAS
    define(PHPCAS_CONFIGURED, true);
    //pull our config so that we can test different CAS servers per-environment
    $config = $cms->config['unm.cas'];
    // fudge $_SERVER values because UNM's web environment
    // is configured wrong when it comes to forwarding proxy header stuff
    if ($config['fixhttpsproblems']) {
        $_SERVER['SERVER_PORT'] = 443;
        $_SERVER['HTTPS'] = 'on';
    }
    //set up client, initialize phpCAS
    switch ($config['version']) {
        case 'CAS_VERSION_1_0':
            $version = CAS_VERSION_1_0;
            break;
        case 'CAS_VERSION_2_0':
            $version = CAS_VERSION_2_0;
            break;
        case 'CAS_VERSION_3_0':
            $version = CAS_VERSION_3_0;
            break;
        default:
            $version = CAS_VERSION_2_0;
    }
    \phpCAS::client(
        CAS_VERSION_2_0,
        $config['server'],
        intval($config['port']),
        $config['context']
    );
    //set up configured config calls
    if ($config['setnocasservervalidation']) {
        \phpCAS::setNoCasServerValidation();
    }
}
