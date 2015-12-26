<?php
/**
 * Articles database structure.
 *
 * Called by ArticleHooks::setup() to update database upon enabling app.
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
