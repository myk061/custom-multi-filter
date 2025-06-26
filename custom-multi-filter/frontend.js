jQuery(document).ready(function($) {
    const baseUrl = customFilterAjax.base_url;

    function getSelectedSlugs() {
        const slugs = [];
        $('.custom-filter-dropdown').each(function() {
            slugs.push($(this).val() || '');
        });
        return slugs;
    }

function updateUrl(slugs) {
    // Slug’lar boşsa ana sayfaya yönlendir
    if (slugs.every(s => !s)) {
        const homeUrl = customFilterAjax.home_url.replace(/\/+$/, '') + '/';

        // Eğer zaten ana sayfadaysa yeniden yönlendirme yapma
        if (window.location.href !== homeUrl) {
            history.replaceState(null, '', homeUrl);

            setTimeout(() => {
                window.location.href = homeUrl;
            }, 50);
        }

        return;
    }

    // Slug’ların sonundaki boşları temizle
    while (slugs.length > 0 && slugs[slugs.length - 1] === '') {
        slugs.pop();
    }

    const newUrl = customFilterAjax.base_url.replace(/\/+$/, '') + '/' + slugs.join('/') + '/';
    history.replaceState(null, '', newUrl);
}

function updateFilters(changedIndex) {
    const slugs = getSelectedSlugs();
    $('.custom-filter-dropdown').each(function(i) {
    const selectedSlug = slugs[i] || '';
    if (selectedSlug) {
        $(this).find('option').each(function() {
            if ($(this).val() === selectedSlug) {
                $(this).prop('selected', true);
            }
        });
    }
});
	
    $.ajax({
        url: customFilterAjax.ajaxurl,
        method: 'GET',
        dataType: 'json',
        data: {
            action: 'get_filtered_terms',
            selected_slugs: slugs,
            current_index: changedIndex
        },
        success: function(response) {
            if (!response.success) return;

            const termsByFilter = response.data;

            $('.custom-filter-dropdown').each(function(i) {
                if (i <= changedIndex) return;

                const $select = $(this);
                const key = $select.data('key'); // data-key alındı
                const selectedSlug = slugs[i] || '';
                const terms = termsByFilter[i] || [];

                $select.empty();
                const placeholder = customFilterAjax.placeholders[key] || 'Seçim Yap';
                $select.append(`<option value="">${placeholder}</option>`);

                terms.forEach(term => {
                    const selectedAttr = term.slug === selectedSlug ? ' selected' : '';
                    $select.append(`<option value="${term.slug}"${selectedAttr}>${term.name}</option>`);
                });

                if (!selectedSlug || !terms.some(t => t.slug === selectedSlug)) {
                    slugs[i] = '';
                }
            });

            updateUrl(slugs);
        }
    });
}

function applyInitialSelections() {
    const path = window.location.pathname;
    const basePath = '/' + customFilterAjax.base_slug + '/';
    if (!path.startsWith(basePath)) return;

    const slugPart = path.slice(basePath.length).replace(/\/$/, '');
    const slugs = slugPart.split('/');

    $('.custom-filter-dropdown').each(function(i) {
        if (slugs[i]) {
            $(this).val(slugs[i]);
        }
    });
}

    $('.custom-filter-dropdown').on('change', function() {
        const changedIndex = $('.custom-filter-dropdown').index(this);
        updateFilters(changedIndex);
    });

    applyInitialSelections();
    updateFilters(-1);
});

function applyInitialSelections() {
    const path = window.location.pathname;
    const basePath = '/' + customFilterAjax.base_slug + '/';
    if (!path.startsWith(basePath)) return;

    const slugPart = path.slice(basePath.length).replace(/\/$/, '');
    const slugs = slugPart.split('/');

    $('.custom-filter-dropdown').each(function(i) {
        const key = $(this).data('key');
        if (slugs[key]) {
            $(this).val(slugs[key]);
        }
    });
}

$(document).ready(function() {
    applyInitialSelections();
    updateFilters(-1); // Tüm filtreleri yükle
});