// Shared by the browser checks in tests/ui/: headless Chrome driven over the DevTools protocol
// (Node 24+, no packages), with the e2e records of tests/e2e/fixtures.php created before and
// removed after, whatever happens. Environment overrides: BASE (http://localhost/sleepmakers),
// CHROME, PHP.
import { spawn, execFileSync } from 'node:child_process';
import { writeFileSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
export const BASE = process.env.BASE || 'http://localhost/sleepmakers';
const CHROME = process.env.CHROME || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PHP = process.env.PHP || 'C:/xampp/php/php.exe';
export const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const fixtures = (command) => execFileSync(PHP, [join(ROOT, 'tests/e2e/fixtures.php'), command], { encoding: 'utf8' });

// the cradle / keyboard: a key press as Chrome receives it from a real keyboard
const KEYS = { Enter: { code: 'Enter', windowsVirtualKeyCode: 13, text: '\r' }, Tab: { code: 'Tab', windowsVirtualKeyCode: 9 } };

export class UiRun {
  constructor(out, port) {
    this.out = out || null;
    this.port = port;
    this.failures = 0;
    this.dialogs = [];
    this.exceptions = [];
    this.id = 0;
    this.pending = new Map();
    this.waiters = [];
  }

  // e2e records up, Chrome up, connected; returns the fixtures (ids, password)
  async start() {
    this.fx = JSON.parse(fixtures('up'));
    this.chrome = spawn(CHROME, ['--headless=new', `--remote-debugging-port=${this.port}`,
      `--user-data-dir=${mkdtempSync(join(tmpdir(), 'uicheck'))}`, '--window-size=1600,1000', '--hide-scrollbars', 'about:blank'], { stdio: 'ignore' });
    for (let i = 0; i < 50 && !this.ws; i++) {
      try {
        const page = (await (await fetch(`http://127.0.0.1:${this.port}/json/list`)).json()).find((t) => t.type === 'page');
        if (page) this.ws = new WebSocket(page.webSocketDebuggerUrl);
      } catch { await sleep(200); }
    }
    if (!this.ws) throw new Error('Chrome did not start');
    await new Promise((r) => this.ws.addEventListener('open', r, { once: true }));
    this.ws.addEventListener('message', (e) => {
      const msg = JSON.parse(e.data);
      if (msg.id && this.pending.has(msg.id)) { this.pending.get(msg.id)(msg); this.pending.delete(msg.id); return; }
      if (msg.method === 'Page.javascriptDialogOpening') { this.dialogs.push(msg.params.message); this.send('Page.handleJavaScriptDialog', { accept: true }); }
      if (msg.method === 'Runtime.exceptionThrown') this.exceptions.push(msg.params.exceptionDetails.exception?.description || msg.params.exceptionDetails.text);
      for (const w of this.waiters.splice(0)) w(msg);
    });
    await this.send('Page.enable');
    await this.send('Runtime.enable');
    return this.fx;
  }

  check(label, ok) {
    console.log(`${ok ? '  PASS' : '  FAIL'}  ${label}`);
    if (!ok) this.failures++;
  }

  send(method, params = {}) {
    const n = ++this.id;
    this.ws.send(JSON.stringify({ id: n, method, params }));
    return new Promise((r) => this.pending.set(n, r));
  }

  loaded() {
    return new Promise((resolve) => {
      const wait = (msg) => (msg.method === 'Page.loadEventFired' ? resolve() : this.waiters.push(wait));
      this.waiters.push(wait);
    });
  }

  async js(expr) {
    const r = await this.send('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true });
    return r.result?.result?.value;
  }

  async go(url) { const l = this.loaded(); await this.send('Page.navigate', { url }); await l; await sleep(400); }

  // run something that loads a new page (a form post, a link, a reload), and wait for it
  async submit(expr, wait = 700) { const l = this.loaded(); await this.js(expr); await l; await sleep(wait); }

  async shot(name) {
    if (!this.out) return;
    const r = await this.send('Page.captureScreenshot', { format: 'png' });
    writeFileSync(join(this.out, name), Buffer.from(r.result.data, 'base64'));
  }

  async press(key) {
    const k = KEYS[key] || { text: key };
    await this.send('Input.dispatchKeyEvent', { type: 'keyDown', key, ...k });
    await this.send('Input.dispatchKeyEvent', { type: 'keyUp', key, code: k.code });
  }

  // the scanner's cradle: every character as a key press, then the scanner's suffix
  async scanner(codes, suffix) {
    for (const code of codes) {
      for (const c of code) await this.press(c);
      if (suffix) await this.press(suffix);
    }
  }

  insertText(text) { return this.send('Input.insertText', { text }); }

  // the main sign in, then the shop's sign in on the shop screen
  async signIn(user, shopId) {
    await this.go(`${BASE}/Public/logout.php`);
    await this.go(`${BASE}/Public/login.php`);
    await this.submit(`document.getElementById('user_name').value=${JSON.stringify(user)};document.getElementById('user_pwd').value=${JSON.stringify(this.fx.password)};document.querySelector('input[name=btn_log_in]').click()`);
    await this.js(`document.querySelector('.synnex-shop[data-shop-id="${shopId}"]').click()`);
    await sleep(800);
    await this.submit(`document.getElementById('shop_user_pwd').value=${JSON.stringify(this.fx.password)};document.querySelector('input[name=btn_shop_login]').click()`);
  }

  // no JavaScript errors from these scripts; errors from other scripts are listed, not failed
  checkScripts(label, pattern) {
    const ours = this.exceptions.filter((e) => pattern.test(e));
    this.check(label, ours.length === 0);
    for (const e of ours) console.log('        ' + e);
    const others = [...new Set(this.exceptions.filter((e) => !pattern.test(e)).map((e) => e.split('\n')[0]))];
    if (others.length) console.log('  note  errors from other scripts (not changed here): ' + others.join('; '));
  }

  // browser closed, e2e records removed; exits with the result
  async finish(error) {
    if (error) { this.failures++; console.log('  FAIL  ' + (error.stack || error)); }
    try { await this.go(`${BASE}/Public/logout.php`); } catch { /* browser gone */ }
    this.ws?.close();
    this.chrome?.kill();
    try { process.stdout.write(fixtures('down')); } catch (e) { console.log('  FAIL  e2e records not removed: ' + e.message); this.failures++; }
    console.log(this.failures === 0 ? '\nAll UI checks passed.' : `\n${this.failures} UI check(s) FAILED.`);
    process.exit(this.failures === 0 ? 0 : 1);
  }
}
