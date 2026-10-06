<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Includes/barcode_helper.php';

//Includes/barcode_helper.php: what a unit sticker needs that a plain label does not - how many
//copies, whether a job fits one page, and the bar height once the code takes two lines.
final class UnitLabelTest extends TestCase
{
    private function options($size_key, array $input = [])
    {
        $size = bcResolveLabelSize($size_key);
        return [bcResolveOptions($input, $size), $size];
    }

    // ---- copies ------------------------------------------------------------------------

    public function test_a_unit_gets_three_stickers_unless_told_otherwise()
    {
        [$options] = $this->options('50x25');

        $this->assertSame(3, $options['unit_copies']);
        $this->assertSame(1, $options['copies'], 'a plain label is still one sticker');
    }

    public function test_the_copies_of_a_unit_are_kept_between_one_and_a_hundred()
    {
        foreach ([[0, 1], [-4, 1], [250, 100], ['7', 7], ['abc', 3], ['', 3], [null, 3]] as [$posted, $expected]) {
            [$options] = $this->options('50x25', ['unit_copies' => $posted]);
            $this->assertSame($expected, $options['unit_copies'], var_export($posted, true));
        }
    }

    public function test_the_copies_of_a_raw_option_set_are_read_the_way_the_options_are()
    {
        $this->assertSame(3, bcUnitCopiesOf([]));
        $this->assertSame(3, bcUnitCopiesOf(null));
        $this->assertSame(5, bcUnitCopiesOf(['unit_copies' => '5']));
        $this->assertSame(100, bcUnitCopiesOf(['unit_copies' => 999]));
        $this->assertSame(1, bcUnitCopiesOf(['unit_copies' => 0]));
    }

    // ---- the one-page ceiling ----------------------------------------------------------

    public function test_a_job_that_fits_is_allowed()
    {
        $this->assertSame('', bcUnitJobCeiling(333, 3, 1000));
        $this->assertSame('', bcUnitJobCeiling(1000, 1, 1000));
        $this->assertSame('', bcUnitJobCeiling(0, 3, 1000), 'nothing to print is not too many');
    }

    public function test_a_job_that_does_not_fit_says_how_many_units_would()
    {
        $message = bcUnitJobCeiling(334, 3, 1000);

        $this->assertStringContainsString('1,002 stickers (334 units x 3 copies)', $message);
        $this->assertStringContainsString('at most 1,000', $message);
        $this->assertStringContainsString('up to 333 units', $message);
    }

    public function test_the_message_reads_properly_for_one_copy()
    {
        $this->assertStringContainsString('(1,001 units x 1 copy)', bcUnitJobCeiling(1001, 1, 1000));
    }

    // ---- the bars once the code takes two lines ----------------------------------------

    public function test_the_bars_keep_their_height_where_the_extra_line_fits()
    {
        [$options, $size] = $this->options('50x25');

        $this->assertEqualsWithDelta(8.0, bcUnitBarHeight($options, $size, true), 0.0001);
    }

    public function test_the_bars_give_back_what_the_extra_line_needs_on_the_smallest_sticker()
    {
        [$options, $size] = $this->options('25x15');

        $this->assertEqualsWithDelta(4.0, bcUnitBarHeight($options, $size, true), 0.0001);   //the profile says 4.6
    }

    public function test_a_bar_height_the_operator_set_is_left_alone()
    {
        [$options, $size] = $this->options('25x15', ['bar_height' => 4.6]);

        $this->assertEqualsWithDelta(4.6, bcUnitBarHeight($options, $size, false), 0.0001);
    }

    public function test_nothing_changes_without_bars_or_without_the_number()
    {
        [$noBars, $size] = $this->options('25x15', ['show_bars' => 0]);
        [$noCode] = $this->options('25x15', ['show_code' => 0]);

        $this->assertEqualsWithDelta($noBars['bar_height'], bcUnitBarHeight($noBars, $size, true), 0.0001);
        $this->assertEqualsWithDelta($noCode['bar_height'], bcUnitBarHeight($noCode, $size, true), 0.0001);
    }

    public function test_the_bars_never_shrink_below_three_millimetres()
    {
        [$options, $size] = $this->options('25x15', ['show_shop' => 1, 'show_second' => 1, 'show_cat' => 1,
            'show_sku' => 1, 'show_batch' => 1, 'show_date' => 1, 'show_footer' => 1]);

        $this->assertEqualsWithDelta(3.0, bcUnitBarHeight($options, $size, true), 0.0001);
    }

    public function test_every_standard_size_holds_the_two_line_code_at_its_default_lines()
    {
        foreach (bcGetLabelSizes() as $key => $profile) {
            [$options, $size] = $this->options($key);
            $bars = bcUnitBarHeight($options, $size, true);
            $inner = (float) $size['height'] - 2 * (float) $options['padding'];

            $this->assertLessThanOrEqual($inner + 0.0001, bcUnitContentHeight($options, $bars), $key . ' clips');
            if ($key !== '25x15') {
                $this->assertEqualsWithDelta((float) $profile['bar_height'], $bars, 0.0001,
                    $key . ' lost bar height it did not need to');
            }
        }
    }
}
