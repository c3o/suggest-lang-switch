<?php
/**
* Plugin Name: Suggest Language Switch (for WPML)
* Plugin URI: https://github.com/c3o/suggest-lang-switch/
* Description: WPML addon: Suggest language switching when content is also available in a user's preferred language
* Version: 1.0
* Author: c3o
* Author URI: http://c3o.org
**/

class Suggest_Language_Switch {

	protected $prompt = 'This page is available in English';
	protected $slug = 'suggest-lang-switch'; # Used as: String context, settings page slug, script & css slug & filename
	protected $wpml_active;

	public function __construct() {

		register_activation_hook( __FILE__, array($this, 'activate'));
		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('admin_init', array($this, 'setup_settings_sections'));
		add_action('admin_init', array($this, 'setup_settings_fields'));
		
		# Don't do anything unless WPML is running
		$this->wpml_active = defined('ICL_SITEPRESS_VERSION');
		if (!$this->wpml_active) return;

		# Register language switch string for translation
		do_action('wpml_register_single_string', $this->slug, 'prompt', $this->prompt, false, 'en');

		# Hook into template
		add_action('wp_enqueue_scripts', array($this, 'scripts'));
		if (get_option('template_hook')) {
			add_action(get_option('template_hook'), array($this, 'check'));
		}

	}

	
	# Activation

	public function activate() {
	    
	    add_option('template_hook', 'wp_body_open');
	
	}

	
	# Settings

	public function admin_menu() {

	    $page_title = 'Suggest Language Switch';
	    $menu_title = 'Suggest Lang Switch';
	    $capability = 'manage_options';
	    $callback = array( $this, 'settings');
	    add_submenu_page('options-general.php', $page_title, $menu_title, $capability, $this->slug, $callback);

	    add_action('admin_notices', array($this, 'admin_notice'));

	}

	public function admin_notice() {
		if (isset($_GET['page']) && $_GET['page'] == $this->slug) {
			if (!$this->wpml_active) {
				wp_admin_notice( 'WPML not found &ndash; plugin is inactive!', ['type' => 'error'] );
			}
		}
	}

	public function settings() { ?>

	    <div class="wrap">
	        <h2>Suggest Language Switch</h2>
	        <form method="post" action="options.php">
	            <?php
	                settings_fields('suggestlangswitch_fields');
	                do_settings_sections('suggestlangswitch_fields');
	                submit_button();
	            ?>
	        </form>
	        <a href="admin.php?page=wpml-string-translation%2Fmenu%2Fstring-translation.php&context=<?= $this->slug ?>">Add/edit prompt translations</a>
	    </div> <?php

	}
	
	public function setup_settings_sections() {
	    add_settings_section('section_templatehook', '', false, 'suggestlangswitch_fields' );
	}
	
	public function setup_settings_fields() {	
	    register_setting('suggestlangswitch_fields', 'template_hook');
	    add_settings_field('template_hook', 'Template Hook', array($this, 'setup_settings_field'), 'suggestlangswitch_fields', 'section_templatehook');
	}
	
	public function setup_settings_field($arguments) {
	    echo '<input name="template_hook" id="template_hook" type="text" value="' . get_option('template_hook') . '" />';
	}
	
	
	# JS, CSS, HTML

	public function scripts() {

	    wp_enqueue_script($this->slug, plugins_url('/'.$this->slug.'.js', __FILE__));
		wp_register_style($this->slug, plugins_url('/'.$this->slug.'.css', __FILE__));
	    wp_enqueue_style($this->slug);

	}
	
	public function check() {

		# Are we on a post/page/attachment?
		if (!get_post_type()) return;

		# Get available language versions
		$site_translations = apply_filters('wpml_active_languages', NULL);
		$site_langs = array_keys($site_translations);

		# Get available translations of this content
		
		$curr_type = 'post_'.get_post_type();
		$trid = apply_filters('wpml_element_trid', NULL, get_the_ID(), $curr_type);
		$post_translations = apply_filters('wpml_get_element_translations', NULL, $trid, $curr_type);
		
		$post_langs = array_intersect($site_langs, array_keys($post_translations));

		$suggest_lang_switch = [
			'current' => ICL_LANGUAGE_CODE,
			'translations' => []
		];
		foreach ($post_langs as $lang) {
			if ($post_translations[$lang]->{'post_status'} == 'publish') { # not just draft
				$suggest_lang_switch['translations'][$lang] = [
					'url' => $site_translations[$lang]['url'],
					'title' => $post_translations[$lang]->{'post_title'},
					'prompt' => apply_filters('wpml_translate_single_string', $this->prompt, $this->slug, 'prompt', $lang)
				];
			}
		}
		$this->html($suggest_lang_switch);

	}

	public function html($suggest_lang_switch) {

		echo '<script type="text/javascript">';
		echo 'var suggestLangSwitch = '.json_encode($suggest_lang_switch).';';
		echo '</script>';

		echo '<div id="suggest-lang-switch">';
		echo '<a class="suggest-lang-switch-link" href="#"></a>';
		echo '<a class="suggest-lang-switch-dismiss" href="#" onclick="return suggestLangSwitchDismiss(this.parentNode)">&times;</a>';
		echo '</div>';

	}

}
new Suggest_Language_Switch();

?>
