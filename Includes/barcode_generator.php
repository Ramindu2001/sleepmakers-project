<?php
/**
 * Automatic barcode generation.
 * -----------------------------------------------------------------------------
 * When a product is saved with the barcode field left empty, the code is built
 * here from the shop's rules instead of falling back to the product number.
 *
 * The rules are a TOKEN PATTERN plus a sequence configuration:
 *
 *      {PREFIX}{SUB}{SEQ}   ->   SM-BEV-00042
 *
 * Two things in this file are load bearing and should not be "simplified":
 *
 *  1. THE PREVIEW NEVER CONSUMES A NUMBER. bcgPreviewBarcode() peeks at the
 *     counter, bcgGenerateBarcode() consumes it. A preview that consumed would
 *     burn a code every time somebody opened the dialog and changed their mind,
 *     and would show two people the same "next" number.
 *
 *  2. UNIQUENESS IS CHECKED ACROSS THE WHOLE products TABLE, not per shop.
 *     Product::getProductByBarcode() resolves a scanned code with no shop
 *     filter, so a per shop unique code would let the POS ring up the wrong
 *     item. See BarcodeSettings::barcodeExists().
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../Model/barcode_settings_class.php';

use Picqer\Barcode\BarcodeGenerator as PicqerBarcodeGenerator;
use Picqer\Barcode\BarcodeGeneratorSVG as PicqerBarcodeGeneratorSVG;

if (!function_exists('bcgDefaultSettings')) {

    /**
     * Marks the place in a rendered pattern where the sequence number goes.
     *
     * A control character is used so it can never collide with something the
     * operator typed into the pattern box.
     */
    define('BCG_SEQ_SLOT', "\x01");

    /**
     * products.Barcode is varchar(45) - nothing longer can ever be stored.
     */
    function bcgHardMaxLength()
    {
        return 45;
    }//bcgHardMaxLength

    /**
     * How many different numbers to try before giving up on a unique code.
     */
    function bcgMaxAttempts()
    {
        return 30;
    }//bcgMaxAttempts

    //========================================================== settings =====

    /**
     * What a shop gets before it has ever opened the settings page.
     *
     * CODE 128 is the default symbology because it encodes any ASCII value -
     * a shop can type any prefix it likes and the code still scans.
     */
    function bcgDefaultSettings()
    {
        return array(
            'AutoGenerate'  => 1,
            'Pattern'       => '{PREFIX}{SUB}{SEQ}',
            'FixedPrefix'   => '',
            'Suffix'        => '',
            'ShopCode'      => '',
            'Separator'     => '',
            'CatCodeLength' => 3,
            'SubCodeLength' => 3,
            'SeqScope'      => 'pattern',
            'SeqStart'      => 1,
            'SeqStep'       => 1,
            'SeqLength'     => 5,
            'SeqPadChar'    => '0',
            'Casing'        => 'upper',
            'Symbology'     => 'CODE128',
            'MaxLength'     => bcgHardMaxLength(),
            'StripInvalid'  => 1,
            'LabelDefaults' => '',
        );
    }//bcgDefaultSettings

    /**
     * Every scope a sequence can restart on, with the label shown in the UI.
     */
    function bcgSeqScopes()
    {
        return array(
            'pattern'     => 'Per prefix (numbering restarts for each different prefix)',
            'subcategory' => 'Per sub category',
            'category'    => 'Per main category',
            'shop'        => 'Per shop (one running number for the whole shop)',
            'global'      => 'Company wide (one running number for every shop)',
        );
    }//bcgSeqScopes

    /**
     * The symbologies the module can generate for, and what each one accepts.
     *
     *   digits   - the value may only contain 0-9
     *   body     - how many digits go in front of the check digit (0 = no fixed
     *              length), so EAN-13 is 12 + 1
     *   even     - the value has to have an even number of digits
     *   alphabet - regex character class of everything the symbology can encode
     */
    function bcgSymbologies()
    {
        return array(
            'CODE128' => array(
                'name'     => 'CODE 128 (recommended - letters, digits and symbols)',
                'digits'   => false,
                'body'     => 0,
                'even'     => false,
                'alphabet' => '\x20-\x7E',
                'upper'    => false,
            ),
            'CODE39' => array(
                'name'     => 'CODE 39 (uppercase letters and digits)',
                'digits'   => false,
                'body'     => 0,
                'even'     => false,
                'alphabet' => '0-9A-Z\-\. \$\/\+\%',
                'upper'    => true,
            ),
            'CODE93' => array(
                'name'     => 'CODE 93 (compact, uppercase letters and digits)',
                'digits'   => false,
                'body'     => 0,
                'even'     => false,
                'alphabet' => '\x20-\x7E',
                'upper'    => true,
            ),
            'EAN13' => array(
                'name'     => 'EAN-13 (retail, exactly 13 digits)',
                'digits'   => true,
                'body'     => 12,
                'even'     => false,
                'alphabet' => '0-9',
                'upper'    => false,
            ),
            'EAN8' => array(
                'name'     => 'EAN-8 (small retail, exactly 8 digits)',
                'digits'   => true,
                'body'     => 7,
                'even'     => false,
                'alphabet' => '0-9',
                'upper'    => false,
            ),
            'UPCA' => array(
                'name'     => 'UPC-A (US retail, exactly 12 digits)',
                'digits'   => true,
                'body'     => 11,
                'even'     => false,
                'alphabet' => '0-9',
                'upper'    => false,
            ),
            'ITF' => array(
                'name'     => 'ITF / Interleaved 2 of 5 (cartons, digits only)',
                'digits'   => true,
                'body'     => 0,
                'even'     => true,
                'alphabet' => '0-9',
                'upper'    => false,
            ),
        );
    }//bcgSymbologies

    /**
     * Turn the picqer symbology key into the constant the library wants.
     */
    function bcgPicqerType($symbology)
    {
        switch (strtoupper((string) $symbology)) {
            case 'EAN13':
                return PicqerBarcodeGenerator::TYPE_EAN_13;
            case 'EAN8':
                return PicqerBarcodeGenerator::TYPE_EAN_8;
            case 'UPCA':
                return PicqerBarcodeGenerator::TYPE_UPC_A;
            case 'CODE39':
                return PicqerBarcodeGenerator::TYPE_CODE_39;
            case 'CODE93':
                return PicqerBarcodeGenerator::TYPE_CODE_93;
            case 'ITF':
                return PicqerBarcodeGenerator::TYPE_INTERLEAVED_2_5;
            default:
                return PicqerBarcodeGenerator::TYPE_CODE_128;
        }//switch
    }//bcgPicqerType

    /**
     * Can this value actually be encoded in this symbology?
     *
     * Asked of the library rather than guessed from a character list, because
     * picqer throws a plain TypeError - not a barcode exception - when a
     * numeric symbology is handed letters.
     */
    function bcgEncodable($code, $symbology)
    {
        $code = (string) $code;
        if ($code === '') {
            return false;
        }//nothing to encode

        try {
            (new PicqerBarcodeGeneratorSVG())->getBarcode($code, bcgPicqerType($symbology), 1, 10);
            return true;
        }//encoded
        catch (Throwable $e) {
            return false;
        }//not encodable
    }//bcgEncodable

    /**
     * Clean up whatever came out of the settings form / database so the rest of
     * the module never has to guard against a missing or silly value.
     */
    function bcgNormalizeSettings($row)
    {
        $defaults = bcgDefaultSettings();
        $out = $defaults;

        if (is_array($row)) {
            foreach ($defaults as $key => $ignore) {
                if (isset($row[$key]) && $row[$key] !== null) {
                    $out[$key] = $row[$key];
                }//present
            }//foreach
        }//has data

        $out['AutoGenerate'] = ((int) $out['AutoGenerate'] === 1) ? 1 : 0;
        $out['StripInvalid'] = ((int) $out['StripInvalid'] === 1) ? 1 : 0;

        $out['Pattern'] = trim((string) $out['Pattern']);
        if ($out['Pattern'] === '') {
            $out['Pattern'] = $defaults['Pattern'];
        }//never empty - an empty pattern generates an empty barcode

        //a pattern with no sequence slot cannot stay unique, so put one back
        if (strpos(strtoupper($out['Pattern']), '{SEQ}') === false) {
            $out['Pattern'] .= '{SEQ}';
        }//no sequence

        $out['FixedPrefix'] = substr(trim((string) $out['FixedPrefix']), 0, 24);
        $out['Suffix']      = substr(trim((string) $out['Suffix']), 0, 24);
        $out['ShopCode']    = substr(trim((string) $out['ShopCode']), 0, 12);
        $out['Separator']   = substr((string) $out['Separator'], 0, 4);

        $out['CatCodeLength'] = bcgClampInt($out['CatCodeLength'], 1, 12, 3);
        $out['SubCodeLength'] = bcgClampInt($out['SubCodeLength'], 1, 12, 3);

        $scopes = bcgSeqScopes();
        if (!isset($scopes[$out['SeqScope']])) {
            $out['SeqScope'] = $defaults['SeqScope'];
        }//unknown scope

        $out['SeqStart']  = bcgClampInt($out['SeqStart'], 0, 999999999, 1);
        $out['SeqStep']   = bcgClampInt($out['SeqStep'], 1, 100000, 1);
        $out['SeqLength'] = bcgClampInt($out['SeqLength'], 1, 18, 5);

        $pad = (string) $out['SeqPadChar'];
        $out['SeqPadChar'] = ($pad === '') ? '0' : substr($pad, 0, 1);

        if (!in_array($out['Casing'], array('upper', 'lower', 'asis'), true)) {
            $out['Casing'] = 'upper';
        }//unknown casing

        $symbologies = bcgSymbologies();
        $out['Symbology'] = strtoupper((string) $out['Symbology']);
        if (!isset($symbologies[$out['Symbology']])) {
            $out['Symbology'] = $defaults['Symbology'];
        }//unknown symbology

        $out['MaxLength'] = bcgClampInt($out['MaxLength'], 4, bcgHardMaxLength(), bcgHardMaxLength());
        $out['LabelDefaults'] = (string) $out['LabelDefaults'];

        return $out;
    }//bcgNormalizeSettings

    /**
     * Read an integer from the browser without trusting any of it.
     */
    function bcgClampInt($value, $min, $max, $fallback)
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
    }//bcgClampInt

    //=========================================================== context =====

    /**
     * Derive a short code from a category name when the shop has not set one.
     *
     * "Beverage" -> BEV, "Party Supplies" -> PAR. Predictable beats clever: the
     * operator can always override it on the category page.
     */
    function bcgDeriveCode($name, $length)
    {
        $name = strtoupper((string) $name);
        $name = preg_replace('/[^A-Z0-9]/', '', $name);

        if ($name === null || $name === '') {
            return '';
        }//nothing usable

        $length = bcgClampInt($length, 1, 12, 3);

        return substr($name, 0, $length);
    }//bcgDeriveCode

    /**
     * Build the token context for one product.
     *
     * $category is the row from BarcodeSettings::getCategoryContext(),
     * $shop is the row from BarcodeSettings::getShopContext().
     */
    function bcgBuildContext($settings, $category, $shop, $item_name = '')
    {
        $cat_code = '';
        $sub_code = '';
        $cat_id = 0;
        $sub_id = 0;

        if (!empty($category)) {
            $cat_id = isset($category['CTID']) ? (int) $category['CTID'] : 0;
            $sub_id = isset($category['SCID']) ? (int) $category['SCID'] : 0;

            $cat_code = isset($category['CategoryCode']) ? trim((string) $category['CategoryCode']) : '';
            if ($cat_code === '') {
                $cat_code = bcgDeriveCode(
                    isset($category['CategoryName']) ? $category['CategoryName'] : '',
                    $settings['CatCodeLength']
                );
            }//derive from the name

            $sub_code = isset($category['SubCatCode']) ? trim((string) $category['SubCatCode']) : '';
            if ($sub_code === '') {
                $sub_code = bcgDeriveCode(
                    isset($category['SubCatName']) ? $category['SubCatName'] : '',
                    $settings['SubCodeLength']
                );
            }//derive from the name
        }//has a category

        //an explicitly set code is still trimmed to the configured length
        $cat_code = substr(preg_replace('/[^A-Za-z0-9]/', '', $cat_code), 0, $settings['CatCodeLength']);
        $sub_code = substr(preg_replace('/[^A-Za-z0-9]/', '', $sub_code), 0, $settings['SubCodeLength']);

        $shop_code = trim((string) $settings['ShopCode']);
        if ($shop_code === '' && !empty($shop)) {
            $shop_code = bcgDeriveCode(isset($shop['ShopName']) ? $shop['ShopName'] : '', 3);
        }//derive from the shop name

        return array(
            'CAT'     => $cat_code,
            'SUB'     => $sub_code,
            'SHOP'    => $shop_code,
            'SHOPID'  => (!empty($shop) && isset($shop['SHID'])) ? (string) (int) $shop['SHID'] : '',
            'PREFIX'  => (string) $settings['FixedPrefix'],
            'ITEM'    => preg_replace('/[^A-Za-z0-9]/', '', (string) $item_name),
            'cat_id'  => $cat_id,
            'sub_id'  => $sub_id,
        );
    }//bcgBuildContext

    //=========================================================== rendering ===

    /**
     * Split a pattern into literal text and {TOKEN} parts.
     */
    function bcgTokenize($pattern)
    {
        $parts = array();
        $chunks = preg_split('/(\{[A-Za-z]+(?::[0-9]+)?\})/', (string) $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($chunks as $chunk) {
            if ($chunk === '' || $chunk === null) {
                continue;
            }//nothing there

            if ($chunk[0] === '{' && substr($chunk, -1) === '}') {
                $inner = substr($chunk, 1, -1);
                $arg = 0;

                if (strpos($inner, ':') !== false) {
                    list($inner, $arg) = explode(':', $inner, 2);
                    $arg = (int) $arg;
                }//has an argument

                $parts[] = array('type' => 'token', 'name' => strtoupper($inner), 'arg' => $arg);
            }//token
            else {
                $parts[] = array('type' => 'literal', 'text' => $chunk);
            }//literal
        }//foreach chunk

        return $parts;
    }//bcgTokenize

    /**
     * Render the pattern with the sequence left as a slot marker.
     *
     * The result is used twice: as the sequence SCOPE KEY (so "restart per
     * prefix" restarts whenever the fixed part changes) and as the template
     * that bcgApplySequence() fills in.
     */
    function bcgComposeTemplate($settings, $context)
    {
        $separator = (string) $settings['Separator'];
        $parts = bcgTokenize($settings['Pattern']);

        $out = '';
        $previous_was_token = false;

        foreach ($parts as $part) {

            if ($part['type'] === 'literal') {
                $out .= $part['text'];
                $previous_was_token = false;
                continue;
            }//literal text passes straight through

            $value = bcgResolveToken($part, $context);

            if ($value === '') {
                //an empty token must not leave a stray separator behind
                continue;
            }//nothing to add

            /*
             * Auto separator: only ever BETWEEN two tokens that both produced
             * something. That is what lets {PREFIX}{SUB}{SEQ} read as
             * SM-BEV-00042 for a product that has a category, and SM-00042 for
             * one that does not, without the operator editing the pattern.
             */
            if ($separator !== '' && $previous_was_token && $out !== '') {
                $out .= $separator;
            }//join

            $out .= $value;
            $previous_was_token = true;
        }//foreach part

        //suffix rides on the same separator rule
        $suffix = (string) $settings['Suffix'];
        if ($suffix !== '') {
            if ($separator !== '' && $out !== '') {
                $out .= $separator;
            }//join
            $out .= $suffix;
        }//has suffix

        //tidy up doubled or dangling separators left by a hand edited pattern
        if ($separator !== '') {
            $quoted = preg_quote($separator, '/');
            $out = preg_replace('/(' . $quoted . '){2,}/', $separator, $out);
            $out = preg_replace('/^' . $quoted . '+|' . $quoted . '+$/', '', $out);
        }//clean separators

        if ($settings['Casing'] === 'upper') {
            $out = strtoupper($out);
        }//upper
        else if ($settings['Casing'] === 'lower') {
            $out = strtolower($out);
        }//lower

        //CODE 39 and CODE 93 cannot encode lowercase at all
        $symbologies = bcgSymbologies();
        if (!empty($symbologies[$settings['Symbology']]['upper'])) {
            $out = strtoupper($out);
        }//force upper

        return $out;
    }//bcgComposeTemplate

    /**
     * The value one {TOKEN} stands for.
     */
    function bcgResolveToken($part, $context)
    {
        $name = $part['name'];
        $arg = (int) $part['arg'];

        switch ($name) {
            case 'SEQ':
                return BCG_SEQ_SLOT;

            case 'PREFIX':
            case 'CAT':
            case 'SUB':
            case 'SHOP':
            case 'SHOPID':
                return isset($context[$name]) ? (string) $context[$name] : '';

            case 'ITEM':
                $item = isset($context['ITEM']) ? (string) $context['ITEM'] : '';
                return ($arg > 0) ? substr($item, 0, $arg) : $item;

            case 'YYYY':
                return date('Y');
            case 'YY':
                return date('y');
            case 'MM':
                return date('m');
            case 'DD':
                return date('d');

            case 'RAND':
                $len = ($arg > 0 && $arg <= 12) ? $arg : 4;
                $value = '';
                for ($i = 0; $i < $len; $i++) {
                    $value .= (string) random_int(0, 9);
                }//for
                return $value;

            case 'SEP':
                return '';  //handled by the auto separator, kept for old patterns

            default:
                return '';  //unknown token renders as nothing
        }//switch
    }//bcgResolveToken

    /**
     * The sequence counter key a template belongs to.
     */
    function bcgScopeKey($settings, $context, $template)
    {
        switch ($settings['SeqScope']) {
            case 'global':
                return 'GLOBAL';

            case 'shop':
                return 'SHOP';

            case 'category':
                return 'CAT:' . (int) $context['cat_id'];

            case 'subcategory':
                return 'SUB:' . (int) $context['sub_id'];

            case 'pattern':
            default:
                /*
                 * The static part of the pattern. Note this means a pattern
                 * carrying {YY} or {MM} restarts its numbering every year or
                 * month, which is exactly what a shop using those tokens wants.
                 */
                $key = str_replace(BCG_SEQ_SLOT, '#', $template);
                return 'PAT:' . substr($key, 0, 150);
        }//switch
    }//bcgScopeKey

    /**
     * Put a sequence number into a template and shape the result for the
     * chosen symbology.
     *
     * Numeric symbologies (EAN / UPC) have a fixed digit count, so the padding
     * is whatever is left after the fixed parts - SeqLength does not apply and
     * the settings page says so.
     */
    function bcgApplySequence($settings, $template, $seq_value)
    {
        $symbologies = bcgSymbologies();
        $spec = $symbologies[$settings['Symbology']];

        $slot = strpos($template, BCG_SEQ_SLOT);
        if ($slot === false) {
            //normalisation guarantees a slot, but never trust that at runtime
            $template .= BCG_SEQ_SLOT;
            $slot = strpos($template, BCG_SEQ_SLOT);
        }//no slot

        $before = substr($template, 0, $slot);
        $after  = substr($template, $slot + strlen(BCG_SEQ_SLOT));

        //--------------------------------------------------- fixed length codes
        if ($spec['digits'] && $spec['body'] > 0) {

            $before = preg_replace('/[^0-9]/', '', $before);
            $after  = preg_replace('/[^0-9]/', '', $after);
            $body   = (int) $spec['body'];

            //always leave room for at least four digits of sequence
            $max_fixed = $body - 4;
            if ($max_fixed < 0) {
                $max_fixed = 0;
            }//tiny symbology

            if (strlen($before) + strlen($after) > $max_fixed) {
                //keep the head of the prefix - it is the part that identifies
                $keep_after = min(strlen($after), $max_fixed);
                $after = substr($after, 0, $keep_after);
                $before = substr($before, 0, max(0, $max_fixed - $keep_after));
            }//fixed parts too long

            $room = $body - strlen($before) - strlen($after);
            if ($room < 1) {
                $room = 1;
            }//should not happen, but never emit a negative pad

            $seq_text = str_pad(substr((string) $seq_value, -$room), $room, '0', STR_PAD_LEFT);

            $digits = $before . $seq_text . $after;
            $digits = str_pad(substr($digits, 0, $body), $body, '0', STR_PAD_LEFT);

            return $digits . bcgCheckDigit($digits);
        }//EAN-13 / EAN-8 / UPC-A

        //------------------------------------------------------- free length
        $seq_text = str_pad(
            (string) $seq_value,
            (int) $settings['SeqLength'],
            (string) $settings['SeqPadChar'],
            STR_PAD_LEFT
        );

        $code = $before . $seq_text . $after;

        if ($spec['digits']) {
            $code = preg_replace('/[^0-9]/', '', $code);
        }//digits only

        if (!empty($settings['StripInvalid'])) {
            $code = preg_replace('/[^' . $spec['alphabet'] . ']/', '', $code);
        }//strip what the symbology cannot encode

        if (!empty($spec['upper'])) {
            $code = strtoupper($code);
        }//force upper

        $max = min((int) $settings['MaxLength'], bcgHardMaxLength());
        if (strlen($code) > $max) {
            //trim the FRONT: the tail carries the sequence, which is what keeps
            //the code unique
            $code = substr($code, -$max);

            //do not leave the code starting on a dangling separator
            $separator = (string) $settings['Separator'];
            if ($separator !== '') {
                $code = ltrim($code, $separator);
            }//tidy
        }//too long

        if (!empty($spec['even']) && (strlen($code) % 2) === 1) {
            $code = '0' . $code;   //ITF has to have an even number of digits
        }//odd length

        return $code;
    }//bcgApplySequence

    /**
     * Modulo 10 check digit, as used by EAN-13, EAN-8 and UPC-A.
     *
     * Weights alternate 3 and 1 starting from the RIGHT hand digit of the body,
     * which is what makes the same routine correct for all three lengths.
     */
    function bcgCheckDigit($digits)
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $digits);
        $sum = 0;
        $weight = 3;

        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $sum += ((int) $digits[$i]) * $weight;
            $weight = ($weight === 3) ? 1 : 3;
        }//for

        return (string) ((10 - ($sum % 10)) % 10);
    }//bcgCheckDigit

    //========================================================== generation ===

    /**
     * The code that WOULD be generated next, without consuming anything.
     *
     * Everything the operator sees before saving comes through here: the add
     * product dialog hint and the settings page live preview.
     */
    function bcgPreviewBarcode($bcObj, $shop_id, $subcat_id, $settings = null, $item_name = '')
    {
        if ($settings === null) {
            $settings = bcgNormalizeSettings($bcObj->getSettings($shop_id));
        }//load the saved rules

        $context = bcgBuildContext(
            $settings,
            $bcObj->getCategoryContext($subcat_id),
            $bcObj->getShopContext($shop_id),
            $item_name
        );

        $template = bcgComposeTemplate($settings, $context);
        $scope_key = bcgScopeKey($settings, $context, $template);
        $next = $bcObj->peekSequence($shop_id, $scope_key, $settings['SeqStart']);

        $code = bcgApplySequence($settings, $template, $next);

        return array(
            'code'      => $code,
            'sequence'  => $next,
            'scope_key' => $scope_key,
            'encodable' => bcgEncodable($code, $settings['Symbology']),
            'symbology' => $settings['Symbology'],
        );
    }//bcgPreviewBarcode

    /**
     * Allocate and return a real, unique barcode.
     *
     * Consumes at least one sequence number. Retries on a collision with an
     * existing product - duplicates already exist in live data, so a collision
     * is a real possibility and not a theoretical one.
     *
     * Returns '' when no code could be produced, which tells the caller to keep
     * its own fallback (the product number).
     */
    function bcgGenerateBarcode($bcObj, $shop_id, $subcat_id, $settings = null, $item_name = '')
    {
        if ($settings === null) {
            $settings = bcgNormalizeSettings($bcObj->getSettings($shop_id));
        }//load the saved rules

        $context = bcgBuildContext(
            $settings,
            $bcObj->getCategoryContext($subcat_id),
            $bcObj->getShopContext($shop_id),
            $item_name
        );

        $template = bcgComposeTemplate($settings, $context);
        $scope_key = bcgScopeKey($settings, $context, $template);

        $attempts = bcgMaxAttempts();

        for ($try = 0; $try < $attempts; $try++) {

            $seq = $bcObj->allocateSequence(
                $shop_id,
                $scope_key,
                $settings['SeqStart'],
                $settings['SeqStep']
            );

            if ($seq <= 0) {
                return '';  //counter table missing - let the caller fall back
            }//no number

            $code = bcgApplySequence($settings, $template, $seq);

            if ($code === '') {
                continue;
            }//pattern produced nothing usable

            if (!bcgEncodable($code, $settings['Symbology'])) {
                /*
                 * The chosen symbology cannot carry this value. Falling back to
                 * CODE 128 keeps the save working and keeps the code scannable;
                 * the settings page warns about this before it can happen.
                 */
                if (!bcgEncodable($code, 'CODE128')) {
                    continue;
                }//not encodable at all
            }//symbology mismatch

            if (!$bcObj->barcodeExists($code)) {
                return $code;
            }//free - use it
        }//for attempt

        return '';  //gave up - caller falls back to the product number
    }//bcgGenerateBarcode

    /**
     * Everything wrong or risky about a settings combination, in plain words.
     *
     * Shown live on the settings page so a shop finds out that "EAN-13 with an
     * SM prefix" is contradictory while it is configuring, not at print time.
     */
    function bcgValidateSettings($settings, $sample_context = null)
    {
        $problems = array();
        $symbologies = bcgSymbologies();
        $spec = $symbologies[$settings['Symbology']];

        if ($sample_context === null) {
            $sample_context = array(
                'CAT' => 'BEV', 'SUB' => 'JUI', 'SHOP' => 'SM', 'SHOPID' => '1',
                'PREFIX' => $settings['FixedPrefix'], 'ITEM' => 'SAMPLE',
                'cat_id' => 0, 'sub_id' => 0,
            );
        }//no real context

        $template = bcgComposeTemplate($settings, $sample_context);
        $static_part = str_replace(BCG_SEQ_SLOT, '', $template);

        if ($spec['digits']) {

            if (preg_match('/[^0-9]/', $static_part)) {
                $problems[] = 'The prefix, suffix and category codes contain letters, but '
                    . $settings['Symbology'] . ' can only encode digits. Everything that is '
                    . 'not a digit will be dropped from the barcode.';
            }//letters in a numeric symbology

            if ($spec['body'] > 0) {
                $fixed = strlen(preg_replace('/[^0-9]/', '', $static_part));
                $room = $spec['body'] - $fixed;

                if ($room < 4) {
                    $problems[] = $settings['Symbology'] . ' holds exactly ' . $spec['body']
                        . ' digits plus a check digit. The fixed part uses ' . $fixed
                        . ' of them, so it will be shortened to leave room for the number.';
                }//no room
                else if ((int) $settings['SeqLength'] !== $room) {
                    $problems[] = 'In ' . $settings['Symbology'] . ' mode the sequence always fills '
                        . 'the remaining ' . $room . ' digits, so the "sequence length" setting '
                        . 'is not used.';
                }//seq length ignored
            }//fixed length
        }//numeric symbology

        if ($settings['Symbology'] === 'CODE39' && strpos($static_part, '_') !== false) {
            $problems[] = 'CODE 39 cannot encode the underscore character. Use a hyphen instead.';
        }//underscore

        if ((int) $settings['SeqLength'] < 4 && $settings['SeqScope'] === 'global') {
            $problems[] = 'A company wide sequence only ' . (int) $settings['SeqLength']
                . ' digits long runs out after ' . str_repeat('9', (int) $settings['SeqLength'])
                . ' products.';
        }//short sequence

        if ($settings['Separator'] !== '' && $spec['digits']) {
            $problems[] = 'The separator will be dropped: ' . $settings['Symbology']
                . ' can only encode digits.';
        }//separator in numeric mode

        return $problems;
    }//bcgValidateSettings

    /**
     * One line entry point for the product controllers.
     *
     * Returns a freshly allocated, unique barcode for a product being saved, or
     * $fallback when automatic generation is switched off, the module has not
     * been installed, or no unique code could be produced.
     *
     * $fallback is normally the product number, which is exactly what the
     * controllers used before this module existed - so a shop that never opens
     * the settings page keeps the behaviour it already had if it switches
     * automatic barcodes off.
     */
    function bcgBarcodeForProduct($shop_id, $subcat_id, $item_name, $fallback)
    {
        try {
            $bcObj = new BarcodeSettings();
            $settings = bcgNormalizeSettings($bcObj->getSettings($shop_id));

            if ((int) $settings['AutoGenerate'] !== 1) {
                return $fallback;
            }//switched off for this shop

            $code = bcgGenerateBarcode($bcObj, $shop_id, $subcat_id, $settings, $item_name);

            return ($code === '') ? $fallback : $code;
        }//try
        catch (Throwable $e) {
            /*
             * Saving a product must never fail because of the barcode module.
             * Falling back to the product number is what happened before this
             * module existed, so the worst case is the old behaviour.
             */
            return $fallback;
        }//catch
    }//bcgBarcodeForProduct

}//function guard
