<?php

use DigraphCMS\Context;
use DigraphCMS\Media\Media;
use DigraphCMS\URL\URL;
use DigraphCMS\HTML\ResponsivePicture;

$url = new URL('/');
$logo_dark = Media::get('/logo_dark.png')
    ->image()->height(240)->url();
$logo_light = Media::get('/logo_light.png')
    ->image()->height(240)->url();

$hero = Media::get('/hero.jpg')->image();
$picture = new ResponsivePicture($hero, '');
$picture->setExpectedWidth(100);

$site_name = Context::fields()['site.name'];

echo "<header id=\"header\">";
echo "<div id='header__wrapper'>";
echo "<div id='header__hero'>$picture</div>";
echo "<div id='header__name'>";
echo "<h1 class=\"header__logo dark-mode-only\"><a href='$url'><img src=\"$logo_dark\" alt='$site_name'></a></h1>";
echo "<h1 class=\"header__logo light-mode-only\"><a href='$url'><img src=\"$logo_light\" alt='$site_name'></a></h1>";
echo "</div>";
echo "</div>";
echo "</header>";
