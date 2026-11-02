// Real-browser check of public/js/searchable-select.js (headless Chromium through puppeteer, which the project already uses for PDFs).
//   node tests/Js/searchable-select.mjs            -> prints PASS/FAIL lines, exits 1 on any failure
//   node tests/Js/searchable-select.mjs --shots    -> also writes light/dark screenshots to storage/app/searchable-select-*.png
import puppeteer from 'puppeteer';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../public');
const url = (p) => 'file://' + path.join(root, p);
const shots = process.argv.includes('--shots');

const page_html = `<!doctype html><html lang="en"><head><meta charset="utf-8">
<link rel="stylesheet" href="${url('vendor/tom-select/tom-select.bootstrap5.min.css')}"><link rel="stylesheet" href="${url('css/searchable-select.css')}">
<style>body{font-family:sans-serif;padding:20px;width:420px} :root{--bs-body-bg:#fff;--bs-body-color:#212529;--bs-border-color:#ced4da;--bs-border-radius:.375rem} [data-bs-theme=dark]{--bs-body-bg:#212529;--bs-body-color:#dee2e6;--bs-border-color:#495057} body{background:var(--bs-body-bg);color:var(--bs-body-color)} select{width:100%;padding:.375rem}</style></head><body>
<form id="f" onsubmit="window.submitted=(window.submitted||0)+1; return false;">
  <select id="dept" name="dept" required><option value="">Choose a department</option><option value="1">Cardiology</option><option value="2">Orthopaedics</option><option value="3">Paediatrics</option><option value="4">ঢাকা মেডিকেল বিভাগ</option></select>
  <select id="opt" name="opt"><option value="">Any ward</option><option value="a">Ward A</option><option value="b">Ward B</option></select>
  <select id="multi" name="multi[]" multiple><option value="x">Alpha</option><option value="y">Beta</option><option value="z">Gamma</option></select>
  <select id="plain" name="plain" data-plain><option>One</option><option>Two</option></select>
  <select id="inline" name="inline" onchange="window.inlineFired=(window.inlineFired||0)+1"><option value="">-</option><option value="p">Pending</option><option value="q">Queued</option></select>
  <select id="org" name="org"><option value="">Organization</option><option value="1">Premier</option></select>
  <select id="pat" name="pat" required data-remote="https://hn.test/lookup/patients" data-placeholder="Type a name"><option value="">Select Patient</option><option value="9" selected>Existing Person (P-9)</option></select>
  <select id="enc" name="enc" data-remote="https://hn.test/lookup/encounters" data-remote-depends="#pat" data-remote-param="patient_id"><option value="">Visit</option></select>
  <select id="branch" name="branch" disabled required><option value="">Branch</option></select>
</form>
<script src="${url('vendor/tom-select/tom-select.complete.min.js')}"></script><script src="${url('js/searchable-select.js')}"></script></body></html>`;

let failures = 0;
const check = (name, ok, extra = '') => { console.log((ok ? 'PASS ' : 'FAIL ') + name + (ok ? '' : '  ' + extra)); if (!ok) failures++; };
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const open = async (page, sel) => { await page.click(sel); await sleep(200); };   // a person types well after the click, not in the same instant

// Uses the Chrome named in BROWSERSHOT_CHROME_PATH (the one the PDF printing uses), then a Chrome installed on the machine, then puppeteer's own download.
const candidates = [process.env.BROWSERSHOT_CHROME_PATH, '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', '/usr/bin/google-chrome', '/usr/bin/chromium', '/usr/bin/chromium-browser'];
const { existsSync } = await import('node:fs');
const executablePath = candidates.find((c) => c && existsSync(c));
const browser = await puppeteer.launch({ headless: true, executablePath, args: ['--no-sandbox', '--allow-file-access-from-files'] });
const page = await browser.newPage();
// a pretend server for the remote dropdown: it answers by phone or name, so a result need not contain the typed text
const asked = [];
const askedVisits = [];
await page.setRequestInterception(true);
page.on('request', (req) => {
  const u = new URL(req.url());
  if (u.hostname === 'hn.test') {
    const cors = { 'Access-Control-Allow-Origin': '*', 'Access-Control-Allow-Headers': '*', 'Access-Control-Allow-Methods': 'GET, OPTIONS' };
    if (req.method() === 'OPTIONS') { req.respond({ status: 204, headers: cors }); return; }   // the browser's preflight (the real app is same-origin and has none)
    if (u.pathname === '/lookup/encounters') {
      const pid = u.searchParams.get('patient_id');
      askedVisits.push(pid);
      req.respond({ status: 200, contentType: 'application/json', headers: cors, body: JSON.stringify(pid ? [{ value: 'e' + pid, text: 'ENC for patient ' + pid }] : []) });
      return;
    }
    const q = u.searchParams.get('q') || '';
    asked.push(q);
    const all = [{ value: '1', text: 'Abdul Karim (P-1)' }, { value: '2', text: 'Rahima Begum (P-2)' }, { value: '3', text: 'Rahim Uddin (P-3)' }];
    const rows = q === '' ? all : q === '017' ? [all[0]] : all.filter((r) => r.text.toLowerCase().includes(q.toLowerCase()));
    req.respond({ status: 200, contentType: 'application/json', headers: cors, body: JSON.stringify(rows) });
  } else { req.continue(); }
});
const errors = [];
page.on('pageerror', (e) => errors.push(String(e)));
await page.setViewport({ width: 900, height: 700 });
const fs = await import('node:fs');
const tmp = path.join(root, '..', 'storage', 'app', 'searchable-select-test.html');
fs.writeFileSync(tmp, page_html);
if (process.env.TRIALS) {   // does typed text ever get lost right after opening a dropdown?
  const seen = {};
  for (let i = 0; i < Number(process.env.TRIALS); i++) {
    await page.goto('file://' + tmp); await sleep(Number(process.env.WAIT || 300));
    await page.click('#dept + .ts-wrapper .ts-control');
    await sleep(Number(process.env.GAP || 0));
    await page.keyboard.type('ortho');
    const v = await page.evaluate(() => document.getElementById('dept').tomselect.control_input.value);
    await sleep(500);
    const v2 = await page.evaluate(() => document.getElementById('dept').tomselect.control_input.value);
    const k = v + ' -> ' + v2; seen[k] = (seen[k] || 0) + 1;
  }
  console.log(JSON.stringify(seen)); fs.unlinkSync(tmp); await browser.close(); process.exit(0);
}
await page.goto('file://' + tmp);
await sleep(300);

const count = (sel) => page.$$eval(sel, (e) => e.length);
check('every dropdown except the opted-out one is enhanced', (await count('.ts-wrapper')) === 8, 'wrappers=' + (await count('.ts-wrapper')));
check('the opted-out dropdown stays native', (await page.$eval('#plain', (e) => !e.tomselect)));

// typing filters, selecting sets the original and fires change
await open(page, '#dept + .ts-wrapper .ts-control');
await page.keyboard.type('ortho');
await sleep(150);
const options = (id) => page.evaluate((i) => [...document.getElementById(i).tomselect.dropdown_content.querySelectorAll('.option')].map((x) => x.textContent.trim()), id);
const shown = await options('dept');
if (process.env.DBG) { for (const ms of [0, 300, 900]) { await sleep(ms); console.log('DBG +' + ms, await page.evaluate(() => { const t = document.getElementById('dept').tomselect; return JSON.stringify({ active: document.activeElement.id, q: t.lastQuery, input: t.control_input.value, open: t.isOpen, n: t.dropdown_content.querySelectorAll('.option').length }); })); } }
check('typing filters the list', shown.length === 1 && shown[0] === 'Orthopaedics', JSON.stringify(shown));
await page.keyboard.press('Enter');
await sleep(100);
check('choosing sets the real field', (await page.$eval('#dept', (e) => e.value)) === '2');

// Bangla search
await page.click('#dept + .ts-wrapper .ts-control');
await page.keyboard.down('Control'); await page.keyboard.press('a'); await page.keyboard.up('Control');
await page.keyboard.type('ঢাকা');
await sleep(150);
const bn = await options('dept');
check('Bangla text can be searched', bn.length === 1 && bn[0].includes('ঢাকা'), JSON.stringify(bn));
await page.keyboard.press('Escape');

// no match message
await open(page, '#dept + .ts-wrapper .ts-control');
await page.keyboard.type('zzzz');
await sleep(150);
check('says so when nothing matches', (await page.evaluate(() => document.getElementById('dept').tomselect.dropdown_content.querySelector('.no-results')?.textContent.trim())) === 'No matches found');
await page.keyboard.press('Escape');


// a remote (server-searched) dropdown
await page.click('#pat + .ts-wrapper .ts-control');
await sleep(500);
check('opening it asks the server for the first few', asked.includes(''), JSON.stringify(asked));
check('the patient already chosen stays chosen', (await page.$eval('#pat', (e) => e.value)) === '9');
await page.keyboard.type('rahim');
await sleep(700);
const rem = (await options('pat')).filter((t) => !t.startsWith('Existing Person'));
check('typing asks the server and shows what it answers', asked.includes('rahim') && rem.length === 2 && rem.every((t) => t.toLowerCase().includes('rahim')), JSON.stringify({ asked, rem }));
for (let i = 0; i < 5; i++) { await page.keyboard.press('Backspace'); }   // rub out "rahim"
await page.keyboard.type('017');
await sleep(900);
const byPhone = (await options('pat')).filter((t) => !t.startsWith('Existing Person'));
check('a result that does not contain the typed text (found by phone) is still shown', byPhone.length === 1 && byPhone[0].startsWith('Abdul Karim'), JSON.stringify({ asked, byPhone }));
await page.keyboard.press('Enter');
await sleep(200);
check('choosing a server result sets the real field', (await page.$eval('#pat', (e) => e.value)) === '1');
check('and its label', (await page.$eval('#pat + .ts-wrapper .item', (e) => e.textContent.trim())) === 'Abdul Karim (P-1)');


// a dropdown that depends on another (the visits of the chosen patient)
check('a dependent dropdown is shut until the other has a value (patient 9 is chosen, so it starts open)', await page.evaluate(() => !document.getElementById('enc').tomselect.isLocked));
await page.evaluate(() => document.getElementById('pat').tomselect.clear());
await sleep(200);
check('and shuts again when the patient is cleared', await page.evaluate(() => document.getElementById('enc').tomselect.isLocked));
await page.evaluate(() => document.getElementById('pat').tomselect.addOption({ value: '5', text: 'Patient Five (P-5)' }));
await page.evaluate(() => document.getElementById('pat').tomselect.setValue('5'));
await sleep(300);
await open(page, '#enc + .ts-wrapper .ts-control');
await sleep(600);
check('opening it asks for the chosen patient only', askedVisits.includes('5'), JSON.stringify(askedVisits));
check('and lists that patient\'s visits', (await options('enc')).includes('ENC for patient 5'));
await page.keyboard.press('Enter');
await sleep(200);
check('a visit can be chosen', (await page.$eval('#enc', (e) => e.value)) === 'e5');
await page.evaluate(() => { const t = document.getElementById('pat').tomselect; t.addOption({ value: '6', text: 'Patient Six (P-6)' }); t.setValue('6'); });
await sleep(300);
check('changing the patient clears the visit', (await page.$eval('#enc', (e) => e.value)) === '');
await page.evaluate(() => document.getElementById('pat').tomselect.setValue('1'));
await sleep(200);

// inline onchange fires when a choice is made
await open(page, '#inline + .ts-wrapper .ts-control');
await page.keyboard.type('queu');
await sleep(100);
await page.keyboard.press('Enter');
await sleep(100);
check('an inline onchange handler still runs', (await page.evaluate(() => window.inlineFired || 0)) >= 1);

// programmatic change
await page.evaluate(() => { const s = document.getElementById('opt'); s.value = 'b'; s.dispatchEvent(new Event('change', { bubbles: true })); });
await sleep(100);
check('select.value = … is shown by the control', (await page.$eval('#opt + .ts-wrapper .item', (e) => e.textContent.trim())) === 'Ward B');

// clear button on a non-required dropdown with an empty option
check('an optional dropdown has a clear button', (await page.$('#opt + .ts-wrapper .clear-button')) !== null);
check('a required dropdown has none', (await page.$('#dept + .ts-wrapper .clear-button')) === null);

// scripts that refill a dropdown (the sign-in branch list) and enable it
await page.evaluate(() => {
  const b = document.getElementById('branch');
  b.innerHTML = '<option value="">Branch</option><option value="10">Main (MAIN)</option><option value="11">Annex (ANX)</option>';
  b.disabled = false;
});
await sleep(300);
check('options a script adds appear in the search list', await page.evaluate(() => Object.keys(document.getElementById('branch').tomselect.options).filter((k) => k !== '').length === 2));
check('a script enabling it enables the control', await page.evaluate(() => !document.getElementById('branch').tomselect.isDisabled));
await open(page, '#branch + .ts-wrapper .ts-control');
await page.keyboard.type('annex');
await sleep(100);
await page.keyboard.press('Enter');
await sleep(100);
check('a refilled dropdown can be chosen from', (await page.$eval('#branch', (e) => e.value)) === '11');
await page.evaluate(() => { document.getElementById('branch').disabled = true; });
await sleep(200);
check('disabling is followed', await page.evaluate(() => document.getElementById('branch').tomselect.isDisabled));
await page.evaluate(() => { document.getElementById('branch').disabled = false; });
await sleep(200);

// required: empty stops the form, chosen lets it through
await page.evaluate(() => { document.getElementById('dept').tomselect.clear(); window.submitted = 0; });
await page.evaluate(() => { document.getElementById('branch').tomselect.setValue('10'); });
await page.evaluate(() => document.getElementById('f').dispatchEvent(new Event('submit', { cancelable: true, bubbles: true })));
await sleep(100);
check('an empty required dropdown blocks the form', (await page.evaluate(() => window.submitted || 0)) === 0);
check('and is outlined', (await page.$eval('#dept + .ts-wrapper', (e) => e.classList.contains('is-invalid'))));
await page.evaluate(() => document.getElementById('dept').tomselect.setValue('1'));
await sleep(100);
check('choosing clears the outline', !(await page.$eval('#dept + .ts-wrapper', (e) => e.classList.contains('is-invalid'))));
await page.evaluate(() => document.getElementById('f').dispatchEvent(new Event('submit', { cancelable: true, bubbles: true })));
await sleep(100);
check('with everything filled the form goes through', (await page.evaluate(() => window.submitted || 0)) === 1);

// reset
await page.evaluate(() => document.getElementById('f').reset());
await sleep(150);
check('reset puts the choices back', (await page.$eval('#dept', (e) => e.value)) === '');

// a dropdown added later
await page.evaluate(() => { const s = document.createElement('select'); s.id = 'late'; s.innerHTML = '<option value="">Late</option><option value="1">Late one</option>'; document.getElementById('f').appendChild(s); });
await sleep(300);
check('a dropdown added later is enhanced', (await page.$('#late + .ts-wrapper')) !== null);

// many dropdowns
await page.evaluate(() => { const box = document.createElement('div'); for (let i = 0; i < 90; i++) { const s = document.createElement('select'); s.className = 'bulk'; s.innerHTML = '<option value="">-</option><option value="1">One</option><option value="2">Two</option>'; box.appendChild(s); } document.body.appendChild(box); });
await sleep(1500);
check('90 dropdowns on a page are all enhanced', (await count('select.bulk.tomselected')) === 90, 'got ' + (await count('select.bulk.tomselected')));

// page never threw
check('no script errors', errors.length === 0, errors.join(' | '));

if (shots) {
  await page.evaluate(() => { document.querySelectorAll('select.bulk').forEach((s) => s.closest('div').remove()); });
  await page.click('#dept + .ts-wrapper .ts-control');
  await sleep(200);
  await page.screenshot({ path: path.join(root, '..', 'storage', 'app', 'searchable-select-light.png') });
  await page.evaluate(() => document.documentElement.setAttribute('data-bs-theme', 'dark'));
  await sleep(200);
  await page.screenshot({ path: path.join(root, '..', 'storage', 'app', 'searchable-select-dark.png') });
}
fs.unlinkSync(tmp);
await browser.close();
console.log(failures === 0 ? '\nALL PASSED' : `\n${failures} FAILED`);
process.exit(failures === 0 ? 0 : 1);
