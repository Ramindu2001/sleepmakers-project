<?php
//Reads what a barcode scanner typed into the upload box
//(docs/superpowers/specs/2026-09-22-scanner-upload-design.md, section 5).
//
//In inventory mode the scanner stores every scan and its cradle types them all at once, each
//followed by Enter (or Tab, or nothing, depending on the scanner's settings). parse() turns
//that text into a count per barcode:
//  - one code per line, or several separated by spaces, Tabs, commas or semicolons
//  - "CODE,10" / "CODE 10" / "CODE<Tab>10": the code with a quantity
//  - codes typed with no separator at all are split against the known barcodes, but only when
//    there is exactly one way to do it - anything else is reported as unknown, never guessed
//  - codes match case-insensitively, with or without leading zeros (as the POS scan does)
//  - where a shop prints a unique barcode on every unit (db/UNIT_BARCODES_MODULE.md), a unit
//    code is a known item code plus a fixed number of characters ("COO00001" + "25090001").
//    $unitSuffixLength says how many; those tokens are reported separately, and the caller
//    looks them up - this class never decides that a unit exists.
//It never touches the database: the caller passes the barcodes that may be matched.
class ScanParser
{
    const MAX_CHARS = 200000;
    const MAX_CODES = 5000;

    //['items' => [stored barcode => count], 'units' => [unit code => count],
    //'unknown' => [token => count], 'scans' => codes counted, 'truncated' => the upload was
    //longer than the limits]
    public static function parse($raw, array $knownBarcodes, $unitSuffixLength = 0)
    {
        $index = [];
        foreach($knownBarcodes as $code)
        {
            $code = trim((string)$code);
            if($code !== '' && !isset($index[strtoupper($code)]))
            {
                $index[strtoupper($code)] = $code;
            }
        }//upper case => stored code
        $lengths = array_values(array_unique(array_map('strlen', array_keys($index))));
        $unit = (int)$unitSuffixLength > 0 ? (int)$unitSuffixLength : 0;

        $raw = (string)$raw;
        $result = ['items' => [], 'units' => [], 'unknown' => [], 'scans' => 0, 'truncated' => strlen($raw) > self::MAX_CHARS];
        $raw = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', substr($raw, 0, self::MAX_CHARS));

        $read = 0;
        foreach(preg_split('/\r\n|\r|\n/', $raw) as $line)
        {
            $tokens = preg_split('/[\s,;]+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);
            if(count($tokens) === 2 && preg_match('/^\d{1,5}$/', $tokens[1]) && self::lookup($tokens[1], $index) === null)
            {
                $entries = [[$tokens[0], (int)$tokens[1]]];
            }//code + quantity
            else
            {
                $entries = array_map(function($token) { return [$token, 1]; }, $tokens);
            }//one or more codes

            foreach($entries as [$token, $qty])
            {
                if($read >= self::MAX_CODES)
                {
                    $result['truncated'] = true;
                    break 2;
                }//limit reached
                $read++;
                if($qty > 0)
                {
                    self::count($result, $token, $qty, $index, $lengths, $unit);
                }
            }//each entry
        }//each line

        return $result;
    }//parse

    //a whole item code first, then a whole unit code, then a run of them typed together
    private static function count(array &$result, $token, $qty, array $index, array $lengths, $unit)
    {
        $match = self::lookup($token, $index);
        if($match !== null)
        {
            $pieces = [['items', $match]];
        }
        elseif(self::unitOf($token, $index, $unit) !== null)
        {
            $pieces = [['units', strtoupper($token)]];
        }
        else
        {
            //item codes first: a run of shelf labels still reads as products in a shop that
            //also numbers its units, and unit pieces are only considered when that fails
            $pieces = self::split($token, $index, $lengths, 0);
            if($pieces === null && $unit > 0)
            {
                $pieces = self::split($token, $index, $lengths, $unit);
            }
        }

        if($pieces === null)
        {
            $result['unknown'][$token] = (isset($result['unknown'][$token]) ? $result['unknown'][$token] : 0) + $qty;
            $result['scans'] += $qty;
            return;
        }//not a code we know

        foreach($pieces as [$bucket, $code])
        {
            $result[$bucket][$code] = (isset($result[$bucket][$code]) ? $result[$bucket][$code] : 0) + $qty;
            $result['scans'] += $qty;
        }
    }//count

    //the item code a unit token belongs to, or null when the token is not shaped like one
    private static function unitOf($token, array $index, $unit)
    {
        if($unit < 1 || strlen($token) <= $unit)
        {
            return null;
        }
        return self::lookup(substr($token, 0, strlen($token) - $unit), $index);
    }//unit of

    //the stored code a token stands for - as scanned, with a leading 0 added, or with its
    //leading zeros removed - or null
    private static function lookup($token, array $index)
    {
        $token = strtoupper($token);
        $stripped = ltrim($token, '0');
        foreach([$token, '0' . $token, $stripped === '' ? '0' : $stripped] as $candidate)
        {
            if(isset($index[$candidate]))
            {
                return $index[$candidate];
            }
        }
        return null;
    }//lookup

    //Codes typed with no separator: the pieces that make up the token when there is exactly
    //one way to split all of it, else null. A piece is a known item code, or - where the shop
    //numbers every unit - that code followed by the unit suffix.
    //Returns [['items'|'units', code], ...].
    private static function split($token, array $index, array $lengths, $unit)
    {
        $text = strtoupper($token);
        $n = strlen($text);
        if($n === 0 || empty($lengths))
        {
            return null;
        }

        //ways[i]: how many ways text[i..] splits into pieces (counted up to 2)
        $ways = array_fill(0, $n + 1, 0);
        $ways[$n] = 1;
        $next = [];
        for($i = $n - 1; $i >= 0; $i--)
        {
            foreach($lengths as $length)
            {
                if($i + $length > $n || !isset($index[substr($text, $i, $length)]))
                {
                    continue;
                }//no known code starts here at this length

                foreach($unit > 0 ? [$length, $length + $unit] : [$length] as $piece)
                {
                    if($i + $piece <= $n && $ways[$i + $piece] > 0)
                    {
                        $ways[$i] = min(2, $ways[$i] + $ways[$i + $piece]);
                        $next[$i] = [$piece, $piece === $length ? 'items' : 'units'];
                    }
                }//the code itself, or the code with a unit suffix
            }
        }
        if($ways[0] !== 1)
        {
            return null;
        }//no way, or more than one

        $pieces = [];
        for($i = 0; $i < $n; $i += $next[$i][0])
        {
            [$length, $bucket] = $next[$i];
            $pieces[] = [$bucket, $bucket === 'items' ? $index[substr($text, $i, $length)] : substr($text, $i, $length)];
        }
        return $pieces;
    }//split
}//ScanParser
