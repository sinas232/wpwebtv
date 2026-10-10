const puppeteer=require('puppeteer-core');
const chromium=require('@sparticuz/chromium').default;
(async()=>{
  const browser=await puppeteer.launch({executablePath:await chromium.executablePath(),args:chromium.args,headless:chromium.headless,env:{...process.env,LD_LIBRARY_PATH:'/tmp/al2023/lib'}});
  const page=await browser.newPage();
  await page.goto(process.env.PIXVA_BASE||'http://127.0.0.1:9414/',{waitUntil:'networkidle0',timeout:90000});
  const out=await page.evaluate(()=>{
    const found=[];
    const walk=(rules, href)=>{
      for(const r of rules){
        if(r.cssRules){walk(r.cssRules, href); continue}
        if(!r.selectorText) continue;
        if(String(r.style.backgroundImage||'').includes('none') || r.style.getPropertyPriority('background-image')==='important'){
          if(r.selectorText.includes('lazy')||r.selectorText.includes('e-parent')||r.selectorText.includes('cta'))
            found.push((href||'inline').slice(-50)+' :: '+r.selectorText.slice(0,150)+' {'+r.style.cssText.slice(0,120)+'}');
        }
      }
    };
    for(const s of document.styleSheets){let rs;try{rs=s.cssRules}catch(e){continue} walk(rs, s.href)}
    return found;
  });
  out.forEach(l=>console.log(l));
  await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
