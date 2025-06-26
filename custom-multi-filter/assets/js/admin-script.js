jQuery(document).ready(function($) {
    // Tab geçişleri
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $($(this).attr('href')).addClass('active');
    });

    // Yeni filtre satırı ekle
    $('#add-filter-row').click(function() {
        const index = Date.now();
        const rowHtml = `<?php echo cmf_filter_row_html('__INDEX__'); ?>`.replace(/__INDEX__/g, index);
        $('#filter-rows-container').append(rowHtml);
    });

    // Satır kaldır
    $(document).on('click', '.remove-row', function() {
        $(this).closest('.filter-row').remove();
    });

    // Taksonomi değişince parent ID alanını göster/gizle
    $(document).on('change', '.cmf-taxonomy-select', function() {
        const row = $(this).closest('.filter-row');
        const taxonomy = $(this).val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'check_taxonomy_hierarchical',
                taxonomy: taxonomy
            },
            success: function(response) {
                if (response.hierarchical) {
                    row.find('.parent-id-field').show();
                } else {
                    row.find('.parent-id-field').hide().find('input').val('');
                }
            }
        });
    });
});