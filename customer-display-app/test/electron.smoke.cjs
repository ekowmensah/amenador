const {_electron}=require('playwright-core');
const fs=require('fs'),path=require('path'),os=require('os'),http=require('http'),assert=require('assert');
const root=path.resolve(__dirname,'../..'), dir=fs.mkdtempSync(path.join(os.tmpdir(),'amenador-app-test-'));
let current={active:false,connected:false,order:null},revoked=false,pairCalls=0,screenAuth=0,app;
const token='a'.repeat(64);
const server=http.createServer((req,res)=>{
 const url=new URL(req.url,'http://localhost');res.setHeader('Cache-Control','no-store');
 if(url.pathname.endsWith('/pair')){let body='';req.on('data',d=>body+=d);req.on('end',()=>{const data=JSON.parse(body);assert.equal(data.code,'12345678');pairCalls++;res.setHeader('Content-Type','application/json');res.end(JSON.stringify({token,device_id:1}));});return;}
 if(url.pathname.endsWith('/state')||url.pathname.endsWith('/screen')){
  if(req.headers.authorization!=='Bearer '+token||revoked){res.writeHead(401);res.end('{}');return;}
  if(url.pathname.endsWith('/state')){res.setHeader('Content-Type','application/json');res.end(JSON.stringify(current));return;}
  screenAuth++;let html=fs.readFileSync(root+'/resources/views/sale_pos/customer_display.blade.php','utf8').replace(/\{\{[\s\S]*?\}\}/g,'').replace('href=""','href="/display.css"').replace('src=""','src="/art.svg"').replace('<script src=""','<script src="/display.js"');
  res.setHeader('Content-Type','text/html');res.end(html);return;
 }
 if(url.pathname==='/display.js'){res.setHeader('Content-Type','text/javascript');res.end(fs.readFileSync(root+'/public/js/customer-display.js'));return;}
 if(url.pathname==='/display.css'){res.setHeader('Content-Type','text/css');res.end(fs.readFileSync(root+'/public/css/customer-display.css'));return;}
 res.writeHead(404);res.end();
});
(async()=>{
 await new Promise(r=>server.listen(0,'127.0.0.1',r));const base='http://127.0.0.1:'+server.address().port+'/subdirectory/';
 const wrapper=path.join(dir,'entry.cjs');fs.writeFileSync(wrapper,`const {app}=require('electron');app.setLoginItemSettings=()=>{};app.setPath('userData',${JSON.stringify(path.join(dir,'profile'))});require(${JSON.stringify(root+'/customer-display-app/main.cjs')});`);
 app=await _electron.launch({executablePath:require('electron'),args:[wrapper],env:Object.fromEntries(Object.entries(process.env).filter(([key])=>key!=='ELECTRON_RUN_AS_NODE')),timeout:30000});
 const setup=await app.firstWindow();await setup.waitForSelector('#pair');
 await setup.locator('#server').fill(base);await setup.locator('#code').fill('12345678');await setup.locator('#pair').click();
 await setup.waitForSelector('#paired',{state:'visible',timeout:20000});assert.equal(pairCalls,1);assert(screenAuth>=1);
 const profile=JSON.parse(fs.readFileSync(path.join(dir,'profile/settings.json'),'utf8'));assert(profile.credential);assert(!JSON.stringify(profile).includes(token));
 await setup.locator('#allow-primary').check();await setup.locator('#save').click();
 const display=await app.waitForEvent('window',{predicate:p=>p.url().includes('/screen'),timeout:1000}).catch(()=>app.windows().find(p=>p.url().includes('/screen')));
 assert(display);await display.waitForSelector('#order-total');
 assert.equal(await display.evaluate(()=>typeof require),'undefined');assert.equal(await display.evaluate(()=>typeof window.amenadorSetup),'undefined');
 current={active:true,connected:true,order:{items:[{name:'<img src=x onerror=alert(1)> Beer',quantity:'12,345.50',amount:'GHS 125.00'}],location:'Bar',subtotal:'GHS 125.00',total:'GHS 125.00',discount:'0',tax:'0',adjustments:'0',has_discount:false,has_tax:false,has_adjustments:false}};
 await display.waitForFunction(()=>document.querySelector('#order-total').textContent==='GHS 125.00');assert.equal(await display.locator('.order-item img').count(),0);
 assert.equal(await display.locator('.order-item > span').first().textContent(),'12,345.50');
 assert(await app.evaluate(({BrowserWindow})=>BrowserWindow.getAllWindows().find(w=>w.webContents.getURL().includes('/screen')).isVisible()));
 await display.screenshot({path:path.join(os.tmpdir(),'amenador-desktop-app.png')});
 current={active:false,connected:false,order:null};await display.waitForFunction(()=>document.querySelector('#order-items').children.length===0);
 assert(!(await app.evaluate(({BrowserWindow})=>BrowserWindow.getAllWindows().find(w=>w.webContents.getURL().includes('/screen')).isVisible())));
 revoked=true;await setup.waitForSelector('#pair-form',{state:'visible',timeout:10000});
 assert.equal(JSON.parse(fs.readFileSync(path.join(dir,'profile/settings.json'),'utf8')).credential,'');
 console.log('PASS real Electron app: pairing, subdirectory server, encrypted credentials, authenticated shared display, no Node access, escaped items, live quantities/totals, inactivity hide and revoked-device re-pairing.');
})().catch(e=>{console.error(e);process.exitCode=1}).finally(async()=>{if(app)await app.close();server.close();});
