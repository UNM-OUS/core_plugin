<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\ResponsivePicture;
use DigraphCMS\Media\Media;
use DigraphCMS\URL\URL;

$url = new URL('/');
$hero = Media::get('/hero.jpg')
    ->image()
    ->cover(1920, 600);
$picture = new ResponsivePicture($hero, 'header hero image');
$picture->setExpectedWidth(100);
echo "<header id=\"header\">";
echo "<div id='header__wrapper'>";
echo "<div id='header__hero'>$picture</div>";
echo "<div id='header__name'><h1><a href='$url'>" . Context::fields()['site.name'] . "</a></h1></div>";
echo "</div>";
echo "</header>";