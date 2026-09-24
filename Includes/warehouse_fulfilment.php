<?php
//Everything POS to warehouse fulfilment needs
//(docs/superpowers/specs/2026-09-24-pos-warehouse-fulfilment-design.md).
require_once __DIR__ . '/../Model/shop_access_class.php';
require_once __DIR__ . '/../Model/stock_allocator_class.php';
require_once __DIR__ . '/../Model/common_class.php';
require_once __DIR__ . '/../Model/transfer_class.php';
require_once __DIR__ . '/../Model/barcode_settings_class.php';
require_once __DIR__ . '/../Model/unit_barcode_refused_class.php';
require_once __DIR__ . '/../Model/product_unit_class.php';
require_once __DIR__ . '/../Model/customer_order_refused_class.php';
require_once __DIR__ . '/../Model/warehouse_order_class.php';
require_once __DIR__ . '/../Model/order_dispatch_class.php';
require_once __DIR__ . '/scan_upload.php';
require_once __DIR__ . '/../Model/scan_dispatch_class.php';
