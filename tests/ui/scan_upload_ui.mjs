// Browser check of the scanner upload dialog: drives headless Chrome over the DevTools
// protocol and types like the scanner's cradle does (fast keys, Enter or Tab after each code,
// or no separator at all) without clicking into any field first.
//
//   node tests/ui/scan_upload_ui.mjs [screenshot-folder]
//
// Needs Node 24+, Chrome, and the local site at BASE with its PHP. Environment overrides:
// BASE (http://localhost/sleepmakers), CHROME, PHP. It creates its own e2e records
// (tests/e2e/fixtures.php) and removes them at the end, whatever happens.
import { spawn, execFileSync } from 'node:child_process';
import { writeFileSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const OUT = process.argv[2] || null;
const BASE = process.env.BASE || 'http://localhost/sleepmakers';
const CHROME = process.env.CHROME || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PHP = process.env.PHP || 'C:/xampp/php/php.exe';
const PORT = 9334;
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const fixtures = (command) => execFileSync(PHP, [join(ROOT, 'tests/e2e/fixtures.php'), command], { encoding: 'utf8' });

let failures = 0;
const check = (label, ok) => { console.log(`${ok ? '  PASS' : '  FAIL'}  ${label}`); if (!ok) failures++; };

const fx = JSON.parse(fixtures('up'));
const chrome = spawn(CHROME, ['--headless=new', `--remote-debugging-port=${PORT}`,
  `--user-data-dir=${mkdtempSync(join(tmpdir(), 'scanui'))}`, '--window-size=1600,1000', '--hide-scrollbars', 'about:blank'], { stdio: 'ignore' });

let ws;
let id = 0;
const pending = new Map();
const waiters = [];
const dialogs = [];
const exceptions = [];

function send(method, params = {}) {
  const n = ++id;
  ws.send(JSON.stringify({ id: n, method, params }));
  return new Promise((r) => pending.set(n, r));
}
function loaded() {
  return new Promise((resolve) => {
    const wait = (msg) => (msg.method === 'Page.loadEventFired' ? resolve() : waiters.push(wait));
    waiters.push(wait);
  });
}
async function js(expr) {
  const r = await send('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true });
  return r.result?.result?.value;
}
async function go(url) { const l = loaded(); await send('Page.navigate', { url }); await l; await sleep(400); }
async function submit(expr) { const l = loaded(); await js(expr); await l; await sleep(700); }
async function shot(name) {
  if (!OUT) return;
  const r = await send('Page.captureScreenshot', { format: 'png' });
  writeFileSync(join(OUT, name), Buffer.from(r.result.data, 'base64'));
}

// the cradle: every character as a real key press, then the scanner's suffix
const KEYS = { Enter: { code: 'Enter', windowsVirtualKeyCode: 13, text: '\r' }, Tab: { code: 'Tab', windowsVirtualKeyCode: 9 } };
async function press(key) {
  const k = KEYS[key] || { text: key };
  await send('Input.dispatchKeyEvent', { type: 'keyDown', key, ...k });
  await send('Input.dispatchKeyEvent', { type: 'keyUp', key, code: k.code });
}
async function scanner(codes, suffix) {
  for (const code of codes) {
    for (const c of code) await press(c);
    if (suffix) await press(suffix);
  }
}
const rows = () => js(`[...document.querySelectorAll('#scan_preview tbody tr')].map(tr => tr.cells[0].textContent.trim() + ':' + tr.cells[2].textContent.trim()).join('|')`);
const boxLines = () => js(`document.getElementById('scan_capture').value.split('\\n').filter(l => l.trim() !== '').length`);
const applyEnabled = () => js(`!document.getElementById('scan_apply').disabled`);
const dialogOpen = () => js(`document.getElementById('scan_upload_modal').classList.contains('show')`);

try {
  for (let i = 0; i < 50 && !ws; i++) {
    try {
      const page = (await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json()).find((t) => t.type === 'page');
      if (page) ws = new WebSocket(page.webSocketDebuggerUrl);
    } catch { await sleep(200); }
  }
  if (!ws) throw new Error('Chrome did not start');
  await new Promise((r) => ws.addEventListener('open', r, { once: true }));
  ws.addEventListener('message', (e) => {
    const msg = JSON.parse(e.data);
    if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg); pending.delete(msg.id); return; }
    if (msg.method === 'Page.javascriptDialogOpening') { dialogs.push(msg.params.message); send('Page.handleJavaScriptDialog', { accept: true }); }
    if (msg.method === 'Runtime.exceptionThrown') exceptions.push(msg.params.exceptionDetails.exception?.description || msg.params.exceptionDetails.text);
    for (const w of waiters.splice(0)) w(msg);
  });
  await send('Page.enable');
  await send('Runtime.enable');

  console.log('Sign in to the e2e Warehouse');
  await go(`${BASE}/Public/logout.php`);
  await go(`${BASE}/Public/login.php`);
  await submit(`document.getElementById('user_name').value='e2e_alice';document.getElementById('user_pwd').value=${JSON.stringify(fx.password)};document.querySelector('input[name=btn_log_in]').click()`);
  await js(`document.querySelector('.synnex-shop[data-shop-id="${fx.shops.W}"]').click()`);
  await sleep(800);
  await submit(`document.getElementById('shop_user_pwd').value=${JSON.stringify(fx.password)};document.querySelector('input[name=btn_shop_login]').click()`);
  check('signed in to the warehouse', (await js('location.pathname')).endsWith('/Public/home.php'));

  console.log('GRN - Scan / Upload');
  await submit(`(() => { const f = document.createElement('form'); f.method = 'post'; f.action = 'grn-details.php';
    for (const [k, v] of [['grn_header_id', '${fx.grn.open}'], ['grn_header_stat', '0']]) { const i = document.createElement('input'); i.name = k; i.value = v; f.appendChild(i); }
    document.body.appendChild(f); f.submit(); })()`);
  check('the GRN page shows Scan / Upload', await js(`!!document.querySelector('.btn-scan-upload')`));
  check('the dialog starts closed', !(await dialogOpen()));

  await js('document.activeElement && document.activeElement.blur()');
  await scanner(['E2EBED01', 'E2EBED01', 'E2ESHT01'], 'Enter');
  await sleep(1500);
  check('an upload with no field selected opens the dialog by itself', await dialogOpen());
  check('each code lands on its own line', (await boxLines()) === 3);
  check('the preview counts each product', (await rows()) === 'E2EBED01:2|E2ESHT01:1');
  check('and Add to GRN is enabled', await applyEnabled());
  await shot('scan-1-auto-open.png');

  await js(`document.getElementById('scan_clear').click()`);
  await scanner(['E2EBED01', 'E2EBED01'], 'Tab');
  await sleep(1500);
  check('a Tab suffix also gives one code per line (the focus stays)', (await boxLines()) === 2 && (await js(`document.activeElement.id`)) === 'scan_capture');
  check('and is counted', (await rows()) === 'E2EBED01:2');

  await js(`document.getElementById('scan_clear').click()`);
  await send('Input.insertText', { text: 'E2EBED01E2EBED01E2ESHT01' });
  await press('Enter');
  await sleep(1500);
  check('codes run together are split on the shop\'s barcodes', (await rows()) === 'E2EBED01:2|E2ESHT01:1');

  await scanner(['NOPE123'], 'Enter');
  await sleep(1500);
  check('an unknown code is a problem line', (await js(`[...document.querySelectorAll('#scan_preview tbody tr')].some(tr => tr.cells[0].textContent.trim() === 'NOPE123' && tr.textContent.includes('Problem'))`)));
  check('and Add to GRN waits until it is left out', !(await applyEnabled()));
  await shot('scan-2-problem.png');
  await js(`[...document.querySelectorAll('.scan-leave-out')].find(b => b.dataset.key === 'NOPE123').click()`);
  await sleep(1500);
  check('Leave out enables Add to GRN again', await applyEnabled());

  await js(`document.getElementById('scan_apply').click()`);
  await sleep(2500);
  check('Add to GRN confirms what it added', dialogs.includes('Added 2 product(s), 3 item(s) to E2E-GRN.'));
  check('the dialog closes', !(await dialogOpen()));
  check('the GRN table shows the new lines', await js(`document.getElementById('tbl_grn_details').textContent.includes('E2EBED01') && document.getElementById('tbl_grn_details').textContent.includes('e2e Bedsheet')`));
  await shot('scan-3-added.png');

  // scenarios of later tasks are added above this line
  check('no JavaScript errors on the pages', exceptions.length === 0);
  for (const e of exceptions) console.log('        ' + e);
} catch (e) {
  failures++;
  console.log('  FAIL  ' + (e.stack || e));
} finally {
  try { await go(`${BASE}/Public/logout.php`); } catch { /* browser gone */ }
  ws?.close();
  chrome.kill();
  process.stdout.write(fixtures('down'));
}
console.log(failures === 0 ? '\nAll UI checks passed.' : `\n${failures} UI check(s) FAILED.`);
process.exit(failures === 0 ? 0 : 1);
