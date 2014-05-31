<?php
/*
Plugin Name: Pages Children
Plugin URI: http://www.codehooligans.com/projects/wordpress/pages-children/
Description: Display hierarchical post types and taxonomies a single level pages at a time. 
Author: Paul Menard
Version: 1.5.2.2
Author URI: http://www.codehooligans.com
*/

define('PAGES_CHILDREN_I18N_DOMAIN', 	'pages-children');

class PagesChildren {

	var $post_type_object;
	
	function __construct() {

		$this->object_post_taxonomy = null;
		$this->terms_args_cnt = 0;

		if (!defined('PAGES_CHILDREN_TAXONOMY'))
			define('PAGES_CHILDREN_TAXONOMY', 3);
		
		add_action( 'init', 						array(&$this, 'init' ));
		add_action( 'admin_init', 					array(&$this, 'admin_init' ));
		add_action( 'admin_head', 					array(&$this, 'admin_head' ));

		add_filter( 'request',		 				array(&$this, 'request_filter' ), 999);
		add_filter( 'get_terms_args',				array($this, 'get_terms_args_filter'), 10, 2 );
	}
	
	function PagesChildren() {
        $this->__construct();
	}
		
	function init() {
		$plugin_dir = basename(dirname(__FILE__))."/lang";
		load_plugin_textdomain( PAGES_CHILDREN_I18N_DOMAIN, null, $plugin_dir );
	}
		
	function admin_init() {
		if ($this->check_page())
		{
			$plugin_dir = "/". basename(dirname(__FILE__));
			
			wp_enqueue_script('jquery'); 
			
			wp_register_script( 'jquery.cookie', WP_PLUGIN_URL . $plugin_dir. '/js/jquery.cookie.js', 'jquery', '2006');
			wp_enqueue_script( 'jquery.cookie');			
			
			add_filter( 'page_row_actions', array(&$this, 'page_row_actions_filter'), 10, 2 );			
			add_filter( 'tag_row_actions', array(&$this, 'tag_row_actions_filter'), 10, 2 );			
		}
		$this->filter_admin_menu();
		
		if (isset($_GET['post_type']))
			$this->object_post_taxonomy = get_post_type_object($_GET['post_type']);

	}

	/* 	
		In the admin menus we have items like the Posts > Categories panel which we want to recode the URL to include the 'orderby'. 
		This will trigger the page class used in WordPress to output things in a way we can control them. 
	*/
	function filter_admin_menu()
	{
		global $submenu;

		$qs = '';
		
		if (!count($submenu)) return;
		
		foreach($submenu as $menu_section => $menu_group)
		{
			if (count($menu_group))
			{
				foreach($menu_group as $group_idx => $menu_items)
				{
					foreach($menu_items as $menu_idx => $menu_item)
					{
						if ($this->get_page_url($menu_item) == "edit-tags.php")
						{
							$qs_vars = $this->get_query_str_array($menu_item);
							if (($qs_vars) && (isset($qs_vars['taxonomy'])))
							{
								if (is_taxonomy_hierarchical($qs_vars['taxonomy']))
								{
									if (!isset($qs_vars['orderby']))
										$qs_vars['orderby'] = 'name';
									if (!isset($qs_vars['order']))
										$qs_vars['order'] = 'asc';
									
									$qs = '';	
									foreach($qs_vars as $qs_idx => $qs_val)
									{						
										if (strlen($qs))	$qs .= "&";

										$qs .= $qs_idx .'='. $qs_val;
									}										
									if (strlen($qs))
									{
										$submenu[$menu_section][$group_idx][$menu_idx] = "edit-tags.php?". $qs;
									}
								}
							}								
						}
					}
				}
			}
		}
	}
	
	function admin_head() {
		if ($this->check_page())
		{
			?>
			<style type="text/css">
				table th.column-child-pages {
					width: 10%;
				}
				div#pages-nav-breadcrumb {
					margin-bottom: 10px;
				}
			</style>
			<?php
			// If we are showing a child section we provide a breadcrumb at the top 
			$post_parent_id = $this->get_post_parent_query_string();
			if ($post_parent_id > 0)
			{
				if ( ($this->check_uri('wp-admin/edit.php')) && (isset($_GET['post_type'])) )
					$parent_page = get_page($post_parent_id);
				else if ( ($this->check_uri('wp-admin/edit-tags.php')) && (isset($_GET['taxonomy'])) )
					$parent_page = get_term($post_parent_id, $_GET['taxonomy']);
					
				if ($parent_page)
				{
					?>
					<script type="text/javascript">
						jQuery(document).ready( function($) {
							
							jQuery.cookie('pages-children-<?php echo $this->object_post_taxonomy->name ?>-parent-id', '<?php 
								echo $post_parent_id; ?>');
														
							jQuery('div .tablenav').after('<div id="pages-nav-breadcrumb">&nbsp; <?php 
							echo _e('Back to Parent:', PAGES_CHILDREN_I18N_DOMAIN); ?> <?php
							echo $this->show_pages_parents($post_parent_id); ?></div>');
							
							jQuery('table tr td.page-title a.row-title').each(function(){
								//var link_text_parts = jQuery(this).text().split('&#8212; ');	// Does not work!
								var link_text_parts = jQuery(this).text().split('— ');
								if (link_text_parts.length > 0)
								{
									var link_text_new = "";
									for (idx in link_text_parts)
									{
										if (link_text_parts[idx] != "")
										{
											if (link_text_new.length > 0)
												link_text_new = link_text_new+" ";
												link_text_new = link_text_new+link_text_parts[idx];
										}
									}
									if (link_text_new.length > 0)
										jQuery(this).text(link_text_new);
								}
							});
						});
					</script>			
					<?php
				}
			}
			else
			{
				?>
				<script type="text/javascript">
					jQuery(document).ready( function($) {

						jQuery.cookie('pages-children-<?php echo $this->object_post_taxonomy->name ?>-parent-id', null);

					});
				</script>			
				<?php	
			}
			if (($this->check_uri('wp-admin/edit.php')) 
 			 || ($this->check_uri('wp-admin/edit-tags.php')) )
			
			?>
			<script type="text/javascript">
				jQuery(document).ready( function($) {

					jQuery('table tr td.page-title strong').each(function(){
						var title_text_new = "";

						var title_strong = jQuery(this).html();
						//var str_pos = title_strong.indexOf('| Parent Page:');
						var str_pos = title_strong.indexOf('| ');
						if (str_pos > -1) // we have a hit
							title_text_new = title_strong.substr(0, str_pos-1);
						else
							title_text_new = title_strong;
							
						var parent_td = jQuery(this).parent();										
						var has_children = jQuery('div.row-actions span.pages-children', parent_td);
						if (has_children.length > 0)
						{
							var has_children_anchor = jQuery(has_children).html();
							title_text_new = title_text_new+" | ("+has_children_anchor+" &raquo;)";
						}
						jQuery(this).html(title_text_new);
						jQuery('a.pages-children', this).text("<?php _e('children', PAGES_CHILDREN_I18N_DOMAIN); ?>");						
					});
					
					jQuery('table tr td.column-name strong').each(function(){
						var title_text_new = "";
						var parent_td = jQuery(this).parent();										
						var has_children = jQuery('div.row-actions span.pages-children', parent_td);
						if (has_children.length > 0)
						{
							var has_children_href = jQuery('a', has_children).attr('href');
							var has_children_label = jQuery('a', has_children).text();
							title_text_new = title_text_new+' (<a href="'+has_children_href+'"><?php _e('children', PAGES_CHILDREN_I18N_DOMAIN); ?> &raquo;)';
							jQuery(this).after(title_text_new);
						}
					});					
				});
			</script>			
			<?php
		}
		if (($this->check_uri('wp-admin/page-new.php')) 
		|| ((isset($_GET['post_type'])) && ($this->check_uri('wp-admin/post-new.php'))))
		{
			
			// On the add new Page form we set the parent dropdown to be the parent page we were on. This is convineint. 
			$post_parent_id = $this->get_post_parent_query_string();
			if ($post_parent_id > 0)
			{
				?>
				<script type="text/javascript">
					jQuery(document).ready( function($) {
						
						jQuery('#pageparentdiv select#parent_id').val(<?php echo $post_parent_id; ?>);
					});
				</script>			
				<?php
				
			}
		}
		if ($this->check_uri('wp-admin/edit-tags.php')) 
		{
			
			// On the add new Page form we set the parent dropdown to be the parent page we were on. This is convineint. 
			$post_parent_id = $this->get_post_parent_query_string();
			if ($post_parent_id > 0)
			{
				?>
				<script type="text/javascript">
					jQuery(document).ready( function($) {
						
						jQuery('select#parent').val(<?php echo $post_parent_id; ?>);
					});
				</script>			
				<?php
				
			}
		}


	}
	
	function get_post_parent_query_string()
	{
		if (isset($_GET['taxonomy']))
		{
			if (isset($_GET['parent']))
				return intval($_GET['parent']);
			else if (isset($_COOKIE['pages-children-'. $_GET['taxonomy'] .'-parent-id']))
				return intval($_COOKIE['pages-children-'. $_GET['taxonomy'] .'-parent-id']);
			
		}
		else if (isset($_GET['post_type']))
		{
			//echo "object_post_taxonomy<pre>"; print_r($this->object_post_taxonomy); echo "</pre>";
			if (isset($_GET['post_parent']))
				return intval($_GET['post_parent']);
			else if (isset($_COOKIE['pages-children-'. $_GET['post_type'] .'-parent-id']))
				return intval($_COOKIE['pages-children-'. $_GET['post_type'] .'-parent-id']);
			else
				return 0;
		}
	}
	
	
	function check_uri($url) {
		if (!$url) return;

		$_REQUEST_URI = explode('?', $_SERVER['REQUEST_URI']);
		$url_len 	= strlen($url);
		$url_offset = $url_len * -1;

		// If out test string ($url) is longer than the page URL. skip
		if (strlen($_REQUEST_URI[0]) < $url_len) return;

		if ($url == substr($_REQUEST_URI[0], $url_offset, $url_len))
				return true;		
	}	
	
	function check_page()
	{
		global $wp_version;

	    if ( version_compare( $wp_version, '2.9.999', '>' ) )
		{
			if ($this->check_uri('wp-admin/edit.php')) // A Page or other hierarchical post type
			{
				if ((isset($_GET['post_type'])) && (is_post_type_hierarchical($_GET['post_type'])))
				{
					$this->object_post_taxonomy = get_post_type_object($_GET['post_type']);

					if (isset($_GET['post_status']))
					{
						if ($_GET['post_status'] != "trash")
							return true;
					}
					else 
						return true;
				}
			}
			else if ($this->check_uri('wp-admin/edit-tags.php')) // A Taxonomt setup as hierarchical
			{
				if ((isset($_GET['taxonomy'])) && (is_taxonomy_hierarchical($_GET['taxonomy'])))
				{
					$this->object_post_taxonomy = get_taxonomy($_GET['taxonomy']);
					return true;
					
				}
			}
		}
		else
		{
			if ($this->check_uri('wp-admin/edit-pages.php'))
			{
				if (isset($_GET['post_status']))
				{
					if ($_GET['post_status'] != "trash")
						return true;
				}
				else 
					return true;
			}
		}		
	}

	function request_filter($qs)
	{
		//echo "qs<pre>"; print_r($qs); echo "</pre>";
		if ($this->check_page())
		{
			if (!isset($_REQUEST['s']))
			{
				$post_parent_id = $this->get_post_parent_query_string();
				//echo "post_parent_id=[". $post_parent_id ."]<br />";
				if ($post_parent_id)
				{
					$qs['post_parent'] = $post_parent_id;
					add_query_arg(array('post_parent' => $post_parent_id));
				}
				else
				{
					$qs['post_parent'] = 0;
					add_query_arg(array('post_parent' => 0));
					
				}
			}
		}
		//echo "qs<pre>"; print_r($qs); echo "</pre>";
		return $qs;
	}

	function get_terms_args_filter($args, $taxonomies)
	{		
		//if (($this->check_uri('wp-admin/edit-tags.php')) && ( isset($_GET['taxonomy'])) && (is_taxonomy_hierarchical($_GET['taxonomy'])))
		if (!$this->check_uri('wp-admin/edit-tags.php'))
			return $args;
			
		if ( !isset($_GET['taxonomy']))
			return $args;

		if (!is_taxonomy_hierarchical($_GET['taxonomy']))
			return $args;
		
		if ((isset($_GET['action'])) && ($_GET['action'] == "edit")) return $args;
		
		// Check if there is a defin for this taxonomy. 
		if (!defined('PAGES_CHILDREN_TAXONOMY_'. strtoupper($_GET['taxonomy']))) {
			// If not use the default 'PAGES_CHILDREN_TAXONOMY' define (3)
			define('PAGES_CHILDREN_TAXONOMY_'. strtoupper($_GET['taxonomy']), PAGES_CHILDREN_TAXONOMY);
		}
		$taxonomy_counter = constant('PAGES_CHILDREN_TAXONOMY_'. strtoupper($_GET['taxonomy']));

		$this->terms_args_cnt += 1;
		
		if ($this->terms_args_cnt != $taxonomy_counter) 
			return $args;

		$parent_id = 0;
		
		if (isset($_GET['parent']))
			$parent_id = intval($_GET['parent']);
		else if (isset($_COOKIE['pages-children-'. $_GET['taxonomy'] .'-parent-id']))
			$parent_id = intval($_COOKIE['pages-children-'. $_GET['taxonomy'] .'-parent-id']);

		remove_filter('get_terms_args',				array($this, 'get_terms_args_filter'), 10, 2);

		$terms_args = array(
			'hide_empty'	=>	0,
			'hierarchical'	=>	1,
			'parent'		=> 	$parent_id,
			'child_of'		=> 	$parent_id
		);

		$child_terms = get_terms( $_GET['taxonomy'], $terms_args);
		
		add_filter('get_terms_args',				array($this, 'get_terms_args_filter'), 10, 2);
		if ($child_terms)
		{
			$args['hide_empty']		= 0;
			$args['parent'] 		= $parent_id;
			$args['child_of'] 		= $parent_id;
			$args['hierarchical']	= 1;
		}
		return $args;
	}
	
	function page_row_actions_filter($actions, $post )
	{
		global $wp_version;
		
		if (!array_key_exists('pages-children', $actions))
		{			
			$query_posts_array = array(				
				"post_type"		=> 	$_GET['post_type'],
				"post_parent"	=>	$post->ID,
				"child_of"		=> 	$post->ID,
				"post_status"	=>	array('publish', 'draft', 'future', 'private')
			);
			if (isset($_GET['post_ststus'])) {
				$query_posts_array['post_status'] = $_GET['post_ststus'];
			}
//			echo "query_posts_array<pre>"; print_r($query_posts_array); echo "</pre>";
			$child_pages = get_posts( $query_posts_array );
			if ($child_pages)
			{
				if ($this->check_page())
				{
				    if ( version_compare( $wp_version, '2.9.999', '>' ) )
						$actions['pages-children'] = '<a class="pages-children" href="edit.php?post_type='. $_GET['post_type'] .'&post_parent='. 
							$post->ID .'">'. __('View Sub-'. $this->object_post_taxonomy->labels->name, PAGES_CHILDREN_I18N_DOMAIN). '</a>';
					else
						$actions['pages-children'] = '<a class="pages-children" href="edit-pages.php?post_type='.$_GET['post_type'].'&post_parent='. 
							$post->ID .'">'. __('View Sub-'. $this->object_post_taxonomy->labels->name, PAGES_CHILDREN_I18N_DOMAIN) .'</a>';
				}
			}
		}
		return $actions;
	}
	
	function tag_row_actions_filter($actions, $tag)
	{				
		global $wp_version;
		
		$terms_args = array(
			'hide_empty'	=>	0,
			'number'		=>	1,
			'child_of'		=> 	$tag->term_id,
			'parent'		=>	$tag->term_id
		);
		
		remove_filter('get_terms_args',				array($this, 'get_terms_args_filter'), 10, 2);
		$child_terms = get_terms( $_GET['taxonomy'], $terms_args);
		add_filter('get_terms_args',				array($this, 'get_terms_args_filter'), 10, 2);
		
		if ($child_terms)
		{
			if ($this->check_page())
			{
				$qs = '';
				$qs_vars = $this->get_query_str_array();

				if (!$qs_vars)	$qs_vars = array();
				$qs_vars['parent'] = $tag->term_id;
				if (!isset($qs_vars['orderby']))
					$qs_vars['orderby'] = 'name';
				if (!isset($qs_vars['order']))
					$qs_vars['order'] = 'asc';
					
				foreach($qs_vars as $qs_idx => $qs_val)
				{						
					if (strlen($qs))	$qs .= "&";
				
					$qs .= $qs_idx .'='. $qs_val;
				}
			    if ( version_compare( $wp_version, '2.9.999', '>' ) )
					$actions['pages-children'] = '<a class="pages-children" href="edit-tags.php?'. $qs .'">'. __('View Sub-'.
					 $this->object_post_taxonomy->labels->name, PAGES_CHILDREN_I18N_DOMAIN). '</a>';
				else
					$actions['pages-children'] = '<a class="pages-children" 
								href="edit-tags.php?'. $qs .'">'. __('View Sub-'. $this->object_post_taxonomy->labels->name,
								 PAGES_CHILDREN_I18N_DOMAIN) .'</a>';
			}			
		}
		return $actions;
	}
	
	// Given a page id will go up the tree to the top parent then display a breadcrumb listing
	function show_pages_parents($page_id='')
	{
		global $wp_version;
		
		if (!$page_id) return;
		
		$breadcrumb_str = '';

		if ( ($this->check_uri('wp-admin/edit.php')) && (isset($_GET['post_type'])) )
		{
			$query_posts_array = array(				
				"post_type"		=> 	$_GET['post_type'],
				"p"				=>	$page_id,
				"post_status"	=>	array('publish', 'draft', 'future', 'private')
			);
			if (isset($_GET['post_ststus'])) {
				$query_posts_array['post_status'] = $_GET['post_ststus'];
			}			
			//$parent_page = get_posts('post_type='. $_GET['post_type'] .'&p='. $page_id);
			$parent_page = get_posts($query_posts_array);
			
			if ($parent_page)
				$parent_page = $parent_page[0];			

			$get_post_parent = $this->get_post_parent_query_string();		
			if ($parent_page->post_parent > 0)
			{
				$breadcrumb_str = $this->show_pages_parents($parent_page->post_parent);
			}
			else
			{
				if ($this->check_page())
				{
					$qs = '';
					$qs_vars = $this->get_query_str_array();
					if (!$qs_vars)	$qs_vars = array();
					$qs_vars['post_parent'] = 0;

					foreach($qs_vars as $qs_idx => $qs_val)
					{						
						if (strlen($qs))	
							$qs .= "&";

						$qs .= $qs_idx .'='. $qs_val;
					}

					if ( version_compare( $wp_version, '2.9.999', '>' ) )
						$breadcrumb_str .= '<a href="edit.php?'. $qs .'">'. __($this->object_post_taxonomy->labels->name,
							 PAGES_CHILDREN_I18N_DOMAIN) .'</a>';
				    else
						$breadcrumb_str .= '<a href="edit-pages.php?'. $qs .'">'. __($this->object_post_taxonomy->labels->name,
							 PAGES_CHILDREN_I18N_DOMAIN) .'</a>';
				}
			}
			if ($breadcrumb_str) $breadcrumb_str .= " &raquo; ";

			if ($get_post_parent == $parent_page->ID)
			{
				$breadcrumb_str .= get_the_title($parent_page->ID);
			}
			else
			{
				if ($this->check_page())
				{
					$qs = '';
					$qs_vars = $this->get_query_str_array();
					if (!$qs_vars)	$qs_vars = array();
					$qs_vars['post_parent'] = $parent_page->ID;

					foreach($qs_vars as $qs_idx => $qs_val)
					{						
						if (strlen($qs))	$qs .= "&";

						$qs .= $qs_idx .'='. $qs_val;
					}

					if ( version_compare( $wp_version, '2.9.999', '>' ) )
						$breadcrumb_str .= '<a href="edit.php?'. $qs .'">'. get_the_title($parent_page->ID) .'</a>';
					else
						$breadcrumb_str .= '<a href="edit-pages.php?'. $qs .'">'. get_the_title($parent_page->ID) .'</a>';
				}			
			}

		}	
		else if ( ($this->check_uri('wp-admin/edit-tags.php')) && (isset($_GET['taxonomy'])) )
		{
			$taxonomy = get_taxonomy($_GET['taxonomy']);
			$term = get_term($page_id, $_GET['taxonomy']);
			if ($term)
			{
				$term_parents = $this->get_term_parents($term, $_GET['taxonomy']);
				if (($term_parents) && (count($term_parents) > 0))
				{
					$qs = '';
					$qs_vars = $this->get_query_str_array();
					if (!$qs_vars)	$qs_vars = array();
					$qs_vars['parent'] = 0;

					foreach($qs_vars as $qs_idx => $qs_val)
					{						
						if ($qs_idx == 'paged') continue;
						if (strlen($qs))	$qs .= "&";

						$qs .= $qs_idx .'='. $qs_val;
					}
					$breadcrumb_str .= '<a href="edit-tags.php?'. $qs .'">'. $taxonomy->labels->name .'</a>';
					
					foreach($term_parents as $idx => $term_id)
					{
						$term = get_term($term_id, $_GET['taxonomy']);
						if ($term)
						{
							$qs = '';
							$qs_vars = $this->get_query_str_array();
							if (!$qs_vars)	$qs_vars = array();
							$qs_vars['parent'] = $term->term_id;

							foreach($qs_vars as $qs_idx => $qs_val)
							{						
								if (strlen($qs))	$qs .= "&";

								$qs .= $qs_idx .'='. $qs_val;
							}
							if ($breadcrumb_str) $breadcrumb_str .= " &raquo; ";

							if ($term->term_id != $page_id)
								$breadcrumb_str .= '<a href="edit-tags.php?'. $qs .'">'. $term->name .'</a>';
							else
								$breadcrumb_str .=  $term->name ;
						}
					}
				}				
			}
		}

		return 	$breadcrumb_str;
	}
	
	function get_page_url($url='')
	{
		if (!$url)
			$url = $_SERVER['REQUEST_URI'];

		$url_parts = explode('?', $url);

		return $url_parts[0];
	}
	
	function get_query_str_array($url='', $key='')
	{
		if (!$url) $url = $_SERVER['REQUEST_URI'];

//		echo "url=[". $url ."]<br />";
//		echo "key=[". $key ."]<br />";
		$defaults = array();

		$url_parts = parse_url($url);
		if ((isset($url_parts['query'])) && (strlen($url_parts['query'])))
		{
			$defaults = array();
			$url_args = wp_parse_args( $url_parts['query'], $defaults );
			
			if ((strlen($key)) && (array_key_exists($key, $url_args)))
				return $url_args[$key];
			else
				return $url_args;
		}	
	}

	function get_term_parents($term, $taxonomy)
	{
		if (!$term) return;
		if (!$taxonomy) return;

		if ( ! taxonomy_exists( $taxonomy ) )
			return false;

		if ( !is_object($term) ) {
			if ( is_int($term) ) {
				$term = &get_term($term, $taxonomy);
			} else {
				$term = &get_term_by('slug', $term, $taxonomy);
			}
		}
		if (!is_taxonomy_hierarchical($taxonomy))
		{
			return (array)$term;
		}

		$term_parents = array();
		$term_parents[] = $term->term_id;

		while ($term->parent != 0) {
			$term = get_term($term->parent, $taxonomy);

			if ( !is_wp_error($term) )
				$term_parents[] = $term->term_id;
			else
				break;
		}

		if (count($term_parents))
		{
			$term_parents = array_reverse($term_parents);
			return $term_parents;		
		}
	}
	
	function activation_hook() {
		
	}

}
$pages_children = new PagesChildren();
