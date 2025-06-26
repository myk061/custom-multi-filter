<?php
add_action('pre_get_posts', 'upf_apply_filters');
function upf_apply_filters($query) {
    if (is_admin() || !$query->is_main_query()) return;
    
    $applied_filters = [];
    $filters = get_option('upf_filters', []);
    
    // Tüm aktif filtreleri topla (tüm filtre parametrelerini kontrol et)
    foreach ($filters as $index => $filter) {
        $param = 'filter_' . $index;
        
        if (isset($_GET[$param]) && !empty($_GET[$param])) {
            $applied_filters[] = [
                'taxonomy' => $filter['taxonomy'],
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_GET[$param])
            ];
        }
    }
    
    // Tüm filtreleri AND mantığıyla uygula
    if (!empty($applied_filters)) {
        $applied_filters['relation'] = 'AND';
        $query->set('tax_query', $applied_filters);
    }
}

add_action('wp_ajax_upf_get_terms', 'upf_get_terms');
add_action('wp_ajax_nopriv_upf_get_terms', 'upf_get_terms');
function upf_get_terms() {
    $taxonomy = sanitize_text_field($_POST['taxonomy']);
    $parent = intval($_POST['parent']);
    $active_filters = $_POST['active_filters'] ?? [];
    
    // Filtrelenmiş postları bul
    $post_args = [
        'post_type' => 'post',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => ['relation' => 'AND']
    ];
    
    // Tüm aktif filtreleri ekle (diğer filtreler dahil)
    foreach ($active_filters as $tax => $term_slug) {
        $post_args['tax_query'][] = [
            'taxonomy' => $tax,
            'field' => 'slug',
            'terms' => $term_slug
        ];
    }
    
    $filtered_post_ids = get_posts($post_args);
    
    // Terimleri getir
    $term_args = [
        'taxonomy' => $taxonomy,
        'hide_empty' => true,
        'object_ids' => $filtered_post_ids
    ];
    
    if ($parent > 0) {
        $term_args['child_of'] = $parent;
    }
    
    $terms = get_terms($term_args);
    $options = [];
    
    foreach ($terms as $term) {
        // Terimin mevcut postlardaki sayısını hesapla
        $count = count(get_objects_in_term($term->term_id, $taxonomy, [
            'post__in' => $filtered_post_ids
        ]));
        
        if ($count > 0) {
            $options[] = [
                'slug' => $term->slug,
                'name' => $term->name,
                'count' => $count
            ];
        }
    }
    
    wp_send_json_success($options);
}