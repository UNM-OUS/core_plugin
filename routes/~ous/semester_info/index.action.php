<h1>Semester information</h1>
<p>
    Each OUS website may have a different configuration for when semesters begin and end, as well as whether Summer semesters are included in their decision-making.
    This page displays information about the "current" semester as interpreted by this specific site, as well as information about upcoming semesters.
</p>
<?php

use DigraphCMS\UI\Format;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;

$semester = Semesters::current();

echo "<h2>This site considers now to be $semester</h2>";

echo '<table>';
echo '<tr><th>Semester</th><th>Start</th><th>End</th></tr>';
for ($i = 0; $i < 5; $i++) {
    echo '<tr>';
    echo "<td>$semester</td>";
    printf('<td>%s</td>', Format::date($semester->start()));
    printf('<td>%s</td>', Format::date($semester->end()));
    echo '</tr>';
    $semester = $semester->next();
}
echo '</table>';
