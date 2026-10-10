const puppeteer=require('puppeteer-core');
const chromium=require('@sparticuz/chromium').default;
(async()=>{
  const browser=await puppeteer.launch({executablePath:await chromium.executablePath(),args:chromium.args,headless:chromium.headless,
    env:{...process.env,LD_LIBRARY_PATH:'/tmp/al2023/lib'}});
  const page=await browser.newPage();
  page.on('requestfailed', r=>console.log('FAILED:', r.url().slice(0,120), r.failure()?.errorText));
  page.on('pageerror', e=>console.log('PAGEERROR:', String(e).slice(0,200)));
  await page.goto(process.env.PIXVA_BASE||'http://127.0.0.1:9414/',{waitUntil:'networkidle2',timeout:90000});
  await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
