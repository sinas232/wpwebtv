const puppeteer=require('puppeteer-core');
const chromium=require('@sparticuz/chromium').default;
(async()=>{
  const browser=await puppeteer.launch({executablePath:await chromium.executablePath(),args:chromium.args,headless:chromium.headless,env:{...process.env,LD_LIBRARY_PATH:'/tmp/al2023/lib'}});
  const page=await browser.newPage();
  await page.goto((process.env.PIXVA_BASE||'http://127.0.0.1:9414')+'/problems/',{waitUntil:'networkidle2',timeout:90000});
  const out=await page.evaluate(()=>{
    const res=[];
    document.querySelectorAll('.section__title, .elementor-heading-title, .section__head').forEach(el=>{
      const r=el.getBoundingClientRect();
      res.push(`${el.tagName}.${el.className.slice(0,40)} text="${(el.textContent||'').trim().slice(0,40)}" y=${Math.round(r.y)} w=${Math.round(r.width)}`);
    });
    // find ::after bars: elements with section title pseudo
    res.push('--- bands/sections:');
    document.querySelectorAll('section.section, .e-con').forEach(el=>{
      const r=el.getBoundingClientRect();
      res.push(`${el.tagName} cls=${el.className.slice(0,70)} y=${Math.round(r.y)} h=${Math.round(r.height)}`);
    });
    return res;
  });
  out.forEach(l=>console.log(l));
  await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
