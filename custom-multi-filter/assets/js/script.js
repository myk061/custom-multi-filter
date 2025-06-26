document.addEventListener('DOMContentLoaded', function() {
    const dropdowns = document.querySelectorAll('.cmf-filter-dropdown');
    const baseSlug = cmf_vars.base_slug || 'filter';
    const homeUrl = cmf_vars.home_url;
    const ajaxUrl = cmf_vars.ajax_url;
    const filtersConfig = cmf_vars.filters;
    
    // 1. URL'den mevcut filtreleri yükle
    let selectedFilters = [];
    const pathSegments = window.location.pathname.split('/');
    if (pathSegments.includes(baseSlug)) {
        const filterStartIndex = pathSegments.indexOf(baseSlug) + 1;
        selectedFilters = pathSegments.slice(filterStartIndex).filter(Boolean);
    }

    // 2. Dropdown değerlerini ayarla
    dropdowns.forEach((dropdown, index) => {
        if (selectedFilters[index]) {
            dropdown.value = selectedFilters[index];
        }
    });

    // 3. Filtre uygulama
    function applyFilters() {
        const activeFilters = [];
        dropdowns.forEach(dropdown => {
            if (dropdown.value) {
                activeFilters.push(dropdown.value);
            }
        });
        
        const filterPath = activeFilters.length ? `${baseSlug}/${activeFilters.join('/')}/` : '';
        window.location.href = homeUrl + filterPath;
    }
    
    // 4. Dropdown etkinlikleri
    dropdowns.forEach((dropdown) => {
        dropdown.addEventListener('change', function() {
            // Filtreleme işlemini hemen uygula
            applyFilters();
        });
    });
});