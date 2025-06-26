jQuery(document).ready(function($) {
    const $form = $('#upf-filter-form');
    const $filterGroups = $('.upf-filter-group');
    let isUpdating = false;
    
    // Filtre seçeneklerini yükle
    function loadFilterOptions() {
        if (isUpdating) return;
        isUpdating = true;
        
        const activeFilters = getActiveFilters();
        
        $filterGroups.each(function() {
            const $group = $(this);
            const taxonomy = $group.data('taxonomy');
            const $container = $group.find('.upf-filter-options');
            const index = $container.data('index');
            
            $.ajax({
                url: upf_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'upf_get_terms',
                    taxonomy: taxonomy,
                    active_filters: activeFilters
                },
                beforeSend: function() {
                    $container.html('<div class="upf-loading">Yükleniyor...</div>');
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        let html = '<div class="upf-terms-container">';
                        
                        response.data.forEach(term => {
                            // Mevcut seçili değeri kontrol et
                            const isActive = activeFilters[taxonomy] === term.slug;
                            
                            html += `
                                <label class="upf-term ${isActive ? 'active' : ''}">
                                    <input type="radio" 
                                           name="filter_${index}" 
                                           value="${term.slug}" 
                                           ${isActive ? 'checked' : ''}>
                                    ${term.name} 
                                    <span class="upf-count">(${term.count})</span>
                                </label>
                            `;
                        });
                        
                        html += '</div>';
                        $container.html(html);
                    } else {
                        $container.html('<div class="upf-no-terms">Filtre seçeneği bulunamadı</div>');
                    }
                },
                complete: function() {
                    isUpdating = false;
                }
            });
        });
    }
    
    // Aktif filtreleri getir (tüm filtre gruplarından)
    function getActiveFilters() {
        const active = {};
        $form.find('input[type="radio"]:checked').each(function() {
            const name = $(this).attr('name');
            const taxIndex = name.split('_')[1];
            
            if (upf_vars.filters[taxIndex]) {
                const taxonomy = upf_vars.filters[taxIndex].taxonomy;
                active[taxonomy] = $(this).val();
            }
        });
        return active;
    }
    
    // Filtre değişikliklerini takip et
    $form.on('change', 'input[type="radio"]', function() {
        // Tüm filtre seçeneklerini yenile
        loadFilterOptions();
    });
    
    // Filtreleme butonu
    $('.upf-filter-button').click(function(e) {
        e.preventDefault();
        
        // Tüm aktif filtreleri topla
        const activeFilters = getActiveFilters();
        const queryParams = [];
        
        // Tüm filtre parametrelerini URL için hazırla
        for (let i = 0; i < upf_vars.filters.length; i++) {
            const taxonomy = upf_vars.filters[i].taxonomy;
            if (activeFilters[taxonomy]) {
                queryParams.push(`filter_${i}=${encodeURIComponent(activeFilters[taxonomy])}`);
            }
        }
        
        // URL'yi oluştur ve yönlendir
        const newUrl = queryParams.length > 0 
            ? `${window.location.pathname}?${queryParams.join('&')}`
            : window.location.pathname;
            
        window.location.href = newUrl;
    });
    
    // Sıfırlama butonu
    $('.upf-reset-button').click(function() {
        window.location.href = window.location.pathname;
    });
    
    // Sayfa yüklendiğinde aktif filtreleri kontrol et
    function initializeActiveFilters() {
        const urlParams = new URLSearchParams(window.location.search);
        const activeFilters = {};
        
        // URL'deki tüm filtre parametrelerini bul
        for (let i = 0; i < upf_vars.filters.length; i++) {
            const param = `filter_${i}`;
            if (urlParams.has(param)) {
                const value = urlParams.get(param);
                activeFilters[upf_vars.filters[i].taxonomy] = value;
                
                // İlgili radio butonu seçili yap
                $(`input[name="${param}"][value="${value}"]`).prop('checked', true);
            }
        }
    }
    
    // İlk yükleme
    initializeActiveFilters();
    loadFilterOptions();
});