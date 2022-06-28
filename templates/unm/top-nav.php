<?php

use DigraphCMS\URL\URL;

?>
<div id="unm-top-nav">
    <div class="unm-top-nav__wrapper">
        <nav class="unm-navbar" role="complementary">
            <a class="navbar-brand" href="http://www.unm.edu">The University of New Mexico</a>

            <div class="menu">
                <div class="unm-menubar">
                    <a class="unm-menuitem" href="http://directory.unm.edu/departments/" title="UNM A to Z">UNM A-Z</a>
                    <a class="unm-menuitem" href="https://my.unm.edu" title="myUNM">myUNM</a>
                    <a class="unm-menuitem" href="http://directory.unm.edu" title="Directory">Directory</a>
                </div>
                <!-- search form -->
                <form action="<?php echo new URL('/~search/'); ?>" id="unm_search_form" method="get">
                    <div class="input-append search-query">
                        <input accesskey="4" id="unm_search_form_q" maxlength="255" name="search--query" placeholder="Search this site" title="input search query here" type="text">
                        <button accesskey="s" class="btn" id="unm_search_for_submit" title="submit search" type="submit">
                            <span class="fa fa-search"></span></button>
                    </div>
                </form>
                <!-- end search form -->
            </div>
        </nav>
    </div>
</div>