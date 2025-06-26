<?php
// Dinamik başlık
add_filter('pre_get_document_title', 'upf_dynamic_title');
function upf_dynamic_title($title) {
    if (!is_main_query() || is_admin()) return $title;
    
    $filters = get_option('upf_filters', []);
    $active_filters = [];
    
    foreach ($filters as $index => $filter) {
        $param = 'filter_' . $index;
        if (!empty($_GET[$param])) {
            $term = get_term_by('slug', sanitize_text_field($_GET[$param]), $filter['taxonomy']);
            if ($term) $active_filters[] = $term->name;
        }
    }
    
    if (!empty($active_filters)) {
        $separator = ' - ';
        $suffix = get_bloginfo('name');
        return implode($separator, $active_filters) . ' | ' . $suffix;
    }
    
    return $title;
}

// Canonical URL
add_filter('wpseo_canonical', 'upf_canonical_url');
function upf_canonical_url($canonical) {
    if (!is_main_query()) return $canonical;
    
    $filters = get_option('upf_filters', []);
    $base_url = trailingslashit(home_url());
    $query_params = [];
    
    foreach ($filters as $index => $filter) {
        $param = 'filter_' . $index;
        if (!empty($_GET[$param])) {
            $query_params[$param] = sanitize_text_field($_GET[$param]);
        }
    }
    
    if (!empty($query_params)) {
        return $base_url . '?' . http_build_query($query_params);
    }
    
    return $canonical;
}