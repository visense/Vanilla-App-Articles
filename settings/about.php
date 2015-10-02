<?php
/**
 * An associative array of information about this application.
 *
 * @copyright 2015 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

$ApplicationInfo['Articles'] = array(
    'Description' => 'Provides a way to create articles.',
    'Version' => '2.0.0',
    'Author' => 'Austin S.',
    'AuthorUrl' => 'https://github.com/austins',
    'Url' => 'http://vanillaforums.org/addon/articles-application',
    'License' => 'GPL v2',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'RegisterPermissions' => false,
    'SetupController' => 'setup',
    'SettingsUrl' => '/settings/articles/'
);
