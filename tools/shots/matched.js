const puppeteer=require('puppeteer-core');
const chromium=require('@sparticuz/chromium').default;
(async()=>{
  const browser=await puppeteer.launch({executablePath:await chromium.executablePath(),args:chromium.args,headless:chromium.headless,env:{...process.env,LD_LIBRARY_PATH:'/tmp/al2023/lib'}});
  const page=await browser.newPage();
  await page.setViewport({width:1440,height:900});
  await page.goto(process.env.PIXVA_BASE||'http://127.0.0.1:9414/',{waitUntil:'networkidle2',timeout:90000});
  const client=await page.target().createCDPSession();
  await client.send('DOM.enable'); await client.send('CSS.enable');
  async function dump(expr, isAll){
    const {root}=await client.send('DOM.getDocument');
    const {nodeId}=await client.send('DOM.querySelector',{nodeId:root.nodeId,selector:expr});
    if(!nodeId){console.log('NOT FOUND',expr);return}
    const {matchedCSSRules}=await client.send('CSS.getMatchedStylesForNode',{nodeId});
    console.log('=== '+expr);
    for(const m of matchedCSSRules){
      const sel=m.rule.selectorList.text;
      if(sel.includes('cta')||sel.includes('button')||sel.includes('band')){
        console.log('  ', sel.slice(0,90), '|| media:', (m.matchingSelectors||[]).length, 'styleId:', m.rule.style.styleSheetId||'inline');
        for(const p of m.rule.style.cssProperties){
          if(['background','background-color','background-image','color','padding-top'].includes(p.name))
            console.log('      ', p.name+':', String(p.value).slice(0,90), p.important?'!IMPORTANT':'');
        }
      }
    }
  }
  await dump('.cta');
  await dump('.elementor-widget-button:nth-of-type(1)'); // not reliable
  // dump all elementor-buttons
  const {root}=await client.send('DOM.getDocument');
  const {nodeIds}=await client.send('DOM.querySelectorAll',{nodeId:root.nodeId,selector:'.elementor-button'});
  console.log('buttons found:', nodeIds.length);
  for(const nid of nodeIds.slice(0,4)){
    const {matchedCSSRules}=await client.send('CSS.getMatchedStylesForNode',{nodeId:nid});
    const styles=[];
    for(const m of matchedCSSRules){
      for(const p of m.rule.style.cssProperties){
        if(p.name==='background'||p.name==='background-image'||p.name==='background-color')
          styles.push(m.rule.selectorList.text.slice(0,60)+' {'+p.name+':'+String(p.value).slice(0,55)+'}');
      }
    }
    console.log('BUTTON', nid, '\n   '+styles.join('\n   '));
  }
  await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
