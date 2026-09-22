<?php 
include "../Includes/includes.php";

require '../vendor/autoload.php';

$shop_id = $_SESSION['shop_id'];
$label = "";
if(isset($_GET['label']))
{
    $label = $_GET['label'];
}
else
{
    header("Location: ../Public/product.php");
}

use Dompdf\Dompdf; 
use Dompdf\Options; 
use Picqer\Barcode\BarcodeGeneratorPNG; 

$options = new Options();
$options->set('isRemoteEnabled', TRUE);
$dompdf = new Dompdf(array('enable_remote' => TRUE));

$dbObj = new DBTransactions();

$lbl_data = explode("_", $label);
//product data
$product_id = $lbl_data[0];
$product_price = $lbl_data[1];

$sql_1 = "SELECT * FROM `pricehistory`
INNER JOIN products ON products.PDID = pricehistory.ProductID
WHERE ProductID = ".$product_id.";";

$prodData = $dbObj->getData($sql_1);
$barcode = $prodData[0]['Barcode'];
$item_name = $prodData[0]['ItemName'];
$purchase_price = $prodData[0]['PurchasePrice'] + 0;

//price converter
$arr_cost = str_split($purchase_price);
$cost = "";

foreach($arr_cost as $char)
{
    //$cost .="f";

    switch ($char) 
    {
        case '1':
            $cost .="G";
        break;
        case '2':
            $cost .="O";
        break;
        case '3':
            $cost .="L";
        break;
        case '4':
            $cost .="D";
        break;
        case '5':
            $cost .="M";
        break;
        case '6':
            $cost .="I";
        break;
        case '7':
            $cost .="N";
        break;
        case '8':
            $cost .="E";
        break;
        case '9':
            $cost .="R";
        break;
        case '0':
            $cost .="S";
        break;
        
        default:
            $cost .=".";
        break;
    }//switch case
}//foreach

//label Data
$sql = "SELECT * FROM label WHERE shop_id=".$shop_id." AND lblStat = 1;";
$lblData = $dbObj->getData($sql);

$dpi = floatval($lblData[0]['dpi']);

$num_row = floatval($lblData[0]['numRow']);
$num_col = floatval($lblData[0]['numCol']);

$lbl_width_mm = floatval($lblData[0]['lblWidth']);
$lbl_height_mm = floatval($lblData[0]['lblHeight']);

$stk_width_mm = floatval($lblData[0]['stkWidth']);
$stk_height_mm = floatval($lblData[0]['stkHeight']);
$stk_margin_left_mm = floatval($lblData[0]['stkMarginLeft']);
$stk_margin_right_mm = floatval($lblData[0]['stkMarginRight']);
$stk_margin_top_mm = floatval($lblData[0]['stkMarginTop']);
$stk_margin_bottom_mm = floatval($lblData[0]['stkMarginBottom']);

/*
* in dompdf 1 inch is 72 points
*/

$lbl_width = $lbl_width_mm * 0.0393701 * 72;
$lbl_height = $lbl_height_mm * 0.0393701 * 72;

$stk_width = toPixel($stk_width_mm, $dpi);
$stk_height = toPixel($stk_height_mm, $dpi);
$stk_margin_left = toPixel($stk_margin_left_mm, $dpi);
$stk_margin_right = toPixel($stk_margin_right_mm, $dpi);
$stk_margin_top = toPixel($stk_margin_top_mm, $dpi);
$stk_margin_bottom = toPixel($stk_margin_bottom_mm, $dpi);

$html = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Barcode</title>
    <style>
        /* html {margin: 0px;} */

        @page { margin: 0px; }
        body { margin: 0px; }

        *{
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
        }

        /*div {
            border: 1px solid black;
        }*/

        .div_cell{
            display: inline-block;
            width: ".$stk_width."px;
            height: ".$stk_height."px;

            margin-left: ".$stk_margin_left."px;
            margin-right: ".$stk_margin_right."px;
            margin-top: ".$stk_margin_top."px;
            margin-bottom: ".$stk_margin_bottom."px;

        }

    </style>
    </head>
    <body>";

    $generator = new Picqer\Barcode\BarcodeGeneratorPNG();

    $code_name = "data:image/png;base64," . base64_encode($generator->getBarcode($barcode, $generator::TYPE_CODE_128));

    $html .= "<div id='div_row'>";

    for ($i=0; $i < $num_col; $i++)
    { 
        $html .= "<div class='div_cell'>";

        // $html .= "<p style='margin: 5px; text-align:center; font-size: 15px;'>Synnex IT Solution</p>"; 

        $html .= "<p style='margin: 2px; text-align:center; font-size: 15px;'>".$cost." | Rs: ".$product_price."</p>"; 
        $html .= "<img src='".$code_name."' style='width:80%; height:40px; margin-left:10%;'>";

        $html .= "<p style='margin: 5px; text-align:center; font-size: 12px;'>".$barcode."</p>"; 

        $html .= "<p style='margin: 5px; text-align:center; font-size: 20px;'>Fancy Mahal</p>"; 

        $html .= "</div>";
    }//for loop

    $html .= "</div>";
    
    $html .= "</body></html>";

//resolution
$options = $dompdf->getOptions();
$options->set('dpi', $dpi);
$dompdf->setOptions($options);



$dompdf->loadHtml($html);
$dompdf->setPaper(array(0, 0, $lbl_height, $lbl_width), 'landscape'); 
$dompdf->render(); 
$dompdf->stream("synnex", array("Attachment" => 0));


//==================== Functions ==================//
function toPixel($length, $dpi)
{
    return ($dpi/25.4) * $length; 
}
?>