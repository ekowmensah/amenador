(function () {
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.querySelector('.site-nav');
    var year = document.getElementById('year');

    if (year) year.textContent = new Date().getFullYear();

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!open));
            nav.classList.toggle('is-open', !open);
        });
        nav.addEventListener('click', function (event) {
            if (event.target.closest('a')) {
                nav.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    var catalogue = document.querySelector('[data-catalogue-endpoint]');
    if (!catalogue) return;

    var endpoint = catalogue.getAttribute('data-catalogue-endpoint');
    var grid = document.getElementById('public-product-grid');
    var search = document.getElementById('catalogue-search');
    var category = document.getElementById('catalogue-category');
    var locationFilter = document.getElementById('catalogue-location');
    var submit = document.getElementById('catalogue-submit');
    var more = document.getElementById('catalogue-more');
    var status = document.getElementById('catalogue-status');
    var empty = document.getElementById('catalogue-empty');
    var enquiryModal = document.getElementById('product-enquiry-modal');
    var enquiryName = document.getElementById('enquiry-product-name');
    var enquiryCategory = document.getElementById('enquiry-product-category');
    var enquiryMeta = document.getElementById('enquiry-product-meta');
    var enquiryWhatsApp = document.getElementById('enquiry-whatsapp');
    var currentPage = 1;
    var lastPage = 1;
    var loading = false;
    var categoriesLoaded = false;
    var locationsLoaded = false;
    var searchTimer = null;
    var requestController = null;

    function priceLabel(item) {
        if (item.price_from === null) return 'Contact for price';
        var from = Number(item.price_from).toFixed(2);
        var to = Number(item.price_to).toFixed(2);
        return from === to ? '₵ ' + from : '₵ ' + from + ' – ₵ ' + to;
    }

    function categorySymbol(categoryName) {
        var name = String(categoryName || '').toLowerCase();
        if (/drug|medicine|pharmacy|otc|prescription|surgical|cosmetic/.test(name)) return '✚';
        if (/paint|adhesive|glue/.test(name)) return '◒';
        if (/pipe|pvc|tap|basin|sink|trap/.test(name)) return '⌁';
        if (/tool|screw|bolt|key|safety/.test(name)) return '⚒';
        if (/roof|wood|plywood|door|tile|ceramic/.test(name)) return '⌂';
        return '◫';
    }

    function fallbackMark(name) {
        var words = String(name || 'Product').trim().split(/\s+/).slice(0, 2);
        return words.map(function (word) { return word.charAt(0); }).join('').toUpperCase();
    }

    function openEnquiry(item) {
        var message = 'Hello, I would like to ask about ' + item.name + (item.sku ? ' (' + item.sku + ')' : '') + '.';
        enquiryName.textContent = item.name;
        enquiryCategory.textContent = item.category || 'Product';
        enquiryMeta.textContent = (item.sku ? 'SKU: ' + item.sku + ' · ' : '') + priceLabel(item);
        enquiryWhatsApp.href = 'https://wa.me/233244934742?text=' + encodeURIComponent(message);
        if (typeof enquiryModal.showModal === 'function') enquiryModal.showModal();
        else enquiryModal.setAttribute('open', '');
    }

    function productCard(item) {
        var card = document.createElement('article');
        card.className = 'public-product-card';
        var media = document.createElement('div');
        media.className = 'public-product-media';
        if (item.image) {
            var image = document.createElement('img');
            image.src = item.image;
            image.alt = '';
            image.loading = 'lazy';
            image.addEventListener('error', function () {
                image.remove();
                media.classList.add('is-fallback');
                media.innerHTML = '<span>' + categorySymbol(item.category) + '</span><b>' + fallbackMark(item.name) + '</b>';
            });
            media.appendChild(image);
        } else {
            media.classList.add('is-fallback');
            media.innerHTML = '<span>' + categorySymbol(item.category) + '</span><b>' + fallbackMark(item.name) + '</b>';
        }
        var body = document.createElement('div');
        body.className = 'public-product-body';
        var tag = document.createElement('span');
        tag.className = 'public-product-category';
        tag.textContent = item.category;
        var title = document.createElement('h3');
        title.textContent = item.name;
        var sku = document.createElement('small');
        sku.textContent = item.sku ? 'SKU: ' + item.sku : 'Product enquiry';
        var foot = document.createElement('div');
        foot.className = 'public-product-foot';
        var price = document.createElement('strong');
        price.textContent = priceLabel(item);
        var enquiry = document.createElement('button');
        enquiry.type = 'button';
        enquiry.className = 'public-product-enquiry';
        enquiry.textContent = 'Enquire →';
        enquiry.addEventListener('click', function () { openEnquiry(item); });
        foot.append(price, enquiry);
        body.append(tag, title, sku, foot);
        card.append(media, body);
        return card;
    }

    function setCategories(items) {
        var selected = category.value;
        category.innerHTML = '<option value="">All categories</option>';
        items.forEach(function (item) {
            var option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            category.appendChild(option);
        });
        if (selected && category.querySelector('option[value="' + selected + '"]')) category.value = selected;
        categoriesLoaded = true;
    }

    function setLocations(items) {
        if (locationsLoaded) return;
        items.forEach(function (item) {
            var option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name + (item.city && item.name.toLowerCase().indexOf(item.city.toLowerCase()) === -1 ? ' — ' + item.city : '');
            locationFilter.appendChild(option);
        });
        locationsLoaded = true;
    }

    function loadProducts(append) {
        if (append && loading) return;
        if (!append && requestController) requestController.abort();
        var controller = new AbortController();
        requestController = controller;
        loading = true;
        submit.disabled = true;
        more.disabled = true;
        status.textContent = 'Loading products…';
        if (!append) { currentPage = 1; grid.innerHTML = ''; }
        var params = new URLSearchParams({page: currentPage, per_page: 12});
        if (search.value.trim()) params.set('q', search.value.trim());
        if (category.value) params.set('category', category.value);
        if (locationFilter.value) params.set('location', locationFilter.value);

        fetch(endpoint + '?' + params.toString(), {
            headers: {'Accept': 'application/json'},
            signal: controller.signal
        })
            .then(function (response) { if (!response.ok) throw new Error('Catalogue unavailable'); return response.json(); })
            .then(function (data) {
                setCategories(data.categories || []);
                setLocations(data.locations || []);
                (data.products || []).forEach(function (item) { grid.appendChild(productCard(item)); });
                lastPage = data.pagination.last_page;
                var shown = grid.children.length;
                status.textContent = shown + ' of ' + data.pagination.total.toLocaleString() + ' products shown';
                empty.hidden = shown !== 0;
                more.hidden = currentPage >= lastPage || shown === 0;
            })
            .catch(function (error) {
                if (error.name === 'AbortError') return;
                status.textContent = 'The live catalogue could not be loaded.';
                empty.hidden = false;
                empty.querySelector('h3').textContent = 'Catalogue temporarily unavailable';
                empty.querySelector('p').textContent = 'Please call a branch for product enquiries.';
                more.hidden = true;
            })
            .finally(function () {
                if (requestController !== controller) return;
                loading = false;
                submit.disabled = false;
                more.disabled = false;
            });
    }

    submit.addEventListener('click', function () { loadProducts(false); });
    category.addEventListener('change', function () { loadProducts(false); });
    locationFilter.addEventListener('change', function () { category.value = ''; loadProducts(false); });
    search.addEventListener('input', function () {
        window.clearTimeout(searchTimer);
        status.textContent = search.value.trim() ? 'Searching…' : 'Loading products…';
        searchTimer = window.setTimeout(function () { loadProducts(false); }, 280);
    });
    search.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            window.clearTimeout(searchTimer);
            loadProducts(false);
        }
    });
    more.addEventListener('click', function () { if (currentPage < lastPage) { currentPage += 1; loadProducts(true); } });
    enquiryModal.querySelector('.enquiry-modal-close').addEventListener('click', function () { enquiryModal.close(); });
    enquiryModal.addEventListener('click', function (event) {
        if (event.target === enquiryModal) enquiryModal.close();
    });
    loadProducts(false);
})();
