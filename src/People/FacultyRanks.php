<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\People;

class FacultyRanks {
    /**
     * A list of common ranks that should be used as-is if they match the given
     * title. This allows a simple, fast, and reliable method for converting a
     * title into a rank when it is obvious.
     */
    const COMMON_RANKS = [
        "Assistant Professor",
        "Associate Professor",
        "Associate Provost",
        "Clinician Educator - Assistant Professor",
        "Clinician Educator - Professor",
        "Distinguished Professor",
        "Lecturer I",
        "Lecturer II",
        "Lecturer III",
        "Lecturer",
        "Principal Lecturer I",
        "Principal Lecturer II",
        "Principal Lecturer III",
        "Professor of Practice",
        "Professor",
        "Research Assistant Professor",
        "Research Associate Professor",
        "Research Lecturer II",
        "Research Lecturer III",
        "Research Professor",
        "Research Scholar",
        "Senior Lecturer I",
        "Senior Lecturer II",
        "Senior Lecturer III",
        "Term Teaching Faculty",
        "Visiting Assistant Professor",
        "Visiting Instructor",
        "Visiting Lecturer I",
        "Visiting Lecturer II",
        "Visiting Lecturer III",
        "Visiting Lecturer",
        "Visiting Professor",
        "Visiting Research Assistant Professor",
        "Visiting Scholar",
        "Project Assistant",
    ];
    /**
     * More complex regexes for attempting to infer a rank from a less structured
     * academic_title entry.
     */
    const RANK_REGEX = [
        '/(clin(ician|cian|ical|ican)(\-| )ed(ucator)?( ?\- ?|\, ?| +)|visiting |adjunct |clinical |research )*(assist(ant)? |asst\.? |assoc(iate)? |distinguished )?prof(ess?or)?(of|in)?( |\,|\-|$)/',
        '/(clin(ician|cian|ical|ican)(\-| )ed(ucator)?( ?\- ?|\, ?| +)|visiting |adjunct |clinical )*(assist(ant)? |asst\.? )?instructor( |\,|\-|$)/',
        '/(adjunct |visiting |research )*(senior |principal )*lecturerr?( i{1,3}| l{1,3}| 1| 2| 3)?( |\,|\-|$)/',
        '/term teaching faculty/',
        '/project assist(ant)?/',
        '/research (scholar|scholor)/',
        '/post doctoral fellow/',
        '/research assistant/',
        '/visiting scholar/'
    ];
    const RANK_WORD_CORRECTIONS = [
        'Ii' => 'II',
        'Iii' => 'III',
        'L' => 'I',
        'Ll' => 'II',
        'Lll' => 'III',
        'Prof' => 'Professor',
        'Asst' => 'Assistant',
        'Asst.' => 'Assistant',
        'Assist' => 'Assistant',
        'Profesor' => 'Professor',
        'Professorof' => 'Professor',
        'Professorin' => 'Professor',
        '1' => 'I',
        '2' => 'II',
        '3' => 'III',
        'Lecturerr' => 'Lecturer',
        'Assoc' => 'Associate',
        'Scholor' => 'Scholar',
    ];

    public static function commonRankFromTitle(string $title): ?string
    {
        $title = trim(strtolower($title));
        foreach (static::COMMON_RANKS as $common) {
            if ($title == strtolower($common)) return $common;
        }
        return null;
    }

    public static function inferRankFromTitle(string $title, bool $attempt_inferences = false): ?string
    {
        $title = trim(strtolower($title));
        $rank = null;
        // fix mis-ordered lecturer numbers, because apparently some are in there like "Principal III Lecturer"
        $title = preg_replace('/(i{1,3}) lecturer/', 'lecturer $1', $title);
        // check regexes to try and infer a rank
        foreach (static::RANK_REGEX as $r) {
            if (preg_match($r, $title, $matches)) {
                $rank = trim($matches[0],",- \n\r\t\v\x00");
                break;
            }
        }
        // if a rank is inferred, try to clean it up before returning it
        if ($rank) {
            // fix clinician educator formatting
            /** @var string */
            $rank = preg_replace(
                '/(clin(ician|cian|ical|ican)(\-| )ed(ucator)?( ?\- ?|\, ?| +))/',
                'Clinician Educator - ',
                $rank
            );
            // uppercase words
            /** @var string[] */
            $rank = explode(' ', ucwords($rank));
            // fix known problems, i.e. numbers like II and III
            // or the *baffling* ll or lll (those are lower-case Ls instead of upper-case Is)
            foreach ($rank as $i => $word) {
                $rank[$i] = @static::RANK_WORD_CORRECTIONS[$word] ?? $word;
            }
            return implode(' ', $rank);
        } else {
            return null;
        }
    }

}