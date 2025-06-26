<?php
add_action('admin_menu', 'upf_admin_menu');
function upf_admin_menu() {
    add_menu_page(
        'Filtre Ayarları',
        'Filtre Ayarları',
        'manage_options',
        'upf-settings',
        'upf_settings_page',
        'dashicons-filter',
        80
    );
}

function upf_settings_page() {
    // Filtre yönetimi arayüzü
    ?>
    <div class="wrap">
        <h1>Filtre Yönetimi</h1>
        
        <div id="upf-filters-container">
            <?php
            $filters = get_option('upf_filters', []);
            foreach ($filters as $index => $filter) {
                upf_render_filter_row($index, $filter);
            }
            ?>
        </div>
        
        <button id="add-filter" class="button button-primary">+ Yeni Filtre Ekle</button>
        
        <script>
        jQuery(document).ready(function($) {
            // Yeni filtre ekle
            $('#add-filter').click(function() {
                const index = Date.now();
                $.post(ajaxurl, {
                    action: 'upf_add_filter_row',
                    index: index
                }, function(response) {
                    $('#upf-filters-container').append(response);
                });
            });
            
            // Filtre sil
            $(document).on('click', '.remove-filter', function() {
                $(this).closest('.filter-row').remove();
            });
        });
        </script>
    </div>
    <?php
}

add_action('wp_ajax_upf_add_filter_row', 'upf_add_filter_row');
function upf_add_filter_row() {
    $index = intval($_POST['index']);
    upf_render_filter_row($index);
    wp_die();
}

function upf_render_filter_row($index, $filter = []) {
    $taxonomies = get_taxonomies(['public' => true], 'objects');
    ?>
    <div class="filter-row" data-index="<?= $index ?>">
        <h3>Filtre #<?= $index + 1 ?></h3>
        <div class="filter-settings">
            <div>
                <label>Taksonomi Seçin:</label>
                <select name="upf_filters[<?= $index ?>][taxonomy]">
                    <?php foreach ($taxonomies as $tax) : ?>
                        <option value="<?= $tax->name ?>" <?php selected($filter['taxonomy'] ?? '', $tax->name) ?>>
                            <?= $tax->labels->name ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label>Filtre Etiketi:</label>
                <input type="text" name="upf_filters[<?= $index ?>][label]" 
                       value="<?= esc_attr($filter['label'] ?? '') ?>" 
                       placeholder="Örnek: Kategoriler">
            </div>
            
            <div>
                <label>Ana Kategori ID (opsiyonel):</label>
                <input type="number" name="upf_filters[<?= $index ?>][parent]" 
                       value="<?= esc_attr($filter['parent'] ?? '') ?>" min="0">
            </div>
            
            <button type="button" class="button remove-filter">Filtreyi Sil</button>
        </div>
    </div>
    <?php
}

add_action('admin_init', 'upf_register_settings');
function upf_register_settings() {
    register_setting('upf_settings', 'upf_filters');
}