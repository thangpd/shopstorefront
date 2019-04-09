<?php
/**
 * The sidebar containing the main widget area.
 *
 * @package storefront
 */

if ( ! is_active_sidebar( 'mmycred-area' ) && is_user_logged_in() ) {
	return;
}
?>

<div id="secondary" class="widget-area" role="complementary">
	<?php dynamic_sidebar( 'mmycred-area' ); ?>
</div><!-- #secondary -->
