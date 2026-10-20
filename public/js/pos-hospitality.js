(function ($) {
    'use strict';
    $(function () {
        if (!document.body.classList.contains('hospitality-pos')) return;
        var search = $('#search_product');
        var slot = $('.hospitality-search-slot');
        var customerColumn = $('#customer_id').closest('.col-md-4');
        var customerSlot = $('.pos-customer-search-bar');
        if (customerSlot.length && customerColumn.length) {
            customerColumn.addClass('hospitality-customer-column');
            customerSlot.append(customerColumn);
        }
        if (slot.length && search.length) {
            var column = search.closest('.col-md-8');
            slot.append(column.children());
            column.remove();
            search.attr('placeholder', slot.data('placeholder')).attr('aria-label', slot.data('placeholder'));
        }
        var preset = slot.length ? slot : $('.hospitality-ticket-heading');
        if (preset.data('empty')) $('.pos-cart-empty-state strong').text(preset.data('empty'));
        if (preset.data('hint')) $('.pos-cart-empty-state small').text(preset.data('hint'));
        if (preset.data('icon')) $('.pos-cart-empty-state > span i').attr('class', 'fas ' + preset.data('icon'));
        var mobile = window.matchMedia('(max-width: 850px)');
        var catalogue = document.getElementById('pos-mobile-catalogue-content');
        var catalogueToggle = document.getElementById('pos-mobile-catalogue-toggle');
        var orderButton = document.getElementById('pos-mobile-view-order');
        function syncCatalogue() {
            if (!catalogue || !catalogueToggle) return;
            var collapsed = catalogue.classList.contains('is-mobile-collapsed');
            catalogueToggle.setAttribute('aria-expanded', String(!mobile.matches || !collapsed));
            catalogueToggle.querySelector('span').textContent = collapsed ? 'Browse products' : 'Hide products';
            $('.pos-v2-catalog-panel').toggleClass('is-mobile-catalogue-collapsed', collapsed);
        }
        if (catalogueToggle && catalogue) {
            catalogueToggle.addEventListener('click', function () {
                catalogue.classList.toggle('is-mobile-collapsed');
                syncCatalogue();
            });
            orderButton.addEventListener('click', function () {
                catalogue.classList.add('is-mobile-collapsed');
                syncCatalogue();
                if (document.activeElement === search[0]) search[0].blur();
                document.querySelector('.pos-v2-cart-panel').scrollIntoView({behavior: 'smooth', block: 'start'});
            });
            if (mobile.addEventListener) mobile.addEventListener('change', syncCatalogue);
            else mobile.addListener(syncCatalogue);
            syncCatalogue();
        }
        $(document).on('click', '.main-category, .product_category', function () {
            var value = String($(this).data('value'));
            $('.hospitality-category').each(function () {
                var active = String($(this).data('value')) === value;
                $(this).toggleClass('is-active', active).attr('aria-pressed', String(active));
            });
        });
        var body = document.querySelector('#pos_table tbody');
        function updateCount() {
            var count = $('#pos_table tbody tr.product_row').length;
            $('.hospitality-item-count, .hospitality-mobile-item-count').text(count + (count === 1 ? ' item' : ' items'));
        }
        if (body) new MutationObserver(updateCount).observe(body, {childList: true});
        updateCount();
    });
})(jQuery);
