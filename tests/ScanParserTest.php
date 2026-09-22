<?php
use PHPUnit\Framework\TestCase;

final class ScanParserTest extends TestCase
{
    private const KNOWN = ['COO00001', 'COO00002', 'LIN00001'];

    public function test_one_code_per_line_is_counted()
    {
        $r = ScanParser::parse("COO00001\nCOO00001\nCOO00002\n", self::KNOWN);
        $this->assertSame(['COO00001' => 2, 'COO00002' => 1], $r['items']);
        $this->assertSame([], $r['unknown']);
        $this->assertSame(3, $r['scans']);
        $this->assertFalse($r['truncated']);
    }

    public function test_crlf_tabs_spaces_commas_and_semicolons_separate_codes()
    {
        $r = ScanParser::parse("COO00001\r\nCOO00002\tCOO00001, COO00002;COO00002", self::KNOWN);
        $this->assertSame(['COO00001' => 2, 'COO00002' => 3], $r['items']);
    }

    public function test_codes_typed_with_no_separator_are_split_on_the_known_codes()
    {
        $r = ScanParser::parse('COO00001COO00001LIN00001', self::KNOWN);
        $this->assertSame(['COO00001' => 2, 'LIN00001' => 1], $r['items']);
        $this->assertSame(3, $r['scans']);
    }

    public function test_a_run_that_does_not_split_cleanly_is_unknown_never_guessed()
    {
        $this->assertSame(['COO00001XYZ' => 1], ScanParser::parse('COO00001XYZ', self::KNOWN)['unknown']);
        //1234 is 12+34 or 123+4: two ways, so it is not guessed
        $this->assertSame(['1234' => 1], ScanParser::parse('1234', ['12', '123', '34', '4'])['unknown']);
    }

    public function test_a_code_with_a_quantity()
    {
        $r = ScanParser::parse("COO00001,10\nCOO00002 3\nCOO00001\t2\nLIN00001,0", self::KNOWN);
        $this->assertSame(['COO00001' => 12, 'COO00002' => 3], $r['items']);
        $this->assertSame(15, $r['scans']);
    }

    public function test_a_second_code_that_is_numeric_is_a_code_not_a_quantity()
    {
        $r = ScanParser::parse('COO00001 12345', ['COO00001', '12345']);
        $this->assertSame(['COO00001' => 1, '12345' => 1], $r['items']);
    }

    public function test_matching_ignores_case_and_leading_zeros_and_returns_the_stored_code()
    {
        $this->assertSame(['COO00001' => 1], ScanParser::parse('coo00001', self::KNOWN)['items']);
        $this->assertSame(['0123456' => 1], ScanParser::parse('123456', ['0123456'])['items']);
        $this->assertSame(['123456' => 1], ScanParser::parse('00123456', ['123456'])['items']);
    }

    public function test_control_characters_are_removed_and_empty_input_reads_nothing()
    {
        $this->assertSame(['COO00001' => 1], ScanParser::parse("\x02COO00001\x03\n", self::KNOWN)['items']);
        $this->assertSame(['items' => [], 'unknown' => [], 'scans' => 0, 'truncated' => false], ScanParser::parse("  \n\n", self::KNOWN));
    }

    public function test_an_upload_is_capped_at_5000_codes()
    {
        $r = ScanParser::parse(str_repeat("COO00001\n", 5001), self::KNOWN);
        $this->assertSame(5000, $r['scans']);
        $this->assertTrue($r['truncated']);
    }
}
