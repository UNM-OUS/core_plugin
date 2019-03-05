<?php
//fail gracefully if CAS isn't found
if (!class_exists('\\phpCAS')) {
    $package->error('phpCAS not found');
    return;
}
//set up CAS
if (!defined(PHPCAS_CONFIGURED)) {
    //pull our config so that we can test different CAS servers per-environment
    $config = $cms->config['unm.cas'];
    define(PHPCAS_CONFIGURED, true);
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
    //force authentication
    try {
        \phpCAS::forceAuthentication();
    } catch (\Exception $e) {
        //it's kludgey, but wrapping this keeps it from breaking because of
        //the way digraph redirects URLs
    }
}
