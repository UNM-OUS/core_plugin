<?php
// This file is a honeypot for catching bad actors reading and then disregarding robots.txt

use DigraphCMS\Context;
use Joby\Smol\Sentry\Severity;

Context::sentry()->signal('ignored_robots_txt', Severity::Malicious);
