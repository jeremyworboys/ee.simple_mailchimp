<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require PATH_THIRD.'simple_mailchimp/config.php';

/**
 * Plugin Info
 *
 * @var array
 */
$plugin_info = array(
    'pi_name'           => SIMPLE_MAILCHIMP_NAME,
    'pi_version'        => SIMPLE_MAILCHIMP_VERSION,
    'pi_author'         => 'Jeremy Worboys',
    'pi_author_url'     => 'http://complexcompulsions.com',
    'pi_description'    => 'A simple way to display a MailChimp sign-up form.',
    'pi_usage'          => Simple_mailchimp::usage()
);


/**
 * Simple MailChimp
 *
 * @package    simple_mailchimp
 * @author     Jeremy Worboys <jeremy@complexcompulsions.com>
 * @link       http://complexcompulsions.com/add-ons/simple-mailchimp
 * @copyright  Copyright (c) 2012 Jeremy Worboys
 */
class Simple_mailchimp {

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->EE =& get_instance();
        $this->EE->load->library('form_validation');

        // Fetch parameters and set defaults
        $api_key          = $this->EE->TMPL->fetch_param('api_key');
        $list_id          = $this->EE->TMPL->fetch_param('list_id');
        $form_name        = $this->EE->TMPL->fetch_param('form_name', 'simple_mailchimp');
        $return           = $this->EE->TMPL->fetch_param('return');
        $error_delimiters = $this->EE->TMPL->fetch_param('error_delimiters', '<span class="error">|</span>');
        $success          = FALSE;

        // Set global error delimiters
        $error_delimiters = explode('|', $error_delimiters);
        $this->EE->form_validation->set_error_delimiters($error_delimiters[0], $error_delimiters[1]);

        // Bring in MailChimp API
        require_once(PATH_THIRD.'simple_mailchimp/libraries/MCAPI.class.php');

        $MC = new MCAPI($api_key);
        $fields = $MC->listMergeVars($list_id);

        // Check to see if the form has been submitted
        if (!empty($_POST) AND $this->EE->input->post(md5($form_name), true)) {
            // Prepare validation rules
            foreach ($fields as $field) {
                // Build validation rule array
                $validation = array();
                $validation[] = 'trim';
                if ($field['req']) {
                    $validation[] = 'required';
                }
                if ($field['field_type'] === 'email') {
                    $validation[] = 'valid_email';
                }
                $validation[] = 'xss_clean';
                // Set validation rule
                $this->EE->form_validation->set_rules($field['tag'], $field['name'], implode('|', $validation));
            }

            // Check if form data was valid
            if ($this->EE->form_validation->run()) {
                // Awesome, let's build the merge vars array
                $merge_vars = array();
                foreach ($fields as $field) {
                    $tag = $field['tag'];
                    if (isset($_POST[$tag])) {
                        $merge_vars[$tag] = $this->EE->input->post($tag, TRUE);
                    }
                }
                // Finally subscribe the user
                $MC->listSubscribe($list_id, $this->EE->config->item('webmaster_email'), $merge_vars);

                // Redirect to the "return" path
                if ($return) {
                    $return = $this->EE->functions->create_url($return);
                    $this->EE->functions->redirect($return);
                }
                $success = TRUE;
            }
            // Otherwise, continue displaying the page
        }

        // Parse the variables
        $tag_vars = array();
        foreach ($fields as $field) {
            if (!$field['public']) { continue; }
            extract($field);

            $tag_vars["label:$tag"] = "<label for='$tag'>$name</label>";
            $tag_vars["merge:$tag"] = "<input type='$field_type' name='$tag' id='$tag' value='".set_value($tag, $default).'\''.(($req)?' required="required"':'').' />';
            $tag_vars["error:$tag"] = form_error($tag);
        }
        $tag_vars['submit'] = '<input type="submit" value="Subscribe" />';
        $tag_vars['success'] = $success;

        // Prepare the opening form tag
        $form_details = array();
        $form_details['action']        = '';
        $form_details['name']          = $form_name;
        $form_details['id']            = $this->EE->TMPL->form_id;
        $form_details['class']         = $this->EE->TMPL->form_class;
        $form_details['hidden_fields'] = array(md5($form_name) => '1');

        // Generate the output
        $output  = $this->EE->functions->form_declaration($form_details);
        $output .= $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, array($tag_vars));
        $output .= '</form>';

        // Send to browser
        $this->return_data = $output;
    }

// -----------------------------------------------------------------------------

    /**
     * Usage
     *
     * @return string How to use this plugin.
     */
    public function usage()
    {
        ob_start(); ?>

Simple MailChimp
===========================

There is only one tag to embed a MailChimp for on your website:

{exp:simple_mailchimp}


Parameters
===========================

The tag has seven possible parameters:

- api_key - Your API key.
- list_id - The ID of the list you would like to subscribe users to.
- form_name - A unique name for this form.
- return - The path to the page to display on a successful submission.
- error_delimiters - How the error fields are outputted.
- form_class - The class to be applied to the form element.
- form_id - The ID to be applied to the form element.


Single Variables
===========================

{merge:TAG}
---------------------------

The {merge:TAG} variable displays a merge field where TAG is the merge tag for
that field (e.g. {merge:EMAIL}).

{label:TAG}
---------------------------

The {label:TAG} variable displays a label where TAG is the merge tag for
that field (e.g. {label:EMAIL}).

{error:TAG}
---------------------------

The {error:TAG} variable displays an error if a field is not filled out
correctly where TAG is the merge tag for the field. If the field is filled out
correctly, nothing is displayed (not even the wrapping elements).

{submit}
---------------------------

The {submit} variable displays the submit button for the form.


Conditional Variables
===========================

success
---------------------------

The `success` conditional variable can be used to display a success message if
if the form submission is successful.


Example
===========================

{exp:simple_mailchimp
  api_key="1234567890abcdef1234567890abcdef-us2"
  list_id="1234567890"
  error_delimeters='<p class="error">|</p>'}
    {if success}
        <p class="success">Success! Check your email to activate your subscription.</p>
    {if:else}
        <p>
            {label:EMAIL}
            {merge:EMAIL}
            {error:EMAIL}
        </p>
        <p>
            {label:MMERGE1}
            {merge:MMERGE1}
            {error:MMERGE1}
        </p>
        {submit}
    {/if}
{/exp:simple_mailchimp}


Changelog
===========================

Version 1.0.0
---------------------------

- Initial release

    <?php
        $buffer = ob_get_contents();
        ob_end_clean();

        return $buffer;
    }
}
// END CLASS
