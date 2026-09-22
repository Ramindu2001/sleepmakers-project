<?php
/**
 * Barcode label helper.
 * -----------------------------------------------------------------------------
 * Shared logic for the product barcode LABEL module - sticker geometry, roll
 * layout, symbology, and every content / typography option the print dialog
 * offers.
 *
 * This module is deliberately SELF CONTAINED. It does NOT read the `label`
 * configuration table (Settings > Label Print), so printing keeps working on
 * every shop even when no label profile has ever been uploaded.
 *
 * The one rule that shapes the whole file:
 *
 *      ONE PRINTED PAGE IS ONE ROW OF LABELS, NOT ONE LABEL.
 *
 * A 2 across roll printed one label per page leaves the entire right hand
 * column blank - half the stock in the bin - and, because the browser page is
 * then narrower than the media, the print driver is free to centre it, which
 * drops the content over the die cut gap. Sizing the page to a full row uses
 * every sticker AND makes the page match the media exactly.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Picqer\Barcode\BarcodeGenerator;
use Picqer\Barcode\BarcodeGeneratorSVG;

if (!function_exists('bcGetLabelSizes')) {

    /**
     * The standard sticker sizes. All measurements are millimetres.
     *
     * Every size carries its own typography so a 30x20 sticker stays readable
     * without the content spilling outside the sticker area. The vertical
     * budget of every size is
     *
     *      padding*2 + (shop + name + code + price) * 1.05 + bars + line_gap*4
     *
     * and is kept about 0.8mm under the sticker height so nothing is ever
     * clipped, even with every content line switched on.
     */
    function bcGetLabelSizes()
    {
        return array(
            '100x50' => array(
                'name'       => '100mm x 50mm',
                'width'      => 100.0,
                'height'     => 50.0,
                'padding'    => 2.5,
                'line_gap'   => 0.8,
                'shop_font'  => 4.0,
                'name_font'  => 4.4,
                'code_font'  => 3.6,
                'price_font' => 7.5,
                'small_font' => 3.0,
                'bar_height' => 16.0,
            ),
            '50x30' => array(
                'name'       => '50mm x 30mm',
                'width'      => 50.0,
                'height'     => 30.0,
                'padding'    => 1.4,
                'line_gap'   => 0.5,
                'shop_font'  => 2.5,
                'name_font'  => 2.9,
                'code_font'  => 2.5,
                'price_font' => 4.6,
                'small_font' => 2.0,
                'bar_height' => 10.0,
            ),
            '50x25' => array(
                'name'       => '50mm x 25mm',
                'width'      => 50.0,
                'height'     => 25.0,
                'padding'    => 1.2,
                'line_gap'   => 0.4,
                'shop_font'  => 2.3,
                'name_font'  => 2.7,
                'code_font'  => 2.4,
                'price_font' => 4.2,
                'small_font' => 1.9,
                'bar_height' => 8.0,
            ),
            '40x30' => array(
                'name'       => '40mm x 30mm',
                'width'      => 40.0,
                'height'     => 30.0,
                'padding'    => 1.4,
                'line_gap'   => 0.5,
                'shop_font'  => 2.3,
                'name_font'  => 2.7,
                'code_font'  => 2.3,
                'price_font' => 4.4,
                'small_font' => 1.9,
                'bar_height' => 10.0,
            ),
            '38x25' => array(
                'name'       => '38mm x 25mm',
                'width'      => 38.0,
                'height'     => 25.0,
                'padding'    => 1.2,
                'line_gap'   => 0.4,
                'shop_font'  => 2.1,
                'name_font'  => 2.5,
                'code_font'  => 2.2,
                'price_font' => 4.0,
                'small_font' => 1.8,
                'bar_height' => 7.6,
            ),
            '34x25' => array(
                'name'       => '34mm x 25mm',
                'width'      => 34.0,
                'height'     => 25.0,
                'padding'    => 1.2,
                'line_gap'   => 0.4,
                'shop_font'  => 2.0,
                'name_font'  => 2.4,
                'code_font'  => 2.1,
                'price_font' => 3.8,
                'small_font' => 1.7,
                'bar_height' => 7.2,
            ),
            '34x20' => array(
                'name'       => '34mm x 20mm',
                'width'      => 34.0,
                'height'     => 20.0,
                'padding'    => 1.0,
                'line_gap'   => 0.3,
                'shop_font'  => 1.9,
                'name_font'  => 2.2,
                'code_font'  => 2.0,
                'price_font' => 3.4,
                'small_font' => 1.6,
                'bar_height' => 6.0,
            ),
            '30x20' => array(
                'name'       => '30mm x 20mm',
                'width'      => 30.0,
                'height'     => 20.0,
                'padding'    => 1.0,
                'line_gap'   => 0.3,
                'shop_font'  => 1.8,
                'name_font'  => 2.1,
                'code_font'  => 1.9,
                'price_font' => 3.2,
                'small_font' => 1.5,
                'bar_height' => 5.8,
            ),
            '25x15' => array(
                'name'       => '25mm x 15mm',
                'width'      => 25.0,
                'height'     => 15.0,
                'padding'    => 0.8,
                'line_gap'   => 0.25,
                'shop_font'  => 1.6,
                'name_font'  => 1.8,
                'code_font'  => 1.7,
                'price_font' => 2.8,
                'small_font' => 1.4,
                'bar_height' => 4.6,
            ),
        );
    }//bcGetLabelSizes

    /**
     * Default sticker size used when nothing valid was selected.
     */
    function bcDefaultLabelSize()
    {
        return '50x25';
    }//bcDefaultLabelSize

    /**
     * Smallest and largest sticker edge this module will accept, in mm.
     */
    function bcMinLabelMm()
    {
        return 15.0;
    }//bcMinLabelMm

    function bcMaxLabelMm()
    {
        return 297.0;
    }//bcMaxLabelMm

    /**
     * Most columns this module will lay out across one roll or sheet.
     */
    function bcMaxLabelsAcross()
    {
        return 8;
    }//bcMaxLabelsAcross

    /**
     * Hard ceiling on a single print job, so one mistyped quantity cannot lock
     * up the operator's browser.
     *
     * Measured: every sticker carries its own inline SVG, so 2000 labels is a
     * 5MB page and roughly 200,000 DOM nodes for the browser to lay out and
     * then hand to the print engine. 1000 halves that and is still 25 metres of
     * 50x25 stock; the print page tells the operator to run the rest as a
     * second batch.
     */
    function bcMaxLabelsPerJob()
    {
        return 1000;
    }//bcMaxLabelsPerJob

    /**
     * Sanitise a millimetre value coming from the browser.
     */
    function bcCleanMm($value, $min = 0.0, $max = 297.0)
    {
        $value = str_replace(array(',', ' '), '', (string) $value);
        if (!is_numeric($value)) {
            return $min;
        }//not a number

        $value = round((float) $value, 2);

        if ($value < $min) {
            $value = $min;
        }//floor
        if ($value > $max) {
            $value = $max;
        }//ceiling

        return $value;
    }//bcCleanMm

    /**
     * Sanitise a quantity coming from the browser.
     */
    function bcCleanQty($qty, $max = 999)
    {
        $qty = (int) $qty;
        if ($qty < 1) {
            $qty = 1;
        }//minimum one
        if ($qty > $max) {
            $qty = $max;
        }//cap

        return $qty;
    }//bcCleanQty

    /**
     * Sanitise a price coming from the browser.
     */
    function bcCleanPrice($price)
    {
        $price = str_replace(array(',', ' '), '', (string) $price);
        if (!is_numeric($price)) {
            return 0.00;
        }//not a number

        $price = (float) $price;
        if ($price < 0) {
            $price = 0.00;
        }//never negative

        return round($price, 2);
    }//bcCleanPrice

    /**
     * Build a size definition for stock that is not one of the presets.
     *
     * Typography is scaled off the 50x25 profile by the HEIGHT ratio, because
     * height - not width - is what makes a label clip. The bar height is then
     * trimmed if the content lines would not fit, so a custom size behaves just
     * like one of the hand tuned presets.
     *
     * Returns an empty array when the measurements are unusable.
     */
    function bcCustomLabelSize($width, $height)
    {
        $width  = bcCleanMm($width, 0, bcMaxLabelMm());
        $height = bcCleanMm($height, 0, bcMaxLabelMm());

        if ($width < bcMinLabelMm() || $height < bcMinLabelMm()) {
            return array();
        }//too small to print on

        $sizes = bcGetLabelSizes();
        $base = $sizes['50x25'];

        $scale = $height / 25.0;
        if ($scale < 0.70) {
            $scale = 0.70;
        }//keep the text legible
        if ($scale > 2.60) {
            $scale = 2.60;
        }//stop it ballooning

        $trim = function ($mm) {
            return rtrim(rtrim(number_format($mm, 1, '.', ''), '0'), '.');
        };

        $size = array(
            'name'       => $trim($width) . 'mm x ' . $trim($height) . 'mm',
            'width'      => $width,
            'height'     => $height,
            'padding'    => round($base['padding'] * $scale, 2),
            'line_gap'   => round($base['line_gap'] * $scale, 2),
            'shop_font'  => round($base['shop_font'] * $scale, 2),
            'name_font'  => round($base['name_font'] * $scale, 2),
            'code_font'  => round($base['code_font'] * $scale, 2),
            'price_font' => round($base['price_font'] * $scale, 2),
            'small_font' => round($base['small_font'] * $scale, 2),
            'bar_height' => round($base['bar_height'] * $scale, 2),
            'key'        => 'custom',
        );

        /*
         * Vertical budget, mirroring the note on bcGetLabelSizes(). Anything
         * over budget comes off the bars first - they are the only element that
         * still does its job after losing height.
         */
        $text = ($size['shop_font'] + $size['name_font'] + $size['code_font'] + $size['price_font']) * 1.05;
        $fixed = ($size['padding'] * 2) + $text + ($size['line_gap'] * 4);
        $available = $height - 0.8 - $fixed;

        if ($available < $size['bar_height']) {
            $size['bar_height'] = round(max(3.0, $available), 2);
        }//trim the bars to fit

        return $size;
    }//bcCustomLabelSize

    /**
     * Resolve a posted size key to a real size definition. Always returns a
     * valid definition so the print page can never render with a 0mm page.
     */
    function bcResolveLabelSize($size_key, $custom_width = 0, $custom_height = 0)
    {
        $sizes = bcGetLabelSizes();
        $size_key = is_string($size_key) ? trim($size_key) : '';

        if ($size_key === 'custom') {
            $custom = bcCustomLabelSize($custom_width, $custom_height);
            if (!empty($custom)) {
                return $custom;
            }//usable measurements
            $size_key = bcDefaultLabelSize();
        }//custom stock

        if ($size_key === '' || !isset($sizes[$size_key])) {
            $size_key = bcDefaultLabelSize();
        }//unknown size

        $size = $sizes[$size_key];
        $size['key'] = $size_key;

        return $size;
    }//bcResolveLabelSize

    /**
     * Resolve how the stickers sit across the roll.
     *
     * Returns the column count, both gaps, and the page size to print at.
     */
    function bcResolveLayout($input, $size)
    {
        $across = isset($input['across']) ? (int) $input['across'] : 1;
        if ($across < 1) {
            $across = 1;
        }//at least one column
        if ($across > bcMaxLabelsAcross()) {
            $across = bcMaxLabelsAcross();
        }//cap

        $col_gap = bcCleanMm(isset($input['col_gap']) ? $input['col_gap'] : 0, 0, 100);
        $row_gap = bcCleanMm(isset($input['row_gap']) ? $input['row_gap'] : 0, 0, 100);

        if ($across === 1) {
            $col_gap = 0.0; //nothing to sit between
        }//single column

        //how many stickers at the start of the first row are already used up
        $skip = isset($input['skip']) ? (int) $input['skip'] : 0;
        if ($skip < 0) {
            $skip = 0;
        }//floor
        if ($skip > ($across - 1)) {
            $skip = $across - 1;   //skipping a whole row is just not printing it
        }//cap

        return array(
            'across'  => $across,
            'col_gap' => $col_gap,
            'row_gap' => $row_gap,
            'skip'    => $skip,
            'page_w'  => round(($size['width'] * $across) + ($col_gap * ($across - 1)), 2),
            'page_h'  => round($size['height'] + $row_gap, 2),
        );
    }//bcResolveLayout

    //====================================================== label content ====

    /**
     * Font stacks the operator can pick from. Narrow faces fit noticeably more
     * of a product name onto a 30mm sticker, which is why one is the default.
     */
    function bcFontStacks()
    {
        return array(
            'narrow' => array('name' => 'Arial Narrow (fits the most text)', 'css' => '"Arial Narrow", Arial, Helvetica, sans-serif'),
            'sans'   => array('name' => 'Arial / Helvetica',                 'css' => 'Arial, Helvetica, sans-serif'),
            'serif'  => array('name' => 'Times / serif',                     'css' => '"Times New Roman", Times, serif'),
            'mono'   => array('name' => 'Consolas / monospace',              'css' => 'Consolas, "Courier New", monospace'),
            'system' => array('name' => 'System UI',                         'css' => 'system-ui, "Segoe UI", Roboto, Arial, sans-serif'),
        );
    }//bcFontStacks

    /**
     * Every label option, with the value used when nothing was chosen.
     *
     * Anything that depends on the sticker size (the four font sizes, the bar
     * height, the padding and the line gap) is left at 0 here and filled in
     * from the size profile by bcResolveOptions(), so switching from a 50x25 to
     * a 30x20 re-tunes the typography instead of clipping the sticker.
     */
    function bcOptionDefaults()
    {
        return array(
            //--------------------------------------------------------- geometry
            'size'        => bcDefaultLabelSize(),
            'custom_w'    => 50,
            'custom_h'    => 25,
            'across'      => 1,
            'col_gap'     => 2,
            'row_gap'     => 0,
            'skip'        => 0,
            'padding'     => 0,      //0 = take it from the size profile
            'guides'      => 0,      //print a hairline cut border

            //--------------------------------------------------------- barcode
            'symbology'   => 'AUTO',
            'show_bars'   => 1,
            'bar_height'  => 0,      //0 = take it from the size profile
            'bar_scale'   => 100,    //percent of the usable label width
            'bar_color'   => '#000000',

            //--------------------------------------------------------- content
            'show_shop'   => 0,
            'shop_text'   => '',     //overrides the shop name when filled in
            'show_name'   => 1,
            'name_len'    => 0,      //0 = do not truncate
            'name_upper'  => 0,
            'show_second' => 0,
            'show_cat'    => 0,
            'show_sku'    => 0,
            'show_code'   => 1,
            'show_price'  => 1,
            'price_label' => 'Rs',
            'price_dec'   => 2,
            'show_batch'  => 0,
            'show_date'   => 0,
            'date_format' => 'd/m/Y',
            'show_footer' => 0,
            'footer_text' => '',

            //------------------------------------------------------ typography
            'font'        => 'narrow',
            'align'       => 'center',
            'line_gap'    => 0,      //0 = take it from the size profile
            'shop_font'   => 0,
            'name_font'   => 0,
            'code_font'   => 0,
            'price_font'  => 0,
            'small_font'  => 0,
            'bold_name'   => 0,
            'bold_price'  => 1,
            'letter_sp'   => 0.12,   //tracking on the human readable code, mm

            //-------------------------------------------------------------- job
            'copies'      => 1,
            'auto_print'  => 1,
        );
    }//bcOptionDefaults

    /**
     * Merge a saved / posted option set over the defaults and sanitise every
     * value. Anything the size profile supplies is filled in here, so callers
     * can rely on every key being present and usable.
     */
    function bcResolveOptions($input, $size)
    {
        $defaults = bcOptionDefaults();
        $out = $defaults;

        if (is_array($input)) {
            foreach ($defaults as $key => $ignore) {
                if (array_key_exists($key, $input) && $input[$key] !== null) {
                    $out[$key] = $input[$key];
                }//present
            }//foreach
        }//has input

        //---------------------------------------------------- simple switches
        foreach (bcBooleanOptions() as $flag) {
            $out[$flag] = (!empty($out[$flag]) && $out[$flag] !== '0') ? 1 : 0;
        }//foreach flag

        //------------------------------------------------------------ numbers
        $out['name_len']  = bcClampInt($out['name_len'], 0, 120, 0);
        $out['price_dec'] = bcClampInt($out['price_dec'], 0, 3, 2);
        $out['copies']    = bcClampInt($out['copies'], 1, 100, 1);
        $out['bar_scale'] = bcClampInt($out['bar_scale'], 40, 100, 100);

        //--------------------------------------------------------- typography
        $fonts = bcFontStacks();
        if (!isset($fonts[$out['font']])) {
            $out['font'] = 'narrow';
        }//unknown font
        $out['font_css'] = $fonts[$out['font']]['css'];

        if (!in_array($out['align'], array('left', 'center', 'right'), true)) {
            $out['align'] = 'center';
        }//unknown alignment

        /*
         * A zero means "use the sticker's own tuning". That is what keeps a
         * saved 50x25 profile from clipping when it is printed on 30x20 stock.
         */
        $from_size = array(
            'padding'    => 'padding',
            'line_gap'   => 'line_gap',
            'shop_font'  => 'shop_font',
            'name_font'  => 'name_font',
            'code_font'  => 'code_font',
            'price_font' => 'price_font',
            'small_font' => 'small_font',
            'bar_height' => 'bar_height',
        );

        foreach ($from_size as $option => $size_key) {
            $value = bcCleanMm($out[$option], 0, 100);
            if ($value <= 0) {
                $value = isset($size[$size_key]) ? (float) $size[$size_key] : 2.0;
            }//fall back to the profile
            $out[$option] = $value;
        }//foreach

        $out['letter_sp'] = bcCleanMm($out['letter_sp'], 0, 3);

        //-------------------------------------------------------------- text
        $out['shop_text']   = substr(trim((string) $out['shop_text']), 0, 80);
        $out['footer_text'] = substr(trim((string) $out['footer_text']), 0, 80);
        $out['price_label'] = substr(trim((string) $out['price_label']), 0, 12);

        //only allow the date formats the dialog offers
        $formats = bcDateFormats();
        if (!isset($formats[$out['date_format']])) {
            $out['date_format'] = 'd/m/Y';
        }//unknown format

        //------------------------------------------------------------ colour
        $colour = strtoupper(trim((string) $out['bar_color']));
        if (!preg_match('/^#[0-9A-F]{6}$/', $colour)) {
            $colour = '#000000';
        }//not a hex colour
        $out['bar_color'] = $colour;

        //--------------------------------------------------------- symbology
        $out['symbology'] = strtoupper((string) $out['symbology']);
        $symbologies = bcgSymbologiesSafe();
        if ($out['symbology'] !== 'AUTO' && !isset($symbologies[$out['symbology']])) {
            $out['symbology'] = 'AUTO';
        }//unknown symbology

        return $out;
    }//bcResolveOptions

    /**
     * The options that are on/off switches.
     *
     * These are the ONLY options a missing POST value may be read as "off".
     * An unchecked box sends nothing at all, so a checkbox that is absent has
     * to become 0 - but a text box that is absent has to fall back to its
     * default, or "shop name" would come out on the label as the digit 0.
     */
    function bcBooleanOptions()
    {
        return array(
            'guides', 'show_bars', 'show_shop', 'show_name', 'name_upper', 'show_second',
            'show_cat', 'show_sku', 'show_code', 'show_price', 'show_batch', 'show_date',
            'show_footer', 'bold_name', 'bold_price', 'auto_print',
        );
    }//bcBooleanOptions

    /**
     * The date formats the dialog offers, and how each one reads.
     */
    function bcDateFormats()
    {
        return array(
            'd/m/Y' => '31/12/2026',
            'd-m-Y' => '31-12-2026',
            'Y-m-d' => '2026-12-31',
            'd M Y' => '31 Dec 2026',
            'M Y'   => 'Dec 2026',
            'm/Y'   => '12/2026',
        );
    }//bcDateFormats

    /**
     * Read an integer from the browser without trusting any of it.
     */
    function bcClampInt($value, $min, $max, $fallback)
    {
        if (!is_numeric($value)) {
            return $fallback;
        }//not a number

        $value = (int) $value;

        if ($value < $min) {
            return $min;
        }//floor
        if ($value > $max) {
            return $max;
        }//ceiling

        return $value;
    }//bcClampInt

    /**
     * The symbology list, whether or not barcode_generator.php was included.
     * The print side only needs the keys, so it does not pull in the generator.
     */
    function bcgSymbologiesSafe()
    {
        if (function_exists('bcgSymbologies')) {
            return bcgSymbologies();
        }//generator loaded

        return array(
            'CODE128' => array('name' => 'CODE 128'),
            'CODE39'  => array('name' => 'CODE 39'),
            'CODE93'  => array('name' => 'CODE 93'),
            'EAN13'   => array('name' => 'EAN-13'),
            'EAN8'    => array('name' => 'EAN-8'),
            'UPCA'    => array('name' => 'UPC-A'),
            'ITF'     => array('name' => 'ITF'),
        );
    }//bcgSymbologiesSafe

    //========================================================== symbology ====

    /**
     * Pick the most appropriate symbology for a barcode value.
     * CODE 128 is the universal fallback - it encodes any ASCII value.
     */
    function bcResolveBarcodeType($code, $preferred = 'AUTO')
    {
        $code = (string) $code;
        $preferred = strtoupper((string) $preferred);

        if ($preferred !== 'AUTO' && $preferred !== '') {
            switch ($preferred) {
                case 'EAN13':
                    return BarcodeGenerator::TYPE_EAN_13;
                case 'EAN8':
                    return BarcodeGenerator::TYPE_EAN_8;
                case 'UPCA':
                    return BarcodeGenerator::TYPE_UPC_A;
                case 'CODE39':
                    return BarcodeGenerator::TYPE_CODE_39;
                case 'CODE93':
                    return BarcodeGenerator::TYPE_CODE_93;
                case 'ITF':
                    return BarcodeGenerator::TYPE_INTERLEAVED_2_5;
                case 'CODE128':
                    return BarcodeGenerator::TYPE_CODE_128;
            }//switch
        }//explicit choice

        if (ctype_digit($code)) {
            $len = strlen($code);
            if ($len == 13) {
                return BarcodeGenerator::TYPE_EAN_13;
            }//EAN-13
            if ($len == 12) {
                return BarcodeGenerator::TYPE_UPC_A;
            }//UPC-A
            if ($len == 8) {
                return BarcodeGenerator::TYPE_EAN_8;
            }//EAN-8
        }//numeric code

        return BarcodeGenerator::TYPE_CODE_128;
    }//bcResolveBarcodeType

    /**
     * Render a barcode as inline SVG that stretches to fill its container.
     *
     * SVG is used instead of PNG so the bars stay razor sharp at the printer's
     * native resolution (thermal label printers are typically 203 or 300 dpi).
     *
     * Returns an empty string when the value cannot be encoded - the caller
     * then prints the human readable code only, instead of crashing.
     */
    function bcRenderBarcodeSvg($code, $symbology = 'AUTO', $foreground = '#000000')
    {
        $code = trim((string) $code);
        if ($code === '') {
            return '';
        }//nothing to encode

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $foreground)) {
            $foreground = '#000000';
        }//not a hex colour

        $types = array(bcResolveBarcodeType($code, $symbology));
        if ($types[0] !== BarcodeGenerator::TYPE_CODE_128) {
            $types[] = BarcodeGenerator::TYPE_CODE_128; //checksum/length fallback
        }//add fallback

        $generator = new BarcodeGeneratorSVG();
        $svg = '';

        foreach ($types as $type) {
            try {
                $svg = $generator->getBarcode($code, $type, 2, 60, $foreground);
                break;
            }//encoded
            catch (Throwable $e) {
                $svg = '';
            }//try next symbology
        }//foreach type

        if ($svg === '') {
            return '';
        }//not encodable

        //drop the xml prolog + doctype so the markup can be inlined in HTML
        $start = strpos($svg, '<svg');
        if ($start === false) {
            return '';
        }//unexpected output
        $svg = substr($svg, $start);

        //make the svg fill the sticker area exactly (the viewBox is kept intact)
        $tag_end = strpos($svg, '>');
        if ($tag_end !== false) {
            $open = substr($svg, 0, $tag_end + 1);
            $rest = substr($svg, $tag_end + 1);

            $open = preg_replace('/\swidth="[^"]*"/', ' width="100%"', $open, 1);
            $open = preg_replace('/\sheight="[^"]*"/', ' height="100%"', $open, 1);
            $open = str_replace('<svg', '<svg preserveAspectRatio="none" shape-rendering="crispEdges"', $open);

            $svg = $open . $rest;
        }//rewrite opening tag

        //the same markup is repeated for every copy - keep the DOM id free
        $svg = str_replace(' id="bars"', '', $svg);

        return $svg;
    }//bcRenderBarcodeSvg

    /**
     * Number of encoded modules for a barcode value. Used to warn the operator
     * when a long code is squeezed onto a small sticker.
     */
    function bcBarcodeModuleCount($code, $symbology = 'AUTO')
    {
        $code = trim((string) $code);
        if ($code === '') {
            return 0;
        }//no code

        $types = array(bcResolveBarcodeType($code, $symbology));
        if ($types[0] !== BarcodeGenerator::TYPE_CODE_128) {
            $types[] = BarcodeGenerator::TYPE_CODE_128;
        }//add fallback

        foreach ($types as $type) {
            try {
                $svg = (new BarcodeGeneratorSVG())->getBarcode($code, $type, 1, 10);
                if (preg_match('/viewBox="0 0 ([0-9.]+)/', $svg, $m)) {
                    return (float) $m[1];
                }//matched
            }//try
            catch (Throwable $e) {
                //try next symbology
            }//catch
        }//foreach type

        return 0;
    }//bcBarcodeModuleCount

    //============================================================== data =====

    /**
     * Load the products the current shop is allowed to print labels for.
     *
     * $product_ids is filtered to integers and the query is scoped to the shop
     * (or to the whole company when multi category / common stock is enabled),
     * so a tampered request can never print another company's items.
     *
     * Returns an array keyed by PDID, in the order the operator selected them.
     */
    function bcGetPrintableProducts($dbObj, $product_ids, $shop_id, $company_id, $multi_category)
    {
        $clean_ids = array();
        foreach ((array) $product_ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $clean_ids[$id] = $id;
            }//valid id
        }//foreach id

        if (empty($clean_ids)) {
            return array();
        }//nothing requested

        $id_list = implode(',', $clean_ids);
        $shop_id = (int) $shop_id;
        $company_id = (int) $company_id;

        $columns = "products.PDID, products.Barcode, products.ItemName, products.SecondName,
                    products.ProdSellPrice, products.ProductNo,
                    categories.CategoryName, subcategories.SubCatName";

        $joins = "INNER JOIN subcategories ON subcategories.SCID = products.Subcategories_SCID
                  INNER JOIN categories ON categories.CTID = subcategories.categories_CTID";

        if ((int) $multi_category === 1) {
            $sql = "SELECT " . $columns . "
                    FROM products
                    " . $joins . "
                    INNER JOIN shop ON shop.SHID = products.shop_SHID
                    WHERE products.PDID IN (" . $id_list . ")
                      AND shop.Company_CMID = " . $company_id . ";";
        }//company wide
        else {
            $sql = "SELECT " . $columns . "
                    FROM products
                    " . $joins . "
                    WHERE products.PDID IN (" . $id_list . ")
                      AND products.shop_SHID = " . $shop_id . ";";
        }//single shop

        $rows = $dbObj->getData($sql);

        $products = array();
        foreach ($rows as $row) {
            $products[(int) $row['PDID']] = array(
                'PDID'          => (int) $row['PDID'],
                'ProductNo'     => (string) $row['ProductNo'],
                'Barcode'       => (string) $row['Barcode'],
                'ItemName'      => (string) $row['ItemName'],
                'SecondName'    => (string) $row['SecondName'],
                'CategoryName'  => (string) $row['CategoryName'],
                'SubCatName'    => (string) $row['SubCatName'],
                'ProdSellPrice' => (float) $row['ProdSellPrice'],
            );
        }//foreach row

        //preserve the order the operator selected them in
        $ordered = array();
        foreach ($clean_ids as $id) {
            if (isset($products[$id])) {
                $ordered[$id] = $products[$id];
            }//allowed
        }//foreach id

        return $ordered;
    }//bcGetPrintableProducts

    /**
     * Batch prices recorded for a product, in the order the POS sells the batches: batches with stock
     * first (FIFO, or LIFO when the shop is set to it), then the rest newest first - so the first price
     * is the one charged at the till today.
     * The label price (MRP) is only used where the shop has label prices switched on; elsewhere the
     * label shows the selling price (the price change screen never edits the label price, so it goes stale).
     * Returns array( array('batch'=>.., 'price'=>..), ... ).
     */
    function bcGetProductPrices($dbObj, $product_id, $shop_id, $multi_category)
    {
        $product_id = (int) $product_id;
        $shop_id = (int) $shop_id;

        $shopRows = $dbObj->getData("SELECT is_labelprice, StockTypes_STID FROM shop WHERE SHID = " . $shop_id . ";");
        $use_label_price = !empty($shopRows) && (int) $shopRows[0]['is_labelprice'] === 1;
        $stock_order = (!empty($shopRows) && (int) $shopRows[0]['StockTypes_STID'] === 3) ? 'DESC' : 'ASC';
        $order_by = "ORDER BY (inventory.CurrentQty > 0) DESC, IF(inventory.CurrentQty > 0, inventory.INID, NULL) " . $stock_order . ", pricehistory.PHID DESC;";

        if ((int) $multi_category === 1) {
            $sql = "SELECT pricehistory.BatchID, pricehistory.SellingPrice, pricehistory.labelPrice
                    FROM pricehistory
                    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
                    WHERE inventory.products_PDID = " . $product_id . "
                    " . $order_by;
        }//company wide
        else {
            $sql = "SELECT pricehistory.BatchID, pricehistory.SellingPrice, pricehistory.labelPrice
                    FROM pricehistory
                    INNER JOIN inventory ON inventory.INID = pricehistory.Inventory_INID
                    WHERE inventory.products_PDID = " . $product_id . "
                      AND inventory.shop_SHID = " . $shop_id . "
                    " . $order_by;
        }//single shop

        $rows = $dbObj->getData($sql);

        $prices = array();
        $seen = array();

        foreach ($rows as $row) {
            $label_price = (float) $row['labelPrice'];
            $sell_price = (float) $row['SellingPrice'];
            $price = ($use_label_price && $label_price > 0) ? $label_price : $sell_price;

            if ($price <= 0) {
                continue;
            }//no usable price

            $batch = trim((string) $row['BatchID']);
            $key = $batch . '|' . number_format($price, 2, '.', '');

            if (isset($seen[$key])) {
                continue;
            }//already listed
            $seen[$key] = true;

            $prices[] = array(
                'batch' => ($batch === '') ? 'Batch' : $batch,
                'price' => round($price, 2),
            );
        }//foreach row

        return $prices;
    }//bcGetProductPrices

    /**
     * Does this user hold one right on a feature? ($right is a userroleaccess
     * column: is_view, is_create, is_edit, is_delete, is_print, is_verify.)
     *
     * The buttons are already hidden in the UI - this is the server side
     * counterpart, so the endpoints cannot be called directly without the
     * right. Administrators (UserType 1) always pass.
     */
    function bcUserRight($dbObj, $user_id, $right, $feature_id = 16)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return false;
        }//no user

        //never interpolate a column name that was not vetted here
        $allowed = array('is_view', 'is_create', 'is_edit', 'is_delete', 'is_print', 'is_verify');
        if (!in_array($right, $allowed, true)) {
            return false;
        }//unknown right

        $rows = $dbObj->getData("SELECT UserType, UserRoles_URID FROM user WHERE USID = " . $user_id . ";");
        if (empty($rows)) {
            return false;
        }//unknown user

        if ((int) $rows[0]['UserType'] === 1) {
            return true; //administrator
        }//admin

        //rights come from the role held in the current shop (Model/shop_access_class.php)
        require_once __DIR__ . '/../Model/shop_access_class.php';
        $shop_id = isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 0;
        $role_id = (int) (new ShopAccess())->getShopRoleId($user_id, $shop_id);
        if ($role_id === 0) {
            return false;
        }//no access to this shop

        $access = $dbObj->getData("SELECT " . $right . " FROM userroleaccess
                                   WHERE UserRolls_URID = " . $role_id . "
                                     AND SysFeatures_SFID = " . (int) $feature_id . ";");

        if (empty($access)) {
            return false;
        }//no access record

        return ((int) $access[0][$right] === 1);
    }//bcUserRight

    /**
     * May this user print barcode labels?
     */
    function bcUserCanPrint($dbObj, $user_id, $feature_id = 16)
    {
        return bcUserRight($dbObj, $user_id, 'is_print', $feature_id);
    }//bcUserCanPrint

    /**
     * May this user change the barcode rules?
     */
    function bcUserCanConfigure($dbObj, $user_id, $feature_id = 16)
    {
        return bcUserRight($dbObj, $user_id, 'is_edit', $feature_id);
    }//bcUserCanConfigure

    /**
     * May this user create a product, and therefore ask for a barcode?
     * Editing an existing product counts too - the barcode field is on the same
     * dialog.
     */
    function bcUserCanGenerate($dbObj, $user_id, $feature_id = 16)
    {
        return (bcUserRight($dbObj, $user_id, 'is_create', $feature_id)
             || bcUserRight($dbObj, $user_id, 'is_edit', $feature_id));
    }//bcUserCanGenerate

    /**
     * The shop's saved label defaults, decoded. Falls back to the built in
     * defaults when the shop has never saved any.
     */
    function bcShopLabelDefaults($json)
    {
        $json = trim((string) $json);
        if ($json === '') {
            return array();
        }//never saved

        $data = json_decode($json, true);

        return is_array($data) ? $data : array();
    }//bcShopLabelDefaults

    /**
     * Print a millimetre value without trailing zeros (102.0 reads as 102).
     */
    function bcMm($mm)
    {
        return rtrim(rtrim(number_format((float) $mm, 2, '.', ''), '0'), '.');
    }//bcMm

}//function guard
