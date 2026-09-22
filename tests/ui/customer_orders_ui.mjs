// Browser check of customer orders: the showroom fills the new order form (product search,
// given / warehouse / custom-made lines) and sends it; the warehouse accepts it, creates the
// transfer and marks the custom-made item sent - all through the pages' own buttons.
//
//   node tests/ui/customer_orders_ui.mjs [screenshot-folder]
//
// Needs Node 24+, Chrome and the local site; see tests/ui/harness.mjs.
import { UiRun, BASE, sleep } from './harness.mjs';

const run = new UiRun(process.argv[2], 9335);
const text = (selector) => run.js(`(document.querySelector(${JSON.stringify(selector)})?.textContent || '').replace(/\\s+/g, ' ').trim()`);
const status = () => text('#co_order .badge');
const suppliedRows = () => run.js(`[...document.querySelectorAll('#co_supplied tbody tr')].map(tr => [...tr.cells].slice(0, 5).map(td => td.textContent.replace(/\\s+/g, ' ').trim()).join('/')).join('|')`);
const modalOk = async () => {
  await sleep(600);
  await run.submit(`document.getElementById('co_modal_ok').click()`, 900);
};

let failure = null;
try {
  const fx = await run.start();

  console.log('The showroom places an order');
  await run.signIn('e2e_bob', fx.shops.S);
  run.check('Customer Orders is in the menu', await run.js(`!!document.querySelector('a[href="../Public/customer-orders.php"]')`));
  await run.go(`${BASE}/Public/customer-orders.php`);
  await run.submit(`[...document.querySelectorAll('a')].find(a => a.textContent.includes('New Order')).click()`);
  run.check('New Order opens the form, ordering from the e2e Warehouse', (await run.js('location.search')) === '?new=1'
    && (await text('#co_supplier_name')) === 'e2e Warehouse');

  await run.js(`document.getElementById('co_cust_name').value = 'e2e Customer'; document.getElementById('co_cust_phone').value = '0770000000';`);
  await run.js(`document.getElementById('co_add_given').click()`);
  await run.js(`(() => { const row = document.querySelector('#co_given .co-line');
    row.querySelector('.co-description').value = 'e2e Bedsheet'; row.querySelector('.co-invoice').value = 'INV-UI-1'; })()`);

  await run.js(`$('#co_warehouse .co-product').first().select2('open')`);
  await sleep(300);
  await run.insertText('E2EBED');
  await sleep(1500);
  run.check('the product search shows the warehouse stock', (await text('.select2-results__option')) === 'E2EBED01 - e2e Bed (in stock: 10)');
  await run.press('Enter');
  await sleep(300);
  run.check('and picks the product', (await run.js(`$('#co_warehouse .co-product').first().val()`)) === String(fx.products.bed));
  await run.js(`(() => { const row = document.querySelector('#co_warehouse .co-line');
    row.querySelector('.co-qty').value = '2'; row.querySelector('.co-notes').value = 'King size'; })()`);
  await run.js(`document.getElementById('co_add_custom').click()`);
  await run.js(`(() => { const row = document.querySelector('#co_warehouse .co-line[data-kind="custom"]');
    row.querySelector('.co-description').value = 'Headboard, walnut'; row.querySelector('.co-notes').value = 'Match the bed'; })()`);
  await run.shot('orders-1-form.png');

  await run.submit(`document.getElementById('co_save').click()`, 900);
  run.check('Send to supplier opens the new order', (await run.js('location.pathname')).endsWith('/Public/customer-order.php') && (await status()) === 'Requested');
  run.check('the bedsheet is listed as already given, not to send', (await text('#co_order')).includes('Already given - do not send')
    && (await text('#co_order')).includes('e2e Bedsheet'));
  run.check('the bed and the custom-made headboard are to be supplied',
    (await suppliedRows()) === 'e2e Bed/King size/2/0/0|Custom-made Headboard, walnut/Match the bed/1/0/0');
  await run.shot('orders-2-placed.png');

  console.log('The warehouse fulfils it');
  await run.signIn('e2e_alice', fx.shops.W);
  run.check('the menu shows one order waiting', (await text('a[href="../Public/customer-orders.php"] .badge')) === '1');
  await run.go(`${BASE}/Public/customer-orders.php`);
  run.check('the list opens on Incoming', await run.js(`document.querySelector('#co_tab_incoming').classList.contains('active') && document.querySelector('#co_tab_incoming').textContent.includes('CO_000001')`));
  await run.submit(`document.querySelector('#co_tab_incoming a[href^="customer-order.php"]').click()`);

  await run.js(`document.querySelector('.co-action[data-action="accept"]').click()`);
  await modalOk();
  run.check('Accept', (await status()) === 'Accepted' && (await text('.alert-success')).startsWith('Order accepted.'));

  await run.js(`document.querySelector('.co-action[data-action="create_transfer"]').click()`);
  await modalOk();
  run.check('Create transfer puts the bed on a transfer', (await status()) === 'In transit'
    && (await text('.alert-success')).startsWith('Transfer GT_') && (await suppliedRows()).startsWith('e2e Bed/King size/2/2/0'));
  run.check('which is listed with a link', (await run.js(`document.querySelectorAll('#co_transfers tbody tr').length`)) === 1);

  await run.js(`document.querySelector('.co-action[data-action="custom_sent"]').click()`);
  await sleep(600);
  run.check('Mark sent offers the quantity', (await run.js(`document.getElementById('co_field_qty').value`)) === '1');
  await run.js(`document.getElementById('co_field_note').value = 'Van 2'`);
  await modalOk();
  run.check('and the headboard shows as sent', (await suppliedRows()).endsWith('Custom-made Headboard, walnut/Match the bed Sent: Van 2/1/1/1'));
  await run.shot('orders-3-warehouse.png');

  run.checkScripts('no JavaScript errors from the order pages', /customer_order\.js/);
} catch (e) {
  failure = e;
}
await run.finish(failure);
