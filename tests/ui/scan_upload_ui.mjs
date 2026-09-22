// Browser check of the scanner upload dialog: types like the scanner's cradle does (fast keys,
// Enter or Tab after each code, or no separator at all) without clicking into any field first.
//
//   node tests/ui/scan_upload_ui.mjs [screenshot-folder]
//
// Needs Node 24+, Chrome and the local site; see tests/ui/harness.mjs.
import { UiRun, BASE, sleep } from './harness.mjs';

const run = new UiRun(process.argv[2], 9334);
const rows = () => run.js(`[...document.querySelectorAll('#scan_preview tbody tr')].map(tr => tr.cells[0].textContent.trim() + ':' + tr.cells[2].textContent.trim()).join('|')`);
const boxLines = () => run.js(`document.getElementById('scan_capture').value.split('\\n').filter(l => l.trim() !== '').length`);
const applyEnabled = () => run.js(`!document.getElementById('scan_apply').disabled`);
const dialogOpen = () => run.js(`document.getElementById('scan_upload_modal').classList.contains('show')`);

let failure = null;
try {
  const fx = await run.start();

  console.log('Sign in to the e2e Warehouse');
  await run.signIn('e2e_alice', fx.shops.W);
  run.check('signed in to the warehouse', (await run.js('location.pathname')).endsWith('/Public/home.php'));

  console.log('GRN - Scan / Upload');
  await run.submit(`(() => { const f = document.createElement('form'); f.method = 'post'; f.action = 'grn-details.php';
    for (const [k, v] of [['grn_header_id', '${fx.grn.open}'], ['grn_header_stat', '0']]) { const i = document.createElement('input'); i.name = k; i.value = v; f.appendChild(i); }
    document.body.appendChild(f); f.submit(); })()`);
  run.check('the GRN page shows Scan / Upload', await run.js(`!!document.querySelector('.btn-scan-upload')`));
  run.check('the dialog starts closed', !(await dialogOpen()));

  await run.js('document.activeElement && document.activeElement.blur()');
  await run.scanner(['E2EBED01', 'E2EBED01', 'E2ESHT01'], 'Enter');
  await sleep(1500);
  run.check('an upload with no field selected opens the dialog by itself', await dialogOpen());
  run.check('each code lands on its own line', (await boxLines()) === 3);
  run.check('the preview counts each product', (await rows()) === 'E2EBED01:2|E2ESHT01:1');
  run.check('and Add to GRN is enabled', await applyEnabled());
  await run.shot('scan-1-auto-open.png');

  await run.js(`document.getElementById('scan_clear').click()`);
  await run.scanner(['E2EBED01', 'E2EBED01'], 'Tab');
  await sleep(1500);
  run.check('a Tab suffix also gives one code per line (the focus stays)', (await boxLines()) === 2 && (await run.js(`document.activeElement.id`)) === 'scan_capture');
  run.check('and is counted', (await rows()) === 'E2EBED01:2');

  await run.js(`document.getElementById('scan_clear').click()`);
  await run.insertText('E2EBED01E2EBED01E2ESHT01');
  await run.press('Enter');
  await sleep(1500);
  run.check('codes run together are split on the shop\'s barcodes', (await rows()) === 'E2EBED01:2|E2ESHT01:1');

  await run.scanner(['NOPE123'], 'Enter');
  await sleep(1500);
  run.check('an unknown code is a problem line', await run.js(`[...document.querySelectorAll('#scan_preview tbody tr')].some(tr => tr.cells[0].textContent.trim() === 'NOPE123' && tr.textContent.includes('Problem'))`));
  run.check('and Add to GRN waits until it is left out', !(await applyEnabled()));
  await run.shot('scan-2-problem.png');
  await run.js(`[...document.querySelectorAll('.scan-leave-out')].find(b => b.dataset.key === 'NOPE123').click()`);
  await sleep(1500);
  run.check('Leave out enables Add to GRN again', await applyEnabled());

  await run.js(`document.getElementById('scan_apply').click()`);
  await sleep(2500);
  run.check('Add to GRN confirms what it added', run.dialogs.includes('Added 2 product(s), 3 item(s) to E2E-GRN.'));
  run.check('the dialog closes', !(await dialogOpen()));
  run.check('the GRN table shows the new lines', await run.js(`document.getElementById('tbl_grn_details').textContent.includes('E2EBED01') && document.getElementById('tbl_grn_details').textContent.includes('e2e Bedsheet')`));
  await run.shot('scan-3-added.png');

  console.log('Transfer - sending');
  await run.go(`${BASE}/Public/transfer-details.php?id=${fx.transfer}`);
  run.check('the sending shop\'s transfer page shows Scan / Upload', await run.js(`!!document.querySelector('.btn-scan-upload')`));
  await run.js(`document.querySelector('.btn-scan-upload').click()`);
  await run.scanner(['E2EBED01', 'E2EBED01', 'E2EBED01'], 'Enter');
  await sleep(1500);
  run.check('the preview shows the stock and the batch it comes from',
    (await run.js(`[...document.querySelectorAll('#scan_preview tbody tr')].map(tr => [...tr.cells].slice(0, 5).map(td => td.textContent.trim()).join('/')).join('|')`))
      === 'E2EBED01/e2e Bed/3/10/E2EB1 × 3');
  run.check('and Add to Transfer is enabled', await applyEnabled());
  await run.shot('scan-4-transfer.png');
  await run.js(`document.getElementById('scan_apply').click()`);
  await sleep(2500);
  run.check('Add to Transfer confirms what it added', run.dialogs.includes('Added 1 product(s), 3 item(s) to E2E-T1.'));
  run.check('and the transfer table shows the line', await run.js(`document.getElementById('tbl_transfer_detail').textContent.includes('e2e Bed')`));

  console.log('Transfer - receiving');
  await run.signIn('e2e_bob', fx.shops.S);
  await run.go(`${BASE}/Public/transfer-details.php?id=${fx.transfer}`);
  run.check('the receiving shop sees Scan received items', (await run.js(`document.querySelector('.btn-scan-upload')?.textContent.trim()`)) === 'Scan received items');
  await run.js('document.activeElement && document.activeElement.blur()');
  await run.scanner(['E2EBED01', 'E2EBED01'], 'Enter');
  await sleep(1500);
  run.check('the preview compares sent, scanned and received',
    (await run.js(`[...document.querySelectorAll('#scan_preview tbody tr')].map(tr => [...tr.cells].slice(2, 6).map(td => td.textContent.trim()).join('/')).join('|')`))
      === '3/2/2/Check Short 1');
  await run.shot('scan-5-receive.png');
  const before = run.dialogs.length;
  await run.js(`document.getElementById('scan_apply').click()`);
  await sleep(3000);
  run.check('a shortage is confirmed first, then applied', run.dialogs[before] === '1 item(s) short. They stay in the sending shop. Apply the received quantities?'
    && run.dialogs[before + 1] === 'Received quantities set on E2E-T1: 2 item(s) received, 1 short.');

  run.checkScripts('no JavaScript errors from the scanner dialog or the GRN / transfer scripts', /scan_upload\.js|grn_detail\.js|transfer_details\.js/);
} catch (e) {
  failure = e;
}
await run.finish(failure);
