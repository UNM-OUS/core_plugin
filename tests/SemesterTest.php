<?php

namespace DigraphCMS_Plugins\unmous\ous_digraph_module;

use DateTime;
use DigraphCMS\Config;
use PHPUnit\Framework\TestCase;

class SemesterTest extends TestCase
{
    public function testConstructSpring(): Semester
    {
        $semester = new Semester(2000, 'Spring');
        $this->assertEquals('Spring 2000', $semester->__toString());
        return $semester;
    }

    public function testConstructSummer(): Semester
    {
        $semester = new Semester(2000, 'Summer');
        $this->assertEquals('Summer 2000', $semester->__toString());
        return $semester;
    }

    public function testConstructFall(): Semester
    {
        $semester = new Semester(2000, 'Fall');
        $this->assertEquals('Fall 2000', $semester->__toString());
        return $semester;
    }

    public function testFromString(): void
    {
        $this->assertEquals(
            new Semester(2000, 'spring'),
            Semesters::fromString('spring 2000')
        );
        $this->assertEquals(
            new Semester(2001, 'summer'),
            Semesters::fromString('summer 2001')
        );
        $this->assertEquals(
            new Semester(2002, 'fall'),
            Semesters::fromString('fall 2002')
        );
        $this->assertNull(
            Semesters::fromString('derp 2001')
        );
        $this->assertNull(
            Semesters::fromString('spring 999')
        );
        $this->assertNull(
            Semesters::fromString('spring 10000')
        );
    }

    public function testToString(): void
    {
        $this->assertEquals('Spring 2000', (new Semester(2000, 'spring'))->__toString());
        $this->assertEquals('Summer 2001', (new Semester(2001, 'Summer'))->__toString());
        $this->assertEquals('Fall 1999', (new Semester(1999, 'fALL'))->__toString());
    }

    public function testFromCode(): void
    {
        $this->assertEquals(
            new Semester(2000, 'spring'),
            Semesters::fromCode('200010')
        );
        $this->assertEquals(
            new Semester(2001, 'summer'),
            Semesters::fromCode('200160')
        );
        $this->assertEquals(
            new Semester(2002, 'fall'),
            Semesters::fromCode('200280')
        );
        $this->assertNull(
            Semesters::fromCode('200100')
        );
        $this->assertEquals(
            new Semester(2000, 'spring'),
            Semesters::fromCode(200010)
        );
        $this->assertEquals(
            new Semester(2001, 'summer'),
            Semesters::fromCode(200160)
        );
        $this->assertEquals(
            new Semester(2002, 'fall'),
            Semesters::fromCode(200280)
        );
        $this->assertNull(
            Semesters::fromCode(200100)
        );
    }

    public function testToInt(): void
    {
        $this->assertEquals(
            200010,
            (new Semester(2000, 'spring'))->intVal()
        );
        $this->assertEquals(
            200060,
            (new Semester(2000, 'summer'))->intVal()
        );
        $this->assertEquals(
            200080,
            (new Semester(2000, 'fall'))->intVal()
        );
    }

    public function testInvalidConstruct(): void
    {
        $this->expectExceptionMessage("Invalid semester name");
        new Semester(2000, 'Gorp');
    }

    public function testDefaultDates(): void
    {
        Config::set('unm.semesters.1800', null);
        $spring = new Semester(1800, 'spring');
        $this->assertEquals(1800, $spring->year());
        $this->assertEquals(1, $spring->month());
        $this->assertEquals(15, $spring->day());
        $summer = new Semester(1800, 'summer');
        $this->assertEquals(1800, $summer->year());
        $this->assertEquals(6, $summer->month());
        $this->assertEquals(1, $summer->day());
        $fall = new Semester(1800, 'fall');
        $this->assertEquals(1800, $fall->year());
        $this->assertEquals(8, $fall->month());
        $this->assertEquals(15, $fall->day());
    }

    public function testConfiguredDates(): void
    {
        Config::set('unm.semesters.1803', [
            'spring' => [2, 18],
            'summer' => [7, 8],
            'fall' => [9, 23],
        ]);
        $spring = new Semester(1803, 'spring');
        $this->assertEquals(1803, $spring->year());
        $this->assertEquals(2, $spring->month());
        $this->assertEquals(18, $spring->day());
        $summer = new Semester(1803, 'summer');
        $this->assertEquals(1803, $summer->year());
        $this->assertEquals(7, $summer->month());
        $this->assertEquals(8, $summer->day());
        $fall = new Semester(1803, 'fall');
        $this->assertEquals(1803, $fall->year());
        $this->assertEquals(9, $fall->month());
        $this->assertEquals(23, $fall->day());
    }

    public function testNext(): void
    {
        $spring = new Semester(2000, 'spring');
        $this->assertEquals(new Semester(2000, 'summer'), $spring->next());
        $this->assertEquals(new Semester(2000, 'fall'), $spring->next()->next());
        $this->assertEquals(new Semester(2001, 'spring'), $spring->next()->next()->next());
    }

    public function testNextFull(): void
    {
        $spring = new Semester(2000, 'spring');
        $this->assertEquals(new Semester(2000, 'fall'), $spring->nextFull());
        $this->assertEquals(new Semester(2001, 'spring'), $spring->nextFull()->nextFull());
        $summer = new Semester(2000, 'summer');
        $this->assertEquals(new Semester(2000, 'fall'), $summer->nextFull());
    }

    public function testPrevious(): void
    {
        $fall = new Semester(2000, 'fall');
        $this->assertEquals(new Semester(2000, 'summer'), $fall->previous());
        $this->assertEquals(new Semester(2000, 'spring'), $fall->previous()->previous());
        $this->assertEquals(new Semester(1999, 'fall'), $fall->previous()->previous()->previous());
    }

    public function testPrevousFull(): void
    {
        $fall = new Semester(2000, 'fall');
        $this->assertEquals(new Semester(2000, 'spring'), $fall->previousFull());
        $this->assertEquals(new Semester(1999, 'fall'), $fall->previousFull()->previousFull());
        $summer = new Semester(2000, 'summer');
        $this->assertEquals(new Semester(2000, 'spring'), $summer->previousFull());
    }

    public function testAllUpcoming()
    {
        $semester = new Semester(2000, 'spring');
        $count = 0;
        foreach ($semester->allUpcoming(10) as $next) {
            $this->assertEquals($semester->next(), $next);
            $semester = $next;
            $count++;
        }
        $this->assertEquals(10, $count);
        // pull 1000 from unbounded generator
        $count = 0;
        foreach ($semester->allUpcoming() as $s) {
            $count++;
            if ($count == 1000) break;
        }
        $this->assertEquals(1000, $count);
    }

    public function testAllPast()
    {
        $semester = new Semester(2000, 'spring');
        $count = 0;
        foreach ($semester->allPast(10) as $next) {
            $this->assertEquals($semester->previous(), $next);
            $semester = $next;
            $count++;
        }
        $this->assertEquals(10, $count);
        // pull 1000 from unbounded generator
        $count = 0;
        foreach ($semester->allPast() as $s) {
            $count++;
            if ($count == 1000) break;
        }
        $this->assertEquals(1000, $count);
    }

    public function testAllUpcomingFull()
    {
        $semester = new Semester(2000, 'spring');
        $count = 0;
        foreach ($semester->allUpcomingFull(10) as $next) {
            $this->assertEquals($semester->nextFull(), $next);
            $semester = $next;
            $count++;
        }
        $this->assertEquals(10, $count);
        // pull 1000 from unbounded generator
        $count = 0;
        foreach ($semester->allUpcomingFull() as $s) {
            $count++;
            if ($count == 1000) break;
        }
        $this->assertEquals(1000, $count);
    }

    public function testAllPastFull()
    {
        $semester = new Semester(2000, 'spring');
        $count = 0;
        foreach ($semester->allPastFull(10) as $next) {
            $this->assertEquals($semester->previousFull(), $next);
            $semester = $next;
            $count++;
        }
        $this->assertEquals(10, $count);
        // pull 1000 from unbounded generator
        $count = 0;
        foreach ($semester->allPastFull() as $s) {
            $count++;
            if ($count == 1000) break;
        }
        $this->assertEquals(1000, $count);
    }

    public function testDateTimeOutput(): void
    {
        Config::set('unm.semesters.1800', null);
        Config::set('unm.semesters.1801', null);
        $spring = new Semester(1800, 'spring');
        $this->assertEquals(
            (new DateTime())->setDate(1800, 1, 8)->setTime(0, 0, 0, 0),
            $spring->start()
        );
        $this->assertEquals(
            (new DateTime())->setDate(1800, 5, 25)->setTime(0, 0, 0, 0)->modify('-1 second'),
            $spring->end()
        );
        $summer = new Semester(1800, 'summer');
        $this->assertEquals(
            (new DateTime())->setDate(1800, 5, 25)->setTime(0, 0, 0, 0),
            $summer->start()
        );
        $this->assertEquals(
            (new DateTime())->setDate(1800, 8, 8)->setTime(0, 0, 0, 0)->modify('-1 second'),
            $summer->end()
        );
        $fall = new Semester(1800, 'fall');
        $this->assertEquals(
            (new DateTime())->setDate(1800, 8, 8)->setTime(0, 0, 0, 0),
            $fall->start()
        );
        $this->assertEquals(
            (new DateTime())->setDate(1801, 1, 8)->setTime(0, 0, 0, 0)->modify('-1 second'),
            $fall->end()
        );
    }

    public function testNextN(): void
    {
        $this->assertEquals(
            new Semester(2000, 'summer'),
            (new Semester(2000, 'summer'))->next(0)
        );
        $this->assertEquals(
            new Semester(2000, 'fall'),
            (new Semester(2000, 'summer'))->next()
        );
        $this->assertEquals(
            new Semester(2000, 'fall'),
            (new Semester(2000, 'summer'))->next(1)
        );
        $this->assertEquals(
            new Semester(2001, 'spring'),
            (new Semester(2000, 'summer'))->next(2)
        );
        $this->assertEquals(
            new Semester(2001, 'summer'),
            (new Semester(2000, 'summer'))->next(3)
        );
    }

    public function testNextFullN(): void
    {
        $this->assertEquals(
            new Semester(2000, 'summer'),
            (new Semester(2000, 'summer'))->nextFull(0)
        );
        $this->assertEquals(
            new Semester(2000, 'fall'),
            (new Semester(2000, 'summer'))->nextFull()
        );
        $this->assertEquals(
            new Semester(2000, 'fall'),
            (new Semester(2000, 'summer'))->nextFull(1)
        );
        $this->assertEquals(
            new Semester(2001, 'spring'),
            (new Semester(2000, 'summer'))->nextFull(2)
        );
        $this->assertEquals(
            new Semester(2001, 'fall'),
            (new Semester(2000, 'summer'))->nextFull(3)
        );
    }

    public function testPreviousN(): void
    {
        $this->assertEquals(
            new Semester(2000, 'summer'),
            (new Semester(2000, 'summer'))->previous(0)
        );
        $this->assertEquals(
            new Semester(2000, 'spring'),
            (new Semester(2000, 'summer'))->previous()
        );
        $this->assertEquals(
            new Semester(2000, 'spring'),
            (new Semester(2000, 'summer'))->previous(1)
        );
        $this->assertEquals(
            new Semester(1999, 'fall'),
            (new Semester(2000, 'summer'))->previous(2)
        );
        $this->assertEquals(
            new Semester(1999, 'summer'),
            (new Semester(2000, 'summer'))->previous(3)
        );
    }

    public function testPreviousFullN(): void
    {
        $this->assertEquals(
            new Semester(2000, 'summer'),
            (new Semester(2000, 'summer'))->previousFull(0)
        );
        $this->assertEquals(
            new Semester(2000, 'spring'),
            (new Semester(2000, 'summer'))->previousFull()
        );
        $this->assertEquals(
            new Semester(2000, 'spring'),
            (new Semester(2000, 'summer'))->previousFull(1)
        );
        $this->assertEquals(
            new Semester(1999, 'fall'),
            (new Semester(2000, 'summer'))->previousFull(2)
        );
        $this->assertEquals(
            new Semester(1999, 'spring'),
            (new Semester(2000, 'summer'))->previousFull(3)
        );
    }
}
