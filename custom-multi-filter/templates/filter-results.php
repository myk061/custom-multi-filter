<?php
get_header();

// Filtre terimlerini topla
$filter_terms = array();
for ($i = 1; $i <= 3; $i++) {
    $var = 'cmf_filter' . ($i > 1 ? $i : '');
    if (!empty($wp_query->query_vars[$var])) {
        $filter_terms[] = sanitize_text_field($wp_query->query_vars[$var]);
    }
}

// Filtreleme sorgusu
$args = array(
    'post_type' => 'post',
    'posts_per_page' => -1,
    'tax_query' => array('relation' => 'AND')
);

$filters = get_option('cmf_filters', array());
foreach ($filter_terms as $index => $term_slug) {
    if (!empty($filters[$index]['taxonomy'])) {
        $taxonomy = $filters[$index]['taxonomy'];
        $args['tax_query'][] = array(
            'taxonomy' => $taxonomy,
            'field' => 'slug',
            'terms' => $term_slug
        );
    }
}

$filtered_posts = new WP_Query($args);
?>

<div class="cmf-results-container">
    <h2>Filtrelenmiş Sonuçlar</h2>
    
    <?php if ($filtered_posts->have_posts()) : ?>
        <ul class="cmf-results-list">
            <?php while ($filtered_posts->have_posts()) : $filtered_posts->the_post(); ?>
                <li class="cmf-result-item">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else : ?>
        <p class="cmf-no-results">Filtrenizle eşleşen yazı bulunamadı.</p>
    <?php endif; ?>
    
    <div class="cmf-back-link">
        <a href="<?php echo home_url(); ?>">← Filtreyi Temizle</a>
    </div>
</div>

<?php
wp_reset_postdata();
get_footer();