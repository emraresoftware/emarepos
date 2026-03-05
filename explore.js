const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  // 1. Login sayfasına git
  console.log('=== ADIM 1: Login sayfasına gidiliyor ===');
  await page.goto('https://www.sanaladisyon.com', { waitUntil: 'networkidle', timeout: 30000 });
  console.log('URL:', page.url());
  console.log('Title:', await page.title());
  
  // Sayfanın HTML yapısını incele
  const loginPageHTML = await page.content();
  
  // Form elemanlarını bul
  const inputs = await page.$$eval('input', els => els.map(el => ({
    type: el.type,
    name: el.name,
    id: el.id,
    placeholder: el.placeholder,
    className: el.className
  })));
  console.log('\nLogin sayfası input elemanları:', JSON.stringify(inputs, null, 2));

  const buttons = await page.$$eval('button, input[type="submit"], a.btn', els => els.map(el => ({
    tag: el.tagName,
    text: el.textContent?.trim(),
    type: el.type,
    className: el.className
  })));
  console.log('\nButonlar:', JSON.stringify(buttons, null, 2));

  // 2. Giriş yap
  console.log('\n=== ADIM 2: Giriş yapılıyor ===');
  
  // Email alanını bul ve doldur
  const emailSelectors = ['input[name="email"]', 'input[type="email"]', 'input[name="username"]', 'input[name="user"]', '#email', '#username'];
  let emailFilled = false;
  for (const sel of emailSelectors) {
    try {
      const el = await page.$(sel);
      if (el) {
        await el.fill('info@sanaladisyon.com');
        console.log(`Email dolduruldu: ${sel}`);
        emailFilled = true;
        break;
      }
    } catch(e) {}
  }
  
  if (!emailFilled) {
    // Tüm text/email inputlarını dene
    const allInputs = await page.$$('input[type="text"], input[type="email"], input:not([type])');
    if (allInputs.length > 0) {
      await allInputs[0].fill('info@sanaladisyon.com');
      console.log('Email ilk input alanına dolduruldu');
      emailFilled = true;
    }
  }

  // Şifre alanını bul ve doldur
  const passwordSelectors = ['input[name="password"]', 'input[type="password"]', '#password'];
  let passFilled = false;
  for (const sel of passwordSelectors) {
    try {
      const el = await page.$(sel);
      if (el) {
        await el.fill('1111');
        console.log(`Şifre dolduruldu: ${sel}`);
        passFilled = true;
        break;
      }
    } catch(e) {}
  }

  // Giriş butonuna tıkla
  const submitSelectors = ['button[type="submit"]', 'input[type="submit"]', 'button:has-text("Giriş")', 'button:has-text("Login")', 'button:has-text("Oturum")', '.btn-login', '.login-btn'];
  let clicked = false;
  for (const sel of submitSelectors) {
    try {
      const el = await page.$(sel);
      if (el) {
        await el.click();
        console.log(`Giriş butonuna tıklandı: ${sel}`);
        clicked = true;
        break;
      }
    } catch(e) {}
  }

  // Sayfanın yüklenmesini bekle
  await page.waitForTimeout(5000);
  await page.waitForLoadState('networkidle').catch(() => {});
  
  console.log('\nGiriş sonrası URL:', page.url());
  console.log('Giriş sonrası Title:', await page.title());

  // 3. Dashboard / Ana sayfa içeriğini incele
  console.log('\n=== ADIM 3: Dashboard inceleniyor ===');
  
  // Navigation/menü yapısını al
  const navLinks = await page.$$eval('a, .nav-link, .menu-item, .sidebar a, nav a, .nav a', els => 
    els.map(el => ({
      text: el.textContent?.trim().substring(0, 50),
      href: el.href,
      className: el.className?.substring(0, 80)
    })).filter(el => el.text && el.text.length > 0)
  );
  console.log('\nNavigasyon linkleri (ilk 30):', JSON.stringify(navLinks.slice(0, 30), null, 2));

  // Sayfa içindeki başlıkları al
  const headings = await page.$$eval('h1, h2, h3, h4, h5, h6', els => 
    els.map(el => ({ tag: el.tagName, text: el.textContent?.trim() }))
  );
  console.log('\nBaşlıklar:', JSON.stringify(headings, null, 2));

  // Sayfadaki kartlar veya widget'ları bul
  const cards = await page.$$eval('.card, .widget, .panel, .box, .stat', els =>
    els.map(el => el.textContent?.trim().substring(0, 100))
  );
  if (cards.length > 0) {
    console.log('\nKartlar/Widget\'lar (ilk 10):', JSON.stringify(cards.slice(0, 10), null, 2));
  }

  // Tabloları bul
  const tables = await page.$$eval('table', els =>
    els.map(el => ({
      id: el.id,
      className: el.className,
      headers: Array.from(el.querySelectorAll('th')).map(th => th.textContent?.trim()),
      rowCount: el.querySelectorAll('tbody tr').length
    }))
  );
  if (tables.length > 0) {
    console.log('\nTablolar:', JSON.stringify(tables, null, 2));
  }

  // 4. Sidebar menüsünü detaylı incele
  console.log('\n=== ADIM 4: Sidebar/Menü detaylı inceleme ===');
  const sidebarItems = await page.$$eval('.sidebar, .side-menu, .left-menu, .main-menu, [class*="sidebar"], [class*="menu"], nav', els =>
    els.map(el => ({
      className: el.className?.substring(0, 80),
      text: el.textContent?.trim().substring(0, 500)
    }))
  );
  console.log('Sidebar elemanları:', JSON.stringify(sidebarItems.slice(0, 5), null, 2));

  // 5. Sayfaların listesini çıkar
  console.log('\n=== ADIM 5: Tüm benzersiz sayfalar ===');
  const allLinks = await page.$$eval('a[href]', els => 
    [...new Set(els.map(el => el.href))].filter(h => h.includes('sanaladisyon.com'))
  );
  console.log('Benzersiz linkler:', JSON.stringify(allLinks, null, 2));

  // Screenshot al
  await page.screenshot({ path: '/Users/emre/Desktop/adisyon sistemi/screenshot-dashboard.png', fullPage: true });
  console.log('\nScreenshot alındı: screenshot-dashboard.png');

  // 6. Her ana sayfayı ziyaret et ve içeriğini özetle
  console.log('\n=== ADIM 6: Alt sayfaları ziyaret et ===');
  const uniquePaths = [...new Set(allLinks.map(l => {
    try { return new URL(l).pathname; } catch(e) { return ''; }
  }))].filter(p => p && p !== '/' && p.length > 1).slice(0, 15);
  
  for (const path of uniquePaths) {
    try {
      await page.goto(`https://www.sanaladisyon.com${path}`, { waitUntil: 'networkidle', timeout: 15000 });
      const title = await page.title();
      const h1 = await page.$eval('h1', el => el.textContent?.trim()).catch(() => 'N/A');
      const h2s = await page.$$eval('h2, h3', els => els.map(el => el.textContent?.trim()).slice(0, 3)).catch(() => []);
      console.log(`\n--- Sayfa: ${path} ---`);
      console.log(`  Title: ${title}`);
      console.log(`  H1: ${h1}`);
      console.log(`  Başlıklar: ${h2s.join(', ')}`);
      
      // Tablolar ve formlar
      const pageTables = await page.$$eval('table', els => els.length).catch(() => 0);
      const pageForms = await page.$$eval('form', els => els.length).catch(() => 0);
      const pageButtons = await page.$$eval('button', els => els.map(e => e.textContent?.trim()).filter(t => t)).catch(() => []);
      console.log(`  Tablo sayısı: ${pageTables}, Form sayısı: ${pageForms}`);
      console.log(`  Butonlar: ${pageButtons.slice(0, 5).join(', ')}`);
    } catch(e) {
      console.log(`\n--- Sayfa: ${path} --- HATA: ${e.message?.substring(0, 100)}`);
    }
  }

  await browser.close();
  console.log('\n=== İnceleme tamamlandı ===');
})();
