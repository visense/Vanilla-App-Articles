<?php
/**
 * Set up the Articles database structure.
 *
 * @copyright 2015-2016 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/*
 * Called by ArticleHooks->Setup() to update database upon enabling app.
 */
$database = Gdn::database();
$sql = $database->sql();
$construct = $database->Structure();
$px = $construct->DatabasePrefix();

$drop = false;
$explicit = true;

// Construct the ArticleCategory table.
$construct->table('ArticleCategory');
$articleCategoryExists = $construct->tableExists();
$permissionArticleCategoryIDExists = $construct->columnExists('PermissionArticleCategoryID');
$construct->primaryKey('ArticleCategoryID')
    ->column('Name', 'varchar(255)')
    ->column('UrlCode', 'varchar(255)', false, 'unique')
    ->column('Description', 'varchar(500)', true)
    ->column('DateInserted', 'datetime')
    ->column('DateUpdated', 'datetime', true)
    ->column('InsertUserID', 'int', false, 'key')
    ->column('UpdateUserID', 'int', true)
    ->column('LastDateInserted', 'datetime', null)
    ->column('CountArticles', 'int', 0)
    ->column('LastArticleID', 'int', null)
    ->column('CountArticleComments', 'int', 0)
    ->column('LastArticleCommentID', 'int', null)
    ->column('Sort', 'int', true)
    ->column('PermissionArticleCategoryID', 'int', '-1')// Default to root category
    ->set($explicit, $drop);

$systemUserID = Gdn::userModel()->getSystemUserID();
$now = Gdn_Format::toDateTime();

if ($sql->getWhere('ArticleCategory', array('ArticleCategoryID' => -1))->numRows() == 0) {
    // Insert root article category for use with permissions.
    $sql->insert('ArticleCategory', array(
        'ArticleCategoryID' => -1,
        'Name' => 'Root',
        'UrlCode' => '',
        'Description' => 'Root of article category tree. Users should never see this.',
        'DateInserted' => $now,
        'InsertUserID' => $systemUserID,
        'PermissionArticleCategoryID' => -1));
}

if ($drop || !$articleCategoryExists) {
    // Insert first article category.
    $sql->insert('ArticleCategory', array(
        'Name' => 'General',
        'UrlCode' => 'general',
        'Description' => 'Uncategorized articles.',
        'DateInserted' => $now,
        'InsertUserID' => $systemUserID,
        'LastDateInserted' => $now,
        'CountArticles' => 1,
        'LastArticleID' => 1,
        'PermissionArticleCategoryID' => -1
    ));
} elseif ($articleCategoryExists && !$permissionArticleCategoryIDExists) {
    // Existing installations need to be set up with per/ArticleCategory permissions.
    $sql->update('ArticleCategory')->set('PermissionArticleCategoryID', -1, false)->put();
    $sql->update('Permission')->set('JunctionColumn', 'PermissionArticleCategoryID')
        ->where('JunctionColumn', 'ArticleCategoryID')->put();
}

// Construct the Article table.
$construct->table('Article');
$articleExists = $construct->tableExists();

// AttributionUserID has been depreciated. InsertUserID is now used.
$attributionUserIDExists = $construct->columnExists('AttributionUserID');
if ($articleExists && $attributionUserIDExists) {
    $attributionUserIDNotSameCount = $sql->query('SELECT COUNT(CASE WHEN AttributionUserID != InsertUserID'
        . ' THEN 1 ELSE NULL END) AS NotSameCount FROM ' . $px . 'Article;')->firstRow()->NotSameCount;

    if ($attributionUserIDNotSameCount > 0) {
        $sql->update('Article a')->set('a.InsertUserID', 'a.AttributionUserID', false, false)->put();
    }
}

$construct->primaryKey('ArticleID')
    ->column('ArticleCategoryID', 'int', false, array('key', 'index.CategoryPages'))
    ->column('Name', 'varchar(100)', false, 'fulltext')
    ->column('UrlCode', 'varchar(255)', false, 'unique')
    ->column('Body', 'longtext', false, 'fulltext')
    ->column('Excerpt', 'text', true)
    ->column('Format', 'varchar(20)', true)
    ->column('Status', 'varchar(20)', 'Draft')// Draft; Pending; Published; Trash
    ->column('Closed', 'tinyint(1)', 0)
    ->column('DateInserted', 'datetime', false, 'index')
    ->column('DateUpdated', 'datetime', true)
    ->column('InsertUserID', 'int', false, 'key')
    ->column('UpdateUserID', 'int', true)
    ->column('InsertIPAddress', 'varchar(15)', true)
    ->column('UpdateIPAddress', 'varchar(15)', true)
    ->column('CountArticleComments', 'int', 0)
    ->column('FirstArticleCommentID', 'int', true)
    ->column('LastArticleCommentID', 'int', true)
    ->column('DateLastArticleComment', 'datetime', null, array('index', 'index.CategoryPages'))
    ->column('LastArticleCommentUserID', 'int', true)
    ->set($explicit, $drop);

// Construct the ArticleComment table.
$construct->table('ArticleComment');
$construct->primaryKey('ArticleCommentID')
    ->column('ArticleID', 'int', false, 'index.1')
    ->column('Body', 'text', false, 'fulltext')
    ->column('Format', 'varchar(20)', true)
    ->column('DateInserted', 'datetime', false, array('index.1', 'index'))
    ->column('DateUpdated', 'datetime', true)
    ->column('InsertUserID', 'int', true)
    ->column('UpdateUserID', 'int', true)
    ->column('InsertIPAddress', 'varchar(39)', true)
    ->column('UpdateIPAddress', 'varchar(39)', true)
    ->column('GuestName', 'varchar(50)', true)
    ->column('GuestEmail', 'varchar(200)', true)
    ->column('ParentArticleCommentID', 'int', true)
    ->set($explicit, $drop);

// Add extra columns to user table for tracking articles and comments.
$construct->table('User')
    ->column('CountArticles', 'int', 0)
    ->column('CountArticleComments', 'int', 0)
    ->set(false, false);

// Construct the ArticleMedia table.
$construct->table('ArticleMedia');
$construct->primaryKey('ArticleMediaID')
    ->column('ArticleID', 'int(11)', true)
    ->column('Name', 'varchar(255)')
    ->column('Path', 'varchar(255)')
    ->column('Type', 'varchar(128)')
    ->column('Size', 'int(11)')
    ->column('ImageWidth', 'usmallint', null)
    ->column('ImageHeight', 'usmallint', null)
    ->column('StorageMethod', 'varchar(24)', 'local')
    ->column('IsThumbnail', 'tinyint(1)', 0)
    ->column('DateInserted', 'datetime')
    ->column('InsertUserID', 'int(11)')
    ->set($explicit, $drop);

/*
 * Create activity types.
 */
$activityModel = new ActivityModel();
$activityModel->defineType('Article');
$activityModel->defineType('ArticleComment');

/*
 * Set up permissions.
 */
$permissionModel = Gdn::permissionModel();
$permissionModel->Database = $database;
$permissionModel->SQL = $sql;

// Undefine old global permissions (Articles v1.1.1 and older)
// before category-based permissions were implemented
if (!$permissionArticleCategoryIDExists) {
    $permissionModel->undefine(array(
        'Articles.Articles.Add',
        'Articles.Articles.Close',
        'Articles.Articles.Delete',
        'Articles.Articles.Edit',
        'Articles.Articles.View',
        'Articles.Comments.Add',
        'Articles.Comments.Delete',
        'Articles.Comments.Edit'
    ));
}

// Define some global category-based permissions.
$permissionModel->define(array(
    'Articles.Articles.Add' => 0,
    'Articles.Articles.Close' => 0,
    'Articles.Articles.Delete' => 0,
    'Articles.Articles.Edit' => 0,
    'Articles.Articles.View' => 1,
    'Articles.Comments.Add' => 1,
    'Articles.Comments.Delete' => 0,
    'Articles.Comments.Edit' => 0
),
    'tinyint',
    'ArticleCategory',
    'PermissionArticleCategoryID');

// Set default permissions for roles.
If (!$permissionArticleCategoryIDExists) {
    // Guest defaults
    $permissionModel->save(array(
        'Role' => 'Guest',
        'JunctionTable' => 'ArticleCategory',
        'JunctionColumn' => 'PermissionArticleCategoryID',
        'JunctionID' => -1,
        'Articles.Articles.View' => 1
    ), true);

    // Unconfirmed defaults
    $permissionModel->save(array(
        'Role' => 'Unconfirmed',
        'JunctionTable' => 'ArticleCategory',
        'JunctionColumn' => 'PermissionArticleCategoryID',
        'JunctionID' => -1,
        'Articles.Articles.View' => 1
    ), true);

    // Applicant defaults
    $permissionModel->save(array(
        'Role' => 'Applicant',
        'JunctionTable' => 'ArticleCategory',
        'JunctionColumn' => 'PermissionArticleCategoryID',
        'JunctionID' => -1,
        'Articles.Articles.View' => 1
    ), true);

    // Member defaults
    $permissionModel->save(array(
        'Role' => 'Member',
        'JunctionTable' => 'ArticleCategory',
        'JunctionColumn' => 'PermissionArticleCategoryID',
        'JunctionID' => -1,
        'Articles.Articles.View' => 1,
        'Articles.Comments.Add' => 1
    ), true);

    // Moderator defaults
    $permissionModel->save(array(
        'Role' => 'Moderator',
        'JunctionTable' => 'ArticleCategory',
        'JunctionColumn' => 'PermissionArticleCategoryID',
        'JunctionID' => -1,
        'Articles.Articles.Add' => 1,
        'Articles.Articles.Close' => 1,
        'Articles.Articles.Delete' => 1,
        'Articles.Articles.Edit' => 1,
        'Articles.Articles.View' => 1,
        'Articles.Comments.Add' => 1,
        'Articles.Comments.Delete' => 1,
        'Articles.Comments.Edit' => 1
    ), true);

    // Administrator defaults
    $permissionModel->save(array(
        'Role' => 'Administrator',
        'JunctionTable' => 'ArticleCategory',
        'JunctionColumn' => 'PermissionArticleCategoryID',
        'JunctionID' => -1,
        'Articles.Articles.Add' => 1,
        'Articles.Articles.Close' => 1,
        'Articles.Articles.Delete' => 1,
        'Articles.Articles.Edit' => 1,
        'Articles.Articles.View' => 1,
        'Articles.Comments.Add' => 1,
        'Articles.Comments.Delete' => 1,
        'Articles.Comments.Edit' => 1
    ), true);
}
