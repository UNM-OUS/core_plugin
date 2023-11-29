<?php

use DigraphCMS\Context;
use DigraphCMS\Media\Media;
use DigraphCMS\Users\Users;

$logo = Media::get('/unm-footer-logo.png');

?>
<footer id="footer">
    <div class="unm-info">
        <h1>
            <a href="http://www.unm.edu/">
                <img src="<?= $logo->url() ?>" height="60" width="300" alt="The University of New Mexico">
            </a>
        </h1>
        <p>
            &copy; The University of New Mexico
            <br>
            Albuquerque, NM 87131, (505) 277-0111
            <br>
            New Mexico's Flagship University
        </p>
    </div>
    <div class="unm-links">
        <p>
            <a href="https://www.facebook.com/universityofnewmexico" class="social-icon-link"><i class="fab fa-facebook"></i></a>
            <a href="http://instagram.com/uofnm" class="social-icon-link"><i class="fab fa-instagram"></i></a>
            <a href="https://twitter.com/unm" class="social-icon-link"><i class="fab fa-twitter"></i></a>
            <a href="http://www.youtube.com/user/unmlive" class="social-icon-link"><i class="fab fa-youtube"></i></a>
            <br>
            more at
            <a href="http://social.unm.edu/">social.unm.edu</a>
        </p>

        <p>
            <a href="http://www.unm.edu/accessibility.html">Accessibility</a>
            <a href="http://www.unm.edu/legal.html">Legal</a>
            <a href="http://www.unm.edu/contactunm.html">Contact UNM</a>
            <a href="https://www.unm.edu/consumer-information/"><span>Consumer Information</span></a>
            <a href="http://nmhedss2.state.nm.us/Dashboard/index.aspx?ID=21">New Mexico Higher Education Dashboard</a>
        </p>

        <p style="opacity:0.5;">
            <a href="<?php echo Users::signinUrl(Context::url()) ?>">Log in to this site</a>
        </p>
    </div>
</footer>