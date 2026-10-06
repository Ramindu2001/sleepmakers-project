// Browser check of the Print Barcode dialog for units and of the stickers it prints: three copies
// of each unit by default, the live sticker total and its ceiling, the code on two lines (the item
// barcode above the bars, the date and serial below them), and nothing clipped on any sticker size.
//
//   BASE=http://localhost/sleepmakers/sleepmakers node tests/ui/unit_label_ui.mjs [screenshot-folder]
//
// Needs Node 24+, Chrome and the local site; see tests/ui/harness.mjs.
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { UiRun, BASE, sleep } from './harness.mjs';

const PHP = process.env.PHP || 'C:/xampp/php/php.exe';
const FIXTURES = fileURLToPath(new URL('../e2e/fixtures.php', import.meta.url));
const SIZES = ['100x50', '50x30', '50x25', '40x30', '38x25', '34x25', '34x20', '30x20', '25x15'];
const run = new UiRun(process.argv[2], 9337);

const summary = () => run.js(`document.getElementById('bc_total_summary').textContent`);
const shown = (id) => run.js(`document.getElementById('${id}').offsetParent !== null`);
const printDisabled = () => run.js(`document.getElementById('bc_btn_print').disabled`);

// one print job for the bed, posted the way the dialog posts it, and opened in this tab
const printUnit = (bedId, fields = {}) => run.submit(`(() => {
  const f = document.createElement('form');
  f.method = 'post';
  f.action = '../Public/print-barcode.php';
  const all = Object.assign({ btn_print_barcode: 1, print_mode: 'units', produced_date: '2025-09-12',
    'item_id[]': ${bedId}, 'item_qty[]': 1, 'item_price[]': '1500.00', 'item_batch[]': '', size: '50x25', across: 1,
    show_bars: 1, show_code: 1, show_name: 1, show_price: 1, auto_print: 0, unit_copies: 1 }, ${JSON.stringify(fields)});
  for (const [name, value] of Object.entries(all)) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    f.appendChild(input);
  }
  document.body.appendChild(f);
  f.submit();
})()`);

// the first sticker of the page: where its lines are, and whether anything pokes out of it
const firstSticker = async () => JSON.parse(await run.js(`(() => {
  const label = document.querySelector('.bc-label:not(.bc-blank)');
  const box = label.getBoundingClientRect();
  const clipped = [...label.children].some((k) => {
    const r = k.getBoundingClientRect();
    return r.top < box.top - 0.5 || r.bottom > box.bottom + 0.5;
  });
  const prefix = label.querySelector('.bc-code-prefix');
  const suffix = [...label.querySelectorAll('.bc-code')].find((e) => !e.classList.contains('bc-code-prefix'));
  const bars = label.querySelector('.bc-bars');
  const after = (a, b) => !!(a && b && (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING));
  return JSON.stringify({ clipped, prefix: prefix && prefix.textContent, suffix: suffix && suffix.textContent,
    ordered: after(prefix, bars) && after(bars, suffix) });
})()`));

let failure = null;
try {
  const fx = await run.start();
  execFileSync(PHP, [FIXTURES, 'unitmode', String(fx.shops.W)]);   // the e2e warehouse numbers its units
  const bed = fx.products.bed;

  console.log('Open the Print Barcode dialog for one product');
  await run.signIn('e2e_admin', fx.shops.W);
  await run.go(`${BASE}/Public/product.php`);
  await run.js(`$('#product_search').val('E2EBED01').trigger('keyup')`);
  for (let i = 0; i < 25 && !(await run.js(`!!document.querySelector('#tbl_products tr[data-id="${bed}"]')`)); i++) {
    await sleep(200);
  }
  await run.js(`document.querySelector('#tbl_products tr[data-id="${bed}"] .btn_open_barcode').click()`);
  for (let i = 0; i < 25 && (await run.js(`document.querySelectorAll('#bc_items_body tr').length`)) < 1; i++) {
    await sleep(200);
  }
  await sleep(400);

  run.check('the dialog offers a unique barcode on every unit, switched on',
    await run.js(`document.getElementById('bc_unit_mode').checked`));
  run.check('with three copies of each unit by default',
    (await run.js(`document.getElementById('bc_unit_copies').value`)) === '3');
  run.check('and the total says so: ' + (await summary()), (await summary()).includes('1 unit x 3 copies = 3 labels'));
  run.check('the long-code warning measures a unit code, which is longer than the product barcode',
    await run.js(`(() => { const r = $('#bc_items_body tr').first();
      return parseFloat(r.data('bc-unit-modules')) > parseFloat(r.data('bc-modules')); })()`));
  await run.shot('dialog-units.png');

  // 400 units at 3 copies is 1,200 stickers: more than one page holds
  await run.js(`$('#bc_items_body .bc-qty').val(400).trigger('input')`);
  run.check('400 units at 3 copies is called out', await shown('bc_ceiling_warning'));
  run.check('and cannot be printed', await printDisabled());
  await run.shot('dialog-too-many.png');

  await run.js(`$('#bc_unit_copies').val(2).trigger('input')`);
  run.check('400 units at 2 copies (800) fits again', !(await shown('bc_ceiling_warning')) && !(await printDisabled()));

  await run.js(`$('#bc_unit_mode').prop('checked', false).trigger('change')`);
  run.check('a plain label job counts the old way: ' + (await summary()), (await summary()).includes('400 labels'));
  run.check('and hides the unit controls', !(await shown('bc_unit_copies_row')) && !(await shown('bc_unit_date_row')));
  await run.js(`$('#bc_unit_mode').prop('checked', true).trigger('change')`);

  console.log('Print one unit sticker on every sticker size');
  for (const size of SIZES) {
    await printUnit(bed, { size });
    const s = await firstSticker();
    run.check(`${size}: prefix above the bars, suffix below, nothing clipped (${s.prefix} / ${s.suffix})`,
      !s.clipped && s.prefix === 'E2EBED01' && /^250912\d{4}$/.test(s.suffix) && s.ordered);
    await run.shot(`label-${size}.png`);
  }

  console.log('Switches and copies');
  await printUnit(bed, { show_bars: 0 });
  const noBars = JSON.parse(await run.js(`JSON.stringify({ bars: document.querySelectorAll('.bc-bars').length,
    lines: [...document.querySelectorAll('.bc-label .bc-code')].slice(0, 2).map((e) => e.textContent) })`));
  run.check('without the bars the two lines still read item barcode, then date and serial',
    noBars.bars === 0 && noBars.lines[0] === 'E2EBED01' && /^250912\d{4}$/.test(noBars.lines[1]));
  await printUnit(bed, { show_code: 0 });
  run.check('without the number neither line prints', (await run.js(`document.querySelectorAll('.bc-code').length`)) === 0);
  await printUnit(bed, { 'item_qty[]': 2, unit_copies: 3 });
  run.check('two units at three copies is six stickers',
    (await run.js(`document.querySelectorAll('.bc-label:not(.bc-blank)').length`)) === 6);

  run.checkScripts('no JavaScript errors from the label dialog', /barcode-label\.js/);
} catch (e) {
  failure = e;
}
await run.finish(failure);
