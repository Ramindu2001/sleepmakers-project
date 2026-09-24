<?php
//What the POS checkout needs to turn a cart into a warehouse order
//(docs/superpowers/specs/2026-09-24-pos-warehouse-fulfilment-design.md).
//
//The cart posts one extra set of fields beside the usual item_id[] arrays:
//  wh_line[i]              '1' when this line cannot be given from the shop
//  wh_custom[i]            '1' when it is a custom-made item nobody stocks
//  wh_supplier_product[i]  the warehouse's product id (empty for a custom item)
//  wh_notes[i]             the customer's requirements for that line
//  wh_supplier_shop, wh_customer_id, wh_cust_name, wh_cust_phone, wh_cust_address,
//  wh_deliver_to, wh_address, wh_phone, wh_needed_by, wh_note
//
//Checkout uses it in three steps, so a sale can never be taken without the order that tells
//the warehouse to send the goods:
//  1. warehouseOrderWanted()  - is there anything for the warehouse in this cart?
//  2. warehouseOrderCheck()   - would the order be accepted? Refuse the SALE if not.
//  3. warehouseOrderPlace()   - after the invoice exists, write the order.
require_once __DIR__ . '/warehouse_fulfilment.php';

//does this cart have anything the shop cannot hand over?
function warehouseOrderWanted()
{
    if(empty($_POST['wh_line']) || !is_array($_POST['wh_line']))
    {
        return false;
    }
    foreach($_POST['wh_line'] as $flag)
    {
        if(!empty($flag))
        {
            return true;
        }
    }
    return false;
}//warehouse order wanted

//The cart as an order. With $create true the shop's own copy of each warehouse product is made
//(and a custom item's service product), and item_id[] is rewritten to it - the invoice line has
//to carry a product of the shop that billed it. With $create false nothing is written, so the
//same data can be checked before the sale commits to anything.
function warehouseOrderBuild($shop_id, $user_id, $create)
{
    $orders = new WarehouseOrder();
    $lines = array();
    $count = count($_POST['item_id']);
    for($i = 0; $i < $count; $i++)
    {
        $warehouse = !empty($_POST['wh_line'][$i]);
        $custom = !empty($_POST['wh_custom'][$i]);
        $supplier_product = ($warehouse && !$custom && !empty($_POST['wh_supplier_product'][$i]))
            ? (int)$_POST['wh_supplier_product'][$i] : null;
        $product_id = (int)$_POST['item_id'][$i];

        if($warehouse && $create)
        {
            $product_id = $custom
                ? $orders->customItemProduct($shop_id, $user_id)
                : $orders->shopCopyOf($supplier_product, $shop_id, $user_id);
            $_POST['item_id'][$i] = $product_id;
        }//the invoice bills the shop's own product, not the warehouse's

        $lines[] = array(
            'source' => $warehouse ? 'WAREHOUSE' : 'GIVEN',
            //a custom item has no product of its own: it is described in words
            'product_id' => ($warehouse && $custom) ? null : ($create || !$warehouse ? $product_id : null),
            'supplier_product_id' => $supplier_product,
            'description' => isset($_POST['Item_name'][$i]) ? $_POST['Item_name'][$i] : '',
            'qty' => isset($_POST['qty'][$i]) ? $_POST['qty'][$i] : 0,
            'notes' => isset($_POST['wh_notes'][$i]) ? $_POST['wh_notes'][$i] : '',
            'unit_price' => isset($_POST['rate'][$i]) ? $_POST['rate'][$i] : 0,
            //what the customer was actually charged for the line, after any discount on it -
            //warehouse staff reconcile the job sheet against the invoice, so it has to agree
            'line_total' => isset($_POST['totals'][$i]) ? $_POST['totals'][$i] : null,
        );
    }//each cart line

    return array(
        'invoice_id' => null,
        'supplier_shop_id' => isset($_POST['wh_supplier_shop']) ? (int)$_POST['wh_supplier_shop'] : 0,
        'customer_id' => isset($_POST['wh_customer_id']) ? $_POST['wh_customer_id'] : null,
        'cust_name' => isset($_POST['wh_cust_name']) ? $_POST['wh_cust_name'] : '',
        'cust_phone' => isset($_POST['wh_cust_phone']) ? $_POST['wh_cust_phone'] : '',
        'cust_address' => isset($_POST['wh_cust_address']) ? $_POST['wh_cust_address'] : '',
        'deliver_to' => isset($_POST['wh_deliver_to']) ? (int)$_POST['wh_deliver_to'] : WarehouseOrder::DELIVER_CUSTOMER,
        'delivery_address' => isset($_POST['wh_address']) ? $_POST['wh_address'] : '',
        'delivery_phone' => isset($_POST['wh_phone']) ? $_POST['wh_phone'] : '',
        'delivery_note' => isset($_POST['wh_note']) ? $_POST['wh_note'] : '',
        'needed_by' => isset($_POST['wh_needed_by']) ? $_POST['wh_needed_by'] : null,
        'notes' => '',
        'lines' => $lines,
    );
}//warehouse order build

//Would the order be accepted? Called before the invoice is written, so a sale that cannot
//leave a proper order for the warehouse is refused outright rather than taking the money and
//losing the bed. Returns '' when all is well, or the message to show the cashier.
function warehouseOrderCheck($shop_id, $user_id)
{
    try
    {
        (new WarehouseOrder())->checkSale($shop_id, warehouseOrderBuild($shop_id, $user_id, false));
        return '';
    }
    catch(CustomerOrderRefused $e)
    {
        return $e->getMessage();
    }
}//warehouse order check

//Write the order for an invoice that now exists. The data was already checked, so a failure
//here is a database problem and the cashier is told which invoice it was.
function warehouseOrderPlace($shop_id, $user_id, $invoice_id, array &$alert)
{
    try
    {
        $sale = warehouseOrderBuild($shop_id, $user_id, true);
        $sale['invoice_id'] = (int)$invoice_id;
        $order = (new WarehouseOrder())->createFromSale($shop_id, $user_id, $sale);
        $alert['Success'][] = 'Warehouse order ' . $order['order_no'] . ' created.';
        return $order;
    }
    catch(Throwable $e)
    {
        //there is no transaction around the legacy checkout, so the sale stands. Leave a
        //trace in the log as well as on the screen: a toast is easy to miss.
        error_log('warehouse order NOT created for invoice ' . $invoice_id . ': ' . $e->getMessage());
        $alert['Error'][] = 'The invoice was saved but the warehouse order was not: ' . $e->getMessage()
            . ' Please tell the warehouse about this invoice.';
        return null;
    }
}//warehouse order place

//The shop's own copies have to exist before the invoice lines are written, so this runs inside
//the checkout just before the invoice header, rewriting item_id[] for the warehouse lines.
function warehouseOrderPrepareCart($shop_id, $user_id)
{
    warehouseOrderBuild($shop_id, $user_id, true);
}//warehouse order prepare cart
