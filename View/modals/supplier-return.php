<div id="return_head_modal" class="modal fade show" aria-labelledby="bs-example-modal-md" aria-modal="true"
    role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-md">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center">
                <h4 class="modal-title" id="myModalLabel">Add new supplier return</h4>
                <button type="button" class="btn-close" id="close_return_modal" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="../Controller/SupplierReturnController.php" method="post">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="m-2">
                                <label for="" class="form-label">Return No</label>
                                <input type="text" name="srn_no" id="srn_no" placeholder="SR_000000"
                                    class="form-control" readonly>
                            </div>
                            <div class="m-2">
                                <?php
                                date_default_timezone_set("Asia/Colombo");
                                $transfer_date = date("Y-m-d");
                                ?>
                                <label for="" class="form-label">Return Date</label>
                                <input type="date" name="transfer_date" id="transfer_date"
                                    value="<?php echo $transfer_date; ?>" class="form-control" readonly>
                            </div>

                            <?php 
                            $shopObj = new Shop();
                            if($shopObj->hasSuppliers($shop_id))
                            {
                                ?>
                                <div class="col-md-6">
                                <div class="m-2">
                                    <label for="" class="form-label">Select Supplier</label><br>
                                    <select name="cmb_supplier" id="cmb_supplier" class="form-select">
                                    <?php 
                                    $shop_id = $_SESSION['shop_id'];
                                    $supObj = new Supplier();
                                    $supData = $supObj->getAllSuppliers($shop_id);
                                    foreach($supData as $row)
                                    {
                                        ?>
                                        <option value="<?php echo $row['SPID'];?>"><?php echo $row['SupplierName'] ." ~ ". $row['Contact'];?></option>
                                        <?php 
                                    }//foreach
                                    ?>
                                    </select>
                                </div>
                                </div>
                                <?php 
                            }//has suppliers
                            ?>
                           
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="btn_add_return" class="btn bg-primary-subtle text-primary waves-effect"
                    id="btn_add_return">
                    Create
                </button>
                <button type="button" class="btn bg-warning-subtle text-warning waves-effect" data-bs-dismiss="modal"
                    id="close_return_modal">
                    Close
                </button>
            </div>
            </form>
        </div>
    </div>
</div>