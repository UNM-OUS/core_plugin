<?php
//set up CAS
if (!defined(PHPCAS_CONFIGURED)) {
    define(PHPCAS_CONFIGURED, true);
    \phpCAS::client(CAS_VERSION_2_0, 'login.unm.edu', 443, 'cas');
    \phpCAS::setNoCasServerValidation();
    \phpCAS::forceAuthentication();
}
