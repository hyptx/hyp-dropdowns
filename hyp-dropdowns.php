<?php
 /*
Plugin Name: Hyp Dropdowns
Plugin URI: http://dropdowns.myhyperspace.com/
Description: A nav menu designed for use with the Wordpress Menu system. With support for css or superfish dropdowns.
Version: 1.0
Author: Adam J Nowak
Author URI: http://hyperspatial.com
License: GPL2
*/

define('HYPD_SUPERFISH',true);
define('HYPD_PLUGIN',WP_PLUGIN_URL . '/' . basename(dirname(__FILE__)) . '/');
define('HYPD_PLUGIN_SERVERPATH',dirname(__FILE__) . '/');

//Menus
register_nav_menu('hypd_primary',__('Hypd Primary','hypd'));
register_nav_menu('hypd_secondary',__('Hypd Secondary','hypd'));
//register_nav_menu('hypd_footer',__('Hypd Footer','hypd'));

/* HypdWalkerNavMenu
*  Use with wp_nav_menu */
class HypdWalkerNavMenu extends Walker_Nav_Menu{
	public function start_lvl(&$output,$depth){
		$indent = str_repeat("\t",$depth);
		//if($depth < 1) $dropdown_menu = ' dropdown-menu'; //Sub level class
		$output .= "\n$indent<ul class=\"sub-menu$dropdown_menu\">\n";
	}
	public function start_el(&$output,$item,$depth,$args){
		global $wp_query;
		$indent = ($depth) ? str_repeat("\t",$depth) : '';
		$class_names = $value = '';
		$classes = empty($item->classes) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;
		if($depth == 0) $classes[] = 'top'; //Extra custom class
		if($args->has_children && (integer)$depth < 1) $classes[] = 'dropdown';
		$class_names = join(' ',apply_filters('nav_menu_css_class',array_filter($classes),$item,$args));
		$class_names = ' class="' . esc_attr($class_names) . '"';
		$id = apply_filters('nav_menu_item_id','menu-item-' . $item->ID,$item,$args);
		$id = strlen($id) ? ' id="' . esc_attr($id) . '"' : '';
		$output .= $indent . '<li' . $id . $value . $class_names .'>';
		$attributes  = ! empty($item->attr_title) ? ' title="' . esc_attr($item->attr_title) .'"' : '';
		$attributes .= ! empty($item->target) ? ' target="' . esc_attr($item->target) .'"' : '';
		$attributes .= ! empty($item->xfn) ? ' rel="' . esc_attr($item->xfn) .'"' : '';
		$attributes .= ! empty($item->url) ? ' href="' . esc_attr($item->url) .'"' : '';
		$item_output = $args->before;
		$item_output .= '<a'. $attributes .'>';
		$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
		$item_output .= '</a>';
		//$item_output .= ($args->has_children && (integer)$depth < 1) ? '<b data-toggle="dropdown" class="caret"></b>' : ''; //Caret
		$item_output .= $args->after;
		$output .= apply_filters('walker_nav_menu_start_el',$item_output,$item,$depth,$args);
	}
	function display_element($element,&$children_elements,$max_depth,$depth = 0,$args,&$output){
		if(!$element) return;
		$id_field = $this->db_fields['id'];
		if(is_array($args[0])) $args[0]['has_children'] = !empty($children_elements[$element->$id_field]);
		elseif(is_object($args[0]))	$args[0]->has_children = !empty($children_elements[$element->$id_field]); //** Add has_children value, only mod in method **
		$cb_args = array_merge(array(&$output,$element,$depth),$args);
		call_user_func_array(array(&$this,'start_el'),$cb_args);
		$id = $element->$id_field;
		if(($max_depth == 0 || $max_depth > $depth+1) && isset($children_elements[$id])){
			foreach($children_elements[$id] as $child){
				if(!isset($newlevel)){
					$newlevel = true;
					$cb_args = array_merge(array(&$output,$depth),$args);
					call_user_func_array(array(&$this,'start_lvl'),$cb_args);
				}
				$this->display_element($child,$children_elements,$max_depth,$depth + 1,$args,$output);
			}
			unset($children_elements[$id]);
		}
		if(isset($newlevel) && $newlevel){
			$cb_args = array_merge(array(&$output,$depth),$args);
			call_user_func_array(array(&$this,'end_lvl'),$cb_args);
		}
   		$cb_args = array_merge(array(&$output,$element,$depth),$args);
    	call_user_func_array(array(&$this,'end_el'),$cb_args);
	}
}

/* HypdWalkerPage
*  Fallback, use with wp_list_pages */
class HypdWalkerPage extends Walker_Page{
	function start_lvl(&$output,$depth){
		$indent = str_repeat("\t",$depth);
		//if($depth < 1) $dropdown_menu = ' dropdown-menu'; //Sub level class
		$output .= "\n$indent<ul class=\"sub-menu$dropdown_menu\">\n";
	}
	function start_el(&$output,$page,$depth,$args,$current_page){
		if($depth) $indent = str_repeat("\t", $depth);
		else $indent = '';
		extract($args, EXTR_SKIP);
		$css_class = array('page_item', 'page-item-'.$page->ID);
		if(!empty($current_page)){
			$_current_page = get_page($current_page);
			_get_post_ancestors($_current_page);
			if(isset($_current_page->ancestors) && in_array($page->ID,(array)$_current_page->ancestors)) $css_class[] = 'current_page_ancestor';
			if($page->ID == $current_page) $css_class[] = 'current_page_item';
			elseif($_current_page && $page->ID == $_current_page->post_parent) $css_class[] = 'current_page_parent';
		}
		elseif($page->ID == get_option('page_for_posts')) $css_class[] = 'current_page_parent';
		if($args['has_children'] && (integer)$depth < 1) $css_class[] = 'dropdown';
		if($depth < 1) $css_class[] = 'top'; //Extra custom class (Touchstone)
		$css_class = implode(' ',apply_filters('page_css_class',$css_class,$page,$depth,$args,$current_page));
		$output .= $indent . '<li class="' . $css_class . '"><a href="' . get_permalink($page->ID) . '">' . $link_before . apply_filters('the_title',$page->post_title,$page->ID ) . $link_after . '</a>';
		if(!empty($show_date)){
			if('modified' == $show_date) $time = $page->post_modified;
			else $time = $page->post_date;
			$output .= " " . mysql2date($date_format,$time);
		}
		//if($args['has_children'] && (integer)$depth < 1) $output .= $indent . '<b data-toggle="dropdown" class="caret"></b>'; //Caret
	}
}


/* Nav Bar
*  The theme nav system, uses wp menus with fallback to wp_list_pages
*  Argument1 = Menu slug 
*  Argument2 = Menu css ID 
*  Argument2 = Pass false to hide navbar when menu does not exist */
function hypd_navbar($location = 'hypd_primary',$container_id = 'menu',$container_class = 'sf-menu',$fallback = true){
	if(!has_nav_menu($location) && $fallback == false) return;
	?>
	<div id="<?php echo $container_id ?>">
		<ul id="<?php echo $location . '-' . $container_id  ?>-ul" class="<?php echo $container_class ?>">
			<?php wp_nav_menu(array('fallback_cb' => 'hypd_navbar_fallback','theme_location' => $location,'container' => false,'items_wrap' => '%3$s','walker' => new HypdWalkerNavMenu())) ?>
		</ul>
	</div>
    <?php
}

/* Nav Bar Fallback */
function hypd_navbar_fallback(){ wp_list_pages(array('title_li' => '','walker' => new HypdWalkerPage())); }

//Enqueue Styles
function hypd_add_stylesheet(){
     wp_enqueue_style('hypd_sf_css',HYPD_PLUGIN . 'superfish-1.4.8/css/superfish.css');
}

/* ~~~~~~~~~~~~ Callbacks ~~~~~~~~~~~~ */

//Enqueue Javascript
function hypd_enqueue_js(){
	wp_enqueue_script('jquery');
     wp_enqueue_script('hypd_superfish',HYPD_PLUGIN . 'superfish-1.4.8/js/superfish.js');
     wp_enqueue_script('hypd_supersubs',HYPD_PLUGIN . 'superfish-1.4.8/js/supersubs.js');
	 wp_enqueue_script('hypd_hoverintent',HYPD_PLUGIN . 'superfish-1.4.8/js/hoverIntent.js');
}


//Print jQuery
function hypd_print_jquery(){?>
	
	<script type="text/javascript">
	jQuery(document).ready(function() { 
        jQuery('ul.sf-menu').superfish({ 
            delay:       800,                            // one second delay on mouseout 
            animation:   {opacity:'show',height:'show'},  // fade-in and slide-down animation 
            speed:       'fast',                          // faster animation speed 
            autoArrows:  false,                           // disable generation of arrow mark-up 
            dropShadows: false                            // disable drop shadows 
        }); 
    });
	</script>
	<?php    
}


/* ~~~~~~~~~~~~ Actions ~~~~~~~~~~~~ */
add_action('wp_head', 'hypd_print_jquery');
//add_action('wp_print_styles', 'hypd_add_stylesheet');
add_action('wp_print_scripts', 'hypd_enqueue_js');
?>