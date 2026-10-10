const puppeteer=require('puppeteer-core');
const chromium=require('@sparticuz/chromium').default;
(async()=>{
  const browser=await puppeteer.launch({executablePath:await chromium.executablePath(),args:chromium.args,headless:chromium.headless,env:{...process.env,LD_LIBRARY_PATH:'/tmp/al2023/lib'}});
  const page=await browser.newPage();
  await page.setViewport({width:1440,height:900});
  await page.goto(process.env.PIXVA_BASE||'http://127.0.0.1:9414/',{waitUntil:'networkidle0',timeout:90000});
  await new Promise(r=>setTimeout(r,2500));
  const out=await page.evaluate(()=>{
    const c=document.querySelector('.cta');
    const cs=getComputedStyle(c);
    const b=[...document.querySelectorAll('.elementor-button')];
    const res={
      cta:{bg:cs.backgroundColor,img:cs.backgroundImage.slice(0,120),pos:cs.backgroundPosition,size:cs.backgroundSize,cls:c.className,inline:c.getAttribute('style')||''},
      vars:{
        dot: getComputedStyle(document.documentElement).getPropertyValue('--dot-pattern').slice(0,60),
        navy: getComputedStyle(document.documentElement).getPropertyValue('--grad-navy').slice(0,60),
        accent: getComputedStyle(document.documentElement).getPropertyValue('--grad-accent').slice(0,60),
      },
      btns: b.map(x=>({cls:x.className.slice(0,70), bg:getComputedStyle(x).backgroundColor, img:getComputedStyle(x).backgroundImage.slice(0,60), inline:x.getAttribute('style')||''})),
      darkBg: (()=>{const d=document.querySelector('.band--dark'); return d?{cls:d.className.slice(0,60),bg:getComputedStyle(d).backgroundColor,img:getComputedStyle(d).backgroundImage.slice(0,60)}:'none'})(),
    };
    return res;
  });
  console.log(JSON.stringify(out,null,1));
  await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
