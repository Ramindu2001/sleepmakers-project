<div id="edit-expenses-modal" class="modal fade show" tabindex="-1" aria-labelledby="edit-expense-label" aria-modal="true"
    role="dialog" style="display: none; background: #00000075;">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Expenses</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="expenseForm" action="../Controller/AddExpenseController.php" method="POST">
                    
                    <!-- Hidden Expense ID -->
                    <input type="hidden" id="edit_expense_id" name="EPID">

                    <!-- Date -->
                    <div class="m-2">
                        <label class="form-label">Date</label>
                        <input type="date" id="edit_expense_date" name="date" class="form-control" required>
                    </div>

                    <!-- Expense Category -->
                    <div class="m-2">
                        <label class="form-label">Select a Category</label>
                        <select name="cmb_expense_category" id="edit_expense_category" class="form-select mb-2" required>
                            <option value="">Select a Category</option>
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
                        <select name="cmb_paymethod" id="ecmb_paymethod" class="form-select">
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

                    <div class="chqrow" id="chqrow"></div>

                    <!-- Amount -->
                    <div class="m-2">
                        <label class="form-label">Amount</label>
                        <input type="number" id="edit_expense_amount" name="amount" class="form-control mb-2" placeholder="Enter Amount" required>
                    </div>
                    
                    <!-- Remarks -->
                    <div class="m-2">
                        <label class="form-label">Remark</label>
                        <input type="text" id="edit_expense_remark" name="remark" class="form-control mb-2" placeholder="Enter Any Other Details" required>
                    </div>

                    <!-- Hidden Fields -->
                    <input type="hidden" id="shop_SHID" name="shop_SHID" value="<?php echo $_SESSION['shop_id']; ?>">
                    <input type="hidden" id="user_USID" name="user_USID" value="<?php echo $_SESSION['user_id']; ?>"> 
            </div>

            <div class="modal-footer">
                <button type="submit" name="btn_edit_expense" class="btn bg-primary-subtle text-primary waves-effect">
                    Save Changes
                </button>
                <button type="button" class="btn bg-danger-subtle text-danger waves-effect" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
            </form>
        </div>
    </div>
</div>
