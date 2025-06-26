<?php
// Başlık SEO optimizasyonu
add_filter('pre_get_document_title', 'cmf_seo_title', 20);
function cmf_seo_title($title) {
    global $wp_query;
    
    if (!isset($wp_query->query_vars['cmf_filter'])) return $title;
    
    $filters = [];
    $separator = get_option('cmf_title_separator', '-');
    $final_separator = get_option('cmf_final_separator', '|');
    $suffix = get_option('cmf_title_suffix', get_bloginfo('name'));
    
    $filter_values = [
        $wp_query->get('cmf_filter'),
        $wp_query->get('cmf_filter2')
    ];
    
    $filters_config = get_option('cmf_filters', []);
    
    foreach ($filter_values as $index => $term_slug) {
        if (!empty($term_slug) && isset($filters_config[$index]['taxonomy'])) {
            $term = get_term_by('slug', $term_slug, $filters_config[$index]['taxonomy']);
            if ($term) $filters[] = $term->name;
        }
    }
    
    if (empty($filters)) return $title;
    
    return implode(" $separator ", $filters) . " $final_separator $suffix";
}

// Canonical URL SEO optimizasyonu
add_filter('wpseo_canonical', 'cmf_seo_canonical');
function cmf_seo_canonical($canonical) {
    global $wp_query;
    
    if (isset($wp_query->query_vars['cmf_filter'])) {
        $base_slug = get_option('cmf_base_slug', 'filter');
        $path = "$base_slug/";
        
        $filter_values = [
            $wp_query->get('cmf_filter'),
            $wp_query->get('cmf_filter2')
        ];
        
        foreach ($filter_values as $term_slug) {
            if (!empty($term_slug)) $path .= sanitize_text_field($term_slug) . '/';
        }
        
        return home_url($path);
    }
    
    return $canonical;
}