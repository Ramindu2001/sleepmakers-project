<div id="invoice_expense_modal" class="modal fade show" tabindex="-1" aria-labelledby="bs-example-modal-md" aria-modal="true"
    role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Add Expenses</h4>
                <button type="button" class="btn-close" id="close_SysFe_modal" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <div class="modal-body">

                <p id="p_expense_message"></p>

                <div class="m-2">
                    <label class="form-label">Date: </label>
                    <span id="current_date"></span>
                    <input type="hidden" id="date" name="date" >
                </div>

                <div class="m-2">
                    <label class="form-label">Select a Category</label>
                    <select name="cmb_expense_category"  id="cmb_expense_category" class="form-select mb-2" required>
                        <?php
                        $CategoryObj = new AddExpensesModels();
                        $CategoryName = $CategoryObj->getCategories();
                        foreach ($CategoryName as $Category): ?>
                            <option value="<?php echo $Category['ECID']; ?>">
                                <?php echo $Category['expense_ctg']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="m-2">
                    <label class="form-label">Select a Paymethod</label>
                    <select name="cmb_exp_paymethod" id="cmb_exp_paymethod" class="form-select">
                        <?php 
                            $dbObj = new DBTransactions();
                            $sql = "SELECT * FROM `shoppaymethod` 
                            INNER JOIN paymethod ON paymethod.PMID = shoppaymethod.paymethod_PMID
                            WHERE shoppaymethod.shop_SHID = ".$shop_id.";";

                            $payData = $dbObj->getData($sql);

                            foreach($payData as $row)
                            {
                                ?>
                                <option value="<?php echo $row['paymethod_PMID'];?>"><?php echo $row['PaymethodName'];?></option>
                                <?php 
                            }//foreach
                        ?>
                    </select>
                </div>

                <div class="m-2">
                    <label class="form-label">Amount</label>
                    <input type="number" id="expense_amount" name="expense_amount" class="form-control mb-2"
                        placeholder="Enter Amount" required>
                </div>

                <div class="m-2">
                    <label class="form-label">Remark</label>
                    <input type="text" id="expense_reason" name="expense_reason" class="form-control mb-2"
                        placeholder="Enter Any Other Details" required>
                </div>
            
            </div>
            <div class="modal-footer">
                <button type="submit" id="btn_add_expense" name="btn_add_expense" class="btn bg-primary-subtle text-primary waves-effect">Add Expense</button>
                <!-- <button type="button" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal" id="close_SysFe_modal">Close</button> -->
            </div>
        </div>
    </div>
</div>
</div>