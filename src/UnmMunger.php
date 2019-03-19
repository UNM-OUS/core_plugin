<?php
namespace Digraph\Modules\ous_digraph_module;

class UnmMunger extends \Digraph\Mungers\AbstractMunger
{
    protected function doMunge(&$package)
    {
        $config = $package->cms()->config;
        if ($config['unm.cpanelhttps']) {
            $this->makeHttps();
        }
    }

    protected function doConstruct($name)
    {
    }

    public function makeHttps()
    {
        if (!$this->isHttps()) {
            $newUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $newUrl = preg_replace('/index\.(php)$/i', '', $newUrl);
            if (count($_GET) >= 1) {
                $newUrl .= '?'.http_build_query($_GET);
            }
            header("Location: $newUrl");
            die('Attempting to make this page secure by redirecting to ' . $newUrl);
        }
    }

    protected function isHttps()
    {
        //use $_SERVER['HTTPS'] if it exists
        if (isset($_SERVER['HTTPS'])) {
            return $_SERVER['HTTPS'] !== 'off';
        }
        //if we're on port 443 return true
        if ($_SERVER['SERVER_PORT'] == 443) {
            return true;
        }
        //as a fallback, set a cookie that the browser is asked to only
        //send back over HTTPS
        if (!isset($_COOKIE['UNM_isHttps_HttpsOnly'])) {
            setcookie('UNM_isHttps_HttpsOnly', time(), 0, "", "", true);
            return false;
        }
        return true;
    }
}
