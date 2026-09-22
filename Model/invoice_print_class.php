<?php

//Everything the A4 invoice (Receipts/a4-invoice.php) prints.
//
//Every query is a prepared statement and every query is limited to one shop: an invoice is only
//returned to the shop it was billed in, so an invoice id typed into the address bar can never
//show - or count a print of - another shop's bill.
class InvoicePrint extends Dbh
{
    const PAYMETHOD_CREDIT = 4;     //paymethod "Credit": the unpaid part of a bill, not money received

    //the invoice with its shop, company, customer, sales rep and cashier, or null when this
    //shop has no such invoice
    public function getInvoice($invoice_id, $shop_id)
    {
        $sql = "SELECT ih.IHID, ih.BillNo, ih.InvEndTime, ih.InvStat, ih.NetAmount, ih.returnAmount,
                       ih.remarks, ih.print_count, ih.customers_CTID,
                       shop.ShopName, shop.ReceiptLogo, shop.AddressLineOne, shop.AddressLineTwo, shop.City,
                       shop.PhoneNumber, shop.emailAddress,
                       company.ComName,
                       customer.CustName, customer.CustAddress, customer.CustContact,
                       salesman.SalesmansName,
                       cashier.UserName
                FROM invoiceheader ih
                INNER JOIN shop ON shop.SHID = ih.shop_SHID
                LEFT JOIN company ON company.CMID = shop.Company_CMID
                LEFT JOIN customers customer ON customer.CTID = ih.customers_CTID
                LEFT JOIN salesmans salesman ON salesman.SLID = ih.Salesmans_SLID
                LEFT JOIN user cashier ON cashier.USID = ih.user_USID
                WHERE ih.IHID = ? AND ih.shop_SHID = ?
                LIMIT 1;";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$invoice_id, $shop_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }//get invoice

    //the bill's lines in the order they were rung up. LEFT JOIN: a line whose product was later
    //deleted is still printed, so the lines always add up to the bill.
    public function getLines($invoice_id, $shop_id)
    {
        $sql = "SELECT d.IDID, d.Item_Name, d.SellQty, d.UnitPrice, d.SoldAmount, product.ItemName, product.Barcode
                FROM invoicedetails d
                INNER JOIN invoiceheader ih ON ih.IHID = d.InvoiceHeader_IHID
                LEFT JOIN products product ON product.PDID = d.products_PDID
                WHERE d.InvoiceHeader_IHID = ? AND ih.shop_SHID = ?
                ORDER BY d.IDID ASC;";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$invoice_id, $shop_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }//get lines

    //money taken when the bill was made, one row per payment method
    public function getPayments($invoice_id, $shop_id)
    {
        $sql = "SELECT t.paymethod_PMID AS PMID, pm.PaymethodName, SUM(t.TransferAmount) AS Amount
                FROM transactions t
                INNER JOIN invoiceheader ih ON ih.IHID = t.InvoiceHeader_IHID
                LEFT JOIN paymethod pm ON pm.PMID = t.paymethod_PMID
                WHERE t.InvoiceHeader_IHID = ? AND ih.shop_SHID = ? AND t.paymethod_PMID <> ?
                GROUP BY t.paymethod_PMID, pm.PaymethodName
                ORDER BY t.paymethod_PMID ASC;";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$invoice_id, $shop_id, self::PAYMETHOD_CREDIT]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }//get payments

    //the credit ledger of a bill that was not paid in full: what was owed and what has been paid
    //against it since. Same figures the Invoice List shows as "Balance Due".
    public function getCredit($invoice_id, $shop_id)
    {
        $sql = "SELECT COUNT(cc.CCID) AS Entries,
                       COALESCE(SUM(cc.CreditAmount), 0) AS Owed,
                       COALESCE(SUM(cc.DebitAmount), 0) AS Paid
                FROM creditcustomer cc
                INNER JOIN invoiceheader ih ON ih.IHID = cc.invoice_header_id
                WHERE cc.invoice_header_id = ? AND ih.shop_SHID = ? AND cc.CreditStat = 1;";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$invoice_id, $shop_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }//get credit

    //one more printed copy of this bill
    public function countPrint($invoice_id, $shop_id)
    {
        $sql = "UPDATE invoiceheader SET print_count = print_count + 1 WHERE IHID = ? AND shop_SHID = ?;";

        $stmt = $this->connect()->prepare($sql);
        return $stmt->execute([$invoice_id, $shop_id]);
    }//count print

}//class invoice print
