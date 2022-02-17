<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;

$url = new URL('/');
echo "<header id=\"header\">";
echo "<div id='header__wrapper'>";
echo "<h1><a href='$url'>" . Context::fields()['site.name'] . "</a></h1>";
echo Templates::render('sections/navbar.php');
echo "</div>";
echo "</header>";