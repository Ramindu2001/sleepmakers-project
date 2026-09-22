<?php
include "../Includes/includes.php";
include "../Includes/barcode_generator.php";
$shop_id = $_SESSION['shop_id'];
$prodObj = new Product();
$invObj = new Inventory();
$dbObj = new DBTransactions();
$priceObj = new PriceHistory();
date_default_timezone_set("Asia/Colombo");

if(isset($_POST['btn_save_product']))
{
    //PDID, ProductNo, ProdImage, Barcode, ItemName, ProdDescription, SecondName, ProdPurchasePrice, ProdSellPrice, CartonQty, ProductStat, AddedDate, UpdatedDate, ItemType, user_USID, UpdateUserID, Subcategories_SCID, shop_SHID
    $productData = $prodObj->getProductCount($shop_id);
    $prod_count = intval($productData[0]['ProductCount']);
    $prod_count += 1;

    //image count
    $productImage = $prodObj->getProductImageCount($shop_id);
    $prod_image_count = intval($productImage[0]['ProducImagetCount']);
    $prod_image_count += 1;

    $commObj = new Common();
    $product_no = $commObj->createCount("PD", $prod_count);
    $prod_image_name = $commObj->createCount("PI", $prod_image_count);

    $subcat_id = $_POST['cmb_subcategory'];
    $prod_name = $_POST['prod_name'];

    /*
     * Barcode. A value typed by the operator always wins. An empty field goes
     * to the shop's barcode rules (Settings > Barcode Settings), which allocate
     * a sequence number and return a code that is unique across every product.
     *
     * The number is consumed HERE, at save time, and not when the dialog was
     * opened - so a dialog that was opened and abandoned wastes nothing.
     *
     * bcgBarcodeForProduct() falls back to the product number when automatic
     * barcodes are switched off, which is exactly the old behaviour.
     */
    if(!empty($_POST['barcode']))
    {
        $barcode = trim($_POST['barcode']);
    }
    else
    {
        $barcode = bcgBarcodeForProduct($shop_id, $subcat_id, $prod_name, $product_no);
    }

    $second_name = isset($_POST['second_name']) ? $_POST['second_name'] : "NULL";
    $prod_description = $_POST['prod_description'];

    //carton qty
    $prod_carton_qty = isset($_POST['prod_carton_qty']) ? $_POST['prod_carton_qty'] : 1;

    $prod_stat = 1;
    if(isset($_POST["chk_fp"]))
    {
        $chk_fp=1;
    }
    else
    {
        $chk_fp=0;
    }

    //units
    if(isset($_POST['cmb_purchase_unit']))
    {
       $purchase_unit = $_POST['cmb_purchase_unit']; 
    }
    else
    {
        $purchase_unit = 0; 
    }
    if(isset($_POST['conversion_rate']))
    {
       $conversion_rate = $_POST['conversion_rate']; 
    }
    else
    {
        $conversion_rate = 1; 
    }
    if(isset($_POST['cmb_selling_unit']))
    {
       $selling_unit = $_POST['cmb_selling_unit']; 
    }
    else
    {
        $selling_unit = 0; 
    }

    //purchase price
    $prod_purchase_price = isset($_POST['prod_purchase_price']) ? $_POST['prod_purchase_price'] : 0;

    //selling price
    $prod_selling_price = isset($_POST['prod_selling_price']) ? $_POST['prod_selling_price'] : 0;

    //Item Discount    
    $prod_Discount = isset($_POST['prod_Item_Dis']) ? $_POST['prod_Item_Dis'] : 0;
    $prod_flat_Discount = isset($_POST['prod_Item_Dis_flat']) ? $_POST['prod_Item_Dis_flat'] : 0;

    if ($prod_Discount > 0) {        
        $prod_Discount = floatval($prod_Discount); 
    }
    
    if ($prod_flat_Discount > 0) {        
        $prod_flat_Discount = floatval($prod_flat_Discount); 
    }


    $use_service = "P";
    $shopObj = new Shop();
    if($shopObj->hasService($shop_id))
    {
        $use_service = isset($_POST['chk_service']) ? "S" : "P";
    }//has service
    else
    {
        $use_service = "P";
    }//no service
    
    //get current date
    date_default_timezone_set("Asia/Colombo");
    $this_date = date("Y-m-d");

    //user id
    $user_id = $_SESSION['user_id'];


    //file upload directry
    $target_dir = "../Assets/Images/prod_images/";
    if(isset($_FILES['prod_image']['name']))
    {
        $target_file_path = $target_dir . $prod_image_name . basename($_FILES['prod_image']['name']);
        $file_type = pathinfo($target_file_path, PATHINFO_EXTENSION);
    }
    if(isset($_POST["chk_fp"]))
    {
        $chk_fp=1;
    }
    else
    {
        $chk_fp=0;
    }
    
    //check subcategory
    if($subcat_id > 0)
    {
        //check barcode and item name
        if(!empty($barcode))
        {
            //check prodname
            if(!empty($prod_name))
            {
                //check image
                $allow_types = array('jpg','JPG','png','PNG','jpeg','JPEG');
                if(isset($file_type))
                {
                    if(in_array($file_type, $allow_types))
                    {
                        $prod_image_name = $prod_image_name . "." . $file_type;
                        $target_file_path = $target_dir . $prod_image_name;

                        $size = floatval($_FILES['prod_image']['size']);
                        //check size 500KB
                        if($size < 500000)
                        {
                            if(move_uploaded_file($_FILES["prod_image"]["tmp_name"], $target_file_path))
                            {
                                $prodObj->setProduct(ProductNo: $product_no, ProdImage: $prod_image_name, Barcode: $barcode, ItemName: $prod_name, ProdDescription: $prod_description, SecondName: $second_name, ProdPurchasePrice: $prod_purchase_price, ProdSellPrice: $prod_selling_price, CartonQty: $prod_carton_qty, ProductStat: $prod_stat, AddedDate: $this_date, UpdatedDate: $this_date, ItemType: $use_service, user_USID: $user_id, UpdateUserID: $user_id, Subcategories_SCID: $subcat_id, shop_SHID: $shop_id, PurchaseUnit: $purchase_unit, UnitConversion: $conversion_rate, SellingUnit: $selling_unit, prod_Discount: $prod_Discount, Flat_discount: $prod_flat_Discount, chk_fp:$chk_fp);
                                $product_id=$prodObj->getProductCount($shop_id);
                                $product_id=$product_id[0]["ProductCount"];
                                $count=$invObj->getInventorywithproductID($product_id);
                                $count=$count[0]["procount"];
                                $count=$count+1;
                                $batch_id=$invObj->getSequence($count);
                                $batch_id="B".$batch_id;
                                $CurrentQty=0;
                                $BillQty=0;
                                $ReturnQty=0;
                                $TransfeInQty=0;
                                $TransferOutQty=0;
                                $RackID=1;
                                $default=1;
                                $invObj->setInventory2($CurrentQty, $BillQty, $ReturnQty, $TransfeInQty, $TransferOutQty, $product_id, $shop_id, $RackID, $batch_id,$default);
                                $sql = "SELECT max(INID) AS MAXSID FROM inventory WHERE shop_SHID='$shop_id';";
                                $dbMax = $dbObj->getData($sql);
                                $max_id = floatval($dbMax[0]['MAXSID']);
                                $new_inventory_id = $max_id ;
                                $grn_detail_id = 0;
                                $CurrentQty=0;
                                $BillQty=0;
                                $ReturnQty=0;
                                $TransfeInQty=0;
                                $TransferOutQty=0;
                                $RackID=1;
                                $default=1;
                                $mnf_date=null;
                                $exp_date=null;
                                $effective_date=date("Y-m_d");
                                $priceObj->setPriceHistory($product_id, 0, $effective_date, $prod_purchase_price, $prod_selling_price, $prod_selling_price, $mnf_date, $exp_date, $batch_id, $new_inventory_id, $grn_detail_id);
                                $_SESSION['product_update'] = 3; //save successfully
                                ECHO $_SESSION['product_update'];
                                header("Location: ../Public/product.php");
                            }//move file
                            else
                            {
                                $_SESSION['product_update'] = 4; //unable to save
                                ECHO $_SESSION['product_update'];
                                header("Location: ../Public/product.php");
                                die("Error: barcode or username empty.");
                            }//cannot save image
                        }//less than 500kb
                        else
                        {
                            $_SESSION['product_update'] = 5;//wrong image size
                            ECHO $_SESSION['product_update'];
                            header("Location: ../Public/product.php");
                            die("Error: barcode or username empty.");
                        }//greater than 500kb
                    }
                    else
                    {
                        $prod_image_name = null;
                        $prodObj->setProduct(ProductNo: $product_no, ProdImage: $prod_image_name, Barcode: $barcode, ItemName: $prod_name, ProdDescription: $prod_description, SecondName: $second_name, ProdPurchasePrice: $prod_purchase_price, ProdSellPrice: $prod_selling_price, CartonQty: $prod_carton_qty, ProductStat: $prod_stat, AddedDate: $this_date, UpdatedDate: $this_date, ItemType: $use_service, user_USID: $user_id, UpdateUserID: $user_id, Subcategories_SCID: $subcat_id, shop_SHID: $shop_id, PurchaseUnit: $purchase_unit, UnitConversion: $conversion_rate, SellingUnit: $selling_unit,prod_Discount: $prod_Discount, Flat_discount: $prod_flat_Discount, chk_fp:$chk_fp);
                        $product_id=$prodObj->getProductCount($shop_id);
                        $product_id=$product_id[0]["ProductCount"];
                        $count=$invObj->getInventorywithproductID($product_id);
                        $count=$count[0]["procount"];
                        $count=$count+1;
                        $batch_id=$invObj->getSequence($count);
                        $batch_id="B".$batch_id;
                        $CurrentQty=0;
                        $BillQty=0;
                        $ReturnQty=0;
                        $TransfeInQty=0;
                        $TransferOutQty=0;
                        $RackID=1;
                        $default=1;
                        $mnf_date=null;
                        $exp_date=null;
                        $effective_date=date("Y-m_d");
                        $invObj->setInventory2($CurrentQty, $BillQty, $ReturnQty, $TransfeInQty, $TransferOutQty, $product_id, $shop_id, $RackID, $batch_id,$default);
                        $sql = "SELECT max(INID) AS MAXSID FROM inventory WHERE shop_SHID='$shop_id';";
                        $dbMax = $dbObj->getData($sql);
                        $max_id = floatval($dbMax[0]['MAXSID']);
                        $new_inventory_id = $max_id ;
                        $grn_detail_id = 0;
                        $priceObj->setPriceHistory($product_id, 0, $effective_date, $prod_purchase_price, $prod_selling_price, $prod_selling_price, $mnf_date, $exp_date, $batch_id, $new_inventory_id, $grn_detail_id);
                        $_SESSION['product_update'] = 3; //save successfully
                                    ECHO $_SESSION['product_update'];
                        header("Location: ../Public/product.php");
                    }
                    
                }//is array
                else
                {
                    //save product without image
                    $prod_image_name = null;
                    $prodObj->setProduct(ProductNo: $product_no, ProdImage: $prod_image_name, Barcode: $barcode, ItemName: $prod_name, ProdDescription: $prod_description, SecondName: $second_name, ProdPurchasePrice: $prod_purchase_price, ProdSellPrice: $prod_selling_price, CartonQty: $prod_carton_qty, ProductStat: $prod_stat, AddedDate: $this_date, UpdatedDate: $this_date, ItemType: $use_service, user_USID: $user_id, UpdateUserID: $user_id, Subcategories_SCID: $subcat_id, shop_SHID: $shop_id, PurchaseUnit: $purchase_unit, UnitConversion: $conversion_rate, SellingUnit: $selling_unit,prod_Discount: $prod_Discount, Flat_discount: $prod_flat_Discount, chk_fp:$chk_fp);
                    $product_id=$prodObj->getProductCount($shop_id);
                    $product_id=$product_id[0]["ProductCount"];
                    $count=$invObj->getInventorywithproductID($product_id);
                    $count=$count[0]["procount"];
                    $count=$count+1;
                    $batch_id=$invObj->getSequence($count);
                    $batch_id="B".$batch_id;
                    $CurrentQty=0;
                    $BillQty=0;
                    $ReturnQty=0;
                    $TransfeInQty=0;
                    $TransferOutQty=0;
                    $RackID=1;
                    $default=1;
                    $mnf_date="";
                    $exp_date="";
                    $effective_date=date("Y-m_d");
                    $invObj->setInventory2($CurrentQty, $BillQty, $ReturnQty, $TransfeInQty, $TransferOutQty, $product_id, $shop_id, $RackID, $batch_id,$default);
                    $sql = "SELECT max(INID) AS MAXSID FROM inventory WHERE shop_SHID='$shop_id';";
                    $dbMax = $dbObj->getData($sql);
                    $max_id = floatval($dbMax[0]['MAXSID']);
                    $new_inventory_id = $max_id ;
                    $grn_detail_id = 0;
                    $priceObj->setPriceHistory($product_id, 0, $effective_date, $prod_purchase_price, $prod_selling_price, $prod_selling_price, $mnf_date, $exp_date, $batch_id, $new_inventory_id, $grn_detail_id);
                    $_SESSION['product_update'] = 3; //save successfully
                                ECHO $_SESSION['product_update'];
                    header("Location: ../Public/product.php");
                }//not in array
            }//has name
            else
            {
                $_SESSION['product_update'] = 1;
                                ECHO $_SESSION['product_update'];
                header("Location: ../Public/product.php");
                die("Error: barcode or username empty.");
            }//no prod name
        }//has barcode and name
        else
        {
            $_SESSION['product_update'] = 1;
                                ECHO $_SESSION['product_update'];
            header("Location: ../Public/product.php");
            die("Error: barcode or username empty.");
        }//no barcode and name
    }//has category
    else
    {
        $_SESSION['product_update'] = 0;
                                ECHO $_SESSION['product_update'];
        header("Location: ../Public/product.php");
        die("Error: No category selected.");
    }//no category
}//save product

if (isset($_POST["bulk_upload"])) {
    $filename = $_FILES["file"]["tmp_name"];
    $erroProduct=array();
    $successProduct=array();
    if ($_FILES["file"]["size"] > 0) {
        // Define target directory
        $target_dir = "../Assets/uploads/";
        $date = date("Y-m-d h-i-s a");
        $target_file = $target_dir . basename($date . " - " . $_FILES["file"]["name"]);
        
        // Move uploaded file to target directory
        if (move_uploaded_file($filename, $target_file)) {
            // Open the file for reading
            $file = fopen($target_file, "r");

            // Database connection (assuming PDO)
                fgetcsv($file, 10000, ",");

                // Read and process the CSV file
                while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
                    $commObj = new Common();
                    $productData = $prodObj->getProductCount($shop_id);
                    $prod_count = intval($productData[0]['ProductCount']);
                    $prod_count += 1;
                    $ProductNo = $commObj->createCount("PD", $prod_count);
                    $ProdImage = null;
                    $date_create=date("Y-m-d");
                    /* the sub category is only resolved further down, and the
                       generated barcode needs it for the {CAT} / {SUB} prefix -
                       so the decision is deferred until just before the insert */
                    $auto_barcode = ($column[0]=="" || $column[0]==null);
                    $Barcode = $auto_barcode ? "" : $column[0];
                    $ItemName = $column[1];
                    $ProdDescription = $column[2];
                    $SecondName = $column[3];
                    $ProdPurchasePrice = $column[4];
                    $ProdSellPrice = $column[5];
                    $CartonQty = 1;
                    $ProductStat = 1;
                    $AddedDate = $date_create;
                    $UpdatedDate = $date_create;
                    $ItemType = "P";
                    $user_USID = $_SESSION["user_id"];
                    $UpdateUserID = $_SESSION["user_id"];
                    $subcat=$prodObj->getSubCat($column[6]);
                    // echo $column[6];
                    $shop_SHID = $_SESSION["shop_id"];
                    $PurchaseUnit = 0;
                    $UnitConversion = 1;
                    $SellingUnit = 0;

                    /* Generate only for a row that is actually going to be
                       inserted - a row with an unknown sub category is rejected
                       below, and burning a sequence number on it would leave a
                       hole in the numbering for no reason. */
                    if($auto_barcode)
                    {
                        $auto_subcat = (count($subcat) > 0) ? (int) $subcat[0]["SCID"] : 0;

                        $Barcode = ($auto_subcat > 0)
                            ? bcgBarcodeForProduct($shop_SHID, $auto_subcat, $ItemName, $ProductNo)
                            : $ProductNo;
                    }//no barcode in the file

                    $checkbarcode=$prodObj->checkbarcode($Barcode);
                    $checkproduct=$prodObj->checkproduct($Barcode);
                    $data["message"]="";
                    $data["product"] = "";
                    if(!$checkbarcode || !$checkproduct || count($subcat)==0)
                    {
                        if(!$checkbarcode)
                        {
                            $data["message"] .= "Barcode Already Available, ";
                            $data["product"] = $ItemName;
                        }
                        if(!$checkproduct)
                        {
                            $data["message"] .="Product Already Available, ";
                            $data["product"] = $ItemName;
                        }
                        if(count($subcat)==0)
                        {
                            $data["message"] .= "Sub Category Not Found, ";
                            $data["product"] = $ItemName;
                        }
                        array_push($erroProduct, $data);
                    }
                    else
                    {
                        
                        $Subcategories_SCID = $subcat[0]["SCID"];
                        $upload=$prodObj->setProduct(ProductNo: $ProductNo, ProdImage: $ProdImage, Barcode: $Barcode, ItemName: $ItemName, ProdDescription: $ProdDescription, SecondName: $SecondName, ProdPurchasePrice: $ProdPurchasePrice, ProdSellPrice: $ProdSellPrice, CartonQty: $CartonQty, ProductStat: $ProductStat, AddedDate: $AddedDate, UpdatedDate: $UpdatedDate, ItemType: $ItemType, user_USID: $user_USID, UpdateUserID: $UpdateUserID, Subcategories_SCID: $Subcategories_SCID, shop_SHID: $shop_SHID, PurchaseUnit: $PurchaseUnit, UnitConversion: $UnitConversion, SellingUnit: $SellingUnit);
                        $data["message"] .= "Product uploaded, ";
                        $data["product"] = $ItemName;
                        $product_id=$prodObj->getProductCount($shop_id);
                        $product_id=$product_id[0]["ProductCount"];
                        $count=$invObj->getInventorywithproductID($product_id);
                        $count=$count[0]["procount"];
                        $count=$count+1;
                        $batch_id=$invObj->getSequence($count);
                        $batch_id="B".$batch_id;
                        $CurrentQty=0;
                        $BillQty=0;
                        $ReturnQty=0;
                        $TransfeInQty=0;
                        $TransferOutQty=0;
                        $RackID=1;
                        $default=1;
                        $mnf_date="";
                        $exp_date="";
                        $effective_date=date("Y-m_d");
                        $invObj->setInventory2($CurrentQty, $BillQty, $ReturnQty, $TransfeInQty, $TransferOutQty, $product_id, $shop_id, $RackID, $batch_id,$default);
                        $sql = "SELECT max(INID) AS MAXSID FROM inventory WHERE shop_SHID='$shop_id';";
                        $dbMax = $dbObj->getData($sql);
                        $max_id = floatval($dbMax[0]['MAXSID']);
                        $new_inventory_id = $max_id ;
                        $grn_detail_id = 0;
                        $priceObj->setPriceHistory($product_id, 0, $effective_date, $ProdPurchasePrice, $ProdSellPrice, $ProdSellPrice, $mnf_date, $exp_date, $batch_id, $new_inventory_id, $grn_detail_id);
                        array_push($successProduct, $data);
                    }

                    if(isset($erroProduct))
                    {
                        $_SESSION["erroProduct"]=$erroProduct;
                    }
                    if(isset($successProduct))
                    {
                        $_SESSION["successProduct"]=$successProduct;
                    }
                    
                    if (isset($upload)) {
                        echo "Uploaded<br>";
                    } else {
                        echo "<br>Error<br>";
                    }
                }
                fclose($file);
            }
        } 
        else 
        {
            echo "Failed to move uploaded file.";
        }
        header("Location:../Public/upload_product.php");
        ?>
        <script>
            window.location.href = "../Public/upload_product.php";
        </script>
        <?php
} 

if(isset($_POST['btn_update_product']))
{
    $product_id = $_POST['hide_product_id'];
    //get count
    $productData = $prodObj->getProductCount($shop_id);
    $prod_count = intval($productData[0]['ProductCount']);
    $prod_count += 1;

    //image count
    $productImage = $prodObj->getProductImageCount($shop_id);
    $prod_image_count = intval($productImage[0]['ProducImagetCount']);
    $prod_image_count += 1;

    $commObj = new Common();
    $product_no = $commObj->createCount("PD", $prod_count);
    $prod_image_name = $commObj->createCount("PI", $product_id);//get prduct id for image

    $subcat_id = $_POST['cmb_subcategory'];
    $prod_name = $_POST['prod_name'];

    /*
     * Clearing the barcode on an existing product used to fail with "barcode or
     * username empty". It now generates one the same way a new product does.
     */
    $barcode = trim((string) $_POST['barcode']);
    if($barcode === "")
    {
        $barcode = bcgBarcodeForProduct($shop_id, $subcat_id, $prod_name, $product_no);
    }

    $prod_description = $_POST['prod_description'];

    //second name
    $second_name = isset($_POST['second_name']) ? $_POST['second_name'] : "NULL";

    //carton qty
    $prod_carton_qty = isset($_POST['prod_carton_qty']) ? $_POST['prod_carton_qty'] : 1;

    //units
    $purchase_unit = isset($_POST['cmb_purchase_unit']) ? $_POST['cmb_purchase_unit'] : 1;
    $conversion_rate = isset($_POST['conversion_rate']) ? $_POST['conversion_rate'] : 1;
    $selling_unit = isset($_POST['cmb_selling_unit']) ? $_POST['cmb_selling_unit'] : 1;

    //purchase price
    $prod_purchase_price = isset($_POST['prod_purchase_price']) ? $_POST['prod_purchase_price'] : 0;
    $prodStatus = isset($_POST['prodStatus']) ? $_POST['prodStatus'] : 0;

    //selling price
    $prod_selling_price = isset($_POST['prod_selling_price']) ? $_POST['prod_selling_price'] : 0;

     //Item Discount
     $prod_Discount = isset($_POST['prod_Item_Dis']) ? $_POST['prod_Item_Dis'] : 0;
     $prod_flat_Discount = isset($_POST['prod_Item_Dis_flat']) ? $_POST['prod_Item_Dis_flat'] : 0;

     if ($prod_Discount > 0) 
     {        
         $prod_Discount = floatval($prod_Discount); 
     }
    
    if ($prod_flat_Discount > 0) 
    {        
        $prod_flat_Discount = floatval($prod_flat_Discount); 
    }

    $use_service = "P";
    $shopObj = new Shop();
    if($shopObj->hasService($shop_id))
    {
        $use_service = isset($_POST['chk_service']) ? "S" : "P";
    }//has service
    else
    {
        $use_service = "P";
    }//no service

    //get current date
    date_default_timezone_set("Asia/Colombo");
    $this_date = date("Y-m-d");

    //user id
    $user_id = $_SESSION['user_id'];

    //file upload directry
    $target_dir = "../Assets/Images/prod_images/";

    $target_file_path = $target_dir . $prod_image_name . basename($_FILES['prod_image']['name']);
    $file_type = pathinfo($target_file_path, PATHINFO_EXTENSION);

    //validation
    /*
    * check subcat_id
    * check barcode
    * check itemname
    * check image
        if(new image to add)
        {
            * check system image
                if(has image) {unlink image}
                else {update image}
        }
        else
        {
            update record
        }
    */
    //check sub category
    
    if(isset($_POST["chk_fp"]))
    {
        $chk_fp=1;
    }
    else
    {
        $chk_fp=0;
    }
    echo "chk_fp ".$chk_fp;
    if($subcat_id > 0)
    {
        //check barcode and item name
        if(!empty($barcode))
        {
            //check prodname
            if(!empty($prod_name))
            {
                //check image
                $allow_types = array('jpg','JPG','png','PNG','jpeg','JPEG');
                if(in_array($file_type, $allow_types))
                {
                    //check system image
                    $prodOne = $prodObj->getOneProduct($product_id);
                    $sys_image_name = $prodOne[0]['ProdImage'];
                    if(!empty($sys_image_name))
                    {
                        //unlink system image
                        $delete_path = "../Assets/Images/prod_images/" . $prodOne[0]['ProdImage'];
                        if(unlink($delete_path))
                        {
                            //update record
                            $prod_image_name = $prod_image_name . "." . $file_type;
                            $target_file_path = $target_dir . $prod_image_name;

                            $size = floatval($_FILES['prod_image']['size']);
                            //check size 500kb
                            if($size < 500000)
                            {
                                if(move_uploaded_file($_FILES["prod_image"]["tmp_name"], $target_file_path))
                                {
                                    //update record
                                    $editP=$prodObj->editProduct($prod_image_name, $barcode, $prod_name, $prod_description, $second_name, $prod_purchase_price, $prod_selling_price, $prod_carton_qty, $this_date, $use_service, $user_id, $subcat_id, $shop_id, $purchase_unit, $conversion_rate, $selling_unit, $product_id, $prod_Discount,$prodStatus, $prod_flat_Discount, $chk_fp);
                                    echo "editP ".$editP;


                                    $_SESSION['product_update'] = 5; //update successfully
                                    header("Location: ../Public/product.php");
                                }//move file
                                else
                                {
                                    $_SESSION['product_update'] = 4; //unable to save
                                    header("Location: ../Public/product.php");
                                    die("Error: unable to save image");
                                }//cannot save image
                            }//less than 500kb
                            else
                            {
                                $_SESSION['product_update'] = 5;//wrong image size
                                header("Location: ../Public/product.php");
                                die("Error: barcode or username empty.");
                            }//greater than 500kb
                        }//unlink system image
                        else
                        {
                            $_SESSION['product_update'] = 4;
                            header("Location: ../Public/product.php");
                            die("Error: unable to remove previous image");
                        }//cannot delete image
                    }//has sys image
                    else
                    {
                        //check image
                        $allow_types = array('jpg','JPG','png','PNG','jpeg','JPEG');
                        if(in_array($file_type, $allow_types))
                        {
                            //update record
                            $prod_image_name = $prod_image_name . "." . $file_type;
                            $target_file_path = $target_dir . $prod_image_name;

                            $size = floatval($_FILES['prod_image']['size']);
                            //check size 500kb
                            if($size < 500000)
                            {
                                if(move_uploaded_file($_FILES["prod_image"]["tmp_name"], $target_file_path))
                                {
                                    //update record
                                    $editP=$prodObj->editProduct($prod_image_name, $barcode, $prod_name, $prod_description, $second_name, $prod_purchase_price, $prod_selling_price, $prod_carton_qty, $this_date, $use_service, $user_id, $subcat_id, $shop_id, $purchase_unit, $conversion_rate, $selling_unit, $product_id, $prod_Discount,$prodStatus,$prod_flat_Discount,$chk_fp);
                                    echo "editP ".$editP;
                                    editPrice($prod_purchase_price, $prod_selling_price, $product_id);

                                    $_SESSION['product_update'] = 5; //update successfully
                                    header("Location: ../Public/product.php");
                                }//move file
                                else
                                {
                                    $_SESSION['product_update'] = 4; //unable to save
                                    header("Location: ../Public/product.php");
                                    die("Error: unable to save image");
                                }//cannot save image
                            }//less than 500kb
                            else
                            {
                                $_SESSION['product_update'] = 5;//wrong image size
                                header("Location: ../Public/product.php");
                                die("Error: barcode or username empty.");
                            }//greater than 500kb
                        }//support file type
                        else
                        {
                            $_SESSION['product_update'] = 6;
                            header("Location: ../Public/product.php");
                            die("Error: not support image type.");
                        }//not support file
                    }//no sys image
                }//has image
                else
                {
                    /*
                    * should check if the $_POST['prod_image'] isset
                    * if(isset($_POST['prod_image']))
                        {
                            update
                        }
                        else
                        {
                            not support file type
                        }
                    */

                    //check system image
                    $prodOne = $prodObj->getOneProduct($product_id);
                    $sys_image_name = $prodOne[0]['ProdImage'];

                    $editP=$prodObj->editProduct($sys_image_name, $barcode, $prod_name, $prod_description, $second_name, $prod_purchase_price, $prod_selling_price, $prod_carton_qty, $this_date, $use_service, $user_id, $subcat_id, $shop_id, $purchase_unit, $conversion_rate, $selling_unit, $product_id,$prod_Discount,$prodStatus,$prod_flat_Discount,$chk_fp);
                    echo "editP ".$editP;

                    editPrice($prod_purchase_price, $prod_selling_price, $product_id);
                    
                    $_SESSION['product_update'] = 5; //update successfully
                    header("Location: ../Public/product.php");
                }//no image
            }//has product name
            else
            {
                $_SESSION['product_update'] = 1;
                header("Location: ../Public/product.php");
                die("Error: barcode or username empty.");
            }//no product name
        }//has barcode
        else
        {
            $_SESSION['product_update'] = 1;
            header("Location: ../Public/product.php");
            die("Error: barcode or username empty.");
        }//no barcdoe
    }//has sub category
    else
    {
        $_SESSION['product_update'] = 0; //no category
        header("Location: ../Public/product.php");
        die("Error: No category selected.");
    }//no sub category
}//update product

//============================= Functions ==============================//
function editPrice($purchase_price, $selling_price, $product_id)
{   
    $dbObj = new DBTransactions();
    $sql = "SELECT PHID FROM pricehistory WHERE ProductID = ".$product_id." AND BatchID='B000000001';";

    $priceData = $dbObj->getData($sql);

    $price_history_id = $priceData[0]['PHID'];

    $priceObj = new PriceHistory();

    $priceObj->editPriceHistoryPrice($purchase_price, $selling_price, $price_history_id);

}//edit price