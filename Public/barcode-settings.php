<?php
/**
 * Settings > Barcode Settings.
 * -----------------------------------------------------------------------------
 * Everything that decides what an automatically generated barcode looks like:
 * the token pattern, the prefixes, the sequence numbering and the symbology -
 * with a live preview that shows the next three codes as they are typed.
 *
 * The preview NEVER consumes a sequence number. See Includes/barcode_generator.php.
 */

include '../Includes/includes.php';
include '../Includes/authcheck.php';
include '../Includes/barcode_helper.php';
include '../Includes/barcode_generator.php';

$shop_id = (int) $_SESSION['shop_id'];

$dbObj = new DBTransactions();
$bcObj = new BarcodeSettings();
$catObj = new Category();

//get company stat
$shopData = $dbObj->getData(
    "SELECT * FROM shop
     INNER JOIN company ON company.CMID = shop.Company_CMID
     WHERE SHID = " . $shop_id . ";"
);

$multi_category = empty($shopData) ? 0 : (int) $shopData[0]['is_multicategory'];
$company_id     = empty($shopData) ? 0 : (int) $shopData[0]['CMID'];
$shop_name      = empty($shopData) ? '' : (string) $shopData[0]['ShopName'];

//the saved rules, with every default filled in
$settings   = bcgNormalizeSettings($bcObj->getSettings($shop_id));
$scopes     = bcgSeqScopes();
$symbologies = bcgSymbologies();
$sequences  = $bcObj->getSequences($shop_id);

//the shop's saved label print defaults, for the summary card
$saved_row = $bcObj->getSettings($shop_id);
$label_defaults = bcShopLabelDefaults(isset($saved_row['LabelDefaults']) ? $saved_row['LabelDefaults'] : '');

//categories, for the preview picker and the code table
$categories = $catObj->getCategoryByShop($shop_id, $multi_category, $company_id);
$subcategories = $catObj->getSubcategoryByShop($shop_id, $multi_category, $company_id);

//how many still have no code of their own
$missing_codes = 0;
foreach ($categories as $row) {
    if (trim((string) $row['CategoryCode']) === '') {
        $missing_codes++;
    }//no code
}//foreach
foreach ($subcategories as $row) {
    if (trim((string) $row['SubCatCode']) === '') {
        $missing_codes++;
    }//no code
}//foreach

/**
 * Escape for HTML. Used on every value that reaches the page.
 */
function bcsE($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}//bcsE
?>
<!doctype html>
<html lang="en">

<head>
    <?php include '../View/head.php'; ?>
    <style>
        .bcs-token {
            font-family: Consolas, "Courier New", monospace;
            cursor: pointer;
        }

        .bcs-preview-card {
            border: 1px solid #b6d4fe;
            background: #eaf3ff;
            border-radius: 8px;
            padding: 16px;
        }

        .bcs-preview-code {
            font-family: Consolas, "Courier New", monospace;
            font-size: 26px;
            font-weight: 700;
            color: #0b4a8f;
            word-break: break-all;
            line-height: 1.2;
        }

        .bcs-preview-next {
            font-family: Consolas, "Courier New", monospace;
            font-size: 13px;
            color: #46617f;
        }

        .bcs-bars {
            background: #fff;
            border-radius: 4px;
            padding: 8px 10px;
            height: 70px;
        }

        .bcs-bars svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        .bcs-pattern-input {
            font-family: Consolas, "Courier New", monospace;
            font-size: 15px;
            letter-spacing: .3px;
        }

        .bcs-code-cell {
            font-family: Consolas, "Courier New", monospace;
        }

        /* vertical scroll for a long list, horizontal so the inputs in the
           numbering table never get crushed on a narrow screen */
        .bcs-scroll {
            max-height: 320px;
            overflow: auto;
        }

        .bcs-scroll table {
            min-width: 420px;
        }

        .bcs-scroll table th {
            position: sticky;
            top: 0;
            background: #f8f9fb;
            z-index: 2;
        }

        /* ---------------------------------------------------- small screens */
        @media (max-width: 767.98px) {
            /* the ready made formats become a 2 up grid instead of a ragged row */
            .bcs-preset {
                flex: 1 1 calc(50% - .5rem);
                min-width: 0;
                text-align: left;
            }

            .bcs-preview-code {
                font-size: 20px;
            }

            .bcs-bars {
                height: 60px;
            }

            /* the token buttons are tap targets, not decoration */
            .bcs-token {
                padding: .35rem .5rem;
            }
        }

        @media (max-width: 575.98px) {
            .bcs-preview-card {
                padding: 12px;
            }

            .bcs-preview-code {
                font-size: 17px;
            }
        }
    </style>
</head>

<body>
<div class="h-100vh">
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
     data-sidebar-position="fixed" data-header-position="fixed">

    <?php
    include '../View/sidebar.php';
    $feature_id = 16;   //Products - the barcode rules belong to the product module
    include '../Includes/viewPermission.php';
    ?>

    <div class="body-wrapper">
        <?php include '../View/header.php'; ?>

        <div class="container-fluid">

            <!-- ------------------------------------------------- messages -->
            <div class="container">
                <?php
                if (isset($_SESSION['barcode_settings_update'])) {
                    $status = (int) $_SESSION['barcode_settings_update'];
                    $messages = array(
                        0 => array('danger',  'You do not have permission to change the barcode settings.'),
                        1 => array('success', 'Barcode settings saved.'),
                        2 => array('success', 'Numbering updated.'),
                        3 => array('warning', 'Nothing was changed.'),
                        4 => array('success', 'Counter removed. The next product starts again from the beginning.'),
                        5 => array('success', 'Label print defaults cleared.'),
                        6 => array('success', 'Missing category codes filled in'
                            . (isset($_SESSION['barcode_codes_filled'])
                                ? ' (' . (int) $_SESSION['barcode_codes_filled'] . ' updated).' : '.')),
                        7 => array('danger',  'The settings could not be saved. Run db/barcode_module_install.php and try again.'),
                    );

                    if (isset($messages[$status])) {
                        ?>
                        <div class="alert alert-<?php echo $messages[$status][0]; ?> alert-dismissible border-0 fade show" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <?php echo bcsE($messages[$status][1]); ?>
                        </div>
                        <?php
                    }//known status

                    unset($_SESSION['barcode_settings_update']);
                    unset($_SESSION['barcode_codes_filled']);
                }//session set
                ?>
            </div>

            <form action="../Controller/barcodeSettingsController.php" method="POST" id="bcs_form">

            <div class="row">
                <!-- ================================================ rules -->
                <div class="col-12 col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title fw-semibold mb-0" style="margin-top:0;">
                                Automatic Barcode &mdash; <?php echo bcsE($shop_name); ?>
                            </h5>
                            <span class="text-muted fs-2">
                                Used whenever a product is saved with the barcode field left empty.
                            </span>
                        </div>

                        <div class="card-body">

                            <!-- ------------------------------------ on/off -->
                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" name="AutoGenerate" id="AutoGenerate"
                                       value="1" <?php echo ((int) $settings['AutoGenerate'] === 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="AutoGenerate">
                                    Generate a barcode automatically when none is typed
                                </label>
                                <div class="text-muted fs-2">
                                    Switch this off and a product saved without a barcode keeps its
                                    product number (PD_000123) as before.
                                </div>
                            </div>

                            <!-- ---------------------------------- presets -->
                            <label class="form-label fw-semibold mb-2">1. Start from a ready made format</label>
                            <div class="d-flex flex-wrap gap-2 mb-4" id="bcs_preset_group">
                                <button type="button" class="btn btn-sm btn-outline-primary bcs-preset"
                                        data-pattern="{PREFIX}{SUB}{SEQ}" data-sep="-">
                                    Prefix + Sub category<br><small class="text-muted">SM-BEV-00042</small>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary bcs-preset"
                                        data-pattern="{CAT}{SUB}{SEQ}" data-sep="-">
                                    Category + Sub category<br><small class="text-muted">FOO-BEV-00042</small>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary bcs-preset"
                                        data-pattern="{CAT}{SEQ}" data-sep="">
                                    Category only<br><small class="text-muted">FOO00042</small>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary bcs-preset"
                                        data-pattern="{PREFIX}{YY}{MM}{SEQ}" data-sep="-">
                                    Prefix + year/month<br><small class="text-muted">SM-26-09-00042</small>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary bcs-preset"
                                        data-pattern="{SHOP}{SUB}{SEQ}" data-sep="-">
                                    Shop + Sub category<br><small class="text-muted">DEM-BEV-00042</small>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary bcs-preset"
                                        data-pattern="{SEQ}" data-sep="">
                                    Plain running number<br><small class="text-muted">00042</small>
                                </button>
                            </div>

                            <!-- ---------------------------------- pattern -->
                            <label class="form-label fw-semibold mb-2" for="Pattern">2. Barcode format</label>
                            <input type="text" name="Pattern" id="Pattern" maxlength="160"
                                   class="form-control bcs-pattern-input mb-2"
                                   value="<?php echo bcsE($settings['Pattern']); ?>">

                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <span class="text-muted fs-2 me-1 align-self-center">Insert:</span>
                                <?php
                                $tokens = array(
                                    '{PREFIX}' => 'your fixed prefix',
                                    '{CAT}'    => 'main category code',
                                    '{SUB}'    => 'sub category code',
                                    '{SHOP}'   => 'shop code',
                                    '{SEQ}'    => 'the running number',
                                    '{YYYY}'   => '4 digit year',
                                    '{YY}'     => '2 digit year',
                                    '{MM}'     => 'month',
                                    '{DD}'     => 'day',
                                    '{ITEM:3}' => 'first 3 letters of the item name',
                                    '{RAND:4}' => '4 random digits',
                                );
                                foreach ($tokens as $token => $hint) {
                                    ?>
                                    <button type="button" class="btn btn-sm btn-light border bcs-token"
                                            data-token="<?php echo bcsE($token); ?>"
                                            title="<?php echo bcsE($hint); ?>"><?php echo bcsE($token); ?></button>
                                    <?php
                                }//foreach token
                                ?>
                            </div>
                            <p class="text-muted fs-2 mb-4">
                                Anything that is not a token is used exactly as typed.
                                A <strong>{SEQ}</strong> is always added if you leave it out &mdash; without a
                                running number every product would get the same barcode.
                            </p>

                            <!-- --------------------------- prefix / suffix -->
                            <label class="form-label fw-semibold mb-2">3. Fixed parts</label>
                            <div class="row g-3 mb-4">
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-2 text-muted mb-1" for="FixedPrefix">Prefix &#123;PREFIX&#125;</label>
                                    <input type="text" name="FixedPrefix" id="FixedPrefix" maxlength="24"
                                           class="form-control" value="<?php echo bcsE($settings['FixedPrefix']); ?>"
                                           placeholder="e.g. SM">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-2 text-muted mb-1" for="Suffix">Suffix</label>
                                    <input type="text" name="Suffix" id="Suffix" maxlength="24"
                                           class="form-control" value="<?php echo bcsE($settings['Suffix']); ?>"
                                           placeholder="optional">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-2 text-muted mb-1" for="ShopCode">Shop code &#123;SHOP&#125;</label>
                                    <input type="text" name="ShopCode" id="ShopCode" maxlength="12"
                                           class="form-control" value="<?php echo bcsE($settings['ShopCode']); ?>"
                                           placeholder="from shop name">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-2 text-muted mb-1" for="Separator">Separator</label>
                                    <input type="text" name="Separator" id="Separator" maxlength="4"
                                           class="form-control" value="<?php echo bcsE($settings['Separator']); ?>"
                                           placeholder="none">
                                </div>
                            </div>
                            <p class="text-muted fs-2 mb-4">
                                The separator is only put <em>between</em> parts that actually have a value, so a
                                product with no category never comes out as <code>SM--00042</code>.
                            </p>

                            <!-- ------------------------------ category codes -->
                            <label class="form-label fw-semibold mb-2">4. Category codes</label>
                            <div class="row g-3 mb-2">
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-2 text-muted mb-1" for="CatCodeLength">&#123;CAT&#125; length</label>
                                    <input type="number" min="1" max="12" name="CatCodeLength" id="CatCodeLength"
                                           class="form-control" value="<?php echo (int) $settings['CatCodeLength']; ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-2 text-muted mb-1" for="SubCodeLength">&#123;SUB&#125; length</label>
                                    <input type="number" min="1" max="12" name="SubCodeLength" id="SubCodeLength"
                                           class="form-control" value="<?php echo (int) $settings['SubCodeLength']; ?>">
                                </div>
                                <div class="col-12 col-md-6 d-flex align-items-end">
                                    <button type="submit" name="btn_fill_category_codes" value="1"
                                            class="btn btn-light border w-100">
                                        Fill in the <?php echo (int) $missing_codes; ?> missing category code<?php echo ($missing_codes == 1 ? '' : 's'); ?>
                                    </button>
                                </div>
                            </div>
                            <p class="text-muted fs-2 mb-4">
                                A category with no code of its own falls back to the first letters of its name
                                (&quot;Beverage&quot; &rarr; BEV). Writing the codes down makes them editable on the
                                category pages and stops a later rename quietly changing every new barcode.
                            </p>

                            <!-- ---------------------------------- sequence -->
                            <label class="form-label fw-semibold mb-2">5. The running number &#123;SEQ&#125;</label>
                            <div class="row g-3 mb-2">
                                <div class="col-md-12">
                                    <label class="form-label fs-2 text-muted mb-1" for="SeqScope">Restart numbering</label>
                                    <select name="SeqScope" id="SeqScope" class="form-select">
                                        <?php
                                        foreach ($scopes as $key => $label) {
                                            $sel = ($settings['SeqScope'] === $key) ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo bcsE($key); ?>" <?php echo $sel; ?>>
                                                <?php echo bcsE($label); ?>
                                            </option>
                                            <?php
                                        }//foreach scope
                                        ?>
                                    </select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-2 text-muted mb-1" for="SeqStart">Start at</label>
                                    <input type="number" min="0" max="999999999" name="SeqStart" id="SeqStart"
                                           class="form-control" value="<?php echo (int) $settings['SeqStart']; ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-2 text-muted mb-1" for="SeqStep">Step by</label>
                                    <input type="number" min="1" max="100000" name="SeqStep" id="SeqStep"
                                           class="form-control" value="<?php echo (int) $settings['SeqStep']; ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-2 text-muted mb-1" for="SeqLength">Digits</label>
                                    <input type="number" min="1" max="18" name="SeqLength" id="SeqLength"
                                           class="form-control" value="<?php echo (int) $settings['SeqLength']; ?>">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label fs-2 text-muted mb-1" for="SeqPadChar">Pad with</label>
                                    <input type="text" maxlength="1" name="SeqPadChar" id="SeqPadChar"
                                           class="form-control" value="<?php echo bcsE($settings['SeqPadChar']); ?>">
                                </div>
                            </div>
                            <p class="text-muted fs-2 mb-4">
                                &quot;Start at&quot; only applies to a counter that has never been used.
                                To change one that is already running, use <strong>Current numbering</strong> below.
                            </p>

                            <!-- --------------------------------- symbology -->
                            <label class="form-label fw-semibold mb-2">6. Barcode type</label>
                            <div class="row g-3 mb-2">
                                <div class="col-md-12">
                                    <select name="Symbology" id="Symbology" class="form-select">
                                        <?php
                                        foreach ($symbologies as $key => $spec) {
                                            $sel = ($settings['Symbology'] === $key) ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo bcsE($key); ?>" <?php echo $sel; ?>>
                                                <?php echo bcsE($spec['name']); ?>
                                            </option>
                                            <?php
                                        }//foreach symbology
                                        ?>
                                    </select>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label fs-2 text-muted mb-1" for="Casing">Letters</label>
                                    <select name="Casing" id="Casing" class="form-select">
                                        <option value="upper" <?php echo ($settings['Casing'] === 'upper') ? 'selected' : ''; ?>>UPPERCASE</option>
                                        <option value="lower" <?php echo ($settings['Casing'] === 'lower') ? 'selected' : ''; ?>>lowercase</option>
                                        <option value="asis"  <?php echo ($settings['Casing'] === 'asis')  ? 'selected' : ''; ?>>As typed</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label fs-2 text-muted mb-1" for="MaxLength">Maximum length</label>
                                    <input type="number" min="4" max="45" name="MaxLength" id="MaxLength"
                                           class="form-control" value="<?php echo (int) $settings['MaxLength']; ?>">
                                </div>
                                <div class="col-12 col-md-4 d-flex align-items-end">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="StripInvalid" id="StripInvalid"
                                               value="1" <?php echo ((int) $settings['StripInvalid'] === 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fs-2" for="StripInvalid">
                                            Remove characters this barcode type cannot encode
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="product.php" class="btn btn-light border">Back to Products</a>
                                <?php if ($userType == 1 || (isset($edit) && $edit == 1)) { ?>
                                    <button type="submit" name="btn_save_barcode_settings" value="1" class="btn btn-primary">
                                        <i class="ti ti-device-floppy"></i> Save Settings
                                    </button>
                                <?php } ?>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ============================================== preview -->
                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title fw-semibold mb-0" style="margin-top:0;">Live Preview</h5>
                            <span class="text-muted fs-2">Nothing here uses up a number.</span>
                        </div>
                        <div class="card-body">

                            <label class="form-label fs-2 text-muted mb-1" for="preview_subcat">Preview against</label>
                            <select id="preview_subcat" name="preview_subcat" class="form-select mb-3">
                                <option value="0">=== No category ===</option>
                                <?php
                                foreach ($subcategories as $row) {
                                    ?>
                                    <option value="<?php echo (int) $row['SCID']; ?>">
                                        <?php echo bcsE($row['CategoryName'] . ' / ' . $row['SubCatName']); ?>
                                    </option>
                                    <?php
                                }//foreach subcategory
                                ?>
                            </select>

                            <div class="bcs-preview-card mb-3">
                                <div class="text-muted fs-2 mb-1">Next barcode</div>
                                <div class="bcs-preview-code" id="bcs_code">&hellip;</div>
                                <div class="bcs-preview-next mt-2" id="bcs_series"></div>
                                <div class="bcs-bars mt-3" id="bcs_bars"></div>
                            </div>

                            <div class="row g-2 fs-2 text-muted mb-3">
                                <div class="col-4">
                                    <div class="text-muted">&#123;CAT&#125;</div>
                                    <div class="bcs-code-cell text-dark" id="bcs_cat">&ndash;</div>
                                </div>
                                <div class="col-4">
                                    <div class="text-muted">&#123;SUB&#125;</div>
                                    <div class="bcs-code-cell text-dark" id="bcs_sub">&ndash;</div>
                                </div>
                                <div class="col-4">
                                    <div class="text-muted">&#123;SHOP&#125;</div>
                                    <div class="bcs-code-cell text-dark" id="bcs_shop">&ndash;</div>
                                </div>
                            </div>

                            <div id="bcs_warnings"></div>

                            <div class="text-muted fs-2 mt-3">
                                Counter in use:
                                <span class="bcs-code-cell text-dark" id="bcs_scope">&ndash;</span>
                            </div>
                        </div>
                    </div>

                    <!-- ------------------------------- label print defaults -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title fw-semibold mb-0" style="margin-top:0;">Label Print Defaults</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($label_defaults)) { ?>
                                <p class="text-muted fs-2 mb-0">
                                    No shop default saved yet. Open <strong>Products &rarr; Print Barcode</strong>,
                                    set the sticker size and the content you want, then press
                                    <strong>Save as shop default</strong> in that dialog.
                                </p>
                            <?php } else { ?>
                                <p class="text-muted fs-2">
                                    Every Print Barcode dialog in this shop opens on these settings.
                                </p>
                                <ul class="fs-2 mb-3">
                                    <li>Sticker:
                                        <strong><?php
                                            echo bcsE(isset($label_defaults['size']) ? $label_defaults['size'] : '-');
                                            if (isset($label_defaults['size']) && $label_defaults['size'] === 'custom') {
                                                echo ' (' . bcsE($label_defaults['custom_w']) . ' x ' . bcsE($label_defaults['custom_h']) . ' mm)';
                                            }//custom
                                        ?></strong>
                                    </li>
                                    <li>Across the roll:
                                        <strong><?php echo (int) (isset($label_defaults['across']) ? $label_defaults['across'] : 1); ?></strong>
                                    </li>
                                    <li>Barcode type:
                                        <strong><?php echo bcsE(isset($label_defaults['symbology']) ? $label_defaults['symbology'] : 'AUTO'); ?></strong>
                                    </li>
                                </ul>
                                <button type="submit" name="btn_reset_label_defaults" value="1"
                                        class="btn btn-sm btn-light border">
                                    Clear shop default
                                </button>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            </form>

            <!-- ==================================== running counters ===== -->
            <div class="row">
                <div class="col-12 col-lg-7">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title fw-semibold mb-0" style="margin-top:0;">Current Numbering</h5>
                            <span class="text-muted fs-2">
                                One counter per group. The number shown is what the <em>next</em> product will get.
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($sequences)) { ?>
                                <p class="text-muted mb-0">
                                    No barcode has been generated yet. A counter appears here the first time
                                    a product is saved without a barcode.
                                </p>
                            <?php } else { ?>
                                <div class="bcs-scroll">
                                    <table class="table table-sm align-middle">
                                        <thead>
                                            <tr>
                                                <th>Group</th>
                                                <th style="width:150px;">Next number</th>
                                                <th style="width:170px;">Change to</th>
                                                <th style="width:90px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sequences as $row) { ?>
                                                <tr>
                                                    <td class="bcs-code-cell fs-2"><?php echo bcsE($row['ScopeKey']); ?></td>
                                                    <td class="fw-semibold"><?php echo (int) $row['NextValue']; ?></td>
                                                    <td>
                                                        <form action="../Controller/barcodeSettingsController.php" method="POST"
                                                              class="d-flex gap-1">
                                                            <input type="hidden" name="scope_key"
                                                                   value="<?php echo bcsE($row['ScopeKey']); ?>">
                                                            <input type="number" min="0" name="next_value"
                                                                   class="form-control form-control-sm"
                                                                   value="<?php echo (int) $row['NextValue']; ?>">
                                                            <button type="submit" name="btn_reset_sequence" value="1"
                                                                    class="btn btn-sm btn-outline-primary">Set</button>
                                                        </form>
                                                    </td>
                                                    <td>
                                                        <form action="../Controller/barcodeSettingsController.php" method="POST"
                                                              onsubmit="return confirm('Remove this counter? The next product in this group starts again from the beginning.');">
                                                            <input type="hidden" name="scope_key"
                                                                   value="<?php echo bcsE($row['ScopeKey']); ?>">
                                                            <button type="submit" name="btn_delete_sequence" value="1"
                                                                    class="btn btn-sm btn-outline-danger">Reset</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php }//foreach ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <!-- ================================== category code table -->
                <div class="col-12 col-lg-5">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title fw-semibold mb-0" style="margin-top:0;">Category Codes</h5>
                            <span class="text-muted fs-2">
                                Edit these on the Main Categories and Sub Categories pages.
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="bcs-scroll">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th style="width:90px;">&#123;CAT&#125;</th>
                                            <th style="width:90px;">&#123;SUB&#125;</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($subcategories as $row) {
                                            $cat_code = trim((string) $row['CategoryCode']);
                                            $sub_code = trim((string) $row['SubCatCode']);
                                            ?>
                                            <tr>
                                                <td class="fs-2"><?php echo bcsE($row['CategoryName'] . ' / ' . $row['SubCatName']); ?></td>
                                                <td class="bcs-code-cell">
                                                    <?php if ($cat_code !== '') { ?>
                                                        <span class="badge bg-primary-subtle text-primary"><?php echo bcsE($cat_code); ?></span>
                                                    <?php } else { ?>
                                                        <span class="text-muted fs-2"><?php echo bcsE(bcgDeriveCode($row['CategoryName'], $settings['CatCodeLength'])); ?></span>
                                                    <?php } ?>
                                                </td>
                                                <td class="bcs-code-cell">
                                                    <?php if ($sub_code !== '') { ?>
                                                        <span class="badge bg-primary-subtle text-primary"><?php echo bcsE($sub_code); ?></span>
                                                    <?php } else { ?>
                                                        <span class="text-muted fs-2"><?php echo bcsE(bcgDeriveCode($row['SubCatName'], $settings['SubCodeLength'])); ?></span>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                            <?php
                                        }//foreach subcategory
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted fs-2 mb-0 mt-2">
                                A greyed out code is derived from the name and is not stored.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
</div>

<?php include '../View/footer.php'; ?>

<script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../Assets/js/sidebarmenu.js"></script>
<script src="../Assets/js/app.min.js"></script>
<script src="../Assets/libs/simplebar/dist/simplebar.js"></script>
<?php
/*
 * The file modification time is appended so a browser can never serve a stale
 * copy of the script after a deployment - the URL changes with the file.
 */
$bcs_js_path = __DIR__ . '/../Assets/jquery/barcode-settings.js';
$bcs_js_ver = file_exists($bcs_js_path) ? filemtime($bcs_js_path) : time();
?>
<script src="../Assets/jquery/barcode-settings.js?v=<?php echo $bcs_js_ver; ?>"></script>

</body>
</html>
