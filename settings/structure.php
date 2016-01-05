<?php
/**
 * Articles database structure.
 * Called by ArticleHooks::setup() to update database upon enabling app.
 *
 * @copyright 2015-2016 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

$database = Gdn::database();
$drop = false;
$explicit = false;
$sql = $database->sql();
$construct = $database->structure();

// Construct the Article table
$construct->table('Article');
$construct->primaryKey('ArticleID')
    ->column('DiscussionID', 'int', false, 'key')
    ->column('UrlCode', 'varchar(255)', false, 'unique')
    ->column('Excerpt', 'text', true)
    ->set($explicit, $drop);

// Construct the ArticleThumbnail table
$construct->table('ArticleThumbnail');
$construct->primaryKey('ArticleThumbnailID')
    ->column('ArticleID', 'int', true, 'key')
    ->column('Name', 'varchar(255)')
    ->column('Path', 'varchar(255)')
    ->column('Type', 'varchar(128)')
    ->column('Size', 'int(11)')
    ->column('Width', 'usmallint')
    ->column('Height', 'usmallint')
    ->column('DateInserted', 'datetime')
    ->column('InsertUserID', 'int(11)')
    ->set($explicit, $drop);
