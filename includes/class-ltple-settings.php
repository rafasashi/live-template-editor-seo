<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class LTPLE_SEO_Settings {

	/**
	 * The single instance of LTPLE_SEO_Settings.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 * @var 	object
	 * @access  public
	 * @since 	1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	public function __construct ( $parent ) {
		
		$this->parent = $parent;
		
		$this->plugin 		 	= new stdClass();
		$this->plugin->slug  	= 'live-template-editor-seo';

		// add tab to marketing settings
		
		foreach($this->parent->settings->tabs as $i => $tabs){
			
			if( isset($tabs['marketing-channel']) ){
				
				$this->parent->settings->tabs[$i]['seo-pbn'] = array( 'name' => 'PBN', 'post-type' => 'user-app' );
				
				$this->parent->settings->tabs[$i]['seo-anchor'] = array( 'name' => 'Anchors', 'post-type' => 'seo-backlink' );
			}
			elseif( isset($tabs['cb-default-layer']) ){
				
				$this->parent->settings->tabs[$i]['seo-article'] 	= array( 'name' => 'Articles', 	'post-type' => 'seo-article' );
				$this->parent->settings->tabs[$i]['seo-backlink'] 	= array( 'name' => 'Backlinks', 'post-type' => 'seo-backlink' );
			}
		}
		
		add_action('ltple_plugin_settings', array($this, 'plugin_info' ) );
		
		add_action('ltple_plugin_settings', array($this, 'settings_fields' ) );
		
		add_action( 'ltple_admin_menu' , array( $this, 'add_menu_items' ) );	
	}
	
	public function plugin_info(){
		
		$this->parent->settings->seos['seo-plugin'] = array(
			
			'title' 		=> 'SEO Suite',
			'addon_link' 	=> 'https://github.com/rafasashi/live-template-editor-seo',
			'addon_name' 	=> 'live-template-editor-seo',
			'source_url' 	=> 'https://github.com/rafasashi/live-template-editor-seo/archive/master.zip',
			'description'	=> 'SEO Suite including management and tracking of user backlinks.',
			'author' 		=> 'Rafasashi',
			'author_link' 	=> 'https://profiles.wordpress.org/rafasashi/',
		);		
	}

	/**
	 * Build settings fields
	 * @return array Fields to be displayed on settings page
	 */
	public function settings_fields () {
		
		$settings = [];
		
		/*
		$settings['test'] = array(
			'title'					=> __( 'Test', $this->plugin->slug ),
			'description'			=> '',
			'fields'				=> array(
				
				array(
					'id' 			=> 'seo_url',
					'label'			=> __( 'SEO Url' , $this->plugin->slug ),
					'description'	=> '',
					'type'			=> 'text',
					'default'		=> '',
					'placeholder'	=> __( 'http://', $this->plugin->slug )
				),				
			)
		);
		*/
		
		if( !empty($settings) ){
		
			foreach( $settings as $slug => $data ){
				
				if( isset($this->parent->settings->settings[$slug]['fields']) && !empty($data['fields']) ){
					
					$fields = $this->parent->settings->settings[$slug]['fields'];
					
					$this->parent->settings->settings[$slug]['fields'] = array_merge($fields,$data['fields']);
				}
				else{
					
					$this->parent->settings->settings[$slug] = $data;
				}
			}
		}
	}
	
	/**
	 * Add settings page to admin menu
	 * @return void
	 */
	public function add_menu_items () {
		
		//add menu in wordpress dashboard
		/*
		add_submenu_page(
			'live-template-editor-client',
			__( 'SEO Backlinks', $this->plugin->slug ),
			__( 'SEO Backlinks', $this->plugin->slug ),
			'edit_pages',
			'edit.php?post_type=seo-backlink'
		);
		*/
	}
}
