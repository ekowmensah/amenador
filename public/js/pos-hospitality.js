(function ($) {
    'use strict';
    $(function () {
        if (!document.body.classList.contains('hospitality-pos')) return;
        var search = $('#search_product');
        var slot = $('.hospitality-search-slot');
        if (slot.length && search.length) {
            var column = search.closest('.col-md-8');
            slot.append(column.children());
            column.remove();
            $('#customer_id').closest('.col-md-4').addClass('hospitality-customer-column');
            search.attr('placeholder', slot.data('placeholder')).attr('aria-label', slot.data('placeholder'));
        }
        var preset = slot.length ? slot : $('.hospitality-ticket-heading');
        if (preset.data('empty')) $('.pos-cart-empty-state strong').text(preset.data('empty'));
        if (preset.data('hint')) $('.pos-cart-empty-state small').text(preset.data('hint'));
        if (preset.data('icon')) $('.pos-cart-empty-state > span i').attr('class', 'fas ' + preset.data('icon'));
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
            $('.hospitality-item-count').text(count + (count === 1 ? ' item' : ' items'));
        }
        if (body) new MutationObserver(updateCount).observe(body, {childList: true});
        updateCount();
    });
})(jQuery);
