<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Email;
use DigraphCMS\UI\Theme;

/** @var Email */
$email = Context::fields()['email'];
$variables = Theme::variables('light');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <base target="_blank"/>
    <title><?php echo $email->subject(); ?></title>
</head>

<body style="background: #fff;">

<?php echo $email->body_html(); ?>

<p>
    <strong>---</strong><br>
    <strong>Office of the University Secretary</strong><br>
    <a href="tel:5052774664">(505) 277-4664</a><br>
    <a href="mailto:<?= $email->from() ?>"><?= $email->from() ?></a>
</p>
</body>

</html>