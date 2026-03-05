const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  // 1. Panel giriş sayfasına git
  console.log('=== ADIM 1: Panel giriş sayfasına gidiliyor ===');
  await page.goto('https://www.sanaladisyon.com/panel', { waitUntil: 'networkidle', timeout: 30000 });
  console.log('URL:', page.url());
  console.log('Title:', await page.title());
  
  // Sayfanın tam HTML'ini al (form yapısını görmek için)
  const bodyHTML = await page.$eval('body', el => el.innerHTML);
  console.log('\nSayfa HTML (ilk 3000 karakter):\n', bodyHTML.substring(0, 3000));

  // Form elemanlarını bul
  const inputs = await page.$$eval('input, select, textarea', els => els.map(el => ({
    tag: el.tagName,
    type: el.type,
    name: el.name,
    id: el.id,
    placeholder: el.placeholder,
    className: el.className,
    value: el.value
  })));
  console.log('\nForm elemanları:', JSON.stringify(inputs, null, 2));

  const buttons = await page.$$eval('button, input[type="submit"], [role="button"]', els => els.map(el => ({
    tag: el.tagName,
    text: el.textContent?.trim(),
    type: el.type,
    id: el.id,
    className: el.className
  })));
  console.log('\nButonlar:', JSON.stringify(buttons, null, 2));

  // Screenshot
  await page.screenshot({ path: '/Users/emre/Desktop/adisyon sistemi/screenshot-panel-login.png', fullPage: true });
  console.log('\nLogin screenshot alındı');

  // 2. Giriş bilgilerini doldur
  console.log('\n=== ADIM 2: Giriş bilgileri dolduruluyor ===');
  
  // Tüm inputları dene
  const allInputs = await page.$$('input');
  console.log(`Toplam input sayısı: ${allInputs.length}`);
  
  for (let i = 0; i < allInputs.length; i++) {
    const attrs = await allInputs[i].evaluate(el => ({
      type: el.type, name: el.name, id: el.id, placeholder: el.placeholder, visible: el.offsetParent !== null
    }));
    console.log(`  Input ${i}:`, JSON.stringify(attrs));
  }

  // Email/username alanını doldur
  try {
    // Framework bazlı input (Vue, React vb.) olabilir
    const emailInput = await page.$('input[type="email"], input[type="text"], input[name*="email"], input[name*="user"], input[placeholder*="mail"], input[placeholder*="kullan"]');
    if (emailInput) {
      await emailInput.click();
      await emailInput.fill('info@sanaladisyon.com');
      console.log('Email dolduruldu');
    } else {
      console.log('Email inputu bulunamadı, index ile deneniyor...');
      if (allInputs.length >= 2) {
        await allInputs[0].click();
        await allInputs[0].fill('info@sanaladisyon.com');
        console.log('İlk input email ile dolduruldu');
      }
    }
  } catch(e) {
    console.log('Email doldurma hatası:', e.message);
  }

  // Şifre alanını doldur
  try {
    const passInput = await page.$('input[type="password"]');
    if (passInput) {
      await passInput.click();
      await passInput.fill('1111');
      console.log('Şifre dolduruldu');
    } else {
      console.log('Şifre inputu bulunamadı, index ile deneniyor...');
      if (allInputs.length >= 2) {
        await allInputs[1].click();
        await allInputs[1].fill('1111');
        console.log('İkinci input şifre ile dolduruldu');
      }
    }
  } catch(e) {
    console.log('Şifre doldurma hatası:', e.message);
  }

  // Giriş butonuna tıkla
  console.log('\n=== ADIM 3: Giriş yapılıyor ===');
  try {
    const loginBtn = await page.$('button[type="submit"], button:has-text("GİRİŞ"), button:has-text("Giriş"), input[type="submit"]');
    if (loginBtn) {
      await loginBtn.click();
      console.log('Giriş butonuna tıklandı');
    } else {
      // İlk butonu dene
      const firstBtn = await page.$('button');
      if (firstBtn) {
        const btnText = await firstBtn.textContent();
        console.log(`İlk buton tıklanıyor: "${btnText}"`);
        await firstBtn.click();
      }
    }
  } catch(e) {
    console.log('Buton tıklama hatası:', e.message);
  }

  // Sayfanın yüklenmesini bekle
  await page.waitForTimeout(5000);
  try {
    await page.waitForLoadState('networkidle', { timeout: 10000 });
  } catch(e) {}

  console.log('\nGiriş sonrası URL:', page.url());
  console.log('Giriş sonrası Title:', await page.title());

  // Screenshot
  await page.screenshot({ path: '/Users/emre/Desktop/adisyon sistemi/screenshot-after-login.png', fullPage: true });
  console.log('Giriş sonrası screenshot alındı');

  // Eğer hala login sayfasındaysak, hata mesajını kontrol et
  const errorMsg = await page.$eval('.error, .alert-danger, .alert-warning, .hata, [class*="error"], [class*="alert"]', el => el.textContent?.trim()).catch(() => null);
  if (errorMsg) {
    console.log('Hata mesajı:', errorMsg);
  }

  // Panel sayfası ise detaylı incele
  console.log('\n=== ADIM 4: Panel içeriği inceleniyor ===');
  
  // Navigation/sidebar
  const navItems = await page.$$eval('a', els => 
    els.map(el => ({
      text: el.textContent?.trim().substring(0, 60),
      href: el.href
    })).filter(e => e.text && e.text.length > 0)
  );
  console.log('Tüm linkler:', JSON.stringify(navItems.slice(0, 50), null, 2));

  // Başlıklar
  const headings = await page.$$eval('h1, h2, h3, h4', els => 
    els.map(el => ({ tag: el.tagName, text: el.textContent?.trim() }))
  );
  console.log('Başlıklar:', JSON.stringify(headings, null, 2));

  // Body text
  const bodyText = await page.$eval('body', el => el.innerText?.substring(0, 3000));
  console.log('\nSayfa metni (ilk 3000):\n', bodyText);

  // 5. Alt sayfaları keşfet
  console.log('\n=== ADIM 5: Panel alt sayfalarını keşfet ===');
  const panelLinks = navItems
    .filter(l => l.href && l.href.includes('sanaladisyon.com/panel'))
    .map(l => ({ text: l.text, href: l.href }));
  console.log('Panel linkleri:', JSON.stringify(panelLinks, null, 2));

  // Her panel sayfasını ziyaret et
  for (const link of panelLinks.slice(0, 20)) {
    if (link.href === page.url()) continue;
    try {
      await page.goto(link.href, { waitUntil: 'networkidle', timeout: 15000 });
      const title = await page.title();
      const h1 = await page.$eval('h1, h2, h3', el => el.textContent?.trim()).catch(() => 'N/A');
      const tables = await page.$$eval('table', els => els.length);
      const forms = await page.$$eval('form', els => els.length);
      const btns = await page.$$eval('button', els => els.map(e => e.textContent?.trim()).filter(t => t && t.length < 30)).catch(() => []);
      console.log(`\n--- ${link.text} (${link.href}) ---`);
      console.log(`  Title: ${title}, H1: ${h1}, Tablo: ${tables}, Form: ${forms}`);
      console.log(`  Butonlar: ${btns.slice(0, 8).join(', ')}`);
      
      // Sayfa body text (ilk 500 karakter)
      const pageText = await page.$eval('body', el => el.innerText?.substring(0, 500));
      console.log(`  İçerik: ${pageText.replace(/\n+/g, ' | ').substring(0, 300)}`);
    } catch(e) {
      console.log(`\n--- ${link.text} --- HATA: ${e.message?.substring(0, 100)}`);
    }
  }

  await browser.close();
  console.log('\n=== İnceleme tamamlandı ===');
})();
