jQuery(document).ready(function($) {
    function buildRow(label = '', taxonomy = 'category', parent = '') {
        let taxSelect = `<select class="filter-taxonomy">`;
        for (let key in customFilterData.taxonomies) {
            let selected = taxonomy === key ? 'selected' : '';
            taxSelect += `<option value="${key}" ${selected}>${customFilterData.taxonomies[key].label}</option>`;
        }
        taxSelect += `</select>`;

        return `
            <div class="filter-row">
                <input type="text" class="filter-label" placeholder="Etiket" value="${label}">
                ${taxSelect}
                <input type="number" class="filter-parent" placeholder="Parent ID" value="${parent}" ${taxonomy !== 'category' ? 'style="display:none;"' : ''}>
                <button class="remove-filter button">Sil</button>
            </div>`;
    }

    $('#add-filter').on('click', function() {
        $('#filter-list').append(buildRow());
    });

    $('#filter-list').on('change', '.filter-taxonomy', function() {
        const parentInput = $(this).siblings('.filter-parent');
        if ($(this).val() === 'category') {
            parentInput.show();
        } else {
            parentInput.hide();
        }
    });

    $('#filter-list').on('click', '.remove-filter', function() {
        $(this).closest('.filter-row').remove();
    });

    $('form').on('submit', function() {
        let filters = [];
        $('.filter-row').each(function() {
            const label = $(this).find('.filter-label').val();
            const taxonomy = $(this).find('.filter-taxonomy').val();
            const parent = $(this).find('.filter-parent').val();
            if (label && taxonomy) {
                let obj = { label, taxonomy };
                if (taxonomy === 'category' && parent) obj.parent = parseInt(parent);
                filters.push(obj);
            }
        });
        $('#custom_filter_definitions').val(JSON.stringify(filters));
    });
});