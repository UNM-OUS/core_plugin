<?php
//set up CAS
\phpCAS::client(CAS_VERSION_2_0, 'login.unm.edu', 443, 'cas');
\phpCAS::setNoCasServerValidation();
\phpCAS::forceAuthentication();
