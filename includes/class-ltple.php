<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LTPLE_SEO {

	/**
	 * The single instance of LTPLE_SEO.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file='', $parent, $version = '1.0.0' ) {

		$this->parent = $parent;
	
		$this->_version = $version;
		$this->_token	= md5($file);
		
		$this->message = '';
		
		// Load plugin environment variables
		$this->file 		= $file;
		$this->dir 			= dirname( $this->file );
		$this->views   		= trailingslashit( $this->dir ) . 'views';
		$this->vendor  		= WP_CONTENT_DIR . '/vendor';
		$this->assets_dir 	= trailingslashit( $this->dir ) . 'assets';
		$this->assets_url 	= esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );
		
		//$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$this->script_suffix = '';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		if( is_admin() ){
		
			// Load admin JS & CSS
			
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );
		}
		else{
			
			// Load frontend JS & CSS
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );
		}
		
		$this->settings = new LTPLE_SEO_Settings( $this->parent );
		
		$this->admin = new LTPLE_SEO_Admin_API( $this );
		
		$this->parent->register_post_type( 'seo-article', __( 'SEO Articles', 'live-template-editor-seo' ), __( 'SEO Article', 'live-template-editor-seo' ), '', array(

			'public' 				=> false,
			'publicly_queryable' 	=> false,
			'exclude_from_search' 	=> true,
			'show_ui' 				=> true,
			'show_in_menu'		 	=> 'seo-article',
			'show_in_nav_menus' 	=> true,
			'query_var' 			=> true,
			'can_export' 			=> true,
			'rewrite' 				=> false,
			'capability_type' 		=> 'post',
			'has_archive' 			=> false,
			'hierarchical' 			=> false,
			'show_in_rest' 			=> true,
			//'supports' 			=> array( 'title', 'editor', 'author', 'excerpt', 'comments', 'thumbnail','page-attributes' ),
			'supports' 				=> array( 'title', 'editor', 'excerpt', 'thumbnail', 'author' ),
			'menu_position' 		=> 5,
			'menu_icon' 			=> 'dashicons-admin-post',
		));
		
		$this->parent->register_post_type( 'seo-backlink', __( 'SEO Backlinks', 'live-template-editor-seo' ), __( 'SEO Backlink', 'live-template-editor-seo' ), '', array(

			'public' 				=> false,
			'publicly_queryable' 	=> false,
			'exclude_from_search' 	=> true,
			'show_ui' 				=> true,
			'show_in_menu'		 	=> 'seo-backlink',
			'show_in_nav_menus' 	=> true,
			'query_var' 			=> true,
			'can_export' 			=> true,
			'rewrite' 				=> false,
			'capability_type' 		=> 'post',
			'has_archive' 			=> false,
			'hierarchical' 			=> false,
			'show_in_rest' 			=> true,
			//'supports' 			=> array( 'title', 'editor', 'author', 'excerpt', 'comments', 'thumbnail','page-attributes' ),
			'supports' 				=> array( 'title','author' ),
			'menu_position' 		=> 5,
			'menu_icon' 			=> 'dashicons-admin-post',
		));
		
		$this->parent->register_taxonomy( 'seo-anchor', __( 'SEO Anchors', 'live-template-editor-seo' ), __( 'SEO Anchors', 'live-template-editor-seo' ),  array('seo-backlink'), array(
			'hierarchical' 			=> false,
			'public' 				=> true,
			'show_ui' 				=> true,
			'show_in_nav_menus' 	=> false,
			'show_tagcloud' 		=> false,
			'meta_box_cb' 			=> null,
			'show_admin_column' 	=> true,
			'update_count_callback' => '',
			'show_in_rest'          => true,
			'rewrite' 				=> array( 'slug' => 'anchor' ),
			'sort' 					=> '',
		));
		
		$this->parent->register_taxonomy( 'seo-tag', __( 'SEO Tags', 'live-template-editor-seo' ), __( 'SEO Tags', 'live-template-editor-seo' ),  array('seo-article'), array(
			'hierarchical' 			=> false,
			'public' 				=> false,
			'show_ui' 				=> true,
			'show_in_nav_menus' 	=> false,
			'show_tagcloud' 		=> false,
			'meta_box_cb' 			=> null,
			'show_admin_column' 	=> true,
			'update_count_callback' => '',
			'show_in_rest'          => true,
			'rewrite' 				=> false,
			'sort' 					=> '',
		));	
		
		$this->parent->register_taxonomy( 'seo-pbn', __( 'PBN', 'live-template-editor-seo' ), __( 'PBN', 'live-template-editor-seo' ),  array('user-app','seo-backlink','seo-article'), array(
			'hierarchical' 			=> true,
			'public' 				=> false,
			'show_ui' 				=> true,
			'show_in_nav_menus' 	=> false,
			'show_tagcloud' 		=> false,
			'meta_box_cb' 			=> null,
			'show_admin_column' 	=> true,
			'update_count_callback' => '',
			'show_in_rest'          => true,
			'rewrite' 				=> false,
			'sort' 					=> '',
		));	
		
		add_action( 'add_meta_boxes', function(){
			
			$this->parent->admin->add_meta_box (
				
				'seo-article-images',
				__( 'Article Images', 'live-template-editor-seo' ), 
				array('seo-article'),
				'advanced'
			);

			$this->parent->admin->add_meta_box (
				
				'seo-article-preview',
				__( 'Article Preview', 'live-template-editor-seo' ), 
				array('seo-article'),
				'advanced'
			);			
			
			$this->parent->admin->add_meta_box (
				
				'seo-article-published',
				__( 'Article Published', 'live-template-editor-seo' ), 
				array('seo-article'),
				'advanced'
			);
		});		
		
		// add cron events
			
		add_action( $this->parent->_base . 'publish_seo_article_event', array( $this, 'publish_seo_article'),1,2);		
		
		// Handle localisation
		
		$this->load_plugin_textdomain();
		
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
		
		//init profiler 
		
		add_action( 'init', array( $this, 'init_seo' ));	

		// Custom editor template

		add_filter( 'template_redirect', array( $this, 'seo_template'), 1 );
		
	} // End __construct ()
	
	// Add campaign trigger custom fields

	public function add_seo_article_fields(){

		$fields=[];

		$fields[]=array(
		
			"metabox" =>
				array( 'name' => "seo-article-published" ),
				'type'				=> 'key_value',
				'id'				=> 'seo-article-published',
				'label'				=> '',
				'description'		=> ''
		);

		$fields[]=array(
		
			"metabox" =>
				array( 'name' => "seo-article-images" ),
				'type'				=> 'key_value',
				'id'				=> 'seo-article-images',
				'label'				=> '',
				'description'		=> ''
		);
		
		$article = $this->get_seo_article(get_the_ID());
		
		$fields[]=array(
		
			"metabox" =>
				array( 'name' => "seo-article-preview" ),
				'type'				=> 'hidden',
				'id'				=> 'seo-article-preview',
				'label'				=> '',
				'value'				=> '',
				'description'		=> '<h1>'.$article['post_title'].'</h1>'.'<img src="'.$article['post_img'].'" style="width:100%;"/>'.$article['post_content'],
		);
		
		return $fields;
	}	
	
	public function set_anchor_columns($columns) {

		// Remove description, posts, wpseo columns
		$columns = [];
		
		// Add artist-website, posts columns

		$columns['cb'] 			= '<input type="checkbox" />';
		$columns['name'] 		= 'Name';
		$columns['redirect'] 	= 'Redirect';
		$columns['short_url'] 	= 'Short Url';
		$columns['slug'] 		= 'Slug';
		$columns['posts'] 		= 'Count';
		
		return $columns;
	}
		
	public function add_anchor_column_content($content, $column_name, $term_id){
	
		$term = get_term($term_id);
	
		if($column_name == 'redirect') {

			$content = $this->parent->admin->display_field( array(
			
				'type'				=> 'switch',
				'id'				=> 'redirect_anchor_'.$term->slug,
				'name'				=> 'redirect_anchor_'.$term->slug,
				'description'		=> '',
				'disabled'			=> true,
				
			), false );				
		}
		elseif($column_name == 'short_url') {
			
			$short_url = get_option( 'short_url_' . $term->slug );
			
			$content = '<a href="'.$short_url.'">'.str_replace(array('http://','https://'),'',$short_url).'</a>';
		}

		return $content;
	}
	
	public function seo_template(){
		
		if( !is_admin() && is_tax('seo-anchor') ) {
			
			$term = get_queried_object();
			
			if( !empty($term->term_id) ){
				
				$target_url = get_option( 'target_url_' . $term->slug );
				
				if ( filter_var($target_url, FILTER_VALIDATE_URL) ) {
					
					$redirect = get_option( 'redirect_anchor_' . $term->slug );
					
					if( $redirect == 'on' ){
					
						wp_redirect($target_url);
						exit;
					}
					else{
						
						echo 'This url has moved <a href="'.$target_url.'">here</a>...';
						exit;
					}
				}
			}
			
			exit;
		}
	}
	
	public function init_seo(){	
		
		if ( is_admin() ) {
		
			add_action('admin_init',function(){
				
				// debug seo article
				
				//$this->publish_seo_article(182,213);
			});
			
			add_action('seo-anchor_edit_form_fields', array( $this, 'get_anchor_fields' ) );
			add_action('edit_seo-anchor', array( $this, 'save_anchor_fields' ) );
			
			add_filter('manage_edit-seo-anchor_columns', array( $this, 'set_anchor_columns' ) );
			add_filter('manage_seo-anchor_custom_column', array( $this, 'add_anchor_column_content' ),10,3);			
		
			add_filter("seo-article_custom_fields", array( $this, 'add_seo_article_fields' ));
		
			add_action('publish_seo-article', array( $this, 'schedule_pbn' ),1,2);
		}
		else{

			// Load API for generic admin functions
			
			add_action( 'wp_head', array( $this, 'seo_header') );
			add_action( 'wp_footer', array( $this, 'seo_footer') );
		}
	}
	
	public function schedule_pbn($post_id, $post){
		
		if( $post->post_status == 'publish' ){
		
			$terms = wp_get_post_terms( $post_id, 'seo-pbn' );
			
			if( !empty($terms) ){
				
				$published = get_post_meta($post_id,'seo-article-published',true);
				
				$i = 0;
				
				foreach( $terms as $term ){
					
					// get connected apps
					
					$apps = get_posts(array(
					
						'post_type' => 'user-app',
						'numberposts' => -1,
						'tax_query' => array(
						
							array(
							
								'taxonomy' => 'seo-pbn',
								'field' => 'id',
								'terms' => $term->term_id,
								'include_children' => false
							)
						)
					));
					
					if( !empty($apps) ){
						
						foreach( $apps as $app){
							
							if( !in_array( strval($app->ID), $published['key']) ){
								
								// schedule posting job	

								wp_schedule_single_event( ( time() + ( 60 * $i ) ) , $this->parent->_base . 'publish_seo_article_event' , [$post_id,$app->ID] );
							
								++$i;							
							}
						}
					}
				}
			}
		}
	}
	
	public function publish_seo_article( $post_id, $app_id ){

		//check is published
		
		$is_published = false;
		
		$published = get_post_meta($post_id,'seo-article-published',true);
		
		if( !empty($published['key']) ){
			
			foreach( $published['key'] as $id ){
				
				if( intval($id) === $app_id ){
					
					$is_published = true;
					break;
				}
			}
		}
		
		if( !$is_published ){

			if( $article = $this->get_seo_article($post_id) ){
				
				// include app
				
				$app = get_post($app_id);

				list($appSlug,$appName) = explode(' - ', $app->post_title );

				$this->parent->apps->includeApp($appSlug);
				
				// post article
				
				if( $article_url = $this->parent->apps->{$appSlug}->appPostArticle($app_id,$article) ){

					// set published
					
					$published['input'][] 	= 'string';
					$published['key'][] 	= $app_id;
					$published['value'][] 	= $article_url;
					
					update_post_meta( $post_id, 'seo-article-published', $published );
				
					// add backlink
					
					if($backlink_id = wp_insert_post( array(
											
						'post_author' 	=> $article['post_author'],
						'post_title' 	=> $article_url,
						'post_type'		=> 'seo-backlink',
						'post_status' 	=> 'publish'
					))){
						
						if( !empty($article['post_anchors']) ){
							
							// set anchors
							
							wp_set_post_terms( $backlink_id, array_keys($article['post_anchors']), 'seo-anchor' );
						}
						
						if( !empty($article['post_pbn']) ){
							
							// set anchors
							
							wp_set_post_terms( $backlink_id, array_keys($article['post_pbn']), 'seo-pbn' );
						}
						
						
					}
				}
			}
		}
	}
	
	public function get_seo_article($post_id){
		
		$article = array();
		
		$post = get_post($post_id);
		
		if( !empty($post) ){
			
			// get post title
			
			$post_title 	= $post->post_title;
			$post_content 	= $post->post_content;
			$post_author	= $post->post_author;

			preg_match_all("/<h1>(.*?)<\/h1>/", $post_content, $matches);
			
			if( !empty($matches[0][0]) ){
				
				// remove h1 from content
				
				$post_content = str_replace($matches[0][0],'',$post_content);
				
				// spin post title
				
				$post_title = strip_tags($this->spinArticle($matches[0][0]));
			}
			
			if( !empty($post_title) ){
				
				// spin post content
				
				$post_content = $this->spinArticle($post_content);
				
				// shuffle sections
				
				$post_content = $this->shuffleSections('<h2>',$post_content);
				
				// get anchors
				
				$anchors = $this->autoLinkAnchors($post_content);
				
				$post_content 	= $anchors['content'];
				$post_anchors 	= $anchors['ids'];

				// get post img
				
				$images = get_post_meta($post_id,'seo-article-images',true);
		
				if( !empty($images['value']) ){
					
					$post_img = $images['value'][array_rand($images['value'])];
				}
				else{
				
					$post_img = get_the_post_thumbnail_url( $post_id );
				}
				
				if(!empty($post_img)){
					
					$post_content = str_replace('[image]','<img title="'.$post_title.'" src="'.$post_img.'" style="width:100%;text-align:center;" />',$post_content);
				}
				
				// get post tags
				
				$post_tags 	= array();
				
				$terms = wp_get_post_terms( $post_id, 'seo-tag' );
				
				if( !empty($terms) ){
					
					foreach( $terms as $term ){
						
						$post_tags[] = $term->name;
					}
				}

				// get post pbn
				
				$post_pbn = array();
				
				$terms = wp_get_post_terms( $post_id, 'seo-pbn');
				
				if(!empty($terms)){
					
					foreach( $terms as $term ){
						
						$post_pbn[$term->term_id] = $term->name;
					}
				}
				
				// build article
				
				$article = array(
					
					'post_author' 	=> $post_author,
					'post_title' 	=> $post_title,
					'post_content' 	=> $post_content,
					'post_img' 		=> $post_img,
					'post_tags' 	=> $post_tags,
					'post_anchors' 	=> $post_anchors,
					'post_pbn' 		=> $post_pbn,
				);
			}
		}
		
		return $article;
	}
	
    public function spinArticle($text) {
		
        return preg_replace_callback(
            '/\{(((?>[^\{\}]+)|(?R))*)\}/x',
            array($this, 'spinReplace'),
            $text
        );
    }
	
    public function spinReplace($text){
		
        $text = $this->spinArticle($text[1]);
		
        $parts = explode('|', $text);
		
        return $parts[array_rand($parts)];
    }
	
	public function shuffleSections( $markup = '<h2>', $string = ''){
	
		$sections = explode($markup,$string);
		
		$first = $sections[0];
		
		$l = ( count($sections) - 1);
		
		if( $l > 0 ){
		
			$last = $sections[$l];
		}
		
		unset($sections[0],$sections[$l]);

		$string = $first;
		
		if(!empty($sections)){
			
			shuffle($sections);
		
			foreach($sections as $section){
				
				if( $markup == '<h2>' ){
					
					$string .= $markup . $this->shuffleSections('<h3>',$section);
				}
				else{
					
					$string .= $markup . $section;
				}
			}
		}
		
		if(!empty($last)){
		
			$string .= $last;
		}

		return $string;
	}
	
	public function autoLinkAnchors( $string ){
		
		$ids = [];
		
		$terms = get_terms( array(
		
			'taxonomy' 		=> 'seo-anchor',
			'hide_empty' 	=> false,
		) );
		
		shuffle($terms);
		
		$t = 0;

		foreach( $terms as $term){
			
			// string count
			
			$len = strlen($string);
			
			// replace first term
			
			$string = preg_replace("/\b(" . $term->name . ")\b/i", "<a href=\"[url]\" title=\"$1\">$1</a>", $string, 1);
			
			$new_len = strlen($string);
			
			if( $new_len > $len ){

				$len = $new_len;

				$url = get_option('short_url_'.$term->slug);
				
				if(empty($url)){
					
					$url = get_term_link($term);
				}
				
				$string = str_replace('[url]',$url,$string);
				
				$ids[$term->term_id] = $term->name;
				
				++$t;
			}
			
			if( $t == 3 ){
				
				break;
			}
		}
		
		return array( 'content' => $string, 'ids' => $ids );
	}
	
	public function seo_header(){
		
		//echo '<link rel="stylesheet" href="https://raw.githubusercontent.com/dbtek/bootstrap-vertical-tabs/master/bootstrap.vertical-tabs.css">';	
	}
	
	public function seo_footer(){
		
		
	}
	
	public function get_anchor_fields($term){

		echo'<tr class="form-field">';
		
			echo'<th valign="top" scope="row">';
				
				echo'<label for="category-text">Short URL</label>';
			
			echo'</th>';
			
			echo'<td>';
				
				echo'<input type="text" name="' . $term->taxonomy . '-short-url" id="' . $term->taxonomy . '-short-url" value="'.get_option('short_url_'.$term->slug).'"/>';
						
			echo'</td>';
			
		echo'</tr>';	
	
		echo'<tr class="form-field">';
		
			echo'<th valign="top" scope="row">';
				
				echo'<label for="category-text">Target URL</label>';
			
			echo'</th>';
			
			echo'<td>';
				
				echo'<input type="text" name="' . $term->taxonomy . '-target-url" id="' . $term->taxonomy . '-target-url" value="'.get_option('target_url_'.$term->slug).'"/>';
						
			echo'</td>';
			
		echo'</tr>';
		
		echo'<tr class="form-field">';
		
			echo'<th valign="top" scope="row">';
				
				echo'<label for="category-text">Redirect</label>';
			
			echo'</th>';
			
			echo'<td>';
				
				$this->parent->admin->display_field( array(
				
					'type'				=> 'switch',
					'id'				=> 'redirect_anchor_'.$term->slug,
					'name'				=> 'redirect_anchor_'.$term->slug,
					'description'		=> ''
					
				), false );
				
			echo'</td>';			
			
		echo'</tr>';
	}

	
	public function save_anchor_fields($term_id){

		//collect all term related data for this new taxonomy
		
		$term = get_term($term_id);

		//save our custom fields as wp-options
		if($this->parent->user->is_admin){
			
			if(isset($_POST[$term->taxonomy . '-target-url'])){

				update_option('target_url_'.$term->slug, sanitize_text_field($_POST[$term->taxonomy . '-target-url'],1));			
			}

			if(isset($_POST[$term->taxonomy . '-short-url'])){

				update_option('short_url_'.$term->slug, sanitize_text_field($_POST[$term->taxonomy . '-short-url'],1));			
			}
			
			if(isset($_POST['redirect_anchor_'.$term->slug])){

				update_option('redirect_anchor_'.$term->slug, $_POST['redirect_anchor_'.$term->slug]);			
			}
			else{
				
				update_option('redirect_anchor_'.$term->slug, '');
			}
		}
	}
	
	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $description Description of post type
	 * @return object              Post type class object
	 */
	public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new LTPLE_Client_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) return;

		$taxonomy = new LTPLE_Client_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		
		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		
		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-frontend' );
	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		
		load_plugin_textdomain( $this->settings->plugin->slug, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
		
	    $domain = $this->settings->plugin->slug;

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main LTPLE_SEO Instance
	 *
	 * Ensures only one instance of LTPLE_SEO is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see LTPLE_SEO()
	 * @return Main LTPLE_SEO instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

}
