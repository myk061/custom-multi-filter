<?php
// Kısa kod
add_shortcode('post_filters', 'upf_display_filters');
function upf_display_filters() {
    wp_enqueue_style('upf-style');
    wp_enqueue_script('upf-script');
    
    $filters = get_option('upf_filters', []);
    ob_start();
    ?>
    <form id="upf-filter-form" method="GET">
        <div class="upf-filters-grid">
            <?php foreach ($filters as $index => $filter) : 
                $taxonomy = $filter['taxonomy'];
                $taxonomy_obj = get_taxonomy($taxonomy);
                $label = $filter['label'] ?? ($taxonomy_obj ? $taxonomy_obj->labels->name : '');
                ?>
                <div class="upf-filter-group" data-taxonomy="<?= esc_attr($taxonomy) ?>">
                    <label><?= esc_html($label) ?></label>
                    <div class="upf-filter-options" data-index="<?= $index ?>">
                        <div class="upf-loading">Yükleniyor...</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="upf-action-buttons">
            <button type="button" class="upf-filter-button">Filtrele</button>
            <button type="button" class="upf-reset-button">Filtreyi Sıfırla</button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

// Script ve stiller
add_action('wp_enqueue_scripts', 'upf_enqueue_assets');
function upf_enqueue_assets() {
    wp_register_style('upf-style', plugin_dir_url(__FILE__) . '../assets/css/frontend.css');
    wp_register_script('upf-script', plugin_dir_url(__FILE__) . '../assets/js/frontend.js', ['jquery'], '1.3', true);
    
    // Filtre konfigürasyonunu JavaScript'e aktar
    $filters_config = [];
    $filters = get_option('upf_filters', []);
    
    foreach ($filters as $index => $filter) {
        $filters_config[$index] = [
            'taxonomy' => $filter['taxonomy'],
            'label' => $filter['label'] ?? ''
        ];
    }
    
    wp_localize_script('upf-script', 'upf_vars', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'filters' => $filters_config
    ]);
}