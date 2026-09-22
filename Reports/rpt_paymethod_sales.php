<?php 
include '../Includes/includes.php';
include '../Includes/authcheck.php';

    //get current date time
    date_default_timezone_set("Asia/Colombo");

    $start_date = "";
    $end_date = "";
    if (isset($_GET['date'])) {
        $date = explode("_", $_GET['date']);
        $start_date = $date[0];
        $end_date = $date[1];
    } 
    if(isset($_GET["start_date"]) && isset($_GET["end_date"]))
    {
        $start_date = $_GET["start_date"];
        $end_date = $_GET["end_date"];
    }
    else {
        $start_date = date("Y-m-d");
        $end_date = date("Y-m-d");
    }

?>
<!doctype html>
<html lang="en">

<head>
  <?php 
  include '../View/head.php';
  // include '../View/loader.php';
//   include '../View/datatables.php';
  ?>  

    <!-- <link rel="stylesheet" href="../Assets/css/datatables.min.css"> -->
    <!-- <script src="../Assets/js/datatables.min.js"></script> -->

</head>
<body>
<!--  Body Wrapper -->
<div class="h-100vh">
<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!-- Sidebar Start -->
    <?php 
    include '../View/sidebar.php';
    $feature_id=58;
    include '../Includes/viewPermission.php';
    if($userType==1 || $print==1)
    {
        
    }
    else
    {
        ?>
        <script>
            setInterval(function(){
                $(".dt-buttons").addClass("d-none");  
            }, 100);
            
        </script>
        <?php
    }
    ?>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
        <?php 
        include '../View/header.php';
        include "../View/modals/main-category.php";
        $shops= new Shop();
        $shop=$shops->getOneShop($shop_id);
        ?>
         <div class="container-fluid">
            <input type="hidden" name="" id="shop_name" value="<?=$shop[0]['ShopName']?>">
            <input type="hidden" name="" id="shop_address_one" value="<?=$shop[0]['AddressLineOne']?>">
            <input type="hidden" name="" id="shop_address_two" value="<?=$shop[0]['AddressLineTwo']?>">
            <input type="hidden" name="" id="shop_city" value="<?=$shop[0]['City']?>">
            <input type="hidden" name="" id="shop_number" value="<?=$shop[0]['PhoneNumber']?>"> 
            <input type="hidden" name="" id="title" value="Payment Method">
            <h5 class="card-title fw-semibold mb-2" style="margin-top: 0px;">Payment Method</h5>
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                    <form action=" " class="form-inline" method="get" accept-charset="utf-8">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="" for="from_date">Start Date</label>
                                    <input type="date" name="start_date" class="form-control datepicker hasDatepicker" id="start_date" placeholder="Start Date" value="<?php echo $start_date;?>">
                                </div> 
                            </div>
                            <div class="col-md-4">
                                  <div class="form-group">
                                    <label class="" for="to_date">End Date</label>
                                    <input type="date" name="end_date"class="form-control datepicker hasDatepicker" id="end_date" placeholder="End Date" value="<?php echo $end_date;?>">
                                </div>
                            </div>
                            <?php 
                                    if($userType==1)
                                    {
                                        ?>
                                        <div class="col-md-2">
                                            <label for="claim" class="form-label">Claim Bill With Invoices</label>
                                            <input type="checkbox" name="claim" id="claim" class="form-check" value="1"
                                            <?php 
                                            if(isset($_GET["claim"]))
                                            {
                                                echo "checked";
                                            }
                                            ?>
                                            >
                                        </div>
                                        <?php
                                    }
                                    ?>
                            <div class="col-md-2 mt-4">`
                                <button type="submit" name="btn_paymethod_date" id="btn-filter" class="btn btn-success">Find</button>
                            </div>
                        </div>
                    </form>
                <table class="table table-hover" id="tbl_sale_reports">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Paymethod</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $dbObj = new DBTransactions();

                        $sql = "SELECT * FROM shoppaymethod 
                        INNER JOIN paymethod ON paymethod.PMID = shoppaymethod.paymethod_PMID
                        WHERE shop_SHID = ".$shop_id.";";

                        $paymethod_data = $dbObj->getData($sql);
                        $i = 0;
                        $total_amount = 0;
                        foreach ($paymethod_data as $row) 
                        {
                            $i += 1;
                            $paymethod_id = $row['paymethod_PMID'];
                            $paymethod = $row['PaymethodName'];

                            $add = "";
                            if (isset($_GET["claim"])) {
                                $add = " OR invoiceheader.InvStat=6";
                            }

                            $sql_1 = "SELECT SUM(TransferAmount) AS TotalAmount FROM transactions
                            INNER JOIN invoiceheader ON invoiceheader.IHID = transactions.InvoiceHeader_IHID
                            WHERE EffectiveDate BETWEEN '".$start_date."' AND '".$end_date."' AND shop_SHID=".$shop_id." AND paymethod_PMID = ".$paymethod_id." AND invoiceheader.InvStat=1 AND transactions.TransactionStat = 1;";

                            $paid_data = $dbObj->getData($sql_1);
                            $pay_amount = $paid_data[0]['TotalAmount'];
                            $total_amount += $pay_amount; 

                            ?>
                            <tr>
                                <td> <?php echo $i;?> </td>
                                <td> <?php echo $paymethod; ?></td>
                                <td> <?php echo number_format((float)$pay_amount, 2, '.', ''); ?></td>
                            </tr>
                            <?php 
                        }//foreach

                        //get credit due payments
                        $sql_2 = "SELECT SUM(CreditAmount) AS total_credit, sum(DebitAmount) as total_debit FROM `creditcustomer`
                        WHERE shop_SHID = '$shop_id' AND CreditStat=1 AND ((EffectiveDate BETWEEN '$start_date' AND '$end_date') OR (EffectiveDate='$start_date' OR EffectiveDate='$end_date'));";

                        $creditData = $dbObj->getData($sql_2);
                        $total_credit = floatval($creditData[0]['total_credit']);
                        $total_debit = floatval($creditData[0]['total_debit']);

                        $total_credit =  number_format((float)$total_credit, 2, '.', '');
                        $total_debit =  number_format((float)$total_debit, 2, '.', '');

                        //$balance_credit = $total_credit - $total_debit;
                        
                        ?>
                        <tr>
                            <td>
                                <?php 
                                $i = $i+1;
                                echo $i;
                                ?>
                            </td>
                            <td>Customer Dues</td>
                            <td><?php echo number_format((float)$total_credit, 2, '.', '');?></td>
                        </tr>
                        <tr>
                            <td>
                                <?php 
                                $i = $i+1;
                                echo $i;
                                ?>
                            </td>
                            <td>Customer Dues Received</td>
                            <td><?php echo $total_debit;?></td>
                        </tr>

                    </tbody>

                     <!-- footer -->
                    <tfoot>
                        <tr>
                            <th colspan="2" style="text-align:right">Total:</th>
                            <th></th>
                        </tr>
                    </tfoot>

                </table>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php include '../View/footer.php';?> 
    <script src="../Assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../Assets/js/sidebarmenu.js"></script>
    <script src="../Assets/js/app.min.js"></script>
    <script src="../Assets/libs/simplebar/dist/simplebar.js"></script>

    <script>
        $(document).ready(function(){
            var shop_name = $("#shop_name").val();
            var shop_address_one = $('#shop_address_one').val();
            var shop_address_two = $('#shop_address_two').val();
            var shop_city = $('#shop_city').val();
            var shop_number = $('#shop_number').val();
            var reportname=$("#title").val();

            $("#tbl_sale_reports").DataTable({
               
                paging: true,
                lengthChange: true,
                searching: true,
                //pageLength: 10,

                layout:{
                    topStart:{
                        buttons:[
                            //====================== PDF
                            {
                                extend: 'pdf',
                                customize: function (doc){
                                    doc.content.splice(0,1,{
                                        text: [
                                            {text: shop_name + "\n", bold: true, fontSize: 16},
                                            {text: shop_address_one + ",\n", bold: true, fontSize: 12},
                                            {text: shop_address_two + ",\n", bold: true, fontSize: 12},
                                            {text: shop_number + ",\n", bold: true, fontSize: 12},
                                            {text: reportname + ",\n", bold: true, fontSize: 14},
                                        ]
                                    }) 
                                },
                                download: 'open'
                            },
                            //===================== print
                            {
                                extend: 'print',
                                title: '',
                                customize: function(win){
                                    var formattedCity = shop_city.charAt(0).toUpperCase() + shop_city.slice(1).toLowerCase();
                    
                                    $(win.document.body).css('font-size', '10pt').prepend(
                                    '<div style="text-align: center; margin-bottom: 20px;">' +
                                    '<p style="margin: 5px; font-size: 24px; font-weight: bold;">' + shop_name + '</p>' +
                                    '<p style="margin-bottom: 5px;">' + shop_address_one + ', ' + shop_address_two + '</p>' +
                                    '<p style="margin-bottom: 5px;">' + formattedCity + '</p>' +  // Reduced margin
                                    '<p style="margin-bottom: 5px;">' + shop_number + '</p>' +  // Reduced margin
                                    '<p style="margin: 5px; font-size: 18px; text-align:center;">'+ reportname +'</p>' + //report name
                                    '</div>'
                                    );
                                }
                            }, 
                            //===================== excel
                            {
                                extend: 'excel',
                                title: reportname,
                            },
                            //====================== csv
                            {
                                extend: 'csv',
                                title: reportname,
                            }
                        ]
                    } 
                },//layouts
                
                //get total
                footerCallback: function (row, data, start, end, display) {
                    let api = this.api();
            
                    // Remove the formatting to get integer data for summation
                    let intVal = function (i) {
                       return typeof i === 'string'
                           ? i.replace(/[\$,]/g, '') * 1
                           : typeof i === 'number'
                           ? i
                           : 0;
                    };
            
                    //Total over all pages
                    total = api
                       .column(2)
                       .data()
                       .reduce((a, b) => intVal(a) + intVal(b), 0);

                    total = total.toFixed(2);
            
                    //Total over this page
                   pageTotal = api
                       .column(2, { page: 'current' })
                       .data()
                       .reduce((a, b) => intVal(a) + intVal(b), 0);
            
                    //Update footer
                    api.column(2).footer().innerHTML =  total;
                }//footercall back

            }); 
        });//jquery
    </script>

</body>
</html>
