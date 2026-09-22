<div id="viewInvoice_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header">

            <h4 class="modal-title" id="ModalLabel">View Invoice</h4>
            <button type="button" class="btn-close" id="close_Prescription_modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="p_cust_message" class="bg-danger text-light rounded my-1 p-1" style="display: none;"></p>

                    <div class="row">
                        <!-- hidden input -->
                         <?php                          
                         $sql = "SELECT * FROM shopreceipts WHERE ReceiptStat = 1 AND shop_id='$shop_id' AND RecieptType=2";
                         $dbObj = new DBTransactions();
                         $dbData = $dbObj->getData($sql);
                         $invoices = empty($dbData) ? "wholesaleInvoice.php" : $dbData[0]['ReceiptPath'];
                         ?>
                        <div class="col-md-12 mt-1" id="prescription" >
                            <iframe src="../Receipts/<?=$invoices?>?invoice=5&Iframe=1"  id="iframe" style="width:100%; height:100vh;" title="Iframe Example"></iframe>
                        </div>                                                             
                    </div>
                    <div class="modal-footer">
                        <!-- Close -->
                        <button type="button" class="btn bg-warning-subtle text-warning  waves-effect" data-bs-dismiss="modal" id="close_Customer_modal">Close</button>
                    </div>
            </div>       
        </div>
    </div>
</div>
