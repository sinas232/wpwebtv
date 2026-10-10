const puppeteer=require('puppeteer-core');
const chromium=require('@sparticuz/chromium').default;
(async()=>{
  const browser=await puppeteer.launch({executablePath:await chromium.executablePath(),args:chromium.args,headless:chromium.headless,env:{...process.env,LD_LIBRARY_PATH:'/tmp/al2023/lib'}});
  const page=await browser.newPage();
  await page.goto(process.env.PIXVA_BASE||'http://127.0.0.1:9414/',{waitUntil:'networkidle0',timeout:90000});
  const out=await page.evaluate(()=>{
    const c=document.querySelector('.cta');
    const log=[];
    const t=(label,fn)=>{fn();const cs=getComputedStyle(c);log.push(label+' => img='+(cs.backgroundImage==='none'?'NONE':'OK')+' col='+cs.backgroundColor+' inline="'+(c.getAttribute('style')||'').slice(0,80)+'"');};
    t('plain-color',()=>{c.style.backgroundColor='#123456'});
    t('plain-gradient',()=>{c.style.backgroundImage='linear-gradient(#000,#111)'});
    t('var-gradient',()=>{c.style.backgroundImage='var(--grad-navy)'});
    t('var-gradient-raw',()=>{c.style.backgroundImage='linear-gradient(160deg, #0B1C2E 0%, #0E2439 55%, #071521 100%)'});
    t('custom-prop',()=>{c.style.setProperty('--t','var(--grad-navy)');c.style.backgroundImage='var(--t)'});
    t('dot-only',()=>{c.style.backgroundImage='var(--dot-pattern)'});
    t('hero-rule-copy',()=>{c.style.background='var(--dot-pattern) 0 0 / 26px 26px, linear-gradient(135deg, color-mix(in srgb, var(--c-accent) 22%, transparent) 0%, transparent 42%), var(--grad-navy)'});
    // check vars readable from element scope
    const cs2=getComputedStyle(c);
    log.push('element sees --grad-navy="'+cs2.getPropertyValue('--grad-navy').slice(0,50)+'"');
    log.push('element sees --c-primary="'+cs2.getPropertyValue('--c-primary')+'"');
    return log;
  });
  out.forEach(l=>console.log(l));
  await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
