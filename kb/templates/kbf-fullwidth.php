<?php
/**
 * Template Name: KBF Full Width
 * Template Post Type: page
 *
 * Full-width layout for KonekBayan pages without the theme container.
 */
if (!defined('ABSPATH')) exit;

get_header();
?>
<style>
  html, body {
    width: 100%;
    max-width: none;
    margin: 0;
    padding: 0;
  }
  #page,
  .wp-site-blocks,
  .wp-block-group,
  .wp-block-group__inner-container,
  .is-layout-constrained,
  .wp-block-post-content {
    width: 100% !important;
    max-width: none !important;
    margin: 0 !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
  }
  .kbf-fullwidth-template{
    width: 100%;
    max-width: none;
    margin: 0;
    padding: 0;
  }
</style>
<main class="kbf-fullwidth-template">
  <?php
  while (have_posts()) {
      the_post();
      echo do_shortcode(get_the_content());
  }
  ?>
</main>
<?php
get_footer();
