<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Email;

/** @var Email */
$email = Context::fields()['email'];

echo $email->body_text();
