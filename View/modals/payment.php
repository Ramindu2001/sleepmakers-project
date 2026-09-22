<div id="modal_payment" class="modal fade show" aria-labelledby="bs-example-modal-md" aria-modal="true" role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Submit Invoice - Bill No: <?php echo $tmp_bill_no;?></h4>
                <button type="button" class="btn-close" id="close_payment_modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

            <p id="p_invoice_message" class="text-light bg-danger rounded my-1 p-1" style="display:none;"></p>

            <form action="" method="POST" id="frm_payment">
                <!-- hidden inputs -->
                <input type="hidden" name="hide_sales_header" id="hide_sales_header" value="<?php echo $header_id?>">
                <input type="hidden" name="hide_row_count" id="hide_row_count" value="0">
                <input type="hidden" name="hide_item_count" id="hide_item_count" value="0">
                <input type="hidden" name="hide_sell_total" id="hide_sell_total" value="0">
                <input type="hidden" name="hide_invoice_discount" id="hide_invoice_discount" value="0">
                <input type="hidden" name="hide_return_header" id="hide_return_header" value="0">
                <input type="hidden" name="hide_max_credit" id="hide_max_credit" value="0">
                <input type="hidden" name="hide_customer_credit" id="hide_customer_credit" value="0">

                <div class="row">
                <?php 
                    $shopObj = new Shop();
                ?>

                <?php 
                if($shopObj->hasCustomers($shop_id))
                {
                    ?>
                    <div class="col-md-6">
                    <label for="cmb_customer"><small>Customer</small></label>
                    <select name="cmb_customer" id="cmb_customer" class="form-select"></select>
                    </div>

                    <div class="col-md-6">
                    <p class="mt-2 p_desc" id="p_customer">
                        <small>Customer</small><br>
                        <b>Walk in Customer</b>
                    </p>
                    </div>
                    <?php 
                }//has customer

                if($shopObj->hasSalesman($shop_id))
                {
                    ?>
                    <div class="col-md-6">
                    <label for="cmb_salesman"><small>Salesman</small></label>
                    <select name="cmb_salesman" id="cmb_salesman" class="form-select">
                        <option value="1">=== Select Salesman ===</option>
                        <?php 
                        $sql = "SELECT * FROM salesmans WHERE shop_SHID = ".$shop_id.";";
                        $dbObj = new DBTransactions();
                        $dbSalesman = $dbObj->getData($sql);
                        foreach($dbSalesman as $row)
                        {
                            ?>
                            <option value="<?php echo $row['SLID'];?>"><?php echo $row['SalesmansName'];?></option>
                            <?php 
                        }//foreach
                        ?>
                    </select>
                    </div>

                    <div class="col-md-6">
                        <p class="mt-2 p_desc" id="p_salesman">
                            <small>Salesman Name</small><br>
                            <b>No Salesman</b>
                        </p>
                    </div>
                    <?php 
                }//has salesman
                ?>

                <!------------------------- Bill Total ------------------------>
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-4">
                            <div class="text-center my-1 p-1 border border-primary rounded">
                                <p class="m-1"><small>Invoice Total</small></p>
                                <h4 class="m-1" id="inv_bill_total"></h4>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center my-1 p-1 border border-primary rounded">
                                <p class="m-1"><small>Invoice Discount</small></p>
                                <h4 class="m-1" id="inv_bill_discount"></h4>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center my-1 p-1 border border-primary rounded">
                                <p class="m-1"><small>Net Total</small></p>
                                <h4 class="m-1" id="inv_net_total"></h4>
                            </div>
                        </div>
                    </div>
                </div>

                    <!---------------------------- Receipt Types ------------------------------>
                    <div class="col-md-12 my-1"  style="overflow-y: auto;">
                        <label for="">Receipt Type(s)</label><br>
                        <?php 
                        //get shop receipts
                        $sql = "SELECT * FROM shopreceipts WHERE shop_id = ".$shop_id.";";
                        $dbObj = new DBTransactions();
                        $receiptData = $dbObj->getData($sql);
                        $is_default = "";
                        foreach($receiptData as $row)
                        {
                            $is_default = $row['is_default'] == 1 ? 'checked': '';

                            ?>
                                <input class="form-check-input p-2" type="radio" name="rdb_receipt" id="rdb_receipt_<?php echo $row['SRID'];?>" value="<?php echo $row['ReceiptPath'];?>" <?php echo $is_default;?>>
                                <label class="form-check-label px-2" for="rdb_receipt_<?php echo $row['SRID'];?>">
                                    <?php echo $row['receiptName'];?>
                                </label>
                            <?php 
                        }//foreach
                        ?>
                        <!-- <button type="submit" name="btn_submit_pay" id="btn_submit_pay" class="btn btn-success mt-2 p-3 w-100">Submit Invoice</button> -->
                    </div>

                    <!-- discounted amount -->
                    <input type="hidden" name="hide_discounted_total" id="hide_discounted_total" value="0">
                    <input type="hidden" name="hide_pending_amount" id="hide_pending_amount" value="0">

                    <!------------------------------ Payemnt Methods ----------------------------->
                    <div class="col-md-12 mt-2" id="div_paymethods" style="overflow-y: auto;">
                        <?php 
                            $sql = "SELECT * FROM shoppaymethod
                            INNER JOIN paymethod ON paymethod.PMID = shoppaymethod.paymethod_PMID
                            WHERE shop_SHID = ".$shop_id.";";
                            $dbObj = new DBTransactions();
                            $dbPaymethods = $dbObj->getData($sql);
                            $count = 0;
                            foreach($dbPaymethods as $row)
                            {
                                $is_checked = $count==0 ? 'checked' : '';
                                ?>
                                <div class="form-check div_paymethod">
                                    <input class="form-check-input rdb_paymethod" type="radio" name="rdb_paymethod" id="rdb_pay_<?php echo $row['PMID']?>" value="<?php echo $row['paymethod_PMID'];?>" <?php echo $is_checked;?>>
                                    <label class="form-check-label" for="rdb_pay_<?php echo $row['PMID']?>">
                                        <img src="../Assets/Images/paymethod_images/<?php echo $row['image_path'];?>" alt="paymethod image" id="img_paymethod">
                                    </label>
                                </div>
                                <?php 
                                $count += 1;
                            }//foreach
                        ?>
                    </div>

                    <!------------------------------ Return Price ---------------------------->
                    <div class="col-md-12 my-2" id="div_return_invoice">
                        <div class="row">
                            <div class="col-6">
                                <input type="text" name="return_no" id="return_no" class="form-control" placeholder="IRT_000000">
                                <span class="text-danger" id="return_no_warning" style="display: none;">Please add return No</span>
                                <input type="hidden" name="hide_return_amount" id="hide_return_amount" value="0">
                            </div>
                            <div class="col-3">
                                <button type="button" name="btn_add_return" id="btn_add_return" class="btn btn-primary w-100">Add Return</button>
                            </div>
                            <div class="col-3">
                                <p class="p_desc" id="p_return_amount">
                                    <small>Return Amount</small><br>
                                    <b>Rs: 0.00</b>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!------------------------------ customer payment ------------------------------>
                    <div class="col-md-6">
                        <label for="cust_payment" style="color:black;"><small>Payment</small></label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="cust_payment" id="cust_payment" class="form-control">
                            <span class="input-group-text" id="btn_add_multipay"><i class="ti ti-plus"></i></span>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <button type="button" name="btn_submit_pay" id="btn_make_payment" class="btn btn-primary mt-2 py-2 w-100">Make Payment</button>
                        <button type="button" name="btn_credit_pay" id="btn_credit_pay" class="btn btn-warning mt-2 py-2 w-100">Credit Payment</button>
                        <!-- <button type="button" name="btn_pay" id="btn_pay" class="btn btn-primary mt-2 p-1 w-100">Pay</button> -->
                        <!-- <button type="button" name="btn_credit" id="btn_credit" class="btn btn-warning mt-2 p-1 w-100">Pay</button> -->
                    </div>

                    <div class="col-md-3">
                        <p class="mt-2 p_desc" id="p_balance">
                            <small>Balance</small><br>
                            <b>Rs: 0.00</b>
                        </p>
                    </div>

                    <div class="col-md-6" id="div_multipay">
                        <table id="tbl_multipay_modal">
                            <tr>
                                <th>Paymethod</th>
                                <th>Amount</th>
                                <th>Action</th>
                            </tr>
                            <?php 
                                //$sql = "SELECT * FROM multipay 
                                //INNER JOIN paymethod ON paymethod.PMID = multipay.paymethod_id
                                //WHERE sellheader_id = ".$header_id.";";

                                //$dbObj = new DBTransactions();
                                //$sellData = $dbObj->getData($sql);

                                //foreach($sellData as $row)
                                //{
                                    ?>
                                    <!-- <tr data-id="<?php //echo $row['MPID'];?>">
                                        <td><?php //echo $row['PaymethodName'];?></td>
                                        <td><?php //echo $row['paidAmount'];?></td>
                                        <td>
                                            <button class="btn btn-danger btn_delete_multipay">
                                                <img src="../Assets/Images/icons/close.png" alt="cancle buttons">
                                            </button>
                                        </td>
                                    </tr> -->
                                    <?php
                               // }//foreach
                            ?>
                        </table>
                    </div>

                    <div class="col-md-6">
                        <!-- <p id="p_multipay_details"></p> -->
                        <table id="tbl_payment"></table>
                    </div>

                </div>
            </form>
            </div>

            <!-- <div class="modal-footer">
                <button type="submit" name="btn_make_payment" class="btn bg-primary text-primary waves-effect" id="btn_make_payment">Pay:</button>
                <button type="button" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="close_SysFe_modal">Close</button>
            </div> -->
            
        </div>
    </div>
</div>