const puppeteer=require('puppeteer-core');
const chromium=require('@sparticuz/chromium').default;
(async()=>{
  const browser=await puppeteer.launch({executablePath:await chromium.executablePath(),args:chromium.args,headless:chromium.headless,
    env:{...process.env,LD_LIBRARY_PATH:'/tmp/al2023/lib'}});
  const page=await browser.newPage();
  await page.setViewport({width:1440,height:900});
  await page.goto(process.env.PIXVA_BASE||'http://127.0.0.1:9414/',{waitUntil:'networkidle2',timeout:90000});
  const out=await page.evaluate(()=>{
    const lines=[];
    document.querySelectorAll('.elementor > .e-con, .elementor > .elementor-element').forEach((el,i)=>{
      const cs=getComputedStyle(el); const r=el.getBoundingClientRect();
      lines.push(`top[${i}] w=${Math.round(r.width)} cls="${el.getAttribute('class')}" pad=${cs.paddingTop}/${cs.paddingBottom} bg=${cs.backgroundColor}`);
    });
    const cta=document.querySelector('.cta');
    if(cta){const cs=getComputedStyle(cta); lines.push(`CTA bg=${cs.backgroundColor} bgimg=${cs.backgroundImage.slice(0,80)} cls=${cta.className}`);}
    const hero=document.querySelector('.hero');
    if(hero){const cs=getComputedStyle(hero); const r=hero.getBoundingClientRect(); lines.push(`HERO w=${Math.round(r.width)} h=${Math.round(r.height)} pad=${cs.paddingTop} bgimg=${cs.backgroundImage.slice(0,60)}`);}
    const b=[...document.querySelectorAll('.elementor-button')].slice(0,6).map(x=>`${x.className} :: ${getComputedStyle(x).backgroundImage.slice(0,60)}`);
    lines.push('BUTTONS:\n'+b.join('\n'));
    const im=[...document.querySelectorAll('img')].map(x=>x.getAttribute('src')||'').filter(s=>s.includes('editorial')||s.includes('hero'));
    lines.push('IMGS: '+im.join(' | '));
    const dark=document.querySelector('.band--dark');
    lines.push('band--dark exists: '+(dark?'yes':'NO'));
    const bands=[...document.querySelectorAll('[class*=band]')].map(e=>e.getAttribute('class').slice(0,60));
    lines.push('BANDS: '+bands.join(' || '));
    // feature list li text color
    const li=document.querySelector('.feature-list li');
    if(li){const cs=getComputedStyle(li); lines.push(`fl li color=${cs.color} text="${li.textContent.slice(0,30)}"`);}
    return lines;
  });
  out.forEach(l=>console.log(l));
  await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
