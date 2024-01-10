<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DigraphCMS\HTML\A;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class ShortCodeLinks
{
    public static function onShortCode_rpm(ShortcodeInterface $s): ?string
    {
        $url = null;
        $title = 'RPM Policy';
        $name = 'RPM Policy';
        if (!$s->getBbCode()) {
            $url = "https://policy.unm.edu/regents-policies/";
            $title = "UNM Regents' Policy Manual";
            $name = "Regents' Policy Manual";
        } elseif (preg_match('/([0-9]+)(\.([0-9]+))?/', $s->getBbCode(), $matches)) {
            $section = intval($matches[1]);
            $number = @$matches[3] ? intval($matches[3]) : null;
            if ($number) {
                $url = sprintf(
                    'https://policy.unm.edu/regents-policies/section-%s/%s-%s.html',
                    $section,
                    $section,
                    $number
                );
                $title = "UNM Regents' Policy Manual Section $section.$number";
                $name = "RPM $section.$number";
            } else {
                $url = sprintf(
                    'https://policy.unm.edu/regents-policies/section-%s/index.html',
                    $section
                );
                $title = "UNM Regents' Policy Manual Section $section";
                $name = "RPM $section";
            }
        } elseif ($s->getBbCode() == 'preface') {
            $url = 'https://policy.unm.edu/regents-policies/index.html';
            $title = "UNM Regents' Policy Manual Preface";
            $name = "RPM Preface";
        } elseif ($s->getBbCode() == 'toc') {
            $url = 'https://policy.unm.edu/regents-policies/table-of-contents.html';
            $title = "UNM Regents' Policy Manual Table of Contents";
            $name = "RPM Table of Contents";
        } elseif ($s->getBbCode() == 'foreword') {
            $url = 'https://policy.unm.edu/regents-policies/foreword.html';
            $title = "UNM Regents' Policy Manual Foreword";
            $name = "RPM Foreword";
        } else {
            return null;
        }
        return (new A($url))
            ->addChild($s->getContent() ?? $name)
            ->setAttribute('title', $title);
    }

    public static function onShortCode_uap(ShortcodeInterface $s): ?string
    {
        $url = null;
        $title = 'UAP Policy';
        $name = 'UAP Policy';
        if (!$s->getBbCode() || $s->getBbCode() == 'preface') {
            $url = 'https://policy.unm.edu/university-policies/index.html';
            $title = "UAP Preface";
            $name = "University Administrative Policies";
        } elseif ($s->getBbCode() == 'toc') {
            $url = 'https://policy.unm.edu/university-policies/table-of-contents.html';
            $title = $name = "UAP Table of Contents";
        } else {
            $number = intval($s->getBbCode());
            $section = floor($number / 1000) * 1000;
            $url = sprintf(
                'https://policy.unm.edu/university-policies/%s/%s.html',
                $section,
                $number
            );
            $title = "UNM University Administrative Policy $number";
            $name = "UAP $number";
        }
        return (new A($url))
            ->addChild($s->getContent() ?? $name)
            ->setAttribute('title', $title);
    }

    public static function onShortCode_fhb(ShortcodeInterface $s): ?string
    {
        $url = null;
        $title = 'Faculty Handbook Policy';
        $name = 'Faculty Handbook Policy';
        if (!$s->getBbCode()) {
            $url = "https://handbook.unm.edu/";
            $title = $name = "Faculty Handbook";
        } elseif (preg_match('/([a-f])([0-9]+)(\.([0-9]+))?/', $s->getBbCode(), $matches)) {
            $section = $matches[1];
            $number1 = intval($matches[2]);
            $number2 = @$matches[4] ? intval($matches[4]) : null;
            if ($number1 && $number2) {
                $url = sprintf(
                    'https://handbook.unm.edu/%s%s_%s/',
                    $section,
                    $number1,
                    $number2
                );
                $name = sprintf(
                    'Faculty Handbook Policy %s%s.%s',
                    strtoupper($section),
                    $number1,
                    $number2
                );
                $title = "UNM $name";
            } elseif ($number1) {
                $url = sprintf(
                    'https://handbook.unm.edu/%s%s/',
                    $section,
                    $number1
                );
                $name = sprintf(
                    'Faculty Handbook Policy %s%s',
                    strtoupper($section),
                    $number1
                );
                $title = "UNM $name";
            } else {
                $url = sprintf(
                    'https://handbook.unm.edu/section_%s/',
                    $section
                );
                $name = sprintf(
                    'Faculty Handbook Section %s',
                    strtoupper($section)
                );
                $title = "UNM $name";
            }
        } elseif ($s->getBbCode() == 'updates') {
            $url = 'https://handbook.unm.edu/policy_updates/';
            $name = "FHB Update History";
            $title = "UNM Faculty Handbook Update History";
        } elseif ($s->getBbCode() == 'comment') {
            $url = 'https://handbook.unm.edu/under_review/';
            $name = "FHB Public Comment Periods";
            $title = "UNM Faculty Handbook Public Comment Periods";
        } elseif ($s->getBbCode() == 'toc') {
            $url = 'https://handbook.unm.edu/policies/';
            $name = "FHB Table of Contents";
            $title = "UNM Faculty Handbook Table of Contents";
        } else {
            return null;
        }
        return (new A($url))
            ->addChild($s->getContent() ?? $name)
            ->setAttribute('title', $title);
    }
}
