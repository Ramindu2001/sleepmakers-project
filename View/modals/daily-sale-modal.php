<!-- Daily Sale Modal -->
<div class="modal fade" id="dailySaleModal" tabindex="-1" aria-labelledby="dailySaleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dailySaleModalLabel">
                    <i class="ti ti-chart-bar"></i> Daily Sales Report
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Date Selection -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="dailySaleDate" class="form-label">Select Date</label>
                        <input type="date" class="form-control" id="dailySaleDate" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">&nbsp;</label><br>
                        <button type="button" class="btn btn-primary" id="loadDailySales">
                            <i class="ti ti-refresh"></i> Load Sales
                        </button>
                    </div>
                </div>

                <!-- Daily Sale Summary -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Daily Sale Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h5 class="text-primary" id="totalSalesAmount">Rs. 0.00</h5>
                                    <small class="text-muted">Total Sales</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h5 class="text-success" id="totalInvoices">0</h5>
                                    <small class="text-muted">Total Invoices</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h5 class="text-warning" id="totalDiscount">Rs. 0.00</h5>
                                    <small class="text-muted">Total Discount</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h5 class="text-info" id="avgSaleAmount">Rs. 0.00</h5>
                                    <small class="text-muted">Average Sale</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User-wise Sales -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">User-wise Sales</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>User Name</th>
                                        <th>Invoices</th>
                                        <th>Total Amount</th>
                                        <th>Average Amount</th>
                                        <th>Discount Given</th>
                                    </tr>
                                </thead>
                                <tbody id="userWiseSalesBody">
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            <i class="ti ti-loader"></i> Loading data...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="printDailySales">
                    <i class="ti ti-printer"></i> Print Report
                </button>
            </div>
        </div>
    </div>
</div>
