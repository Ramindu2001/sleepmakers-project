<?php
/**
 * Product barcode label printing.
 * -----------------------------------------------------------------------------
 * Receives a print job from the "Print Barcode" dialog, renders the stickers at
 * the exact physical size that was chosen, and opens the browser print dialog.
 *
 * ONE PRINTED PAGE IS ONE ROW OF LABELS, not one label.
 *
 * That matters on multi column stock. A 2 across roll printed one label per
 * page leaves the whole right hand column blank - half the roll in the bin -
 * and, because the browser page is then narrower than the media, the print
 * driver is free to centre it, which drops the content over the die cut gap.
 * Sizing the page to a full row fixes both at once: every sticker is used, and
 * the page matches the media exactly so there is nothing left to centre.
 *
 * The job is stored in the session and the browser is redirected (POST/Redirect/
 * GET) so refreshing the page never triggers a form resubmission warning.
 */

include '../Includes/includes.php';
include '../Includes/authcheck.php';
include '../Includes/barcode_helper.php';

$shop_id = (int) $_SESSION['shop_id'];
$user_id = (int) $_SESSION['user_id'];

$dbObj = new DBTransactions();

//------------------------------------------------------------------ shop stat
$shopData = $dbObj->getData(
    "SELECT shop.ShopName, company.CMID, company.is_multicategory
     FROM shop
     INNER JOIN company ON company.CMID = shop.Company_CMID
     WHERE shop.SHID = " . $shop_id . ";"
);

if (empty($shopData)) {
    header("Location: product.php");
    exit;
}//no shop

if (!bcUserCanPrint($dbObj, $user_id)) {
    unset($_SESSION['barcode_job']);
    header("Location: product.php");
    exit;
}//no print right

$shop_name      = (string) $shopData[0]['ShopName'];
$company_id     = (int) $shopData[0]['CMID'];
$multi_category = (int) $shopData[0]['is_multicategory'];

//------------------------------------------------------- accept the print job
if (isset($_POST['btn_print_barcode'])) {

    /*
     * Only the option keys this module knows about are carried over. Everything
     * else in the POST is ignored, and every value is sanitised on the way out
     * of the session by bcResolveOptions().
     */
    $job = array('options' => array(), 'items' => array());

    $booleans = bcBooleanOptions();

    foreach (bcOptionDefaults() as $key => $ignore) {
        if (isset($_POST[$key])) {
            $job['options'][$key] = $_POST[$key];
            continue;
        }//posted

        /*
         * An unchecked box sends nothing at all, so an ABSENT SWITCH has to be
         * read as off - otherwise a line the operator turned off comes back on
         * at the printer.
         *
         * Everything else is left out entirely so bcResolveOptions() falls back
         * to its own default. Writing 0 into a text option instead would print
         * the shop name line as the digit 0.
         */
        if (in_array($key, $booleans, true)) {
            $job['options'][$key] = 0;
        }//switch
    }//foreach option

    $ids     = isset($_POST['item_id']) && is_array($_POST['item_id']) ? $_POST['item_id'] : array();
    $prices  = isset($_POST['item_price']) && is_array($_POST['item_price']) ? $_POST['item_price'] : array();
    $qtys    = isset($_POST['item_qty']) && is_array($_POST['item_qty']) ? $_POST['item_qty'] : array();
    $batches = isset($_POST['item_batch']) && is_array($_POST['item_batch']) ? $_POST['item_batch'] : array();

    foreach ($ids as $index => $id) {
        $id = (int) $id;
        if ($id <= 0) {
            continue;
        }//invalid row

        $job['items'][] = array(
            'id'    => $id,
            'price' => bcCleanPrice(isset($prices[$index]) ? $prices[$index] : 0),
            'qty'   => bcCleanQty(isset($qtys[$index]) ? $qtys[$index] : 1),
            'batch' => isset($batches[$index]) ? substr(trim((string) $batches[$index]), 0, 40) : '',
        );
    }//foreach row

    $_SESSION['barcode_job'] = $job;

    header("Location: print-barcode.php");
    exit;
}//new job

//--------------------------------------------------------- read the saved job
if (!isset($_SESSION['barcode_job']) || empty($_SESSION['barcode_job']['items'])) {
    header("Location: product.php");
    exit;
}//nothing to print

$job = $_SESSION['barcode_job'];
$raw_options = isset($job['options']) && is_array($job['options']) ? $job['options'] : array();

$size    = bcResolveLabelSize(
    isset($raw_options['size']) ? $raw_options['size'] : bcDefaultLabelSize(),
    isset($raw_options['custom_w']) ? $raw_options['custom_w'] : 0,
    isset($raw_options['custom_h']) ? $raw_options['custom_h'] : 0
);
$options = bcResolveOptions($raw_options, $size);
$layout  = bcResolveLayout($options, $size);

//load the products again from the database - names and barcodes are never
//taken from the browser, only the price, the batch and the quantity are
$requested_ids = array();
foreach ($job['items'] as $item) {
    $requested_ids[] = $item['id'];
}//foreach

$products = bcGetPrintableProducts($dbObj, $requested_ids, $shop_id, $company_id, $multi_category);

//------------------------------------------------------------- build stickers
$stickers   = array();    //flat list of labels to print
$skipped    = array();    //items that have no printable barcode value
$svg_cache  = array();    //render each barcode only once
$max_labels = bcMaxLabelsPerJob();
$was_capped = false;

$print_date = date($options['date_format']);
$header_text = ($options['shop_text'] !== '') ? $options['shop_text'] : $shop_name;

foreach ($job['items'] as $item) {

    if (!isset($products[$item['id']])) {
        continue;
    }//not allowed for this shop

    $product = $products[$item['id']];
    $barcode = trim($product['Barcode']);

    if ($barcode === '') {
        $skipped[] = $product['ItemName'];
        continue;
    }//no barcode on the product

    $svg = '';

    if ($options['show_bars']) {
        $cache_key = $barcode . '|' . $options['symbology'] . '|' . $options['bar_color'];

        if (!isset($svg_cache[$cache_key])) {
            $svg_cache[$cache_key] = bcRenderBarcodeSvg($barcode, $options['symbology'], $options['bar_color']);
        }//render once

        $svg = $svg_cache[$cache_key];

        if ($svg === '') {
            $skipped[] = $product['ItemName'];
            continue;
        }//not encodable
    }//bars wanted

    //------------------------------------------------------ the text lines
    $name = (string) $product['ItemName'];
    if ($options['name_upper']) {
        $name = strtoupper($name);
    }//capitals
    if ($options['name_len'] > 0 && strlen($name) > $options['name_len']) {
        $name = substr($name, 0, $options['name_len']);
    }//shortened

    $category_line = trim($product['CategoryName'] . ' / ' . $product['SubCatName'], ' /');

    $sticker = array(
        'name'     => $name,
        'second'   => (string) $product['SecondName'],
        'category' => $category_line,
        'sku'      => (string) $product['ProductNo'],
        'barcode'  => $barcode,
        'batch'    => (string) $item['batch'],
        'price'    => $item['price'],
        'svg'      => $svg,
    );

    //copies multiplies whatever quantity was typed for the row
    $wanted = $item['qty'] * $options['copies'];

    for ($i = 0; $i < $wanted; $i++) {

        if (count($stickers) >= $max_labels) {
            $was_capped = true;
            break 2;
        }//job ceiling reached

        $stickers[] = $sticker;
    }//copies
}//foreach item

/*
 * Skipped stickers at the start of the first row let a partly used sheet be
 * finished off instead of thrown away.
 */
$cells = array();
for ($blank = 0; $blank < $layout['skip']; $blank++) {
    $cells[] = null;
}//leading blanks
foreach ($stickers as $sticker) {
    $cells[] = $sticker;
}//real stickers

/*
 * Split the flat list into rows. The last row is padded with blank cells so the
 * used stickers keep their place across the roll instead of the row collapsing.
 */
$rows = empty($cells) ? array() : array_chunk($cells, $layout['across']);

$product_count = count($job['items']);
$page_w = $layout['page_w'];
$page_h = $layout['page_h'];

//how the media has to be set up on the printer, spelled out for the operator
$media_label = bcMm($page_w) . ' x ' . bcMm($page_h) . ' mm';

/**
 * Escape for HTML.
 */
function bcpE($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}//bcpE
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Barcode | Cloud POS</title>
    <link rel="shortcut icon" type="image/png" href="../Assets/Images/favicon.png" />
    <link rel="stylesheet" href="../Assets/css/styles.min.css" />
    <script src="../JQuery_361.js"></script>

    <style>
        /* ---------- exact media geometry ---------- */
        /* the page is a full ROW of labels, so it matches the physical roll */
        @page {
            size: <?php echo $page_w; ?>mm <?php echo $page_h; ?>mm;
            margin: 0;
        }

        .bc-row {
            width: <?php echo $page_w; ?>mm;
            height: <?php echo $page_h; ?>mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            justify-content: flex-start;
            /* the row gap is left at the bottom, so the stickers sit at the top
               of the page exactly where the die cut starts */
        }

        .bc-label {
            width: <?php echo $size['width']; ?>mm;
            height: <?php echo $size['height']; ?>mm;
            /* never let flex resize a sticker - the width is physical */
            flex: 0 0 <?php echo $size['width']; ?>mm;
            padding: <?php echo $options['padding']; ?>mm;
            box-sizing: border-box;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: <?php
                echo ($options['align'] === 'left') ? 'flex-start'
                   : (($options['align'] === 'right') ? 'flex-end' : 'center');
            ?>;
            justify-content: center;
            text-align: <?php echo $options['align']; ?>;
            font-family: <?php echo $options['font_css']; ?>;
            color: #000;
            background: #fff;
            line-height: 1.05;
        }

        /* margin-left rather than flex gap: print engines honour it reliably */
        .bc-label + .bc-label {
            margin-left: <?php echo $layout['col_gap']; ?>mm;
        }

        .bc-label > div {
            width: 100%;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: <?php echo $options['line_gap']; ?>mm;
        }

        .bc-label > div:last-child {
            margin-bottom: 0;
        }

        .bc-shop   { font-size: <?php echo $options['shop_font']; ?>mm; font-weight: 700; letter-spacing: .02mm; }
        .bc-name   { font-size: <?php echo $options['name_font']; ?>mm; <?php echo $options['bold_name'] ? 'font-weight:700;' : ''; ?> }
        .bc-second { font-size: <?php echo $options['name_font']; ?>mm; }
        .bc-small  { font-size: <?php echo $options['small_font']; ?>mm; }
        .bc-code   { font-size: <?php echo $options['code_font']; ?>mm; letter-spacing: <?php echo $options['letter_sp']; ?>mm; }
        .bc-price  { font-size: <?php echo $options['price_font']; ?>mm; <?php echo $options['bold_price'] ? 'font-weight:700;' : ''; ?> }

        .bc-bars {
            height: <?php echo $options['bar_height']; ?>mm;
            width: <?php echo (int) $options['bar_scale']; ?>% !important;
            line-height: 0;
        }

        .bc-bars svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        <?php if ($options['guides']) { ?>
        /* a hairline so the operator can see where the die cut should fall */
        .bc-label:not(.bc-blank) {
            outline: 0.1mm solid #000;
            outline-offset: -0.1mm;
        }
        <?php } ?>

        /* an unused cell in the last row - holds the others in position */
        .bc-blank {
            background: transparent;
        }

        /* ---------- on screen preview ---------- */
        .bc-toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: #fff;
            border-bottom: 1px solid #e5eaef;
            padding: 14px 20px;
        }

        .bc-media-call {
            border: 1px solid #b6d4fe;
            background: #eaf3ff;
            border-radius: 6px;
            padding: 10px 14px;
        }

        .bc-media-size {
            font-family: Consolas, "Courier New", monospace;
            font-size: 18px;
            font-weight: 700;
            color: #0b4a8f;
            white-space: nowrap;
        }

        .bc-preview {
            padding: 24px 20px 60px;
            background: #f4f6f9;
        }

        .bc-sheet {
            display: inline-block;
        }

        .bc-preview .bc-row {
            margin-bottom: 8px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .06);
        }

        .bc-preview .bc-label {
            border: 1px dashed #b6c2cf;
            border-radius: 2px;
        }

        .bc-preview .bc-blank {
            border-style: dotted;
            background: repeating-linear-gradient(45deg, #fff, #fff 4px, #f1f4f8 4px, #f1f4f8 8px);
        }

        .bc-empty {
            padding: 40px;
            text-align: center;
        }

        /* ---------- the preview on a phone ---------- */
        /* Only the on screen toolbar reflows. The sticker sheet itself is
           deliberately left alone: it is a physical measurement in millimetres,
           so it must NOT shrink to fit a phone - it is scrolled instead, and
           what is printed stays exactly the size the operator asked for. */
        @media (max-width: 767.98px) {
            .bc-toolbar {
                padding: 12px 14px;
            }

            .bc-toolbar .btn {
                flex: 1 1 auto;
            }

            .bc-media-size {
                font-size: 16px;
                white-space: normal;
            }

            .bc-preview {
                padding: 14px 12px 40px;
                overflow-x: auto;
            }
        }

        /* ---------- print ---------- */
        @media print {
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            .no-print,
            .bc-toolbar {
                display: none !important;
            }

            .bc-preview {
                padding: 0 !important;
                background: #fff !important;
            }

            .bc-sheet {
                display: block !important;
            }

            /* the page break belongs to the ROW - never to a single label, or a
               2 across row would be split over two pages */
            .bc-preview .bc-row {
                margin: 0 !important;
                background: #fff !important;
                box-shadow: none !important;
                page-break-after: always;
                break-after: page;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .bc-preview .bc-row:last-child {
                page-break-after: auto;
                break-after: auto;
            }

            .bc-preview .bc-label {
                border: 0 !important;
                border-radius: 0 !important;
            }

            .bc-preview .bc-blank {
                background: transparent !important;
            }

            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body style="background:#f4f6f9;">

    <div class="bc-toolbar">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="mb-1 fw-semibold">Print Barcode Labels</h5>
                <span class="text-muted fs-2">
                    <?php echo bcpE($size['name']); ?>
                    &nbsp;&bull;&nbsp; <?php echo (int) $layout['across']; ?> across
                    &nbsp;&bull;&nbsp; <?php echo (int) $product_count; ?> product<?php echo ($product_count == 1 ? '' : 's'); ?>
                    &nbsp;&bull;&nbsp; <?php echo count($stickers); ?> label<?php echo (count($stickers) == 1 ? '' : 's'); ?>
                    &nbsp;&bull;&nbsp; <?php echo count($rows); ?> page<?php echo (count($rows) == 1 ? '' : 's'); ?>
                </span>
            </div>
            <div class="d-flex gap-2 flex-grow-1 justify-content-end">
                <a href="product.php" class="btn btn-light border">Back to Products</a>
                <button type="button" class="btn btn-primary" id="btn_do_print" <?php echo empty($stickers) ? 'disabled' : ''; ?>>
                    <i class="ti ti-printer"></i> Print
                </button>
            </div>
        </div>

        <?php if (!empty($skipped)) { ?>
            <div class="alert alert-warning mb-0 mt-3 py-2 fs-2">
                Skipped (no printable barcode):
                <strong><?php echo bcpE(implode(', ', array_slice($skipped, 0, 8))); ?></strong><?php echo (count($skipped) > 8 ? ' &hellip;' : ''); ?>
            </div>
        <?php } ?>

        <?php if ($was_capped) { ?>
            <div class="alert alert-warning mb-0 mt-3 py-2 fs-2">
                This job was limited to <strong><?php echo (int) $max_labels; ?> labels</strong>.
                Print the rest in a second batch.
            </div>
        <?php } ?>

        <!-- The single most important line on this page: the media size the
             printer has to be set to. Leaving it at one label while the roll is
             2 across is what skips the whole right hand column. -->
        <div class="bc-media-call mt-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div>
                    <div class="fw-semibold">Set the printer paper / media size to</div>
                    <div class="bc-media-size"><?php echo bcpE($media_label); ?></div>
                </div>
                <div class="text-muted fs-2 flex-grow-1">
                    <?php if ($layout['across'] > 1) { ?>
                        That is one full row of <?php echo (int) $layout['across']; ?> labels
                        (<?php echo bcMm($size['width']); ?>mm each<?php echo ($layout['col_gap'] > 0 ? ' + ' . bcMm($layout['col_gap']) . 'mm gap' : ''); ?>),
                        so every sticker across the roll gets used.<br>
                    <?php } ?>
                    In the print dialog set <strong>Margins = None</strong> and <strong>Scale = 100%</strong>
                    (never "Fit to page"), and turn <strong>Headers and footers off</strong>.
                </div>
            </div>
        </div>
    </div>

    <div class="bc-preview">
        <?php if (empty($stickers)) { ?>
            <div class="bc-empty">
                <h6 class="fw-semibold">Nothing to print</h6>
                <p class="text-muted mb-3">None of the selected products has a barcode that can be encoded.</p>
                <a href="product.php" class="btn btn-primary">Back to Products</a>
            </div>
        <?php } else { ?>
            <div class="bc-sheet">
                <?php foreach ($rows as $row) { ?>
                    <div class="bc-row">
                        <?php
                        foreach ($row as $sticker) {

                            if ($sticker === null) {
                                //a sticker that was already used off this sheet
                                echo '<div class="bc-label bc-blank"></div>';
                                continue;
                            }//skipped cell
                            ?>
                            <div class="bc-label">
                                <?php if ($options['show_shop'] && $header_text !== '') { ?>
                                    <div class="bc-shop"><?php echo bcpE($header_text); ?></div>
                                <?php } ?>

                                <?php if ($options['show_name']) { ?>
                                    <div class="bc-name"><?php echo bcpE($sticker['name']); ?></div>
                                <?php } ?>

                                <?php if ($options['show_second'] && $sticker['second'] !== '' && $sticker['second'] !== 'NULL') { ?>
                                    <div class="bc-second"><?php echo bcpE($sticker['second']); ?></div>
                                <?php } ?>

                                <?php if ($options['show_cat'] && $sticker['category'] !== '') { ?>
                                    <div class="bc-small"><?php echo bcpE($sticker['category']); ?></div>
                                <?php } ?>

                                <?php if ($options['show_sku'] && $sticker['sku'] !== '') { ?>
                                    <div class="bc-small"><?php echo bcpE($sticker['sku']); ?></div>
                                <?php } ?>

                                <?php if ($options['show_bars']) { ?>
                                    <div class="bc-bars"><?php echo $sticker['svg']; ?></div>
                                <?php } ?>

                                <?php if ($options['show_code']) { ?>
                                    <div class="bc-code"><?php echo bcpE($sticker['barcode']); ?></div>
                                <?php } ?>

                                <?php if ($options['show_price']) { ?>
                                    <div class="bc-price">
                                        <?php
                                        echo bcpE($options['price_label']);
                                        echo ($options['price_label'] !== '') ? ' ' : '';
                                        echo number_format($sticker['price'], (int) $options['price_dec']);
                                        ?>
                                    </div>
                                <?php } ?>

                                <?php if ($options['show_batch'] && $sticker['batch'] !== '') { ?>
                                    <div class="bc-small"><?php echo bcpE($sticker['batch']); ?></div>
                                <?php } ?>

                                <?php if ($options['show_date']) { ?>
                                    <div class="bc-small"><?php echo bcpE($print_date); ?></div>
                                <?php } ?>

                                <?php if ($options['show_footer'] && $options['footer_text'] !== '') { ?>
                                    <div class="bc-small"><?php echo bcpE($options['footer_text']); ?></div>
                                <?php } ?>
                            </div>
                            <?php
                        }//foreach sticker
                        ?>

                        <?php
                        //pad the last row so the used cells keep their position
                        for ($blank = count($row); $blank < $layout['across']; $blank++) {
                            echo '<div class="bc-label bc-blank"></div>';
                        }//for blank
                        ?>
                    </div>
                <?php }//foreach row ?>
            </div>
        <?php } ?>
    </div>

    <script>
        $(function () {
            $("#btn_do_print").on("click", function () {
                window.print();
            });
        });

        <?php if (!empty($stickers) && $options['auto_print']) { ?>
        //wait for the full layout before opening the print dialog, otherwise a
        //large job can be measured while the stickers are still being placed
        var bcPrinted = false;
        $(window).on("load", function () {
            if (bcPrinted) {
                return;
            }
            bcPrinted = true;

            window.setTimeout(function () {
                window.print();
            }, 300);
        });
        <?php } ?>
    </script>

</body>
</html>
