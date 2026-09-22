<?php
//The scanner uploads applied to GRNs and transfers (table scanbatches): who, where, when and
//what. The fingerprint of the applied lines finds the same batch uploaded twice to one
//document - a scanner whose memory was not cleared sends it again.
class ScanBatches extends Dbh
{
    const GRN = 'GRN';
    const TRANSFER_OUT = 'TRF_OUT';
    const TRANSFER_IN = 'TRF_IN';

    //SHA-256 of the lines (barcode => quantity), whatever their order or letter case
    public static function fingerprint(array $lines)
    {
        $rows = [];
        foreach($lines as $code => $qty)
        {
            $rows[] = strtoupper((string)$code) . "\t" . (0 + $qty);
        }
        sort($rows, SORT_STRING);
        return hash('sha256', implode("\n", $rows));
    }//fingerprint

    //the latest upload of this batch to this document (UserName, CreatedAt), or null
    public function findDuplicate($doc_type, $doc_id, $fingerprint)
    {
        $stmt = $this->connect()->prepare("SELECT user.UserName, scanbatches.CreatedAt FROM scanbatches
            LEFT JOIN user ON user.USID = scanbatches.user_USID
            WHERE scanbatches.DocType = ? AND scanbatches.DocID = ? AND scanbatches.Fingerprint = ?
            ORDER BY scanbatches.SBID DESC LIMIT 1;");
        $stmt->execute([$doc_type, (int)$doc_id, $fingerprint]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }//find duplicate

    //record an applied upload; returns its id
    public function record($doc_type, $doc_id, $shop_id, $user_id, array $lines, $scan_count)
    {
        $stmt = $this->connect()->prepare("INSERT INTO scanbatches
            (DocType, DocID, shop_SHID, user_USID, Fingerprint, ScanCount, LineCount, Summary, CreatedAt)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);");
        $stmt->execute([$doc_type, (int)$doc_id, (int)$shop_id, (int)$user_id, self::fingerprint($lines),
            (int)$scan_count, count($lines), json_encode($lines), date('Y-m-d H:i:s')]);
        return (int)$this->connect()->lastInsertId();
    }//record
}//ScanBatches
