<?php
get_header();

$template = get_home_template(); // Genellikle index.php veya home.php

if ($template && file_exists($template)) {
    include $template;
} else {
    echo '<p>İçerik şablonu bulunamadı.</p>';
}

get_footer();