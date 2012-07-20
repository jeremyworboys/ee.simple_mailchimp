<?php

if (!defined('SIMPLE_MAILCHIMP_VERSION')) {
    define('SIMPLE_MAILCHIMP_NAME', 'Simple MailChimp');
    define('SIMPLE_MAILCHIMP_VERSION', '1.0.0');
}

$config['name'] = SIMPLE_MAILCHIMP_NAME;
$config['version'] = SIMPLE_MAILCHIMP_VERSION;
$config['nsm_addon_updater']['versions_xml'] = 'http://complexcompulsions.com/nsm_version/simple_mailchimp/changelog.xml';
