<?php
/*
Plugin Name: Custom Multi Filter
Description: Admin panelden filtre tanımlayıp SEO dostu URL yönlendirmesi yapan filtreleme eklentisi.
Version: 1.2
Author: MYK
*/

// Admin script ve stil dosyalarını ekle
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'settings_page_custom-filter-settings') {
        wp_enqueue_script('custom-filter-admin', plugin_dir_url(__FILE__) . 'admin.js', ['jquery'], null, true);
        wp_enqueue_style('custom-filter-admin-style', plugin_dir_url(__FILE__) . 'admin.css');
        wp_localize_script('custom-filter-admin', 'customFilterData', [
            'taxonomies' => get_taxonomies(['public' => true], 'objects')
        ]);
    }
});

// Ayarlar menüsünü sekmeli olarak ekle (güncellenmiş)
add_action('admin_menu', function() {
    add_options_page('Filtre Ayarları', 'Filtre Ayarları', 'manage_options', 'custom-filter-settings', function() {
        ?>
        <div class="wrap">
            <h1>Filtre Ayarları</h1>

            <h2 class="nav-tab-wrapper" id="filter-tabs">
                <a href="#tab-filters" class="nav-tab nav-tab-active">Filtreler</a>
                <a href="#tab-seo" class="nav-tab">SEO Ayarları</a>
            </h2>

            <form method="post" action="options.php">
                <?php settings_fields('custom_filter_group'); ?>

                <div id="tab-filters" class="tab-content">
                    <?php
                    $val = get_option('custom_filter_definitions', '[]');
                    $filters = json_decode($val, true);
                    $filters = is_array($filters) ? $filters : [];
                    $taxonomies = get_taxonomies(['public' => true], 'objects');

                    echo '<div id="filter-list">';
                    foreach ($filters as $filter) {
                        $label = esc_attr($filter['label'] ?? '');
                        $taxonomy = esc_attr($filter['taxonomy'] ?? 'category');
                        $parent = esc_attr($filter['parent'] ?? '');
                        echo '<div class="filter-row">';
                        echo '<input type="text" class="filter-label" placeholder="Etiket" value="'.$label.'">';
                        echo '<select class="filter-taxonomy">';
                        foreach ($taxonomies as $tax) {
                            echo '<option value="'.$tax->name.'" '.selected($tax->name, $taxonomy, false).'>'.$tax->label.'</option>';
                        }
                        echo '</select>';
                        echo '<input type="number" class="filter-parent" placeholder="Parent ID" value="'.$parent.'" '.($taxonomy === 'category' ? '' : 'style="display:none;"').'>';
                        echo '<button class="remove-filter button">Sil</button>';
                        echo '</div>';
                    }
                    echo '</div>';

                    echo '<p style="color: #d54e21; font-weight: bold;">Kategori dışındaki seçimler yapım aşamasındadır.</p>';

                    echo '<button type="button" id="add-filter" class="button button-primary">Filtre Ekle</button>';
                    echo '<input type="hidden" name="custom_filter_definitions" id="custom_filter_definitions">';
                    ?>
                </div>

                <div id="tab-seo" class="tab-content" style="display:none;">
                    <?php
                    $base_slug = get_option('custom_filter_base_slug', 'filter');
                    $title_sep = get_option('custom_filter_title_separator', ' - ');
                    $site_sep = get_option('custom_filter_site_name_separator', ' | ');
                    $site_name = get_option('custom_filter_site_name', get_bloginfo('name')); // Yeni eklendi
                    $permalink_url = admin_url('options-permalink.php');
                    ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Filtre URL Temeli</th>
                            <td>
                                <input type="text" name="custom_filter_base_slug" value="<?php echo esc_attr($base_slug); ?>" placeholder="filter veya filtre gibi" style="width: 300px;">
                                <p style="margin-top:8px; color:#555;">Bu alanı değiştirdikten sonra <a href="<?php echo esc_url($permalink_url); ?>" target="_blank">Kalıcı Bağlantılar</a> sayfasını açıp "Kaydet" butonuna basmalısınız.</p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Başlık Ayırıcı</th>
                            <td><input type="text" name="custom_filter_title_separator" value="<?php echo esc_attr($title_sep); ?>" style="width:100px;"></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Site Adı Ayırıcı</th>
                            <td><input type="text" name="custom_filter_site_name_separator" value="<?php echo esc_attr($site_sep); ?>" style="width:100px;"></td>
                        </tr>
                        <tr valign="top"> <!-- Site adı girişi -->
                            <th scope="row">Title</th>
                            <td><input type="text" name="custom_filter_site_name" value="<?php echo esc_attr($site_name); ?>" style="width:300px;"></td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>

        <script>
        (function($){
            $(document).ready(function(){
                $('#filter-tabs a').click(function(e){
                    e.preventDefault();
                    $('#filter-tabs a').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active');
                    $('.tab-content').hide();
                    $($(this).attr('href')).show();
                });
            });
        })(jQuery);
        </script>

        <style>
            .tab-content { margin-top: 20px; }
        </style>
        <?php
    });
});

// Ayar alanlarını kaydet
add_action('admin_init', function() {
    register_setting('custom_filter_group', 'custom_filter_definitions');
    register_setting('custom_filter_group', 'custom_filter_base_slug', ['default' => 'filter']);

    function custom_filter_add_spaces($input) {
        $input = trim($input);
        return ' ' . $input . ' ';
    }

    register_setting('custom_filter_group', 'custom_filter_title_separator', [
        'default' => ' - ',
        'sanitize_callback' => 'custom_filter_add_spaces',
    ]);

    register_setting('custom_filter_group', 'custom_filter_site_name_separator', [
        'default' => ' | ',
        'sanitize_callback' => 'custom_filter_add_spaces',
    ]);

    // Yeni kayıt eklendi
    register_setting('custom_filter_group', 'custom_filter_site_name', [
        'default' => get_bloginfo('name'),
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    add_settings_section('custom_filter_section', 'Filtre Tanımları', null, 'custom-filter-settings');

    add_settings_field('custom_filter_base_slug', 'Filtre URL Temeli', function() {
        $val = get_option('custom_filter_base_slug', 'filter');
        $permalink_url = admin_url('options-permalink.php');
        echo '<input type="text" name="custom_filter_base_slug" value="'.esc_attr($val).'" placeholder="filter veya filtre gibi" style="width: 300px;">';
        echo '<p style="margin-top:8px; color:#555;">Bu alanı değiştirdikten sonra <a href="'.esc_url($permalink_url).'" target="_blank">Kalıcı Bağlantılar</a> sayfasını açıp "Kaydet" butonuna basmalısınız.</p>';
    }, 'custom-filter-settings', 'custom_filter_section');

    /*
    // Daha önceki manuel eklenen alanlar artık gereksiz, formda gösterildiği için yorumda
    add_settings_field('title_separator', 'Başlık Ayırıcı', function() {
        $val = get_option('custom_filter_title_separator', ' - ');
        echo '<input type="text" name="custom_filter_title_separator" value="'.esc_attr($val).'" style="width:100px;">';
    }, 'custom-filter-settings', 'custom_filter_section');

    add_settings_field('site_name_separator', 'Site Adı Ayırıcı', function() {
        $val = get_option('custom_filter_site_name_separator', ' | ');
        echo '<input type="text" name="custom_filter_site_name_separator" value="'.esc_attr($val).'" style="width:100px;">';
    }, 'custom-filter-settings', 'custom_filter_section');
    */
});

// Shortcode [custom_filters]
add_shortcode('custom_filters', function() {
    $filters = json_decode(get_option('custom_filter_definitions', '[]'), true);
    if (!$filters || !is_array($filters)) return '';
    $html = '';
    foreach ($filters as $key => $filter) {
        $label = esc_html($filter['label'] ?? 'Seçim Yap');
        $taxonomy = $filter['taxonomy'] ?? 'category';
        $args = ['taxonomy' => $taxonomy, 'hide_empty' => false];

        if ($taxonomy === 'category' && isset($filter['parent'])) {
            $args['parent'] = intval($filter['parent']);
        }

        $terms = get_terms($args);
        if (is_wp_error($terms)) continue;

        $html .= '<select class="custom-filter-dropdown" id="cf-'.$key.'" data-key="'.esc_attr($key).'" data-taxonomy="'.esc_attr($taxonomy).'">';
        $html .= '<option value="">'.$label.'</option>';
        foreach ($terms as $term) {
            $html .= '<option value="'.esc_attr($term->slug).'">'.esc_html($term->name).'</option>';
        }
        $html .= '</select> ';
    }
    return $html;
});

// JS ile filtre seçimini işle
add_action('wp_footer', function() {
    $base_slug = get_option('custom_filter_base_slug', 'filter');
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const selects = document.querySelectorAll('.custom-filter-dropdown');
        const baseUrl = '<?php echo rtrim(site_url("/" . $base_slug . "/"), '/'); ?>/';
        const path = window.location.pathname;
        const basePath = new URL(baseUrl).pathname;
        let slugsStr = '';
        if (path.startsWith(basePath)) {
            slugsStr = path.slice(basePath.length);
        }
        let slugs = slugsStr.split('/').filter(s => s.length > 0);

        // Dropdownlara seçilen slugları uygula
        selects.forEach((select, index) => {
            if (slugs[index]) {
                select.value = slugs[index];
            }
        });

        // Dropdown değişince URL'yi güncelle
        selects.forEach((select, index) => {
            select.addEventListener('change', () => {
                let updatedSlugs = [];
                selects.forEach(s => {
                    updatedSlugs.push(s.value || '');
                });

                // boş olanları sona kadar sil
                while (updatedSlugs.length > 0 && updatedSlugs[updatedSlugs.length - 1] === '') {
                    updatedSlugs.pop();
                }

                if (updatedSlugs.length > 0) {
                    window.location.href = baseUrl + updatedSlugs.join('/') + '/';
                } else {
                    window.location.href = baseUrl;
                }
            });
        });
    });
    </script>
    <?php
});

// Rewrite kuralı tanımla
add_action('init', function() {
    $base_slug = get_option('custom_filter_base_slug', 'filter');
    add_rewrite_rule('^' . $base_slug . '/(.+)/?$', 'index.php?custom_filters=$matches[1]', 'top');
});

// Query var ekle
add_filter('query_vars', function($vars) {
    $vars[] = 'custom_filters';
    return $vars;
});

// URL'den gelen sluglara göre şablonu göster
add_action('template_redirect', function() {
    $slugs = get_query_var('custom_filters');
    if ($slugs) {
        $slugs_arr = explode('/', trim($slugs, '/'));
        $terms = [];
        foreach ($slugs_arr as $slug) {
            $term = get_term_by('slug', $slug, 'category');
            if ($term) $terms[] = $term;
        }
        if (!empty($terms)) {
            include plugin_dir_path(__FILE__) . 'template.php';
            exit;
        } else {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            get_template_part(404);
            exit;
        }
    }
});

// Sorguya kategori filtreleri uygula
add_action('pre_get_posts', function($query) {
    if (!is_admin() && $query->is_main_query() && get_query_var('custom_filters')) {
        $slugs_arr = explode('/', trim(get_query_var('custom_filters'), '/'));
        $tax_query = [];

        foreach ($slugs_arr as $slug) {
            $term = get_term_by('slug', $slug, 'category');
            if ($term) {
                $tax_query[] = [
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => [$term->term_id],
                ];
            }
        }

        if (!empty($tax_query)) {
            $query->set('tax_query', [
                'relation' => 'AND',
                ...$tax_query
            ]);
        }
    }
});

// Ayar değişirse rewrite kurallarını sıfırla
add_action('update_option_custom_filter_base_slug', function($old, $new) {
    if ($old !== $new) flush_rewrite_rules();
}, 10, 2);

// SEO başlığı filtrele
add_filter('pre_get_document_title', function($title) {
    if (is_admin()) return $title;

    $slugs = get_query_var('custom_filters');
    if (!$slugs) return $title;

    $filters = json_decode(get_option('custom_filter_definitions', '[]'), true);
    if (!is_array($filters)) return $title;

    $title_sep = get_option('custom_filter_title_separator', ' - ');
    $site_sep = get_option('custom_filter_site_name_separator', ' | ');
    $site_name = get_option('custom_filter_site_name', get_bloginfo('name'));

    $slugs_arr = explode('/', trim($slugs, '/'));
    $parts = [];

    foreach ($filters as $i => $filter) {
        if (!isset($slugs_arr[$i]) || empty($slugs_arr[$i])) continue;
        $taxonomy = $filter['taxonomy'] ?? 'category';
        $term = get_term_by('slug', $slugs_arr[$i], $taxonomy);
        if ($term && !is_wp_error($term)) {
            $parts[] = $term->name;
        }
    }

    if (!empty($parts)) {
        return implode($title_sep, $parts) . $site_sep . $site_name;
    }

    return $title;
}, 9999);

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('custom-filter-frontend', plugin_dir_url(__FILE__) . 'frontend.js', ['jquery'], null, true);

    $filters = json_decode(get_option('custom_filter_definitions', '[]'), true);
    $placeholders = [];
    if (is_array($filters)) {
        foreach ($filters as $key => $filter) {
            $placeholders[$key] = $filter['label'] ?? 'Seçim Yap';
        }
    }

wp_localize_script('custom-filter-frontend', 'customFilterAjax', [
    'ajaxurl'     => admin_url('admin-ajax.php'),
    'base_slug'   => get_option('custom_filter_base_slug', 'filter'),
    'base_url'    => rtrim(home_url(), '/') . '/' . trim(get_option('custom_filter_base_slug', 'filter'), '/'),
    'home_url'    => trailingslashit(home_url()),
    'placeholders'=> $placeholders,
]);
});

add_action('wp_ajax_get_filtered_terms', 'custom_filter_get_filtered_terms');
add_action('wp_ajax_nopriv_get_filtered_terms', 'custom_filter_get_filtered_terms');

function custom_filter_get_filtered_terms() {
    $filters = json_decode(get_option('custom_filter_definitions', '[]'), true);
    $selected_slugs = isset($_GET['selected_slugs']) ? (array) $_GET['selected_slugs'] : [];
    $current_index = isset($_GET['current_index']) ? intval($_GET['current_index']) : -1;

    $results = [];

    // 1. Adım: Seçili slug'lara göre içerikleri bul
    $query_args = [
        'post_type' => 'post',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ];

    $tax_query = [];

    foreach ($selected_slugs as $slug) {
        if (!$slug) continue;
        $term = get_term_by('slug', $slug, 'category');
        if ($term) {
            $tax_query[] = [
                'taxonomy' => 'category',
                'field'    => 'term_id',
                'terms'    => $term->term_id,
            ];
        }
    }

    if (count($tax_query) > 1) {
        $tax_query['relation'] = 'AND'; // Her seçilen kategoriyle ilişkili içerikler
    }

    if (!empty($tax_query)) {
        $query_args['tax_query'] = $tax_query;
    }

    $matching_post_ids = get_posts($query_args); // Seçilen kategorilere göre eşleşen içerikler

    // 2. Adım: Geriye kalan filtreler için sadece ortak içeriklerdeki kategorileri döndür
    foreach ($filters as $i => $filter) {
        if ($i <= $current_index) {
            $results[$i] = []; // önceki filtreler değiştirilmez
            continue;
        }

        $taxonomy = $filter['taxonomy'] ?? 'category';

        $args = [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ];

        if ($taxonomy === 'category' && isset($filter['parent'])) {
            $args['parent'] = intval($filter['parent']);
        }

        $terms = get_terms($args);
        $valid_terms = [];

        foreach ($terms as $term) {
            $post_ids = get_objects_in_term($term->term_id, $taxonomy);
            if (array_intersect($post_ids, $matching_post_ids)) {
                $valid_terms[] = [
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }
        }

        $results[$i] = $valid_terms;
    }

    wp_send_json_success($results);
}