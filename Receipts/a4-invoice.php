<?php
//A4 invoice for the POS bill, in the Sleep Makers receipt layout.
//
//A shop prints this when "A4 Invoice Print" is switched on in its shop settings
//(Model/receipt_format_class.php); every other shop keeps the 80mm receipt. It is opened with the
//same parameters as the 80mm receipt - invoice / invoice_id, print, Iframe, pdf, invoiceList -
//prints itself, returns to the page it came from and counts the print.
//
//Security
//  - a logged in user who is allowed into the current shop (same rule as the dashboard)
//  - the invoice id must be a whole number and the invoice must belong to the current shop
//  - prepared statements only (Model/invoice_print_class.php)
//  - every printed value is HTML-escaped; the sale details typed at checkout are rich text and
//    are reduced to plain text first
//  - a Content Security Policy lets only this page's own script and styles run
//  - never cached: the page carries the customer's name, address and phone number
//
//Figures
//  Sub total  = every line at its unit price (the UNIT PRICE column)
//  Net total  = what the POS charged (invoiceheader.NetAmount)
//  Discount   = the difference (line discounts, the invoice discount and price changes), so the
//               invoice always adds up to the amount that was charged. GrossAmount and
//               DiscountAmount on the header are not used: on older bills they disagree with the lines.
//  VAT        = included in the selling prices, shown as the part of the net total that is VAT
//  Payments   = taken at the sale, plus any credit settled later; the balance is the one the
//               Invoice List shows as "Balance Due"

//------------------------------------------------------ settings ---------------------------------
//every shop that prints A4
$invoice_settings = [
    'title'    => 'RECEIPT',
    'website'  => '',
    'vat_rate' => 0,        //% VAT included in the selling prices; 0 prints no VAT lines
    'min_rows' => 16,       //the item table is never shorter than this many lines, like the paper form
];

//per shop (shop.SHID), on top of the above. The VAT rate is a statement about THAT shop's prices,
//so a VAT line is only ever printed for a shop listed here.
$shop_invoice_settings = [
    2 => ['website' => 'www.valentino.lk', 'vat_rate' => 18],     //Sleep Makers (Pvt) Ltd
];

//payment rows always printed, in this order (paymethod.PMID => label). Any other method used on
//the bill is printed below them.
$fixed_payment_rows = [1 => 'Cash', 2 => 'Card', 5 => 'Cheque', 11 => 'KOKO', 3 => 'Online Transfer'];

$walk_in_customer_id = 1;   //"Common Customer": the Bill To lines are left blank to fill in by hand

//------------------------------------------------------ access -----------------------------------
include_once '../Includes/includes.php';
include '../Includes/authcheck.php';
ini_set('display_errors', '0');     //authcheck switches it on; a PHP warning must never print on a customer's bill

function a4_e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}//escape for HTML

function a4_money($amount)
{
    return number_format((float)$amount, 2, '.', ',');
}//money

function a4_qty($qty)
{
    return rtrim(rtrim(number_format((float)$qty, 3, '.', ''), '0'), '.');
}//quantity without trailing zeros

function a4_fail($status, $message)
{
    http_response_code($status);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Invoice</title></head><body>'
        . '<p style="font-family:sans-serif;margin:40px;">' . a4_e($message) . '</p></body></html>';
    exit;
}//stop with a message

//rich text typed at checkout (CKEditor HTML) as plain lines
function a4_plain_text($html)
{
    $text = preg_replace('~<(script|style)\b[^>]*>.*?<\s*/\s*\1\s*>~is', '', (string)$html);
    $text = preg_replace('~<\s*br\s*/?\s*>~i', "\n", $text);
    $text = preg_replace('~<\s*/\s*(p|div|li|h[1-6]|tr)\s*>~i', "\n", $text);
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace("\xC2\xA0", ' ', $text);

    $lines = [];
    foreach(preg_split('~\r\n|\r|\n~', $text) as $line)
    {
        $line = trim($line);
        if($line !== '')
        {
            $lines[] = $line;
        }
    }//foreach
    return implode("\n", $lines);
}//plain text

//the item name and its notes. The cart's name box starts as "Barcode - Item name - Rs.price";
//the cashier may change that line, and anything typed on the lines below it is a note printed
//under the item (e.g. COMFORT LEVEL - SOFT).
function a4_item_text($line)
{
    $typed = preg_split('~\r\n|\r|\n~', (string)$line['Item_Name']);
    $name = trim((string)array_shift($typed));

    $barcode = trim((string)$line['Barcode']);
    if($barcode !== '' && stripos($name, $barcode . ' - ') === 0)
    {
        $name = substr($name, strlen($barcode) + 3);
    }//generated barcode prefix
    $name = trim(preg_replace('~\s*-\s*Rs\.\s*[0-9][0-9.,]*\s*$~i', '', $name));

    if($name === '')
    {
        $name = trim((string)$line['ItemName']);
    }//nothing typed: the product's name

    $notes = [];
    foreach($typed as $note)
    {
        //reopening a held bill appends " - Rs.price" to the last line
        $note = trim(preg_replace('~\s*-\s*Rs\.\s*[0-9][0-9.,]*\s*$~i', '', $note));
        if($note !== '')
        {
            $notes[] = $note;
        }
    }//foreach

    return [$name, $notes];
}//item text

$session_shop_id = filter_var($_SESSION['shop_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if($session_shop_id === false || !(new RememberMe())->canAccessShop($_SESSION['user_id'], $session_shop_id))
{
    a4_fail(403, 'You do not have access to this shop.');
}//not allowed into this shop

if(isset($shop_invoice_settings[$session_shop_id]))
{
    $invoice_settings = array_merge($invoice_settings, $shop_invoice_settings[$session_shop_id]);
}//this shop's own website and VAT

$invoice_id = filter_var($_GET['invoice'] ?? ($_GET['invoice_id'] ?? null), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if($invoice_id === false)
{
    a4_fail(400, 'No invoice was selected.');
}//not an invoice id

//how the page was opened
$is_iframe = isset($_GET['Iframe']);    //shown inside another page: no printing, not counted
$is_pdf    = isset($_GET['pdf']);       //to save as PDF: not counted as a printed copy

//------------------------------------------------------ data -------------------------------------
try
{
    $printObj = new InvoicePrint();

    $invoice = $printObj->getInvoice($invoice_id, $session_shop_id);
    if($invoice === null)
    {
        a4_fail(404, 'This invoice was not found in this shop.');
    }//not this shop's invoice

    $lines    = $printObj->getLines($invoice_id, $session_shop_id);
    $payments = $printObj->getPayments($invoice_id, $session_shop_id);
    $credit   = $printObj->getCredit($invoice_id, $session_shop_id);
}
catch (Throwable $e)
{
    error_log("a4-invoice.php invoice " . $invoice_id . ": " . $e->getMessage());
    a4_fail(500, 'The invoice could not be loaded. Please try again.');
}//catch

//shop / company
$company_name = trim((string)$invoice['ComName']) !== '' ? $invoice['ComName'] : $invoice['ShopName'];
$address = implode(', ', array_filter(array_map('trim', [
    (string)$invoice['AddressLineOne'], (string)$invoice['AddressLineTwo'], (string)$invoice['City'],
]), 'strlen'));

$logo_file = basename((string)$invoice['ReceiptLogo']);
$logo_src = null;
if(preg_match('~^[A-Za-z0-9._-]+\.(png|jpe?g|gif|webp)$~i', $logo_file) && is_file(__DIR__ . '/../Assets/Images/shop_images/' . $logo_file))
{
    $logo_src = '../Assets/Images/shop_images/' . $logo_file;
}//a real image in the shop images folder

//bill to
$is_walk_in = ((int)$invoice['customers_CTID'] === $walk_in_customer_id);
$customer_name    = $is_walk_in ? '' : trim((string)$invoice['CustName']);
$customer_address = $is_walk_in ? '' : trim((string)$invoice['CustAddress']);
$customer_phone   = $is_walk_in ? '' : trim((string)$invoice['CustContact']);

$bill_time = strtotime((string)$invoice['InvEndTime']);
$bill_date = $bill_time ? date('d/m/Y', $bill_time) : '';

//lines
$rows = [];
$table_lines = 0;
$list_total = 0.0;
foreach($lines as $line)
{
    list($name, $notes) = a4_item_text($line);
    $qty  = (float)$line['SellQty'];
    $unit = round((float)$line['UnitPrice'], 2);
    $sold = round((float)$line['SoldAmount'], 2);

    $rows[] = [
        'name'       => $name,
        'notes'      => $notes,
        'qty'        => $qty,
        'unit'       => $unit,
        'disc_unit'  => ($qty != 0) ? round($sold / $qty, 2) : $sold,
        'disc_total' => $sold,
    ];
    $list_total += round($qty * $unit, 2);
    $table_lines += 1 + count($notes);
}//foreach
$filler_rows = max(0, $invoice_settings['min_rows'] - $table_lines);

$special_instruction = a4_plain_text($invoice['remarks']);

//totals
$list_total   = round($list_total, 2);
$net_total    = round((float)$invoice['NetAmount'], 2);
$return_total = round(max(0, (float)$invoice['returnAmount']), 2);
$discount     = round($list_total - $return_total - $net_total, 2);

$vat_rate = (float)$invoice_settings['vat_rate'];
$vat_amount = ($vat_rate > 0) ? round($net_total * $vat_rate / (100 + $vat_rate), 2) : 0.0;
$net_excluding_vat = round($net_total - $vat_amount, 2);

//payments
$paid_by_method = [];
$other_payments = [];
$paid_at_sale = 0.0;
foreach($payments as $payment)
{
    $amount = round((float)$payment['Amount'], 2);
    $paid_at_sale += $amount;

    if(isset($fixed_payment_rows[(int)$payment['PMID']]))
    {
        $paid_by_method[(int)$payment['PMID']] = $amount;
    }//one of the printed rows
    else
    {
        $other_payments[] = ['label' => trim((string)$payment['PaymethodName']) !== '' ? $payment['PaymethodName'] : 'Other', 'amount' => $amount];
    }//another method
}//foreach
$paid_at_sale = round($paid_at_sale, 2);

if(!empty($credit) && (int)$credit['Entries'] > 0)
{
    $paid_later = round((float)$credit['Paid'], 2);
    $balance_due = round(max(0, (float)$credit['Owed'] - (float)$credit['Paid']), 2);
}//went on credit: settled so far, and what is still owed
else
{
    $paid_later = 0.0;
    $balance_due = round(max(0, $net_total - $paid_at_sale), 2);
}//paid at the sale
$change_given = round(max(0, $paid_at_sale - $net_total), 2);

$prepared_by = trim((string)$invoice['UserName']);
$is_cancelled = ((string)$invoice['InvStat'] === '0');

//------------------------------------------------------ after printing ---------------------------
//where the window goes once printed - the same as the 80mm receipt. Built only from fixed text
//and the validated invoice id.
$after_print = '';
if(isset($_SESSION["deliveryNote"]))
{
    if($_SESSION["deliveryNote"] == 1)
    {
        $after_print = 'window.location = "./deliveryNote.php?invoice_id=' . (int)$invoice_id . '";';
    }
    else
    {
        $after_print = 'window.location = "../Public/gui-pos.php";';
    }
    unset($_SESSION["deliveryNote"]);
}
elseif(isset($_SESSION["sales_returni"]))
{
    unset($_SESSION["sales_returni"]);
    $after_print = 'window.location = "../Public/sales-return.php";';
}
elseif(isset($_GET["invoiceList"]))
{
    $after_print = 'window.history.back();';
}
elseif($is_iframe)
{
    $after_print = '';
}
elseif(isset($_GET["avoice"]))
{
    $after_print = 'history.back();';
}
elseif($is_pdf || isset($_GET["print"]))
{
    $after_print = 'window.close();';
}
else
{
    $after_print = 'window.location = "../Public/wholesale-invoice.php";';
}
$after_print_delay = $is_pdf ? 5000 : 2000;

//a printed copy is counted as before: not when shown inside a page or saved as PDF
if(!$is_iframe && !$is_pdf)
{
    try
    {
        $printObj->countPrint($invoice_id, $session_shop_id);
    }
    catch (Throwable $e)
    {
        error_log("a4-invoice.php print count, invoice " . $invoice_id . ": " . $e->getMessage());
    }//the bill still prints
}//count print

//------------------------------------------------------ headers ----------------------------------
$nonce = base64_encode(random_bytes(18));
if(!headers_sent())
{
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, private');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'none'; img-src 'self' data:; style-src 'nonce-" . $nonce . "'; script-src 'nonce-" . $nonce . "'; base-uri 'none'; form-action 'none'; frame-ancestors 'self'");
}//headers
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?=a4_e($company_name . " - " . $invoice['BillNo'])?></title>
<style nonce="<?=a4_e($nonce)?>">
    /*------------------------------ page ------------------------------*/
    @page{
        size: A4 portrait;
        margin: 10mm;
    }
    *{
        box-sizing: border-box;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    :root{
        --green: #70ad47;
        --line: #000;
        --muted: #595959;
    }
    html, body{
        margin: 0;
        padding: 0;
        color: #000;
        background: #e6e6e6;
        font-family: Calibri, Carlito, 'Segoe UI', Arial, sans-serif;
        font-size: 9.5pt;
    }
    .sheet{
        width: 210mm;
        min-height: 297mm;
        margin: 8mm auto;
        padding: 10mm;
        background: #fff;
        box-shadow: 0 1mm 4mm rgba(0, 0, 0, .25);
    }
    table{
        border-collapse: collapse;
        width: 100%;
    }
    td, th{
        padding: 0 1.5mm;
        vertical-align: middle;
    }
    .num{
        text-align: right;
        white-space: nowrap;
    }
    .center{
        text-align: center;
    }

    /*------------------------------ head ------------------------------*/
    .doc_title{
        text-align: center;
        font-size: 20pt;
        font-weight: bold;
        letter-spacing: .5mm;
        margin: 0 0 2mm 0;
        padding-bottom: 1mm;
        border-bottom: 0.6pt solid var(--line);
    }
    .cancelled{
        display: block;
        width: max-content;
        margin: 0 auto 2mm auto;
        padding: .5mm 4mm;
        border: 1.5pt solid #c00000;
        color: #c00000;
        font-size: 12pt;
        font-weight: bold;
        letter-spacing: 1mm;
    }
    .head{
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: start;
        column-gap: 6mm;
        margin-bottom: 3mm;
    }
    .company_name{
        font-size: 19pt;
        margin: 0 0 1.5mm 0;
    }
    .company_detail{
        margin: 0;
        font-size: 8pt;
        line-height: 1.55;
    }
    .logo{
        max-width: 45mm;
        max-height: 30mm;
        display: block;
    }

    /*------------------------------ bill to ------------------------------*/
    .bill{
        display: grid;
        grid-template-columns: 1fr 58mm;
        column-gap: 3mm;
        margin-bottom: 1.5mm;
        align-items: start;
    }
    .bar{
        background: var(--green);
        color: #fff;
        font-weight: bold;
        font-size: 8.5pt;
        height: 5mm;
    }
    .bill_to td{
        height: 5.5mm;
        font-size: 9pt;
    }
    .bill_to .label{
        width: 26mm;
        font-size: 8.5pt;
        white-space: nowrap;
    }
    .bill_to .colon{
        width: 4mm;
    }
    .bill_to .value{
        font-size: 9.5pt;
    }
    .bill_to .rule td{
        border-bottom: 0.6pt solid var(--line);
    }
    .bill_meta td{
        border: 0.6pt solid var(--line);
        height: 5mm;
        font-size: 8.5pt;
    }
    .bill_meta .label{
        width: 26mm;
        white-space: nowrap;
    }
    .bill_meta .value{
        text-align: center;
    }

    /*------------------------------ items ------------------------------*/
    .items{
        table-layout: fixed;
        border: 0.6pt solid var(--line);
    }
    .items thead th{
        background: var(--green);
        color: #fff;
        font-size: 8pt;
        font-weight: bold;
        height: 8mm;
        text-align: center;
        line-height: 1.15;
        border: 0.6pt solid var(--line);
    }
    .items .col_qty{ width: 13mm; }
    .items .col_unit{ width: 24mm; }
    .items .col_disc_unit{ width: 24mm; }
    .items .col_disc_total{ width: 26mm; }
    .items tbody td{
        height: 5.3mm;
        font-size: 9pt;
        border-left: 0.6pt solid var(--line);
        border-right: 0.6pt solid var(--line);
        overflow-wrap: anywhere;
    }
    .items tbody tr{
        break-inside: avoid;
    }
    .items .note{
        font-weight: bold;
    }
    .items .instruction td{
        vertical-align: top;
        padding-top: 1mm;
        padding-bottom: 1mm;
        border-top: 0.6pt solid var(--line);
    }
    .instruction_title{
        font-weight: bold;
        text-decoration: underline;
        margin: 0 0 .5mm 0;
    }
    .instruction_text{
        font-weight: bold;
        margin: 0;
    }

    /*------------------------------ totals and payments ------------------------------*/
    .foot{
        display: grid;
        grid-template-columns: 1fr 97mm;
        column-gap: 6mm;
        break-inside: avoid;
    }
    .totals td{
        height: 5.3mm;
        font-size: 9pt;
    }
    .totals .label{
        text-align: right;
        font-weight: bold;
        white-space: nowrap;
    }
    .totals .value{
        width: 26mm;
        text-align: right;
        white-space: nowrap;
        border: 0.6pt solid var(--line);
    }
    .totals .net td{
        font-size: 10.5pt;
        font-weight: bold;
    }
    .totals .vat td{
        font-size: 8pt;
        color: var(--muted);
        height: 4.6mm;
    }
    .totals .vat .label{
        font-weight: normal;
    }
    .totals .vat .value{
        border: none;
    }
    .payments{
        margin-top: 5.3mm;
    }
    .payments td{
        border: 0.6pt solid var(--line);
        height: 5mm;
        font-size: 8.5pt;
    }
    .payments .label{
        width: 35mm;
        white-space: nowrap;
    }
    .payments .value{
        text-align: right;
        font-size: 9.5pt;
    }
    .payments .strong td{
        font-weight: bold;
    }
    .signatures{
        margin-top: 9mm;
        display: grid;
        row-gap: 12mm;
    }
    .signature{
        margin-left: 12mm;
        text-align: center;
        font-size: 8.5pt;
    }
    .signature .who{
        min-height: 4mm;
    }
    .signature .line{
        border-top: 0.6pt solid var(--line);
        padding-top: 1mm;
    }
    .powered{
        margin-top: 6mm;
        text-align: center;
        font-size: 6.5pt;
        color: var(--muted);
    }

    @media print{
        html, body{
            background: #fff;
        }
        .sheet{
            width: auto;
            min-height: 0;
            margin: 0;
            padding: 0;
            box-shadow: none;
        }
    }
</style>
</head>
<body>
<div class="sheet">

    <!------------------------------------------ head ------------------------------------------>
    <h1 class="doc_title"><?=a4_e($invoice_settings['title'])?></h1>
    <?php if($is_cancelled) { ?>
    <span class="cancelled">CANCELLED</span>
    <?php } ?>

    <div class="head">
        <div>
            <p class="company_name"><?=a4_e($company_name)?></p>
            <p class="company_detail">
                <?php if($address !== '') { ?><?=a4_e($address)?>.<br><?php } ?>
                <?php if(trim((string)$invoice['PhoneNumber']) !== '') { ?>Phone: <?=a4_e($invoice['PhoneNumber'])?><br><?php } ?>
                <?php if(trim((string)$invoice['emailAddress']) !== '') { ?>Email: <?=a4_e($invoice['emailAddress'])?><br><?php } ?>
                <?php if($invoice_settings['website'] !== '') { ?>Website: <?=a4_e($invoice_settings['website'])?><?php } ?>
            </p>
        </div>
        <div>
            <?php if($logo_src !== null) { ?>
            <img src="<?=a4_e($logo_src)?>" alt="<?=a4_e($company_name)?>" class="logo">
            <?php } ?>
        </div>
    </div>

    <!------------------------------------------ bill to ------------------------------------------>
    <div class="bill">
        <table class="bill_to">
            <tr class="bar"><td colspan="3">BILL TO</td></tr>
            <tr class="rule">
                <td class="label">NAME</td><td class="colon">:</td>
                <td class="value"><?=a4_e($customer_name)?></td>
            </tr>
            <tr class="rule">
                <td class="label">STREET ADDRESS</td><td class="colon">:</td>
                <td class="value"><?=a4_e($customer_address)?></td>
            </tr>
            <tr class="rule">
                <td class="label">PHONE</td><td class="colon">:</td>
                <td class="value"><?=a4_e($customer_phone)?></td>
            </tr>
        </table>

        <table class="bill_meta">
            <tr><td class="label">DATE</td><td class="value"><?=a4_e($bill_date)?></td></tr>
            <tr><td class="label">RECEIPT NO</td><td class="value"><?=a4_e($invoice['BillNo'])?></td></tr>
            <tr><td class="label">DELIVERY ON</td><td class="value"></td></tr>
            <tr><td class="label">SALE ID</td><td class="value"><?=a4_e($invoice['SalesmansName'])?></td></tr>
        </table>
    </div>

    <!------------------------------------------ items ------------------------------------------>
    <table class="items">
        <thead>
            <tr>
                <th>DETAILS</th>
                <th class="col_qty">QTY</th>
                <th class="col_unit">UNIT PRICE</th>
                <th class="col_disc_unit">DISCOUNTED PRICE</th>
                <th class="col_disc_total">DISCOUNTED TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($rows as $row) { ?>
            <tr>
                <td><?=a4_e($row['name'])?></td>
                <td class="center"><?=a4_e(a4_qty($row['qty']))?></td>
                <td class="num"><?=a4_money($row['unit'])?></td>
                <td class="num"><?=a4_money($row['disc_unit'])?></td>
                <td class="num"><?=a4_money($row['disc_total'])?></td>
            </tr>
                <?php foreach($row['notes'] as $note) { ?>
            <tr>
                <td class="note"><?=a4_e($note)?></td>
                <td></td><td></td><td></td><td></td>
            </tr>
                <?php } ?>
            <?php } ?>

            <?php for($i = 0; $i < $filler_rows; $i++) { ?>
            <tr><td></td><td></td><td></td><td></td><td></td></tr>
            <?php } ?>

            <tr class="instruction">
                <td>
                    <p class="instruction_title">SPECIAL INSTRUCTION</p>
                    <p class="instruction_text"><?=nl2br(a4_e($special_instruction))?></p>
                </td>
                <td></td><td></td><td></td><td></td>
            </tr>
        </tbody>
    </table>

    <!------------------------------------------ payments and totals ------------------------------------------>
    <div class="foot">
        <div>
            <table class="payments">
                <?php foreach($fixed_payment_rows as $pmid => $label) { ?>
                <tr>
                    <td class="label"><?=a4_e($label)?></td>
                    <td class="value"><?=isset($paid_by_method[$pmid]) ? a4_money($paid_by_method[$pmid]) : ''?></td>
                </tr>
                <?php } ?>
                <?php foreach($other_payments as $payment) { ?>
                <tr>
                    <td class="label"><?=a4_e($payment['label'])?></td>
                    <td class="value"><?=a4_money($payment['amount'])?></td>
                </tr>
                <?php } ?>
                <tr>
                    <td class="label">Later Payments</td>
                    <td class="value"><?=$paid_later > 0 ? a4_money($paid_later) : ''?></td>
                </tr>
                <?php if($change_given > 0) { ?>
                <tr>
                    <td class="label">Change Given</td>
                    <td class="value"><?=a4_money($change_given)?></td>
                </tr>
                <?php } ?>
                <tr class="strong">
                    <td class="label">Balance to be Paid</td>
                    <td class="value"><?=a4_money($balance_due)?></td>
                </tr>
                <tr class="strong">
                    <td class="label">Total</td>
                    <td class="value"><?=a4_money($net_total)?></td>
                </tr>
            </table>
        </div>

        <div>
            <table class="totals">
                <tr>
                    <td class="label">SUB TOTAL (LIST PRICE) Rs.</td>
                    <td class="value"><?=a4_money($list_total)?></td>
                </tr>
                <tr>
                    <td class="label">DISCOUNT Rs.</td>
                    <td class="value"><?=a4_money($discount)?></td>
                </tr>
                <?php if($return_total > 0) { ?>
                <tr>
                    <td class="label">RETURNED ITEMS Rs.</td>
                    <td class="value"><?=a4_money($return_total)?></td>
                </tr>
                <?php } ?>
                <tr class="net">
                    <td class="label">NET TOTAL Rs.</td>
                    <td class="value"><?=a4_money($net_total)?></td>
                </tr>
                <?php if($vat_rate > 0) { ?>
                <tr class="vat">
                    <td class="label">Amount excluding VAT</td>
                    <td class="value"><?=a4_money($net_excluding_vat)?></td>
                </tr>
                <tr class="vat">
                    <td class="label">VAT <?=a4_e(a4_qty($vat_rate))?>% (included in Net Total)</td>
                    <td class="value"><?=a4_money($vat_amount)?></td>
                </tr>
                <?php } ?>
            </table>

            <div class="signatures">
                <div class="signature">
                    <div class="who"><?=a4_e($prepared_by)?></div>
                    <div class="line">Prepared By</div>
                </div>
                <div class="signature">
                    <div class="who"></div>
                    <div class="line">Product Sizes &amp; Quantities Checked, Found Correct</div>
                </div>
            </div>
        </div>
    </div>

    <p class="powered">Powered by : Synnex IT Solution PVT(LTD)</p>
</div>

<script nonce="<?=a4_e($nonce)?>">
    window.addEventListener("load", function () {
        <?php if(!$is_iframe) { ?>
        window.print();
        <?php } ?>
        setTimeout(function () {
            <?=$after_print?>
        }, <?=(int)$after_print_delay?>);
    });
</script>
</body>
</html>
