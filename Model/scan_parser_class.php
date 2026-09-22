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
//It never touches the database: the caller passes the barcodes that may be matched.
class ScanParser
{
    const MAX_CHARS = 200000;
    const MAX_CODES = 5000;

    //['items' => [stored barcode => count], 'unknown' => [token => count], 'scans' => codes
    //counted, 'truncated' => the upload was longer than the limits]
    public static function parse($raw, array $knownBarcodes)
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

        $raw = (string)$raw;
        $result = ['items' => [], 'unknown' => [], 'scans' => 0, 'truncated' => strlen($raw) > self::MAX_CHARS];
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
                    self::count($result, $token, $qty, $index, $lengths);
                }
            }//each entry
        }//each line

        return $result;
    }//parse

    private static function count(array &$result, $token, $qty, array $index, array $lengths)
    {
        $match = self::lookup($token, $index);
        $codes = $match !== null ? [$match] : self::split($token, $index, $lengths);
        if($codes === null)
        {
            $result['unknown'][$token] = (isset($result['unknown'][$token]) ? $result['unknown'][$token] : 0) + $qty;
            $result['scans'] += $qty;
            return;
        }//not a code we know

        foreach($codes as $code)
        {
            $result['items'][$code] = (isset($result['items'][$code]) ? $result['items'][$code] : 0) + $qty;
            $result['scans'] += $qty;
        }
    }//count

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

    //codes typed with no separator: the known codes that make up the token when there is
    //exactly one way to split all of it, else null
    private static function split($token, array $index, array $lengths)
    {
        $text = strtoupper($token);
        $n = strlen($text);
        if($n === 0 || empty($lengths))
        {
            return null;
        }

        //ways[i]: how many ways text[i..] splits into known codes (counted up to 2)
        $ways = array_fill(0, $n + 1, 0);
        $ways[$n] = 1;
        $next = [];
        for($i = $n - 1; $i >= 0; $i--)
        {
            foreach($lengths as $length)
            {
                if($i + $length <= $n && $ways[$i + $length] > 0 && isset($index[substr($text, $i, $length)]))
                {
                    $ways[$i] = min(2, $ways[$i] + $ways[$i + $length]);
                    $next[$i] = $length;
                }
            }
        }
        if($ways[0] !== 1)
        {
            return null;
        }//no way, or more than one

        $codes = [];
        for($i = 0; $i < $n; $i += $next[$i])
        {
            $codes[] = $index[substr($text, $i, $next[$i])];
        }
        return $codes;
    }//split
}//ScanParser
