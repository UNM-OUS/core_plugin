<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\Forms;

use DigraphCMS\HTML\Forms\InputInterface;

class Validation
{

    public static function unmUrl(): callable
    {
        return static::domainUrl(['unm.edu', 'office.com'], 'Only UNM websites are allowed in this field');
    }

    /**
     * @param string|string[] $domain 
     */
    public static function domainUrl(string|array $domain, string|null $message = null): callable
    {
        $domain = (array)$domain;
        return function (InputInterface $input) use ($domain, $message): string|null {
            if (!$input->value())
                return null;
            $url = parse_url($input->value(), PHP_URL_HOST);
            if (!$url) return "Please enter a valid URL";
            $url = strtolower($url);
            foreach ($domain as $d) {
                if ($url == $d) return null;
                if (preg_match('/\.' . preg_quote($d) . '$/', $url)) return null;
            }
            if ($message) return $message;
            $list = implode(', ', $domain);
            $list = preg_replace('/, ([^,]+)$/', ', and $1', $list);
            return "Only URLs from $list and their subdomains are allowed in this field";
        };
    }

    public static function netIDorEmail(): callable
    {
        return function (InputInterface $input): string|null {
            if (!$input->value())
                return null;
            if (strpos($input->value(), '@') !== false) {
                // validate as email
                // make sure it's a valid email address
                if (!filter_var($input->value(), FILTER_VALIDATE_EMAIL)) {
                    return "Please enter a valid email address or NetID";
                }
                // disallow alternate unm emails
                if (preg_match('/@.+\.unm\.edu$/', $input->value(), $matches)) {
                    return "Anyone associated with UNM should be referenced by their main campus NetID, not their <em>" . $matches[0] . "</em> email address. This is in many cases important for data consistency and login system integrations.";
                }
                // return null
                return null;
            } else {
                return static::netID()($input);
            }
        };
    }

    public static function netID(): callable
    {
        return function (InputInterface $input): string|null {
            if (!$input->value())
                return null;
            // validate as NetID
            return static::validateNetId($input->value());
        };
    }

    public static function netIdWithExtension(): callable
    {
        return function (InputInterface $input): string|null {
            if (!$input->value())
                return null;
            // split on dots, so we can validate the NetID part this field lets
            // you enter something like regents.jackfortner to indicate that
            // this is a person who doesn't have a NetID, but should be
            // associated with the NetID "regents"
            $parts = explode('.', $input->value());
            if (count($parts) > 2) {
                return "NetID may only have one extension followed by a dot";
            }
            // validate first part, the NetID
            $netId_validation = static::validateNetId($parts[0]);
            if ($netId_validation) {
                return $netId_validation;
            }
            // validate the second part, the extension (it should be alphanumeric)
            if (isset($parts[1])) {
                if (!preg_match('/^[a-z0-9]+$/', $parts[1])) {
                    return "NetID extensions must be alphanumeric";
                }
            }
            // return null if both parts are valid
            return null;
        };
    }

    protected static function validateNetId(string $input): string|null
    {
        if (preg_match('/^[0-9]{9}$/', $input)) {
            return "Please enter a NetID username, not a Banner ID number";
        }
        if (!preg_match('/^[a-z].{1,19}$/', $input)) {
            return "NetIDs must be 2-20 characters and begin with a letter";
        }
        if (preg_match('/[^a-z0-9_]/', $input)) {
            return "NetIDs must contain only alphanumeric characters and underscores";
        }
        return null;
    }

    public static function notUglyCase(): callable
    {
        return function (InputInterface $input): string|null {
            if (!$input->value())
                return null;
            if ($input->value() == strtoupper($input->value()))
                return 'Please do not enter a value in all upper case';
            if ($input->value() == strtolower($input->value()))
                return 'Please do not enter a value in all lower case';
            return null;
        };
    }

    public static function notInQuotes(): callable
    {
        return function (InputInterface $input): string|null {
            $quotes = ['"', "'", '“', '”', '‘', '’', '«', '»', '「', '」'];
            if (in_array(substr($input->value(), 0, 1), $quotes) && in_array(substr($input->value(), -1, 1), $quotes)) {
                return 'Please do not put quotes around this field\'s value';
            }
            return null;
        };
    }

    public static function integerMin(int $min): callable
    {
        return function (InputInterface $input) use ($min): string|null {
            if ($input->value() == '')
                return null;
            $value = intval($input->value());
            if ($value < $min)
                return 'Must be at least ' . number_format($min);
            return null;
        };
    }

    public static function integerMax(int $max): callable
    {
        return function (InputInterface $input) use ($max): string|null {
            if ($input->value() == '')
                return null;
            $value = intval($input->value());
            if ($value > $max)
                return 'Cannot be more than ' . number_format($max);
            return null;
        };
    }
}
