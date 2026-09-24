// Browser check of the checkbox in front of each item name in the POS cart: "Will be delivered
// by Warehouse". Ticking it hands a line the shop itself sells over to the warehouse; unticking
// gives it back to the counter; an item the warehouse has never held is refused.
//
//   node tests/ui/warehouse_checkbox_ui.mjs [screenshot-folder]
//
// Needs Node 24+, Chrome and the local site; see tests/ui/harness.mjs.
import { UiRun, BASE, sleep } from './harness.mjs';

const run = new UiRun(process.argv[2], 9336);

const rowCount = () => run.js(`document.querySelectorAll('#cart tr').length`);
const boxes = () => run.js(`document.querySelectorAll('#cart tr .wh-toggle').length`);
const ticked = (n) => run.js(`!!document.querySelectorAll('#cart tr')[${n}].querySelector('.wh-toggle:checked')`);
const field = (n, cls) => run.js(`(document.querySelectorAll('#cart tr')[${n}].querySelector('.${cls}') || {}).value`);
const bannerCount = () => run.js(`document.getElementById('wh_banner_count').textContent`);
const bannerShown = () => run.js(`document.getElementById('wh_banner').offsetParent !== null`);
const badge = (n) => run.js(`(document.querySelectorAll('#cart tr')[${n}].querySelector('.wh-badge') || {}).textContent || ''`);
const shelfCap = (n) => run.js(`document.querySelectorAll('#cart tr')[${n}].querySelector("[name='avl_qty[]']").value`);
const toast = () => run.js(`(document.querySelector('#toast-container') || {}).textContent || ''`);

// adds a product to the cart the way the tile does, and waits for the row
const addProduct = async (productId) => {
  const before = await rowCount();
  await run.js(`product(${productId})`);
  for (let i = 0; i < 20 && (await rowCount()) === before; i++) {
    await sleep(200);
  }
};

// clicks a row's checkbox and waits for the round trip to the warehouse to settle
const clickBox = async (n) => {
  await run.js(`document.querySelectorAll('#cart tr')[${n}].querySelector('.wh-toggle').click()`);
  await sleep(1200);
};

let failure = null;
try {
  const fx = await run.start();

  console.log('Sign in to the e2e showroom till');
  await run.signIn('e2e_admin', fx.shops.S);
  await run.go(`${BASE}/Public/gui-pos.php`);
  const landed = await run.js('location.pathname');
  run.check('the till opens', landed.endsWith('/Public/gui-pos.php'));
  if (!landed.endsWith('/Public/gui-pos.php')) {
    console.log('        landed on ' + landed);
  }
  run.check('nothing is going to the warehouse yet', !(await bannerShown()) || (await bannerCount()) === '0');

  console.log('\nA pillow off the shop shelf');
  await addProduct(fx.products.pillow_shop);
  run.check('the pillow is in the cart', (await rowCount()) === 1);
  run.check('and it offers the checkbox in front of its name', (await boxes()) === 1);
  run.check('which starts unticked', !(await ticked(0)));
  run.check('so the line is the shop to hand over', (await field(0, 'wh_line')) === '');
  await run.shot('checkbox-1-unticked.png');

  console.log('\nTicking it hands the line to the warehouse');
  await clickBox(0);
  run.check('the row is now the warehouse job', (await field(0, 'wh_line')) === '1');
  run.check('and is marked as one of our own products', (await field(0, 'wh_own')) === '1');
  run.check('the warehouse product behind it came from the server',
    (await field(0, 'wh_supplier_product')) === String(fx.products.pillow));
  run.check('the row says where it is coming from', (await badge(0)).startsWith('From '));
  run.check('the banner counts it', (await bannerCount()) === '1' && (await bannerShown()));
  run.check('the shop shelf no longer caps the quantity', (await shelfCap(0)) === '99999');
  await run.shot('checkbox-2-ticked.png');

  console.log('\nUnticking gives it back to the counter');
  await clickBox(0);
  run.check('the line is the shop to hand over again', (await field(0, 'wh_line')) === '');
  run.check('the warehouse product is forgotten', (await field(0, 'wh_supplier_product')) === '');
  run.check('the badge is gone', (await badge(0)) === '');
  run.check('the banner is empty again', (await bannerCount()) === '0');
  run.check('and the shelf caps the quantity once more', (await shelfCap(0)) === '4');

  console.log('\nAn item the warehouse has never held');
  await addProduct(fx.products.lamp_shop);
  run.check('the lamp is in the cart too', (await rowCount()) === 2);
  await clickBox(1);
  run.check('the tick is refused', !(await ticked(1)));
  run.check('the line stays the shop to hand over', (await field(1, 'wh_line')) === '');
  run.check('and the cashier is told why', (await toast()).includes('does not keep this item'));
  await run.shot('checkbox-3-refused.png');

  console.log('\nA tile only the warehouse has');
  run.check('the banner is still empty', (await bannerCount()) === '0');
  await run.js(`warehouseProduct(${fx.products.bed})`);
  await sleep(1500);
  run.check('the bed is in the cart', (await rowCount()) === 3);
  run.check('its checkbox is ticked for us', await ticked(2));
  run.check('and cannot be unticked: the shop has none to hand over',
    await run.js(`document.querySelectorAll('#cart tr')[2].querySelector('.wh-toggle').disabled`));
  run.check('the banner counts it', (await bannerCount()) === '1');
  await run.shot('checkbox-4-warehouse-tile.png');


  console.log('\nThe delivery details have to reach the server');
  await run.js(`document.getElementById('wh_open_details').click()`);
  await sleep(700);
  run.check('the delivery details open', await run.js(`document.getElementById('warehouseOrderModal').classList.contains('show')`));
  await run.js(`document.getElementById('wh_cust_name').value = 'e2e Kamala';
    document.getElementById('wh_cust_phone').value = '0777654321';
    document.getElementById('wh_address').value = '4 Temple Lane';
    document.getElementById('wh_phone').value = '0777654321';
    document.getElementById('wh_save_details').click()`);
  await sleep(800);
  run.check('they are accepted', !(await run.js(`document.getElementById('warehouseOrderModal').classList.contains('show')`)));
  run.check('the banner says the sale is ready',
    (await run.js(`document.getElementById('wh_banner_state').textContent`)) === 'ready');

  // the sale posts $("#order_form").serialize(): anything outside that form is simply not sent
  const posted = new URLSearchParams(await run.js(`$("#order_form").serialize()`));
  run.check('the customer name is posted with the sale', posted.get('wh_cust_name') === 'e2e Kamala');
  run.check('the phone number is posted with the sale', posted.get('wh_cust_phone') === '0777654321');
  run.check('the delivery address is posted with the sale', posted.get('wh_address') === '4 Temple Lane');
  run.check('and so is the warehouse the goods come from', Number(posted.get('wh_supplier_shop')) > 0);

  console.log('\nTaking the money');
  // the number the till prints is what the customer quotes back, so the warehouse must show it
  const billNo = await run.js(`document.getElementById('invoice-no').textContent.trim()`);
  run.check('the till shows a bill number', /\w+-\d+/.test(billNo));
  await run.js(`document.getElementById('pay_cash').click()`);
  await sleep(3000);
  run.check('the sale was not refused for want of a customer', !(await toast()).includes('warehouse order'));

  await run.go(`${BASE}/Public/customer-orders.php`);
  const orders = await run.js(`document.body.textContent`);
  run.check('the order is waiting for the warehouse, with the customer on it',
    orders.includes('e2e Kamala') && orders.includes('Pending'));
  // this POS leaves invoiceheader.InvoiceNo empty and puts the printed number on BillNo, so an
  // Invoice column reading InvoiceNo was blank on every order the till ever created
  const invoiceCell = await run.js(`(() => {
    const row = [...document.querySelectorAll('table tbody tr')].find((r) => r.textContent.includes('e2e Kamala'));
    return row ? row.cells[1].textContent.trim() : '';
  })()`);
  run.check('and the Invoice column names the bill it was paid on (' + invoiceCell + ')',
    /\w+-\d+/.test(invoiceCell));
  await run.shot('checkbox-5-order-created.png');
  run.checkScripts('no JavaScript errors from the POS scripts', /guipos\.js|pos_warehouse_order\.js/);
} catch (e) {
  failure = e;
}
await run.finish(failure);
