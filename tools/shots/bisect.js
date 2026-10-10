const puppeteer=require('puppeteer-core');
const chromium=require('@sparticuz/chromium').default;
(async()=>{
  const browser=await puppeteer.launch({executablePath:await chromium.executablePath(),args:chromium.args,headless:chromium.headless,env:{...process.env,LD_LIBRARY_PATH:'/tmp/al2023/lib'}});
  const page=await browser.newPage();
  await page.goto(process.env.PIXVA_BASE||'http://127.0.0.1:9414/',{waitUntil:'networkidle0',timeout:90000});
  const out=await page.evaluate(()=>{
    const c=document.querySelector('.cta');
    const tests={
      A:'var(--dot-pattern) 0 0 / 22px 22px',
      B:'linear-gradient(130deg, color-mix(in srgb, var(--c-accent) 24%, transparent) 0%, transparent 46%)',
      C:'var(--grad-navy)',
      D:'var(--dot-pattern) 0 0 / 22px 22px, var(--grad-navy)',
      E:'var(--dot-pattern) 0 0 / 22px 22px, linear-gradient(130deg, color-mix(in srgb, var(--c-accent) 24%, transparent) 0%, transparent 46%), var(--grad-navy)',
    };
    const res={};
    for(const [k,v] of Object.entries(tests)){
      c.style.background=v;
      const cs=getComputedStyle(c);
      res[k]={img:cs.backgroundImage==='none'?'NONE':cs.backgroundImage.slice(0,70), col:cs.backgroundColor};
      c.style.background='';
    }
    // also test as separate props like band--dark
    c.style.backgroundImage='var(--dot-pattern), var(--grad-navy)';
    res.F={img:getComputedStyle(c).backgroundImage==='none'?'NONE':'OK'};
    c.style.backgroundImage='';
    return res;
  });
  console.log(JSON.stringify(out,null,1));
  await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
