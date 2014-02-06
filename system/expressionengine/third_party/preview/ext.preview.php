<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH.'libraries/Template.php';

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * Preview Extension
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Extension
 * @author		WeDoAddons
 * @link		http://wedoaddons.com
 */

class Preview_ext {
	
	public $settings 		= array();
	public $description		= 'Preview (type: proper)';
	public $docs_url		= 'http://wedoaddons.com';
	public $name			= 'Preview';
	public $settings_exist	= 'y';
	public $version			= '1.0';
	
	private $EE;
	
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	public function __construct($settings = '')
	{
		$this->EE =& get_instance();
		$this->settings = $settings;
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Settings Form
	 *
	 * If you wish for ExpressionEngine to automatically create your settings
	 * page, work in this method.  If you wish to have fine-grained control
	 * over your form, use the settings_form() and save_settings() methods 
	 * instead, and delete this one.
	 *
	 * @see http://expressionengine.com/user_guide/development/extensions.html#settings
	 */
	public function settings()
	{
		return array(
			
		);
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Activate Extension
	 *
	 * This function enters the extension into the exp_extensions table
	 *
	 * @see http://codeigniter.com/user_guide/database/index.html for
	 * more information on the db class.
	 *
	 * @return void
	 */
	public function activate_extension()
	{
		// Setup custom settings in this array.
		$this->settings = array();
		
		$data = array(
			'class'		=> __CLASS__,
			'method'	=> 'on_template_fetch_template',
			'hook'		=> 'template_fetch_template',
			'settings'	=> serialize($this->settings),
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);

		$this->EE->db->insert('extensions', $data);

        $data = array(
            'class'		=> __CLASS__,
            'method'	=> 'on_sessions_start',
            'hook'		=> 'sessions_start',
            'settings'	=> serialize($this->settings),
            'version'	=> $this->version,
            'enabled'	=> 'y'
        );

        $this->EE->db->insert('extensions', $data);

        $data = array(
            'class'		=> __CLASS__,
            'method'	=> 'on_sessions_end',
            'hook'		=> 'sessions_end',
            'settings'	=> serialize($this->settings),
            'version'	=> $this->version,
            'enabled'	=> 'y'
        );

        $this->EE->db->insert('extensions', $data);

        $data = array(
            'class'		=> __CLASS__,
            'method'	=> 'on_template_fetch_param',
            'hook'		=> 'template_fetch_param',
            'settings'	=> serialize($this->settings),
            'version'	=> $this->version,
            'enabled'	=> 'y'
        );

        $this->EE->db->insert('extensions', $data);

        $data = array(
            'class'		=> __CLASS__,
            'method'	=> 'on_cp_js_end',
            'hook'		=> 'cp_js_end',
            'settings'	=> serialize($this->settings),
            'version'	=> $this->version,
            'enabled'	=> 'y'
        );

        $this->EE->db->insert('extensions', $data);

        $data = array(
            'class'		=> __CLASS__,
            'method'	=> 'on_entry_submission_absolute_end',
            'hook'		=> 'entry_submission_absolute_end',
            'settings'	=> serialize($this->settings),
            'version'	=> $this->version,
            'enabled'	=> 'y'
        );

        $this->EE->db->insert('extensions', $data);



    }

	// ----------------------------------------------------------------------

    /**
     * Intersect the fetch_param status parameter so that we see preview entries
     *
     * @param $ref
     * @param $tagparts
     * @param $which
     * @param $value
     * @return string
     */
    public function on_template_fetch_param($ref, $tagparts, $which, $value)
    {
        if($which == 'status' && $this->EE->session->userdata('group_id') == 1) {
            if($value != '') {
                $value .= '|preview';
            } else {
                $value .= 'open|preview';
            }
        }

        return $value;
    }

    public function on_entry_submission_absolute_end($entry_id, $meta, $data, $view_url)
    {
        $is_preview = $meta['status'] == 'Preview';
        $is_draft = $meta['status'] == 'Draft';

        if(($is_preview || $is_draft ) && $this->EE->session->userdata('group_id') == 1) {
            $this->EE->extensions->end_script = TRUE;

            $edit_url = str_replace(AMP, '&', BASE)."&C=content_publish&M=entry_form&channel_id=4&entry_id=".$entry_id. ($is_preview?"&preview=y":"");

            // Support for Structure Pages (ie. not listings)
            // Structure requires "parent_id=X" to be added to the URL when editing
            /*if(isset($data['revision_post']) && isset($data['revision_post']['structure__parent_id'])) {
                $structure_parent_id = $data['revision_post']['structure__parent_id'];
                $edit_url .= '&parent_id='.$structure_parent_id;
            }*/

            Header("Location: ".$edit_url);
            die();
        }
    }

    /**
     * Add the "Preview" button here
     */
    public function on_cp_js_end() {
        $site_pages = $this->EE->config->item('site_pages');
        $current_site_pages = $site_pages[ $this->EE->config->item('site_id')];

        $preview_page_urls_js = '';
        foreach($current_site_pages['uris'] as $page_entry_id => $page_uri) {
            $preview_page_urls_js .= 'page_preview_urls['.$page_entry_id.'] = "'.$this->EE->functions->create_url($page_uri).'";';
        }

        $out_js = "

        var page_preview_urls = []; ".$preview_page_urls_js."

        $(document).ready(function(){

            // disable for Structure Pages for now (only listings preview)
            if(window.location.href.indexOf('parent_id=') > 0) {
                return;
            }

            $('.preview_button').live('click', function(e) {
                $('#status').val('Preview');
            });

            $('.save_button').live('click', function(e) {
                var current_entry_id = $('[name=entry_id]').val();
                var current_status = $('#status').val();
                if(current_entry_id == '0' || current_status == 'Preview' ) {
                    $('#status').val('Draft');  // set to draft if this is a first save
                }
            });

            $('#submit_button').live('click', function(e) {
                var current_status = $('#status').val();
                if(current_status == 'Preview' || current_status == 'Draft') {
                    $('#status').val('open');
                }
            });

            if(window.location.href.indexOf('content_publish') > 0 || window.location.href.indexOf('entry_form') > 0)
            {
                if($('#submit_button').length > 0) {
                    $('#submit_button').val('Publish');
                    $('#submit_button').css('background', 'green');

                    // find the current status and display it
                    var current_status = $('#status').val();
                    var the_entry_id = $('[name=entry_id]').val();

                    var current_status_text = false;
                    if(the_entry_id == '0') {
                        current_status_text = 'Not saved!';
                    }
                    else if(current_status == 'open' && the_entry_id != '0') {
                        current_status_text = 'Published';
                    } else if(current_status == 'Preview') {
                        current_status_text = 'In Preview';
                    } else if(current_status == 'Draft' || the_entry_id == '0') {
                        current_status_text = 'Draft (not published)';
                    }
                    if(current_status_text) {
                        $('#publish_submit_buttons').prepend('<li style=\"margin-right: 7px; color: #5F6C74;\">Current status: <strong>'+current_status_text+'</strong></li>');
                    }

                    $('#submit_button').parent().prepend( '<input type=submit value=Preview class=\"submit preview_button\" style=\"background:#F5C400\" name=preview_submit>&nbsp;<input type=submit value=Save class=\"submit save_button\" name=save_submit>&nbsp;');
                }

                if(window.location.href.indexOf('preview=y') > 0) {
                    var the_entry_id = $('[name=entry_id]').val();
                    if(page_preview_urls[the_entry_id]) {
                        var preview_url = page_preview_urls[the_entry_id];
                        window.open(preview_url);
                    }
                }
            }
        });";

        return $this->EE->extensions->last_call.$out_js;
    }

    public function on_sessions_start($ref)
    {
        //$ref->EE->TMPL = new EE_Preview_Template();
    }

    public function on_sessions_end($ref)
    {


        //print_r($ref);

        //$ref->EE->TMPL = new EE_Preview_Template();
    }

	/**
	 * on_template_fetch_template
	 *
	 * @param 
	 * @return 
	 */
	public function on_template_fetch_template($data)
	{
        $data['template_data'] = preg_replace('/\{exp\:channel\:entries(.+)\}/i', '{exp:channel:entries$1 status="open|preview"}', $data['template_data']);

        return $data;
	}

	// ----------------------------------------------------------------------

	/**
	 * Disable Extension
	 *
	 * This method removes information from the exp_extensions table
	 *
	 * @return void
	 */
	function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}

	// ----------------------------------------------------------------------

	/**
	 * Update Extension
	 *
	 * This function performs any necessary db updates when the extension
	 * page is visited
	 *
	 * @return 	mixed	void on update / false if none
	 */
	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
	}	
	
	// ----------------------------------------------------------------------
}

class EE_Preview_Template extends EE_Template
{

    public function fetch_param($which, $default = FALSE) {
        if($which == 'status') {
            return 'open|preview';

        } else {
            return parent::fetch_param($which, $default);
        }
    }

}

/* End of file ext.preview.php */
/* Location: /system/expressionengine/third_party/preview/ext.preview.php */