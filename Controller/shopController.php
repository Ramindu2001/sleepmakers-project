<?php
include "../Includes/includes.php";
require_once "../Includes/remember_me.php";
$shopObj = new Shop();


if (isset($_POST['btn_continue']))
{
    $shop_id = $_POST['cmb_shops'];
    if((isset($_SESSION["remember_me"]) || isset($_COOKIE["remember_meS"])) && isset($_SESSION['user_id']))
    {
        //signed token, only for a shop this user may open - see Includes/remember_me.php
        (new RememberMe())->rememberShop($_SESSION['user_id'], $shop_id);
    }

    //initialize session
    $_SESSION["loading"]=1;
    $_SESSION['shop_id'] = $shop_id;

    //goto main page
    header("Location: ../Public/home.php");
}//goto shop

if (isset($_POST['btn_save_shop']))
{
    $Company_CMID = $_POST['cmb_company'];
    $ShopName = $_POST['ShopName'];
    $StockTypes_STID = $_POST['cmb_stock_type'];

    $address_line_one = $_POST['AddressLineOne'];
    $address_line_two = $_POST['AddressLineTwo'];
    $city = $_POST['City'];
    $email_address = $_POST['EmailAddress'];
    $phone_number = $_POST['PhoneNumber'];
    
    $ShopStat = isset($_POST['ShopStatus']) ? 1 : 0;

    $is_wholesale = 0;
    $is_retail = 0;
    // $shop_sale_type = $_POST['rdb_sale_type'] == 'W' ? $is_wholesale = 1 : $is_retail = 1;

    $is_wholesale = isset($_POST['chk_wholesale']) ? 1 : 0;
    $is_retail = isset($_POST['chk_retail']) ? 1 : 0;

    $is_inventory = isset($_POST['chk_inventory']) ? 1 : 0;
    $is_minus = isset($_POST['chk_minus']) ? 1 : 0;
    $is_expire = isset($_POST['chk_expire']) ? 1 : 0;
    $is_fixed = isset($_POST['chk_fixed_price']) ? 1 : 0;
    $is_variation = isset($_POST['chk_variations']) ? 1 : 0;

    $is_second_language = isset($_POST['chk_second_language']) ? 1 : 0;
    $is_label_price = isset($_POST['chk_label_price']) ? 1 : 0;
    $is_carton = isset($_POST['chk_carton']) ? 1 : 0;
    $is_warranty = isset($_POST['chk_warranty']) ? 1 : 0;
    $is_category = isset($_POST['chk_category']) ? 1 : 0;

    $is_supplier = isset($_POST['chk_supplier']) ? 1 : 0;
    $is_service = isset($_POST['chk_service']) ? 1 : 0;
    $is_salesman = isset($_POST['chk_salesman']) ? 1 : 0;
    $is_expenses = isset($_POST['chk_expenses']) ? 1 : 0;
    $is_customer = isset($_POST['chk_customers']) ? 1 : 0;

    $is_quotation = isset($_POST['chk_quotation']) ? 1 : 0;
    $is_promotion = isset($_POST['chk_promotion']) ? 1 : 0;
    $is_racks = isset($_POST['chk_racks']) ? 1 : 0;
    $is_credit = isset($_POST['chk_credit']) ? 1 : 0;
    $is_prescription = isset($_POST['chk_prescription']) ? 1 : 0;
    $is_counter = isset($_POST['chk_counter']) ? 1 : 0;
    $is_excessAmount = isset($_POST['chk_excess']) ? 1 : 0;
    $is_BatchNo = isset($_POST['chk_batch']) ? 1 : 0;
    $invoice_print = isset($_POST['chk_invoice_print']) ? 1 : 0;
    $chk_is_under_cost = isset($_POST['chk_is_under_cost']) ? 1 : 0;
    //bills print on A4 for this shop; off = the 80mm receipt every shop printed before
    $is_a4invoice = isset($_POST['chk_a4_invoice']) ? 1 : 0;

    $ShopLogosize = floatval($_FILES['shop_logo']['size']);
    $ReceiptLogosize = floatval($_FILES['shop_receipt']['size']);

    //file upload directry
    $target_dir = "../Assets/Images/shop_images/";

    /*
     * --- Validate Inputs ---
     * company_id and shop name and stocktype
     * shop image and receipt image, size
     * 
     */

    if ($Company_CMID > 0) {
        if ($StockTypes_STID > 0) {
            if (!empty($ShopName)) {
                $shop_id=$shopObj->getShopCount();
                $shop_id=$shop_id[0]["ShopCount"]+1;
                $commonObj = new Common();
                $ShopNo = $commonObj->createCount("SH", $shop_id);
                
                $shopObj->setShop($ShopNo, $ShopName,  $is_wholesale, $is_retail, $is_inventory, $is_minus, $is_category, $is_expire, $is_variation, $is_supplier, $is_service, $is_salesman, $is_expenses, $is_customer, $is_fixed, $is_carton, $is_warranty, $is_promotion, $is_second_language, $is_label_price, $is_quotation, $is_racks, $is_credit, $is_prescription, $ShopStat, $Company_CMID, $StockTypes_STID, $address_line_one, $address_line_two, $city, $email_address, $phone_number,$is_counter,$is_excessAmount,
                $is_BatchNo, $invoice_print,$chk_is_under_cost);
                CreateCustomer();
                $_SESSION['company_update'] = 4;
                $shop_id=$shopObj->getShopCount();
                $shop_id=$shop_id[0]["ShopCount"];
                //the print format belongs to the printing feature, not to the shop columns above
                $receiptFormatObj = new ReceiptFormat();
                $format_saved = $receiptFormatObj->setShopFormat($shop_id, $is_a4invoice == 1 ? ReceiptFormat::FORMAT_A4 : ReceiptFormat::FORMAT_RECEIPT_80MM);
                if($is_a4invoice == 1 && !$format_saved)
                {
                    //A4 was asked for but the setting is not in the database yet: say so, do not lose it quietly
                    $_SESSION['shop_update'] = 4;
                }//A4 could not be stored
                if(isset($_FILES['shop_receipt']) && $_FILES['shop_receipt']['error'] == UPLOAD_ERR_OK)
                {
                    $hide_shop_id = $shop_id;
                    $shopOne = $shopObj->getOneShop($hide_shop_id);
                    $current_receipt_logo = $shopOne[0]['ReceiptLogo'];
                    $receipt_path = "../Assets/Images/shop_images/" . $current_receipt_logo;
                    $receipt_file_type=pathinfo($_FILES['shop_receipt']['name'], PATHINFO_EXTENSION);
                    $commonObj = new Common();
                    $current_receipt_logo = $commonObj->createCount("SR", $shop_id). "." . $receipt_file_type;
                    
                    $allow_types = array('jpg', 'png', 'PNG', 'jpeg');
                    $newrecieptfilename=$current_receipt_logo;
                    $recieptUploadDir="../Assets/Images/shop_images/".$newrecieptfilename;
                    if (in_array($receipt_file_type, $allow_types))
                    {
                        clearstatcache();

                        if(file_exists($receipt_path) && $receipt_path!="../Assets/Images/shop_images/no_image.jpg"  && $receipt_path!="../Assets/Images/shop_images/synnex_logo.png")
                        {
                            unlink($receipt_path);

                        }//has receipt file
                        else
                        {

                        }
                        if(move_uploaded_file($_FILES["shop_receipt"]["tmp_name"], $recieptUploadDir))
                        {
                            $shopObj->updateshopreceiptlogo($newrecieptfilename,$hide_shop_id);
                        }
                        
                    }//file reciept type correct

                }
                if(isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] == UPLOAD_ERR_OK )
                {
                    $hide_shop_id = $shop_id;
                    $shopOne = $shopObj->getOneShop($hide_shop_id);
                    $current_shop_logo = $shopOne[0]['ShopLogo'];

                    $logo_path = "../Assets/Images/shop_images/" . $current_shop_logo;
                    
                    $logo_file_type=pathinfo($_FILES['shop_logo']['name'], PATHINFO_EXTENSION);
                    //naming
                    $commonObj = new Common();
                    $shopNo = $commonObj->createCount("SH", $shop_id);
                    $current_shop_logo = $commonObj->createCount("SL", number: $shop_id) . "." . $logo_file_type;

                    

                    
                    $allow_types = array('jpg', 'png', 'PNG', 'jpeg');
                    $newlogofilename=$current_shop_logo;
                    $newrecieptfilename=$current_receipt_logo;
                    $logoUploadDir="../Assets/Images/shop_images/".$newlogofilename;
                    $recieptUploadDir="../Assets/Images/shop_images/".$newrecieptfilename;
                    if (in_array($logo_file_type, $allow_types))
                    {
                        clearstatcache();
                        if(file_exists($logo_path) && $logo_path!="../Assets/Images/shop_images/no_image.jpg" && $logo_path!="../Assets/Images/shop_images/synnex_logo.png")
                        {
                            unlink($logo_path);

                        }//has logo file
                        else
                        {

                        }                        
                        if(move_uploaded_file($_FILES["shop_logo"]["tmp_name"], $logoUploadDir))
                        {
                            $shopObj->updateshoplogo($newlogofilename,$hide_shop_id);
                        }
                    }//file logo type correct
                    

                    // Allow certain file formats 
                    
                }//has shop logo or receipt logo
                else
                {
                    $hide_shop_id = $shop_id;
                    $Shop_logo_name="synnex_logo.png";
                    $shopObj->updateshoplogo($Shop_logo_name,$hide_shop_id);
                    $shopObj->updateshopreceiptlogo($Shop_logo_name,$hide_shop_id);

                }//no images upload

            }//has shop name
            else {
                $_SESSION['shop_update'] = 2;
                die("Error: No shop Name selected.");
            }//no shop name
        }//has stock type
        else {
            $_SESSION['shop_update'] = 1;
            die("Error: No stock type selected.");
        }//no stock type
    }//has company  
    else {
        $_SESSION['shop_update'] = 0;
        //  
        die("Error: No company selected.");
    }//no company id   F
    header("Location: ../Public/Shoplist.php");
}//save new Shop

if (isset($_POST['btn_update_shop']))
{
    $hide_shop_id = $_POST['hide_Shop_id'];
    $Company_CMID = $_POST['cmb_company'];
    $ShopName = $_POST['ShopName'];
    $StockTypes_STID = $_POST['cmb_stock_type'];

    $address_line_one = $_POST['AddressLineOne'];
    $address_line_two = $_POST['AddressLineTwo'];
    $city = $_POST['City'];
    $email_address = $_POST['EmailAddress'];
    $phone_number = $_POST['PhoneNumber'];
    
    $ShopStat = isset($_POST['ShopStatus']) ? 1 : 0;

    $is_wholesale = isset($_POST['chk_wholesale']) ? 1 : 0;
    $is_retail = isset($_POST['chk_retail']) ? 1 : 0;

    $is_inventory = isset($_POST['chk_inventory']) ? 1 : 0;
    $is_minus = isset($_POST['chk_minus']) ? 1 : 0;
    $is_expire = isset($_POST['chk_expire']) ? 1 : 0;
    $is_fixed = isset($_POST['chk_fixed_price']) ? 1 : 0;
    $is_variation = isset($_POST['chk_variations']) ? 1 : 0;

    $is_second_language = isset($_POST['chk_second_language']) ? 1 : 0;
    $is_label_price = isset($_POST['chk_label_price']) ? 1 : 0;
    $is_carton = isset($_POST['chk_carton']) ? 1 : 0;
    $is_warranty = isset($_POST['chk_warranty']) ? 1 : 0;
    $is_category = isset($_POST['chk_category']) ? 1 : 0;

    $is_supplier = isset($_POST['chk_supplier']) ? 1 : 0;
    $is_service = isset($_POST['chk_service']) ? 1 : 0;
    $is_salesman = isset($_POST['chk_salesman']) ? 1 : 0;
    $is_expenses = isset($_POST['chk_expenses']) ? 1 : 0;
    $is_customer = isset($_POST['chk_customers']) ? 1 : 0;

    $is_quotation = isset($_POST['chk_quotation']) ? 1 : 0;
    $is_promotion = isset($_POST['chk_promotion']) ? 1 : 0;
    $is_racks = isset($_POST['chk_racks']) ? 1 : 0;
    $is_credit = isset($_POST['chk_credit']) ? 1 : 0;
    $is_minus = isset($_POST['chk_minus']) ? 1 : 0;
    $is_prescription = isset($_POST['chk_prescription']) ? 1 : 0;
    $is_counter = isset($_POST['chk_counter']) ? 1 : 0;
    $is_excessAmount = isset($_POST['chk_excess']) ? 1 : 0;
    $is_BatchNo = isset($_POST['chk_batch']) ? 1 : 0;    
    $invoice_print = isset($_POST['chk_invoice_print']) ? 1 : 0;
    $chk_is_under_cost = isset($_POST['chk_is_under_cost']) ? 1 : 0;
    //bills print on A4 for this shop; off = the 80mm receipt every shop printed before
    $is_a4invoice = isset($_POST['chk_a4_invoice']) ? 1 : 0;

    $ShopLogosize = floatval($_FILES['shop_logo']['size']);
    $ReceiptLogosize = floatval($_FILES['shop_receipt']['size']);

    // echo "logo size - " . $ShopLogosize . "<br>";

    //file upload directry
    $target_dir = "../Assets/Images/shop_images/";

    $shopObj = new Shop();

    if ($Company_CMID > 0) {
        if ($StockTypes_STID > 0) {
            if (!empty($ShopName)) {
                if(isset($_FILES['shop_receipt']) && $_FILES['shop_receipt']['error'] == UPLOAD_ERR_OK)
                {
                    $shop_id = $hide_shop_id;
                    $shopOne = $shopObj->getOneShop($hide_shop_id);
                    $current_receipt_logo = $shopOne[0]['ReceiptLogo'];
                    $receipt_path = "../Assets/Images/shop_images/" . $current_receipt_logo;
                    $receipt_file_type=pathinfo($_FILES['shop_receipt']['name'], PATHINFO_EXTENSION);
                    $commonObj = new Common();
                    $current_receipt_logo = $commonObj->createCount("SR", $shop_id). "." . $receipt_file_type;
                    
                    $allow_types = array('jpg', 'png', 'PNG', 'jpeg');
                    $newrecieptfilename=$current_receipt_logo;
                    $recieptUploadDir="../Assets/Images/shop_images/".$newrecieptfilename;
                    if (in_array($receipt_file_type, $allow_types))
                    {
                        clearstatcache();

                        if(file_exists($receipt_path) && $receipt_path!="../Assets/Images/shop_images/no_image.jpg"  && $receipt_path!="../Assets/Images/shop_images/synnex_logo.png")
                        {
                            unlink($receipt_path);

                        }//has receipt file
                        else
                        {

                        }
                        if(move_uploaded_file($_FILES["shop_receipt"]["tmp_name"], $recieptUploadDir))
                        {
                            $shopObj->updateshopreceiptlogo($newrecieptfilename,$hide_shop_id);
                        }
                        
                    }//file reciept type correct

                }
                if(isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] == UPLOAD_ERR_OK )
                {
                    $shop_id = $hide_shop_id;
                    $shopOne = $shopObj->getOneShop($hide_shop_id);
                    $current_shop_logo = $shopOne[0]['ShopLogo'];

                    $logo_path = "../Assets/Images/shop_images/" . $current_shop_logo;
                    
                    $logo_file_type=pathinfo($_FILES['shop_logo']['name'], PATHINFO_EXTENSION);
                    //naming
                    $commonObj = new Common();
                    $shopNo = $commonObj->createCount("SH", $shop_id);
                    $current_shop_logo = $commonObj->createCount("SL", number: $shop_id) . "." . $logo_file_type;

                    

                    
                    $allow_types = array('jpg', 'png', 'PNG', 'jpeg');
                    $newlogofilename=$current_shop_logo;
                    $newrecieptfilename=$current_receipt_logo;
                    $logoUploadDir="../Assets/Images/shop_images/".$newlogofilename;
                    $recieptUploadDir="../Assets/Images/shop_images/".$newrecieptfilename;
                    if (in_array($logo_file_type, $allow_types))
                    {
                        clearstatcache();
                        if(file_exists($logo_path) && $logo_path!="../Assets/Images/shop_images/no_image.jpg" && $logo_path!="../Assets/Images/shop_images/synnex_logo.png")
                        {
                            unlink($logo_path);

                        }//has logo file
                        else
                        {

                        }                        
                        if(move_uploaded_file($_FILES["shop_logo"]["tmp_name"], $logoUploadDir))
                        {
                            $shopObj->updateshoplogo($newlogofilename,$hide_shop_id);
                        }
                    }//file logo type correct
                    

                    // Allow certain file formats 
                    
                }//has shop logo or receipt logo
                else
                {

                }//no images upload
                $shopObj->updateShop($ShopName,$is_wholesale, $is_retail, $is_inventory, $is_minus, $is_category, $is_expire, $is_variation,$is_supplier, $is_service, $is_salesman, $is_expenses, $is_customer, $is_fixed, $is_carton, $is_warranty,$is_promotion, $is_second_language, $is_label_price, $is_quotation, $is_racks, $is_credit, $is_prescription,$ShopStat, $Company_CMID, $StockTypes_STID, $address_line_one, $address_line_two, $city, $email_address, $phone_number,$hide_shop_id,$is_counter,$is_excessAmount,
                $is_BatchNo,$invoice_print,$chk_is_under_cost);
                //the print format belongs to the printing feature, not to the shop columns above
                $receiptFormatObj = new ReceiptFormat();
                $format_saved = $receiptFormatObj->setShopFormat($hide_shop_id, $is_a4invoice == 1 ? ReceiptFormat::FORMAT_A4 : ReceiptFormat::FORMAT_RECEIPT_80MM);
                if($is_a4invoice == 1 && !$format_saved)
                {
                    //A4 was asked for but the setting is not in the database yet: say so, do not lose it quietly
                    $_SESSION['shop_update'] = 4;
                }//A4 could not be stored
                header("Location: ../Public/Shoplist.php");
                $_SESSION['company_update'] = 5; //update successfully
               
            }//has shop name
            else {
                $_SESSION['shop_update'] = 2;
                 header("Location: ../Public/Shoplist.php");
                die("Error: No shop Name selected.");
            }//no shop name
        }//has stock type
        else {
            $_SESSION['shop_update'] = 1;
             header("Location: ../Public/Shoplist.php");
            die("Error: No stock type selected.");
        }//no stock type
    }//has company  
    else {
        $_SESSION['shop_update'] = 0;
         header("Location: ../Public/Shoplist.php");
        die("Error: No company selected.");
    }//no company id 
}//update Shop

//============================= Functions ===============================//
function CreateCustomer()
{
    $custObj = new Customer();

    //get the Customer no   
    $CustomerNo = "";                      
    $cusObj = new Customer();                            
    $customer = $cusObj->getCustomerCount();
    if(!empty($customer))
    {
        $Cus_count = floatval($customer[0]['CusCount']);
        $Cus_count += 1;
    }
    else
    {
        $Cus_count = 1;
    }//not max count

    $commonObj = new Common();                                
    $CustomerNo = $commonObj->createCount("CU", $Cus_count);  

    $CustName = "Common Customer";
    $CustAddress = "Address";
    $CustContact = "0712345678";
    $MaxCreditAmount = 75000;
    $PaymentTerm = 60;
    $CustStat = 1;
    $shop_SHID = $_SESSION['shop_id'];

    $custObj->setCustomer($CustomerNo,$CustName,$CustAddress,$CustContact,$MaxCreditAmount, $PaymentTerm, $CustStat,$shop_SHID);
}//create customer