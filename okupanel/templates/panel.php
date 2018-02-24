<?php 

if (!defined('ABSPATH'))
	exit();


if (!empty($_GET['okupanel_debug']) && current_user_can('manage_options')){
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
}
	
show_admin_bar(false);

add_filter('wp_title', 'okupanel_panel_title');
add_filter('document_title', 'okupanel_panel_title');

function okupanel_panel_title($title){

	if ($title == '')
		$title = get_bloginfo('name');
		
	$title = apply_filters('okupanel_panel_title', $title);
	return ($title == '' ? '' : $title.' - ').'OkuPanel';
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js okupanel">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	
	<!-- <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>"> -->
	
	<title><?php wp_title(); ?></title>
	
	<?php wp_head(); ?>

	<script type="text/javascript"> 
		<?= get_option('okupanel_panel_js', '') ?> 
	</script>

	<style>
		<?php echo get_option('okupanel_panel_css', ''); ?>

		html, body {
			margin: 0 !important; 
			padding: 0 !important;
		}
	</style>
</head>
<body <?php body_class((!empty($_GET['fullscreen']) ? 'okupanel-fullscreen' : '').(!empty($_GET['moving']) ? ' okupanel-moving' : '')); ?>>
	<?php 
	
	okupanel_print_popup();
	
	if (!empty($_GET['fullscreen']) && ($bottombar_html = okupanel_bottombar())){ ?>
		<div class="okupanel-bottom-bar">
			<div class="okupanel-bottom-bar-inner" data-okupanel-bottombar="<?= esc_attr(okupanel_bottombar()) ?>">
				<div class="okupanel-bottom-bar-lines">
				<?php echo $bottombar_html; ?>
				</div>
			</div>
		</div>
	<?php } ?>
	<div class="okupanel-bg-wrap">
		<div class="okupanel-header-wrap">
			<div class="okupanel-header"><?= get_option('okupanel_intro', 'OkuPanel').okupanel_clock() ?></span>
			</div>
		</div>
		<div class="okupanel-panel">
			<div class="okupanel-panel-mobile-menu">
				<a href="#" onclick="jQuery('body').toggleClass('okupanel-panel-mobile-menu-open'); return false;"><?= get_option('okupanel_links_label', __('Links', 'okupanel')) ?></a>
			</div>
			<div class="okupanel-panel-inner">
				<div class="okupanel-panel-right">
					<div class="okupanel-panel-right-inner"><?= do_shortcode(get_option('okupanel_right_panel', '')) ?></div>
				</div>
				<div class="okupanel-panel-left">
					<div class="okupanel-table-top"><?php okupanel_print_panel(); ?></div>
					<?php if (!empty($_GET['moving'])){ ?>
						<div class="okupanel-table-bottom"><div class="okupanel-table-bottom-inner"><div class="okupanel-table-moving"></div></div></div>
					<?php } ?>
				</div>
			</div>
			<div class="okupanel-footer"><a href="https://wiki.ingobernable.net/doku.php?id=pantalla_entrada" target="_blank">OkuPanel</a><i class="fa fa-copyright fa-rotate-180"></i><a href="https://hacklab.ingobernable.net/" target="_blank">Ingoberlab</a></div>
		</div>
		<?php wp_footer(); ?>
	</div>
</body>
</html><?php
