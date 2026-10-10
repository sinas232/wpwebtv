const path=require('path'); const fs=require('fs');
const puppeteer=require('puppeteer-core');
const chromium=require('@sparticuz/chromium').default;
(async()=>{
  const browser=await puppeteer.launch({executablePath:await chromium.executablePath(),args:chromium.args,headless:chromium.headless,
    env:{...process.env,LD_LIBRARY_PATH:'/tmp/al2023/lib'}});
  const page=await browser.newPage();
  await page.setViewport({width:1440,height:900});
  await page.goto(process.env.PIXVA_BASE||'http://127.0.0.1:9414/',{waitUntil:'networkidle2',timeout:90000});
  const info=await page.evaluate(()=>{
    const out=[];
    const sel=['.hero','.hero__grid','.hero__figure','.hero__chips','.section','.container','.grid--tiles','.cta'];
    for(const s of sel){
      const el=document.querySelector(s);
      if(!el){out.push([s,'MISSING']);continue}
      const r=el.getBoundingClientRect();
      const cs=getComputedStyle(el);
      out.push([s,Math.round(r.width)+'x'+Math.round(r.height), 'display:'+cs.display, 'cols:'+cs.gridTemplateColumns.slice(0,60), 'parent:'+el.parentElement.className.slice(0,60)]);
    }
    // first section parent chain
    const sec=document.querySelector('.section');
    let chain=[]; let n=sec;
    while(n && n!==document.body){chain.push(n.tagName+'.'+String(n.className).slice(0,50)); n=n.parentElement;}
    out.push(['chain', chain.join(' < ')]);
    return out;
  });
  info.forEach(i=>console.log(i.join(' | ')));
  await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
