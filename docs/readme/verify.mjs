// Preview and validate README artwork using an isolated headless Chrome session.
// Usage (after preview.php): node docs/readme/verify.mjs [preview.html]
// No npm dependencies. Screenshots/reports go to the system temporary directory.
import { spawn } from 'node:child_process';
import { readFile, writeFile, mkdtemp, readdir, access } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { dirname, resolve, join } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const artwork = dirname(fileURLToPath(import.meta.url));
const root = resolve(artwork, '../..');
const markdown = await readFile(join(root, 'README.md'), 'utf8');
const references = [...markdown.matchAll(/(?:src|srcset|href)="([^"]+)"|\]\(([^)]+)\)/g)].map(m=>m[1]||m[2]);
await Promise.all(references.filter(r=>!r.startsWith('#')).map(r=>access(join(root,r))));
if (/\uFEFF|Â|â†|ï»¿/.test(markdown)) throw new Error('Unexpected README encoding artifacts');
const chrome = process.env.CHROME_PATH || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const preview = resolve(process.argv[2] || join(tmpdir(), 'cinemax-readme-preview.html'));
const output = await mkdtemp(join(tmpdir(), 'cinemax-readme-qa-'));
const child = spawn(chrome, ['--headless', '--disable-gpu', '--no-sandbox', '--hide-scrollbars', '--no-first-run', '--no-default-browser-check', '--remote-debugging-port=0', `--user-data-dir=${join(output, 'chrome-profile')}`], { windowsHide: true, stdio: ['ignore', 'ignore', 'pipe'] });
const endpoint = await new Promise((done, fail) => {
  let log = '';
  const timer = setTimeout(() => fail(new Error('Chrome startup timeout')), 15000);
  child.once('error', fail);
  child.stderr.on('data', chunk => {
    log += chunk;
    const match = log.match(/DevTools listening on (ws:\/\/[^\s]+)/);
    if (match) { clearTimeout(timer); done(match[1]); }
  });
});
const socket = new WebSocket(endpoint);
await new Promise((done, fail) => { socket.onopen = done; socket.onerror = fail; });
let serial = 0;
const pending = new Map();
socket.onmessage = ({ data }) => {
  const packet = JSON.parse(data);
  if (packet.id && pending.has(packet.id)) {
    const { done, fail, timer } = pending.get(packet.id);
    clearTimeout(timer); pending.delete(packet.id);
    packet.error ? fail(new Error(packet.error.message)) : done(packet.result);
  }
};
function send(method, params = {}, sessionId) {
  const id = ++serial;
  return new Promise((done, fail) => {
    const timer = setTimeout(() => { pending.delete(id); fail(new Error(`${method}: timeout`)); }, 15000);
    pending.set(id, { done, fail, timer });
    socket.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }));
  });
}
let session;
const pause = ms => new Promise(done => setTimeout(done, ms));
const evaluate = async expression => {
  const result = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true }, session);
  if (result.exceptionDetails) throw new Error(JSON.stringify(result.exceptionDetails));
  return result.result.value;
};
async function navigate(url) {
  await send('Page.navigate', { url }, session);
  for (let i=0;i<100;i++) {
    if (await evaluate('document.readyState === "complete"')) return;
    await pause(50);
  }
  throw new Error(`Page did not finish loading: ${url}`);
}

try {
  const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
  session = (await send('Target.attachToTarget', { targetId, flatten: true })).sessionId;
  await send('Page.enable', {}, session);
  const report = { layouts: [], artwork: [], motion: {} };
  for (const mode of [
    {name:'desktop-light',width:1100,theme:'light'},
    {name:'desktop-dark',width:1100,theme:'dark'},
    {name:'tablet',width:768,theme:'dark'},
    {name:'mobile',width:390,theme:'dark'},
    {name:'mobile-small',width:320,theme:'light'},
    {name:'setup-expanded',width:1100,theme:'dark',expanded:true},
    {name:'mobile-expanded',width:390,theme:'dark',expanded:true},
  ]) {
    await send('Emulation.setDeviceMetricsOverride', { width:mode.width,height:1000,deviceScaleFactor:1,mobile:false }, session);
    await send('Emulation.setEmulatedMedia', { features:[{name:'prefers-color-scheme',value:mode.theme},{name:'prefers-reduced-motion',value:'reduce'}] }, session);
    await navigate(pathToFileURL(preview).href + (mode.expanded ? '?expanded' : ''));
    await evaluate('Promise.all([...document.images].map(i => i.decode().catch(() => null)))');
    const layout = await evaluate(`({width:innerWidth,scrollWidth:document.documentElement.scrollWidth,height:document.documentElement.scrollHeight,images:[...document.images].map(i=>({source:i.currentSrc.split('/').pop(),ok:i.complete&&i.naturalWidth>0})),openDetails:document.querySelectorAll('details[open]').length,links:[...document.querySelectorAll('a[href^="#"]')].map(a=>({href:a.hash,ok:!!document.getElementById(a.hash.slice(1))}))})`);
    const {data} = await send('Page.captureScreenshot', {format:'png',captureBeyondViewport:true,fromSurface:true}, session);
    await writeFile(join(output,`${mode.name}.png`),Buffer.from(data,'base64'));
    report.layouts.push({name:mode.name,...layout});
  }
  for (const filename of (await readdir(artwork)).filter(n=>n.endsWith('.svg'))) {
    await navigate(pathToFileURL(join(artwork,filename)).href);
    const result = await evaluate(`(() => {const s=document.documentElement, w=s.viewBox.baseVal.width,h=s.viewBox.baseVal.height;return {width:w,height:h,overflow:[...document.querySelectorAll('text')].filter(t=>{const b=t.getBBox(),m=t.getCTM();return [[b.x,b.y],[b.x+b.width,b.y],[b.x,b.y+b.height],[b.x+b.width,b.y+b.height]].some(([x,y])=>{const p=new DOMPoint(x,y).matrixTransform(m);return p.x<0||p.y<0||p.x>w-10||p.y>h-8})}).map(t=>t.textContent)}})()`);
    const source = await readFile(join(artwork,filename),'utf8');
    report.artwork.push({name:filename,...result,selfContained:!/<script|<foreignObject|https?:|@import|@font-face/.test(source.replace('http://www.w3.org/2000/svg',''))});
  }
  await navigate(pathToFileURL(join(artwork,'hero.svg')).href);
  report.motion.reduced = await evaluate('document.getAnimations().length');
  await send('Emulation.setEmulatedMedia', {features:[{name:'prefers-reduced-motion',value:'no-preference'}]}, session);
  await pause(100);
  report.motion.enabled = await evaluate('document.getAnimations().length');
  const before = await evaluate('getComputedStyle(document.querySelector(".float")).transform');
  await pause(300);
  const after = await evaluate('getComputedStyle(document.querySelector(".float")).transform');
  report.motion.changes = before !== after;
  await writeFile(join(output,'report.json'),JSON.stringify(report,null,2));
  console.log(JSON.stringify({output,layouts:report.layouts.map(x=>({name:x.name,width:x.width,overflow:x.scrollWidth>x.width,images:x.images.every(i=>i.ok),openDetails:x.openDetails,mobileArtwork:x.images.filter(i=>i.source.includes('-mobile')).length,links:x.links.every(l=>l.ok)})),artwork:report.artwork,motion:report.motion},null,2));
  if (report.layouts.some(x=>x.scrollWidth>x.width||x.images.some(i=>!i.ok)||x.links.some(l=>!l.ok))||report.artwork.some(x=>x.overflow.length||!x.selfContained)||report.motion.reduced!==0||!report.motion.changes) process.exitCode=1;
} finally {
  await send('Browser.close').catch(()=>{});
  socket.close();
  child.kill();
}
