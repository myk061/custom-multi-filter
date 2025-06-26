<?php
/*
Plugin Name: Advanced Custom Multi Filter
Description: SEO uyumlu çoklu taksonomi filtreleme eklentisi
Version: 3.0
Author: Myk
*/

if (!defined('ABSPATH')) exit;

// Admin menüsü
add_action('admin_menu', 'cmf_admin_menu');
function cmf_admin_menu() {
    add_menu_page(
        'Filtre Ayarları',
        'Filtre Ayarları',
        'manage_options',
        'cmf-settings',
        'cmf_settings_page',
        'dashicons-filter',
        80
    );
}

// Admin ayar sayfası
function cmf_settings_page() {
    $new_row_template = cmf_filter_row_html('__INDEX__');
    ?>
    <div class="wrap">
        <h1>Filtre Ayarları</h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="#general-settings" class="nav-tab nav-tab-active">Genel Ayarlar</a>
            <a href="#seo-settings" class="nav-tab">SEO Ayarları</a>
        </h2>
        
        <form method="post" action="options.php" id="cmf-settings-form">
            <?php settings_fields('cmf_settings'); ?>
            
            <div id="general-settings" class="tab-content active">
                <h3>Filtre Konfigürasyonu</h3>
                <div id="filter-rows-container">
                    <?php
                    $filters = get_option('cmf_filters', array());
                    if (empty($filters)) $filters = [['taxonomy' => '', 'parent' => '']];
                    
                    foreach ($filters as $index => $filter) {
                        echo cmf_filter_row_html($index, $filter['taxonomy'], $filter['parent']);
                    }
                    ?>
                </div>
                <button type="button" id="add-filter-row" class="button">+ Yeni Filtre Ekle</button>
            </div>
            
            <div id="seo-settings" class="tab-content" style="display:none;">
                <h3>SEO Optimizasyonu</h3>
                <table class="form-table">
                    <tr>
                        <th>Filtre URL Öneki</th>
                        <td>
                            <input type="text" name="cmf_base_slug" value="<?= esc_attr(get_option('cmf_base_slug', 'filter')) ?>" class="regular-text">
                            <p class="description">Örnek: "filtre" için -> /filtre/berlin/teknoloji</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Başlık Ayırıcı</th>
                        <td>
                            <input type="text" name="cmf_title_separator" value="<?= esc_attr(get_option('cmf_title_separator', '-')) ?>" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th>Son Ayırıcı</th>
                        <td>
                            <input type="text" name="cmf_final_separator" value="<?= esc_attr(get_option('cmf_final_separator', '|')) ?>" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th>Başlık Son Eki</th>
                        <td>
                            <input type="text" name="cmf_title_suffix" value="<?= esc_attr(get_option('cmf_title_suffix', get_bloginfo('name'))) ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php submit_button(); ?>
        </form>
    </div>
    
    <script>
    jQuery(function($) {
        $('.nav-tab').click(function(e) {
            e.preventDefault();
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('.tab-content').hide().removeClass('active');
            $($(this).attr('href')).show().addClass('active');
        });

        $('#add-filter-row').click(function() {
            const index = Date.now();
            const rowHtml = `<?= addslashes($new_row_template) ?>`.replace(/__INDEX__/g, index);
            $('#filter-rows-container').append(rowHtml);
        });

        $(document).on('click', '.remove-row', function() {
            $(this).closest('.filter-row').remove();
        });

        $(document).on('change', '.cmf-taxonomy-select', function() {
            const row = $(this).closest('.filter-row');
            const taxonomy = $(this).val();
            row.find('.parent-id-field').toggle(taxonomy && taxonomy.length > 0);
        });
    });
    </script>
    <style>
    .tab-content:not(.active) { display: none; }
    .filter-row { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
    .parent-id-field { margin-top: 10px; }
    </style>
    <?php
}

// Filtre satırı HTML
function cmf_filter_row_html($index = 0, $selected_tax = '', $parent_id = '') {
    $taxonomies = get_taxonomies(['public' => true], 'objects');
    ob_start(); ?>
    <div class="filter-row" data-index="<?= $index ?>">
        <div class="filter-row-content">
            <select name="cmf_filters[<?= $index ?>][taxonomy]" class="cmf-taxonomy-select">
                <option value="">Taksonomi Seçin</option>
                <?php foreach ($taxonomies as $tax) : ?>
                    <option value="<?= $tax->name ?>" <?= selected($selected_tax, $tax->name) ?>>
                        <?= $tax->labels->singular_name ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <div class="parent-id-field" style="<?= ($selected_tax && is_taxonomy_hierarchical($selected_tax)) ? '' : 'display:none;' ?>">
                <label>Ana Kategori ID:</label>
                <input type="number" name="cmf_filters[<?= $index ?>][parent]" value="<?= esc_attr($parent_id) ?>" min="0">
            </div>
            
            <button type="button" class="button remove-row">Kaldır</button>
        </div>
    </div>
    <?php return ob_get_clean();
}

// Ayarları kaydet
add_action('admin_init', 'cmf_register_settings');
function cmf_register_settings() {
    register_setting('cmf_settings', 'cmf_filters');
    register_setting('cmf_settings', 'cmf_base_slug');
    register_setting('cmf_settings', 'cmf_title_separator');
    register_setting('cmf_settings', 'cmf_final_separator');
    register_setting('cmf_settings', 'cmf_title_suffix');
}

// Script ve stiller
add_action('wp_enqueue_scripts', 'cmf_frontend_scripts');
function cmf_frontend_scripts() {
    wp_enqueue_style('cmf-style', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    wp_enqueue_script('cmf-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', ['jquery'], '3.0', true);
    
    wp_localize_script('cmf-script', 'cmf_vars', [
        'base_slug' => get_option('cmf_base_slug', 'filter'),
        'home_url' => home_url('/'),
        'ajax_url' => admin_url('admin-ajax.php'),
        'filters' => get_option('cmf_filters', [])
    ]);
}

// Kısa kod
add_shortcode('custom_filters', 'cmf_display_filters');
function cmf_display_filters() {
    $filters = get_option('cmf_filters', []);
    ob_start(); ?>
    <div class="cmf-filters">
        <?php foreach ($filters as $index => $filter) : 
            if (empty($filter['taxonomy'])) continue;
            
            $taxonomy = $filter['taxonomy'];
            $parent_id = !empty($filter['parent']) ? (int)$filter['parent'] : 0;
            $taxonomy_obj = get_taxonomy($taxonomy);
            $label = $taxonomy_obj ? $taxonomy_obj->labels->singular_name : $taxonomy;
            
            // Başlangıçta tüm terimleri getir
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => true,
                'child_of' => $parent_id
            ]);
            
            if (!empty($terms) && !is_wp_error($terms)) : ?>
                <div class="cmf-filter-group">
                    <label><?= esc_html($label) ?></label>
                    <select class="cmf-filter-dropdown" data-index="<?= $index ?>" data-taxonomy="<?= esc_attr($taxonomy) ?>">
                        <option value="">Tümü</option>
                        <?php foreach ($terms as $term) : ?>
                            <option value="<?= esc_attr($term->slug) ?>">
                                <?= esc_html($term->name) ?> (<?= $term->count ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif;
        endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Yeniden yazma kuralları
add_action('init', 'cmf_add_rewrite_rules');
function cmf_add_rewrite_rules() {
    $base_slug = get_option('cmf_base_slug', 'filter');
    add_rewrite_rule("^{$base_slug}/([^/]+)/?$", 'index.php?cmf_filter=$matches[1]', 'top');
    add_rewrite_rule("^{$base_slug}/([^/]+)/([^/]+)/?$", 'index.php?cmf_filter=$matches[1]&cmf_filter2=$matches[2]', 'top');
    add_rewrite_rule("^{$base_slug}/([^/]+)/([^/]+)/([^/]+)/?$", 'index.php?cmf_filter=$matches[1]&cmf_filter2=$matches[2]&cmf_filter3=$matches[3]', 'top');
    
    add_rewrite_tag('%cmf_filter%', '([^&]+)');
    add_rewrite_tag('%cmf_filter2%', '([^&]+)');
    add_rewrite_tag('%cmf_filter3%', '([^&]+)');
}

// Template yönlendirme
add_action('template_redirect', 'cmf_template_redirect');
function cmf_template_redirect() {
    global $wp_query;
    if (isset($wp_query->query_vars['cmf_filter'])) {
        include(get_template_directory() . '/index.php');
        exit;
    }
}

// Filtreleri ana sorguya uygula
add_action('pre_get_posts', 'cmf_apply_filters_to_main_query');
function cmf_apply_filters_to_main_query($query) {
    if (is_admin() || !$query->is_main_query()) return;
    
    if (isset($query->query_vars['cmf_filter'])) {
        $filters = get_option('cmf_filters', []);
        $tax_query = ['relation' => 'AND'];
        
        // Tüm aktif filtreleri topla
        $active_filters = [];
        for ($i = 1; $i <= 3; $i++) {
            $var = 'cmf_filter' . ($i > 1 ? $i : '');
            if (!empty($query->query_vars[$var])) {
                $active_filters[] = $query->query_vars[$var];
            }
        }
        
        // Her filtre için taksonomiyi bul ve ekle
        foreach ($active_filters as $index => $term_slug) {
            if (isset($filters[$index]['taxonomy'])) {
                $tax_query[] = [
                    'taxonomy' => $filters[$index]['taxonomy'],
                    'field' => 'slug',
                    'terms' => $term_slug
                ];
            }
        }
        
        $query->set('tax_query', $tax_query);
    }
}

// AJAX ile bağımlı filtre seçeneklerini getir
add_action('wp_ajax_cmf_get_filter_options', 'cmf_get_filter_options');
add_action('wp_ajax_nopriv_cmf_get_filter_options', 'cmf_get_filter_options');
function cmf_get_filter_options() {
    $index = (int) $_POST['index'];
    $filters = get_option('cmf_filters', []);
    $active_filters = $_POST['active_filters'] ?? [];

    if (!isset($filters[$index])) {
        wp_send_json_error('Geçersiz filtre indeksi');
    }

    $taxonomy = $filters[$index]['taxonomy'];
    $parent_id = !empty($filters[$index]['parent']) ? (int)$filters[$index]['parent'] : 0;

    // Filtrelenmiş post ID'lerini al
    $post_args = [
        'post_type' => 'post',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => ['relation' => 'AND']
    ];

    foreach ($active_filters as $tax => $term_slug) {
        $post_args['tax_query'][] = [
            'taxonomy' => $tax,
            'field' => 'slug',
            'terms' => $term_slug
        ];
    }

    $filtered_post_ids = get_posts($post_args);

    // Terimleri filtreli postlara göre getir
    $term_args = [
        'taxonomy' => $taxonomy,
        'hide_empty' => true,
        'object_ids' => $filtered_post_ids,
    ];

    if ($parent_id > 0 && is_taxonomy_hierarchical($taxonomy)) {
        $term_args['child_of'] = $parent_id;
    }

    $terms = get_terms($term_args);
    $options = [];

    foreach ($terms as $term) {
        // Terimin mevcut postlardaki sayısını hesapla
        $count_args = [
            'post_type' => 'post',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => [
                'relation' => 'AND',
                [
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $term->term_id
                ]
            ]
        ];
        
        // Diğer filtreleri de ekle
        foreach ($active_filters as $tax => $term_slug) {
            $count_args['tax_query'][] = [
                'taxonomy' => $tax,
                'field' => 'slug',
                'terms' => $term_slug
            ];
        }
        
        $count = count(get_posts($count_args));
        
        if ($count > 0) {
            $options[] = [
                'slug' => $term->slug,
                'name' => $term->name,
                'count' => $count
            ];
        }
    }

    wp_send_json_success(['options' => $options]);
}

// Aktivasyon/deaktivasyon
register_activation_hook(__FILE__, function() {
    cmf_add_rewrite_rules();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});