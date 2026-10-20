(function () {
    'use strict';
    var cfg = window.SITE_CONFIG || {};
    var one = function (selector) { return document.querySelector(selector); };
    var all = function (selector) { return Array.from(document.querySelectorAll(selector)); };
    all('[data-business-name]').forEach(function (el) { el.textContent = cfg.businessName || el.textContent; });
    if (cfg.businessName) document.title = cfg.businessName + ' | ' + document.body.dataset.siteLabel;
    all('[data-year]').forEach(function (el) { el.textContent = new Date().getFullYear(); });
    var local = /^(localhost|127\.0\.0\.1|\[::1\])$/.test(location.hostname);
    var prefix = location.pathname.split('/main-domain-site/')[0];
    var posBase = cfg.posBaseUrl || (local && location.pathname.includes('/main-domain-site/') ? prefix + '/public' : '/shop/public');
    all('[data-portal]').forEach(function (el) { el.href = posBase.replace(/\/$/, '') + '/login'; });
    var phone = String(cfg.phone || '').trim();
    all('[data-phone]').forEach(function (el) { el.hidden = !phone; el.textContent = phone; el.href = 'tel:' + phone.replace(/[^+0-9]/g, ''); });
    ['address', 'openingHours'].forEach(function (key) { all('[data-' + (key === 'openingHours' ? 'hours' : 'address') + ']').forEach(function (el) { el.hidden = !cfg[key]; el.textContent = cfg[key] || ''; }); });
    all('[data-email]').forEach(function (el) { el.hidden = !cfg.email; el.textContent = cfg.email || ''; el.href = 'mailto:' + (cfg.email || ''); });
    var toggle = one('.site-menu-toggle'), navigation = one('#site-navigation');
    toggle.addEventListener('click', function () { var open = toggle.getAttribute('aria-expanded') !== 'true'; toggle.setAttribute('aria-expanded', String(open)); navigation.classList.toggle('is-open', open); });
    navigation.addEventListener('click', function (event) { if (event.target.closest('a')) { navigation.classList.remove('is-open'); toggle.setAttribute('aria-expanded', 'false'); } });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') { navigation.classList.remove('is-open'); toggle.setAttribute('aria-expanded', 'false'); toggle.focus(); } });
    var catalogue = one('#live-catalogue');
    if (!cfg.enableCatalogue) { all('[data-catalogue-link]').forEach(function (el) { el.hidden = true; }); return; }
    catalogue.hidden = false;
    var endpoint = cfg.catalogueUrl || posBase.replace(/\/$/, '') + '/catalogue/products';
    var grid = one('#product-grid'), form = one('#catalogue-form'), status = one('#catalogue-status'), more = one('#catalogue-more');
    var category = one('#catalogue-category'), locationSelect = one('#catalogue-location'), search = one('#catalogue-search');
    var currentPage = 1, lastPage = 1, request = null, categoriesLoaded = false, locationsLoaded = false, timer;
    var formatter;
    try { formatter = new Intl.NumberFormat(cfg.locale || 'en-GH', {style:'currency', currency:cfg.currency || 'GHS'}); } catch (_) { formatter = new Intl.NumberFormat('en-GH', {style:'currency', currency:'GHS'}); }
    function card(item) {
        var article = document.createElement('article'); article.className = 'catalogue-card';
        var media = document.createElement('div'); media.className = 'catalogue-card-media';
        var mark = document.createElement('span'); mark.className = 'catalogue-fallback'; mark.textContent = String(item.name || 'Item').split(/\s+/).slice(0,2).map(function (word) { return word[0]; }).join('').toUpperCase(); media.append(mark);
        if (item.image) { var img = document.createElement('img'); img.src = item.image; img.alt = ''; img.loading = 'lazy'; img.addEventListener('error', function () { img.remove(); }); media.append(img); }
        var body = document.createElement('div'); body.className = 'catalogue-card-body';
        var group = document.createElement('small'); group.textContent = item.category || 'General';
        var title = document.createElement('h3'); title.textContent = item.name || 'Item';
        var price = document.createElement('strong');
        price.textContent = item.price_from == null ? 'Ask in store' : formatter.format(Number(item.price_from)) + (Number(item.price_to) > Number(item.price_from) ? ' - ' + formatter.format(Number(item.price_to)) : '');
        body.append(group,title,price);article.append(media,body);return article;
    }
    function options(select, values) { values.forEach(function (value) { var option = document.createElement('option'); option.value = value.id; option.textContent = value.name; select.append(option); }); }
    async function load(append) {
        if (request) request.abort();
        request = new AbortController();var active = request;
        var page = append ? currentPage + 1 : 1;
        var url = new URL(endpoint, location.href);url.searchParams.set('q',search.value.trim());url.searchParams.set('category',category.value);url.searchParams.set('location',locationSelect.value);url.searchParams.set('page',page);url.searchParams.set('per_page',12);
        catalogue.setAttribute('aria-busy','true');status.textContent='Loading products...';more.disabled=true;
        try {
            var response=await fetch(url,{signal:active.signal,headers:{Accept:'application/json'}});
            if (!response.ok) throw new Error('Catalogue unavailable');
            var result=await response.json();if(!Array.isArray(result.products))throw new Error('Invalid catalogue');
            if (!append) grid.replaceChildren();result.products.forEach(function(item){grid.append(card(item));});
            currentPage=Number(result.pagination.current_page);lastPage=Number(result.pagination.last_page);
            if(!categoriesLoaded || !append){var selected=category.value;category.replaceChildren(new Option('All categories',''));options(category,result.categories||[]);category.value=selected;categoriesLoaded=true;}
            if(!locationsLoaded){options(locationSelect,result.locations||[]);locationsLoaded=true;}
            status.textContent=Number(result.pagination.total)===0?'No products found. Try another search or category.':result.pagination.total+' products available';
            more.hidden=currentPage>=lastPage;
        } catch(error) {
            if(error.name==='AbortError')return;
            status.textContent='The live catalogue is unavailable right now. Please try again or ask in store.';more.hidden=true;
        } finally { if(request===active){catalogue.setAttribute('aria-busy','false');more.disabled=false;} }
    }
    form.addEventListener('submit',function(event){event.preventDefault();clearTimeout(timer);load(false);});
    category.addEventListener('change',function(){load(false);});locationSelect.addEventListener('change',function(){category.value='';load(false);});
    search.addEventListener('input',function(){clearTimeout(timer);timer=setTimeout(function(){load(false);},300);});
    more.addEventListener('click',function(){load(true);});load(false);
})();
