<?php
include __DIR__.'/cas.php';

//sign out with CAS, we should already be signed out of Digraph
\phpCAS::logout();

//redirect to UNM's central signout
$package->redirect('https://login.unm.edu/cas/logout');
