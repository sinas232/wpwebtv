const puppeteer=require('puppeteer-core');
const chromium=require('@sparticuz/chromium').default;
(async()=>{
  const browser=await puppeteer.launch({executablePath:await chromium.executablePath(),args:chromium.args,headless:chromium.headless,env:{...process.env,LD_LIBRARY_PATH:'/tmp/al2023/lib'}});
  const page=await browser.newPage();
  await page.goto(process.env.PIXVA_BASE||'http://127.0.0.1:9414/',{waitUntil:'networkidle2',timeout:90000});
  const out=await page.evaluate(()=>{
    const res=[];
    for(const s of document.styleSheets){
      let rules; try{rules=s.cssRules}catch(e){continue}
      const href=(s.href||'inline').slice(-60);
      for(const r of rules){
        if(r.selectorText && (r.selectorText.includes('.cta')||r.selectorText==='.cta'||r.selectorText.includes('elementor-widget-button'))){
          res.push(href+' :: '+r.selectorText.slice(0,80)+' => bg:'+String(r.style.background||r.style.backgroundColor||'').slice(0,50));
        }
      }
      res.push(href+' totalRules='+rules.length);
    }
    return res;
  });
  out.forEach(l=>console.log(l));
  await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
