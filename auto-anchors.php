<?php
/**
 * Plugin Name: Auto Anchors
 * Plugin URI: http://seysayux.net
 * Description: Automnatically generate anchor tags for headers
 * Version: 0.1
 * Author: Frank "SeySayux" Erens
 * Author URI: http://seysayux.net
 * License: ZLIB
 */

add_action('wp_print_styles', autoanchors_load_styles);
add_filter('the_content', autoanchors_apply);

function autoanchors_load_styles() {
    wp_enqueue_style('AutoAnchors-CSS',
            plugins_url('css/autoanchors.css', __FILE__));
}

function autoanchors_apply($content) {

    // Only show TOC/anchors on single pages
    if(!is_single()) {
        return $content;
    }

    // Find all header tags in document
    $headers = autoanchors_find_headers($content);

    // Add anchors to these header tags
    $content = autoanchors_add_anchors($content, $headers);

    // Create TOC from header tags and prepend it to the content
    $content = autoanchors_create_toc($headers) . $content;

    return $content;
}

function autoanchors_find_headers($content) {
    $pattern = '#<h([1-6])( [^>]+)?>(.+?)</h\1>#is';

    preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

    // Assign nicer names to our matches
    for($i = 0; $i < count($matches); ++$i) {
        $matches[$i]['full'] = $matches[$i][0];
        $matches[$i]['depth'] = $matches[$i][1];
        $matches[$i]['title'] = $matches[$i][3];
        $matches[$i]['tagopen'] = '<h'. $matches[$i][1] . $matches[$i][2] .'>';
        $matches[$i]['tagclose'] = '</h'. $matches[$i]['depth'].'>';

    }

    return $matches;
}

function autoanchors_add_anchors($content, $headers) {
    if(count($headers) < 1) {
        return;
    }

    foreach($headers as $header) {
        $anchorname = autoanchors_anchor_name($header['title']);
        $anchor = $header['tagopen'] .
            '<a id="'.$anchorname.'" href="#'.$anchorname.'" '.
            'class="autoanchors-anchor">' .
            $header['title'] . '</a>' . $header['tagclose'];

        $headerlen = strlen($header['full']);
        $headeroffset = strpos($content, $header['full']);

        $content = substr_replace($content, $anchor, $headeroffset, $headerlen);
    }

    return $content;
}

function autoanchors_create_toc($headers) {
    if(count($headers) < 1) {
        return;
    }

    $toc = '<div class="autoanchors-toc">';
    $toc .= '<div class="autoanchors-container">';
    $toc .= '<div class="autoanchors-toc-header">Contents</div>';

    $currdepth = 0;

    foreach($headers as $header) {
        // Calculate depth difference
        $depthdiff = $header['depth'] - $currdepth;

        // Add the neccesary amount of opening or closing tags for the given
        // depth level
        while($depthdiff > 0) {
            $toc .= '<ol>';
            --$depthdiff;
        }

        while($depthdiff < 0) {
            $toc .= '</ol>';
            ++$depthdiff;
        }

        $currdepth = $header['depth'];

        // Output link to title
        $toc .= '<li><a href="#';
        $toc .= autoanchors_anchor_name($header['title']);
        $toc .= '">';
        $toc .= $header['title'];
        $toc .= '</a></li>';
    }

    // Close all remaining open tags
    while($currdepth > 0) {
        $toc .= '</ol>';
        --$currdepth;
    }

    $toc .= '</div>';

    // Add adverts
    $toc .= autoanchors_add_adverts();

    $toc .= '</div>';

    return $toc;
}

function autoanchors_add_adverts() {
    return '<div class="autoanchors-advert">&nbsp;</div>';
}

function autoanchors_anchor_name($title) {
    // Remove/replace non-ascii characters
    $toreturn = iconv('UTF-8', 'ASCII//TRANSLIT', $title);

    // Remove non-URL characters
    $toreturn = preg_replace('/[^a-zA-Z0-9\/_|+ -]/', '', $toreturn);

    // Remove leading or trailing dashes
    $toreturn = trim($toreturn, '-');

    // Replace special characters with a dash
    $toreturn = preg_replace('/[\/_|+ -]+/', '-', $toreturn);

    // Convert to lowercase
    $toreturn = strtolower($toreturn);

    return urlencode($toreturn);
}


// vim: ts=4:sts=4:sw=4:et

?>
