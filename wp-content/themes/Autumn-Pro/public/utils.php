<?php

function wpjam_theme_get_setting($setting_name){
	return wpjam_get_setting('wpjam_theme', $setting_name);
}

/** --------------------------------------------------------------------------------- *
 *  面包屑导航
 *  --------------------------------------------------------------------------------- */
function get_breadcrumbs()  {
	global $wp_query;
	if ( !is_home() ){
		// Start the UL
		//echo '<ul class="breadcrumb">'; 
		echo '<i class="iconfont icon-locationfill"></i> ';
		// Add the Home link  
		echo '<a href="'. get_option('home') .'">首页</a>';

		if ( is_category() )  {
			$catTitle = single_cat_title('', false);
			$cat = get_cat_ID( $catTitle );
			echo " <span>&raquo;</span> ". get_category_parents( $cat, TRUE, " <span>&raquo;</span> " ) ."";
		}
		elseif ( is_tag() )  {
			echo " <span>&raquo;</span> ".single_cat_title('', false)."";
		}
		elseif ( is_tax() )  {
			echo " <span>&raquo;</span> ".single_term_title('', false)."";
		}
		elseif ( is_search() ) {
			echo ' <span>&raquo;</span> 搜索结果（共搜索到 ' . $wp_query->found_posts . ' 篇文章）';
		}
		elseif ( is_archive() )  {
			echo " <span>&raquo;</span> 存档";
		}
		elseif ( is_404() )  {
			echo " <span>&raquo;</span> 404 Not Found";
		}
		elseif ( is_single() )  {
			$category = get_the_category();
			if($category){
				$category_id = get_cat_ID( $category[0]->cat_name );
				echo ' <span>&raquo;</span> '. get_category_parents( $category_id, TRUE, "  <span>&raquo;</span> " );
				echo '正文';
			}
		}
		elseif ( is_page() )  {
			$post = $wp_query->get_queried_object();
			if ( $post->post_parent == 0 ){
				echo " <span>&raquo;</span> ".the_title('','', FALSE)."";
			} else {
				$title = the_title('','', FALSE);
				$ancestors = array_reverse( get_post_ancestors( $post->ID ) );
				array_push($ancestors, $post->ID);
	
				foreach ( $ancestors as $ancestor ){
					if( $ancestor != end($ancestors) ){
						echo ' <span>&raquo;</span> <a href="'. get_permalink($ancestor) .'">'. strip_tags( apply_filters( 'single_post_title', get_the_title( $ancestor ) ) ) .'</a>'; 
					} else {
						echo ' <span>&raquo;</span> '. strip_tags( apply_filters( 'single_post_title', get_the_title( $ancestor ) ) ) .'';
					}
				}
			}
		}
		// End the UL
		//echo "</ul>";
	}
}


/** --------------------------------------------------------------------------------- *
 *  超级菜单
 *  --------------------------------------------------------------------------------- */
class Dahuzi_Walker_Nav_Menu extends Walker_Nav_Menu {

	public $tree_type = array( 'post_type', 'taxonomy', 'custom' );
	public $db_fields = array( 'parent' => 'menu_item_parent', 'id' => 'db_id' );
	protected $mega;

	public function __construct( $mega = false ) {
		$this->mega = $mega;
	}

	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "\n$indent<ul class=\"sub-menu\">\n";
	}

	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		if ( $depth == 0 && $this->mega && ( $item->object == 'category' || $item->object == 'post_tag' ) && in_array( 'menu-item-mega', $classes ) ) {
			$classes[] = 'menu-item-has-children';
		}

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args, $depth );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		$output .= $indent . '<li' . $id . $class_names .'>';

		$atts = array();
		$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target'] = ! empty( $item->target )     ? $item->target     : '';
		$atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
		$atts['href']   = ! empty( $item->url )        ? $item->url        : '';

		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( ! empty( $value ) ) {
				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$item_output = $args->before;
		$item_output .= '<a'. $attributes .'>';
		/** This filter is documented in wp-includes/post-template.php */
		$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;

		if ( $depth == 0 && $this->mega && ( $item->object == 'category' || $item->object == 'post_tag' ) && in_array( 'menu-item-mega', $classes ) ) {
			$term_id = $item->object_id;
			$term_args = array( 'posts_per_page' => 10 );
			switch ( $item->object ) {
				case 'category' :
					$term_args['cat'] = $term_id;
					break;
				case 'post_tag' :
					$term_args['tag_id'] = $term_id;
					break;
			}
			$term_posts = new WP_Query( $term_args );

			$item_output .= '<div class="mega-menu">';

			if ( $term_posts->have_posts() ) {
				$item_output .= '<div class="menu-posts owl with-arrow">';

				while ( $term_posts->have_posts() ) : $term_posts->the_post();
					$item_output .= '<div class="menu-post">';

					ob_start();

					if ( has_post_thumbnail() ) {
						//linx_entry_media( array( 'layout' => 'rect' ) );
					}

					//linx_entry_header( array( 'tag' => 'h4', 'author' => false, 'comment' => false ) );
					get_template_part('template-parts/menu-mega-post');

					$item_output .= ob_get_clean();

					$item_output .= '</div>';
				endwhile;

				$item_output .= '</div>';
			}

			wp_reset_postdata();

			$item_output .= '</div>';
		}

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}

	public function end_el( &$output, $item, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}

	function display_element( $element, &$children_elements, $max_depth, $depth=0, $args, &$output ) {
    $id_field = $this->db_fields['id'];
    if ( is_object( $args[0] ) ) {
      $args[0]->has_children = ! empty( $children_elements[ $element->$id_field ] );
    }
    return parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
  }

	public static function fallback( $args ) {
		extract( $args );

		$fb_output = null;

		if ( $container ) {
			$fb_output = '<' . $container;

			if ( $container_id )
				$fb_output .= ' id="' . $container_id . '"';

			if ( $container_class )
				$fb_output .= ' class="' . $container_class . '"';

			$fb_output .= '>';
		}

		$fb_output .= '<ul';

		if ( $menu_id )
			$fb_output .= ' id="' . $menu_id . '"';

		if ( $menu_class )
			$fb_output .= ' class="' . $menu_class . '"';

		$fb_output .= '>';
		$fb_output .= '<li class="menu-item"><a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">请前往「后台-外观-菜单」中添加菜单，注意勾选菜单显示位置</a></li>';
		$fb_output .= '</ul>';

		if ( $container )
			$fb_output .= '</' . $container . '>';

		echo wp_kses( $fb_output, array(
			'ul'   => array( 'id' => array(), 'class' => array() ),
			'li'   => array( 'class' => array() ),
			'a'    => array( 'href' => array() ),
			'span' => array()
		) );
	}

}
