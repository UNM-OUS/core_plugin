<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Email;

/** @var Email */
$email = Context::fields()['email'];

echo $email->body_text();

?>
==========
Office of the University Secretary
(505) 277-4664
<?= $email->from() ?>