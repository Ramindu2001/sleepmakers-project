<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Display</title>
    <link rel="shortcut icon" type="image/png" href="../Assets/Images/favicon.png" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Nunito Sans', 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #0a1628 0%, #1a2940 50%, #0d1f3c 100%);
            color: #ffffff;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: linear-gradient(90deg, #0173BC, #0056a0);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        .header .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .header .logo img {
            height: 40px;
        }
        .header .logo h2 {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
        }
        .connection-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #a0c4ff;
        }
        .connection-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #ff4444;
            transition: background 0.3s;
        }
        .connection-dot.connected {
            background: #00e676;
            box-shadow: 0 0 8px rgba(0, 230, 118, 0.5);
        }
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 20px 30px;
            overflow: hidden;
        }
        .cart-section {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 15px;
        }
        .cart-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 4px;
        }
        .cart-table thead th {
            background: rgba(1, 115, 188, 0.4);
            color: #a0c4ff;
            padding: 12px 15px;
            text-align: left;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
        }
        .cart-table thead th:first-child { border-radius: 8px 0 0 8px; }
        .cart-table thead th:last-child { border-radius: 0 8px 8px 0; text-align: right; }
        .cart-table thead th.text-center { text-align: center; }
        .cart-table tbody tr {
            background: rgba(255, 255, 255, 0.05);
            transition: background 0.3s;
        }
        .cart-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .cart-table tbody tr.new-item {
            animation: slideIn 0.4s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateX(-30px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .cart-table tbody td {
            padding: 10px 15px;
            font-size: 16px;
            border: none;
            vertical-align: middle;
        }
        .cart-table tbody td:first-child { border-radius: 8px 0 0 8px; }
        .cart-table tbody td:last-child { border-radius: 0 8px 8px 0; text-align: right; }
        .cart-table tbody td.text-center { text-align: center; }
        .cart-table tbody td .item-name {
            font-weight: 600;
            color: #fff;
            font-size: 15px;
        }
        .cart-table tbody td .item-price {
            font-size: 12px;
            color: #a0c4ff;
        }
        .empty-cart {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #4a6a8a;
            font-size: 20px;
        }
        .empty-cart .icon {
            font-size: 80px;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        .footer-totals {
            background: rgba(1, 115, 188, 0.15);
            border-radius: 12px;
            padding: 20px 25px;
            border: 1px solid rgba(1, 115, 188, 0.3);
        }
        .totals-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        .total-item {
            text-align: center;
        }
        .total-item .label {
            font-size: 11px;
            color: #7a9ec7;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .total-item .value {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
        }
        .grand-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(90deg, #0173BC, #0056a0);
            border-radius: 10px;
            padding: 15px 25px;
        }
        .grand-total .label {
            font-size: 22px;
            font-weight: 600;
            color: #a0c4ff;
        }
        .grand-total .value {
            font-size: 36px;
            font-weight: 800;
            color: #fff;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        .cart-section::-webkit-scrollbar { width: 6px; }
        .cart-section::-webkit-scrollbar-track { background: transparent; }
        .cart-section::-webkit-scrollbar-thumb {
            background: rgba(1, 115, 188, 0.4);
            border-radius: 3px;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .waiting-text {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <img src="../Assets/Images/synnex_logo.png" alt="Logo">
            <h2>Synnex Cloud POS</h2>
        </div>
        <div class="connection-status">
            <span class="connection-dot" id="connectionDot"></span>
            <span id="connectionText">Connecting...</span>
        </div>
    </div>

    <div class="main-content">
        <div class="cart-section" id="cartSection">
            <div class="empty-cart" id="emptyCart">
                <div class="icon">&#128722;</div>
                <p class="waiting-text">Waiting for items...</p>
            </div>
            <table class="cart-table" id="cartTable" style="display:none;">
                <thead>
                    <tr>
                        <th style="width:5%;">#</th>
                        <th style="width:45%;">Item Name</th>
                        <th class="text-center" style="width:15%;">Price</th>
                        <th class="text-center" style="width:15%;">Qty</th>
                        <th style="width:20%; text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody id="cartBody">
                </tbody>
            </table>
        </div>

        <div class="footer-totals">
            <div class="totals-grid">
                <div class="total-item">
                    <div class="label">Total Items</div>
                    <div class="value" id="totalItems">0</div>
                </div>
                <div class="total-item">
                    <div class="label">Gross Amount</div>
                    <div class="value" id="grossAmount">Rs. 0.00</div>
                </div>
                <div class="total-item">
                    <div class="label">Discount</div>
                    <div class="value" id="totalDiscount">Rs. 0.00</div>
                </div>
                <div class="total-item">
                    <div class="label">Total Qty</div>
                    <div class="value" id="totalQty">0.00</div>
                </div>
            </div>
            <div class="grand-total">
                <div class="label">Net Total</div>
                <div class="value" id="netTotal">Rs. 0.00</div>
            </div>
        </div>
    </div>

    <script>
        // Auto fullscreen on load
        document.addEventListener('click', function autoFS() {
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen().catch(function(){});
            }
            document.removeEventListener('click', autoFS);
        }, { once: true });

        // Try auto fullscreen
        try {
            document.documentElement.requestFullscreen().catch(function(){});
        } catch(e) {}

        // BroadcastChannel for real-time cart sync
        var customerChannel = null;
        try {
            customerChannel = new BroadcastChannel('pos_customer_display');
            document.getElementById('connectionDot').classList.add('connected');
            document.getElementById('connectionText').textContent = 'Connected';

            customerChannel.onmessage = function(event) {
                var data = event.data;
                if (data.type === 'cart_update') {
                    updateDisplay(data);
                } else if (data.type === 'cart_clear') {
                    clearDisplay();
                } else if (data.type === 'ping') {
                    customerChannel.postMessage({ type: 'pong' });
                }
            };
        } catch(e) {
            // Fallback: localStorage polling
            document.getElementById('connectionText').textContent = 'Using fallback sync';
            setInterval(function() {
                var cartData = localStorage.getItem('pos_cart_data');
                if (cartData) {
                    try {
                        var data = JSON.parse(cartData);
                        if (data.type === 'cart_clear') {
                            clearDisplay();
                        } else {
                            updateDisplay(data);
                        }
                        document.getElementById('connectionDot').classList.add('connected');
                    } catch(e) {}
                }
            }, 500);
        }

        function updateDisplay(data) {
            var items = data.items || [];
            var totals = data.totals || {};

            if (items.length === 0) {
                clearDisplay();
                return;
            }

            document.getElementById('emptyCart').style.display = 'none';
            document.getElementById('cartTable').style.display = 'table';

            var tbody = document.getElementById('cartBody');
            tbody.innerHTML = '';

            items.forEach(function(item, index) {
                var tr = document.createElement('tr');
                tr.className = 'new-item';
                tr.innerHTML = 
                    '<td>' + (index + 1) + '</td>' +
                    '<td><div class="item-name">' + (item.name || '') + '</div></td>' +
                    '<td class="text-center"><div class="item-price">Rs. ' + parseFloat(item.rate || 0).toFixed(2) + '</div></td>' +
                    '<td class="text-center">' + parseFloat(item.qty || 0).toFixed(2) + '</td>' +
                    '<td style="text-align:right;">Rs. ' + parseFloat(item.total || 0).toFixed(2) + '</td>';
                tbody.appendChild(tr);
            });

            document.getElementById('totalItems').textContent = items.length;
            document.getElementById('totalQty').textContent = parseFloat(totals.totalQty || 0).toFixed(2);
            document.getElementById('grossAmount').textContent = 'Rs. ' + parseFloat(totals.grossTotal || 0).toFixed(2);
            document.getElementById('totalDiscount').textContent = 'Rs. ' + parseFloat(totals.totalDiscount || 0).toFixed(2);
            document.getElementById('netTotal').textContent = 'Rs. ' + parseFloat(totals.netTotal || 0).toFixed(2);
        }

        function clearDisplay() {
            document.getElementById('emptyCart').style.display = 'flex';
            document.getElementById('cartTable').style.display = 'none';
            document.getElementById('cartBody').innerHTML = '';
            document.getElementById('totalItems').textContent = '0';
            document.getElementById('totalQty').textContent = '0.00';
            document.getElementById('grossAmount').textContent = 'Rs. 0.00';
            document.getElementById('totalDiscount').textContent = 'Rs. 0.00';
            document.getElementById('netTotal').textContent = 'Rs. 0.00';
        }
    </script>
</body>
</html>
