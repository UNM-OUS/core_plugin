<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module\People;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FacultyRanksTest extends TestCase
{
    #[DataProvider('parseRankFromTitleDataProvider')]
    public function testParseRankFromTitle(string $title, string|null $expected_rank): void
    {
        $inferred_rank = FacultyRankParser::inferRankFromTitle($title);
        $this->assertEquals(
            $expected_rank,
            $inferred_rank,
            sprintf(
                '"%s" inferred "%s" but should be "%s"',
                $title,
                $inferred_rank ?? 'NULL',
                $expected_rank ?? 'NULL'
            )
        );
    }

    /**
     * @return array<int,array<int,string|null>>
     */
    public static function parseRankFromTitleDataProvider(): array
    {
        return [
            ["Executive Vice President for Health Sciences Center", null],
            ["Profession of Sociology", "Professor"],
            ["Assist Professor of Internal Medicine", "Assistant Professor"],
            ["Assistant Professor of Internal Medicine", "Assistant Professor"],
            ["Assistant director of bands", null],
            ["Assistant Professor Chicano Studies", "Assistant Professor"],
            ["Assistant Professor for Fine Arts", "Assistant Professor"],
            ["Assistant Professor in American Literature", "Assistant Professor"],
            ["Assistant Professor iun Mechanical Engineering", "Assistant Professor"],
            ["Assistant Professor of Accounting", "Assistant Professor"],
            ["Assistant Professor or Open Educational Resources (OER)", "Assistant Professor"],
            ["Assistant Professor", "Assistant Professor"],
            ["Associate Professor in University Libraries & Learning Sciences", "Associate Professor"],
            ["Associate Professor of Exercise Science", "Associate Professor"],
            ["Associate Profesor of Mathematics and Statistics", "Associate Professor"],
            ["Associate Professor Civil Engineering", "Associate Professor"],
            ["Associate Professor in Chicano and Chicana Studies", "Associate Professor"],
            ["Associate Professor Mathematics and Statistics", "Associate Professor"],
            ["Associate Professor of  Biochemistry/Molecular Biology", "Associate Professor"],
            ["Associate Professor of American Studies", "Associate Professor"],
            ["Associate Professor or  College of University Libraries and Learning Sciences", "Associate Professor"],
            ["Associate Professorof Molecular Genetics and Microbiology", "Associate Professor"],
            ["Chair of Nuclear Engineering", null],
            ["Chairperson of College of Pharmacy", null],
            ["Chairperson of Emergency Medicine", null],
            ["Clincian Educator-Assistant Professor of Surgery", "Clinician Educator - Assistant Professor"],
            ["Clinical Educator -Assistant Professor of Radiology", "Clinician Educator - Assistant Professor"],
            ["Clinical Educator Assistant Professor of Nursing", "Clinician Educator - Assistant Professor"],
            ["Clinical Educator-Assistant Professor of Dermatology", "Clinician Educator - Assistant Professor"],
            ["Clinical Educator, Associate Professor of Nursing", "Clinician Educator - Associate Professor"],
            ["Clinican Educator - Assistant Professor of Anesthesiology", "Clinician Educator - Assistant Professor"],
            ["Clinican Educator-Assistant Professor of Internal Medicine", "Clinician Educator - Assistant Professor"],
            ["Clinician Ed-Assist Prof of Orthopaedics", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator  Assistant Professor of Internal Medicine in Hospital Medici", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator - Assistant Professor of Anesthesiology", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator - Assistant Professor", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator - Associate Professor of Internal Medicine in Endocrinology", "Clinician Educator - Associate Professor"],
            ["Clinician Educator - Professor of Anesthesiology", "Clinician Educator - Professor"],
            ["Clinician Educator - Professor of Internal Medicine in Cardiology", "Clinician Educator - Professor"],
            ["Clinician Educator - Professor of Neurology", "Clinician Educator - Professor"],
            ["Clinician Educator -Assistant Professor of  Radiology", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator Assistant Professor of Family & Community Medicine", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator Assistant Professor of Internal Medicine in Critical Care", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator Assistant Professor of Internal Medicine in Gastroenterology", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator Assistant Professor of Internal Medicine in Hematology", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator Assistant Professor of Internal Medicine in Hematology/Onco", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator Assistant Professor of Internal Medicine in Hospital Medicine", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator Assistant Professor of Internal Medicine in Nephrology", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator Assistant Professor of Neurology", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator Assistant Professor of Obstetrics and Gynecology", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator Assistant Professor of Pharmacy", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator Assistant Professor of Radiology In Neuroradiology", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator Assistant Professor ofAnesthesiology", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator Associate Professor in Emergency Medicine", "Clinician Educator - Associate Professor"],
            ["Clinician Educator Associate Professor of Internal Medicine in Nephrology", "Clinician Educator - Associate Professor"],
            ["Clinician Educator Associate Professor of Neurology", "Clinician Educator - Associate Professor"],
            ["Clinician Educator Professor of Family & Community Medicine", "Clinician Educator - Professor"],
            ["Clinician Educator Professor of Pediatrics", "Clinician Educator - Professor"],
            ["Clinician Educator Professor of Radiology in Abdominal Radiology", "Clinician Educator - Professor"],
            ["Clinician Educator- Assistant Professor of Dental Medicine", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator- Assistant Professor of Dermatology", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator-Assistant Professor of Internal Medicine Hematology \ Oncolo", "Clinician Educator - Assistant Professor"],
            ["Clinician Educator-Professor of Surgery", "Clinician Educator - Professor"],
            ["Dean of CULLS", null],
            ["Dean of Instruction", null],
            ["Director of Africana Studies", null],
            ["Distinguished Professor of Biology", "Distinguished Professor"],
            ["Learning Services Librarian", null],
            ["Lecturer 1 of Nursing", "Lecturer I"],
            ["Lecturer I in Business and Applied Tech", "Lecturer I"],
            ["Lecturer II Health Information Technology", "Lecturer II"],
            ["Lecturer III Geography and Environmental Studies", "Lecturer III"],
            ["Lecturer in Construction", "Lecturer"],
            ["Lecturerr II of Cell Biology & Physiology", "Lecturer II"],
            ["Post Doctoral Fellow", "Post Doctoral Fellow"],
            ["Principal III Lecturer of Chemistry", "Principal Lecturer III"],
            ["Principal Lecturer I in Management", "Principal Lecturer I"],
            ["Principal Lecturer II of Pathology", "Principal Lecturer II"],
            ["Principal Lecturer III  in Religious Studies", "Principal Lecturer III"],
            ["Principal Senior Lecturer III in Special Education", "Principal Senior Lecturer III"],
            ["Professor Arts & Sciences UNM Gallup", "Professor"],
            ["Professor in  Nuclear Engineering", "Professor"],
            ["Professor in Emergency Medicine", "Professor"],
            ["Professor of  Internal Medicine", "Professor"],
            ["Professor", "Professor"],
            ["Project Assistant", "Project Assistant"],
            ["Public Services Librarian", null],
            ["Rank of Discipline", null],
            ["Research Assistant Professor in Biology", "Research Assistant Professor"],
            ["Research Assistant Professor of Pediatrics", "Research Assistant Professor"],
            ["Research Assistant", "Research Assistant"],
            ["Research Assoc Professor in Earth and Planetary Sciences", "Research Associate Professor"],
            ["Research Assoc Professor of Pharmacy", "Research Associate Professor"],
            ["Research Associate Professor Dept. Science & Math", "Research Associate Professor"],
            ["Research Associate Professor of Hemotology/Oncology", "Research Associate Professor"],
            ["Research Associate Professor of Internal Medicine in Translational Informatics", "Research Associate Professor"],
            ["Research Asst Professor of Surgery", "Research Assistant Professor"],
            ["Research Asst. Professor in Biology", "Research Assistant Professor"],
            ["Research Lecturer III of Physics and Astronomy", "Research Lecturer III"],
            ["Research Professor in Chemical and Biological Engineering", "Research Professor"],
            ["Research Professor of Biocomputing", "Research Professor"],
            ["Research Professor", "Research Professor"],
            ["Research Scholar in Civil Engineering", "Research Scholar"],
            ["Research Scholar in Electrical and Computer Engineering", "Research Scholar"],
            ["Research Scholar, Taos Education Center", "Research Scholar"],
            ["Research Scholar", "Research Scholar"],
            ["Research Scholor in COSMIAC", "Research Scholar"],
            ["Research Scholor of COSMIAC Research Center", "Research Scholar"],
            ["Senior Lecturer III", "Senior Lecturer III"],
            ["Senior Lecturer I in Nursing", "Senior Lecturer I"],
            ["Senior Lecturer II in Computer Science", "Senior Lecturer II"],
            ["Senior Lecturer II of Sustainability Studies", "Senior Lecturer II"],
            ["Senior Lecturer of Emergency Medicine", "Senior Lecturer"],
            ["Teaching Assistant", null],
            ["Temporary Part-Time in English", null],
            ["Term Teaching Faculty  in Reglious Studies", "Term Teaching Faculty"],
            ["Term Teaching Faculty in Sociology", "Term Teaching Faculty"],
            ["Term Teaching Faculty of English", "Term Teaching Faculty"],
            ["Term Teaching Faculty of Mathematics and Statistics", "Term Teaching Faculty"],
            ["Vice President", null],
            ["Visiting Assistant Professor of Architecture", "Visiting Assistant Professor"],
            ["Visiting Assistant Professor of Family and Community Medicine", "Visiting Assistant Professor"],
            ["Visiting Assistant Professor of Management", "Visiting Assistant Professor"],
            ["Visiting Assistant Professor", "Visiting Assistant Professor"],
            ["Visiting Asst Professor Gallup Branch", "Visiting Assistant Professor"],
            ["Visiting Instructor in Emergency Medicine", "Visiting Instructor"],
            ["Visiting Instructor in Emergency Medicinie", "Visiting Instructor"],
            ["Visiting Instructor of Psychiatry and Behavioral Sciences", "Visiting Instructor"],
            ["Visiting Instructor of Surgery", "Visiting Instructor"],
            ["Visiting Instructor- Internal Medicine", "Visiting Instructor"],
            ["Visiting Instructor", "Visiting Instructor"],
            ["Visiting Lecturer II for Mathematics", "Visiting Lecturer II"],
            ["Visiting Lecturer II in Arts and Sciences", "Visiting Lecturer II"],
            ["Visiting Lecturer II in Biology", "Visiting Lecturer II"],
            ["Visiting Lecturer II in Nursing", "Visiting Lecturer II"],
            ["Visiting Lecturer II of Natural Resource Management", "Visiting Lecturer II"],
            ["Visiting Lecturer II of Teacher Education, Educational Leadership and Policy", "Visiting Lecturer II"],
            ["Visiting Lecturer II", "Visiting Lecturer II"],
            ["Visiting Lecturer III in Family & Child Studies", "Visiting Lecturer III"],
            ["Visiting Lecturer III of Biology", "Visiting Lecturer III"],
            ["Visiting Professor of Theater and Dance", "Visiting Professor"],
            ["Visiting Professor", "Visiting Professor"],
            ["Visiting Scholar", "Visiting Scholar"],
        ];
    }
}