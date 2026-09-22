<?php 

if(isset($_POST['btn_profitnloss_date']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/profitnloss.php?date=".$start_date."_".$end_date);
}//profit and loss date 

if(isset($_POST['btn_category_date']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/category-sale.php?date=".$start_date."_".$end_date);
}//category sale date

if(isset($_POST['btn_sale_z_report']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/sale_z_report.php?date=".$start_date."_".$end_date);
}//sale z report

if(isset($_POST['btn_test_sale_z_report']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/rpt_test.php?date=".$start_date."_".$end_date);
}//sale z report

if(isset($_POST['btn_search_supplier']))
{
    $supplier_id = $_POST['cmb_suppliers'];

    header("Location: ../Reports/supplier-purchase.php?supplier_id=" . $supplier_id);
}//supplier purchase

if(isset($_POST['btn_sale_summary']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/sale_summary_report.php?date=".$start_date."_".$end_date);
}//sale summary 

if(isset($_POST['btn_monthly_sale']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/rpt_sales_trend.php?date=".$start_date."_".$end_date);
}//monthly sale

//======================= New Reports ====================//
if(isset($_POST['btn_stock_movement']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/rpt_stock_movement.php?date=".$start_date."_".$end_date);
}//stock movement 

if(isset($_POST['btn_stock_adjustment']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/rpt_stock_adjustment.php?date=".$start_date."_".$end_date);
}//stock movement 

//category inventory
if(isset($_POST['btn_category_search']))
{
    $category_id = $_POST['cmb_category'];
    $subcategory_id = $_POST['cmb_subcategory'];

    header("Location: ../Reports/rpt_inventory.php?cat=".$category_id."_".$subcategory_id);
}//inventory search by category

//btn_invoice_sales
if(isset($_POST['btn_invoice_sales']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/rpt_invoice_sales.php?date=".$start_date."_".$end_date);
}// invoice wise sales report

//btn_item_sales
if(isset($_POST['btn_item_sales']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/rpt_item_sales.php?date=".$start_date."_".$end_date);
}//stock movement 

//btn_category_sales
if(isset($_POST['btn_category_sales']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/rpt_category_sales.php?date=".$start_date."_".$end_date);
}//stock movement 

//rpt_paymethod_sale
if(isset($_POST['btn_paymethod_date']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/rpt_paymethod_sales.php?date=".$start_date."_".$end_date);
}//paymethod date

//btn_employee_sales
if(isset($_POST['btn_employee_sales']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/rpt_employee_sales.php?date=".$start_date."_".$end_date);
}//employee sales report

//btn_salesman_summary
if(isset($_POST['btn_salesman_summary']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/rpt_salesman_summary.php?date=".$start_date."_".$end_date);
}//btn_salesman_summary

//btn_salesman_detail
if(isset($_POST['btn_salesman_detail']))
{
    $salesman_id = $_POST['cmb_salesman'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/rpt_salesman_detail.php?date=".$start_date."_".$end_date."_".$salesman_id);
}//btn_salesman_detail

//btn_customer_summary
if(isset($_POST['btn_customer_summary']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/rpt_customer_summary.php?date=".$start_date."_".$end_date);
}//btn_customer_summary

//btn_customer_detail
if(isset($_POST['btn_customer_detail']))
{
    $customer_id = $_POST['cmb_customer'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/rpt_customer_detail.php?date=".$start_date."_".$end_date."_".$customer_id);
}//btn_salesman_detail

//btn_daily_sales
if(isset($_POST['btn_daily_sales']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/rpt_daily_sales.php?date=".$start_date."_".$end_date);
}//btn_salesman_detail


//btn_trialbalance
if(isset($_POST['btn_trialbalance_date']))
{
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header("Location: ../Reports/trialbalance.php?date=".$start_date."_".$end_date);
}