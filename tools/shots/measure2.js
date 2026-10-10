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
    document.querySelectorAll('.e-con').forEach((el,i)=>{
      if(i>6) return;
      const r=el.getBoundingClientRect(); const cs=getComputedStyle(el);
      out.push(`econ[${i}] ${Math.round(r.width)} w:${cs.width} maxw:${cs.maxWidth} pad:${cs.paddingLeft}/${cs.paddingRight} cw:${cs.getPropertyValue('--container-min-width')||''} classes:${el.className.slice(0,80)}`);
    });
    const w=document.querySelector('.elementor-widget');
    if(w){const r=w.getBoundingClientRect(); out.push('widget0 '+Math.round(r.width)+' parent:'+w.parentElement.className.slice(0,60));}
    // find elementor css var for boxed width
    const css=[...document.styleSheets].flatMap(s=>{try{return [...s.cssRules]}catch(e){return []}})
      .filter(r=>r.selectorText&&r.selectorText.includes('elementor-kit-'))
      .map(r=>r.selectorText+' {'+[...r.style].filter(p=>p.includes('container')).map(p=>p+':'+r.style.getPropertyValue(p)).join(';')+'}')
      .slice(0,6);
    out.push(...css);
    out.push('body children: '+[...document.body.children].map(c=>c.tagName+'.'+String(c.className).slice(0,40)).join(' | '));
    const main=document.querySelector('main');
    if(main){out.push('main kids: '+[...main.children].map(c=>c.tagName+'.'+String(c.className).slice(0,50)+' w='+Math.round(c.getBoundingClientRect().width)).join(' | '));}
    return out;
  });
  info.forEach(i=>console.log(i));
  await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
