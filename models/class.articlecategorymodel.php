<?php defined('APPLICATION') or exit();
/**
 * Copyright (C) 2015  Austin S.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Handles data for articles.
 */
class ArticleCategoryModel extends Gdn_Model {
    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('ArticleCategory');
    }

    /**
     * Count recalculation. Called by DBAModel->Counts().
     *
     * @param string $Column
     * @return array
     */
    public function Counts($Column) {
        $Result = array('Complete' => true);

        switch ($Column) {
            case 'CountArticles':
                $this->Database->Query(DBAModel::GetCountSQL('count', 'ArticleCategory', 'Article'));
                break;
            case 'CountArticleComments':
                $this->Database->Query(DBAModel::GetCountSQL('sum', 'ArticleCategory', 'Article', $Column,
                    'CountArticleComments'));
                break;
            case 'LastArticleID':
                $this->Database->Query(DBAModel::GetCountSQL('max', 'ArticleCategory', 'Article'));
                break;
            case 'LastArticleCommentID':
                $Data = $this->SQL
                    ->Select('a.ArticleCategoryID')
                    ->Select('ac.ArticleCommentID', 'max', 'LastArticleCommentID')
                    ->Select('a.ArticleID', 'max', 'LastArticleID')
                    ->Select('ac.DateInserted', 'max', 'DateLastArticleComment')
                    ->Select('a.DateInserted', 'max', 'DateLastArticle')
                    ->From('ArticleComment ac')
                    ->Join('Article a', 'a.ArticleID = ac.ArticleID')
                    ->GroupBy('a.ArticleCategoryID')
                    ->Get()->ResultArray();

                // Now we have to grab the discussions associated with these comments.
                $ArticleCommentIDs = ConsolidateArrayValuesByKey($Data, 'LastArticleCommentID');

                // Grab the discussions for the comments.
                $this->SQL
                    ->Select('ac.ArticleCommentID, ac.ArticleID')
                    ->From('ArticleComment ac')
                    ->WhereIn('ac.ArticleCommentID', $ArticleCommentIDs);

                $Articles = $this->SQL->Get()->ResultArray();
                $Articles = Gdn_DataSet::Index($Articles, array('ArticleCommentID'));

                foreach ($Data as $Row) {
                    $ArticleCategoryID = (int)$Row['ArticleCategoryID'];
                    $Category = $this->GetByID($ArticleCategoryID);
                    $ArticleCommentID = $Row['LastArticleCommentID'];
                    $ArticleID = GetValueR("$ArticleCommentID.ArticleID", $Articles, null);

                    $DateLastArticleComment = Gdn_Format::ToTimestamp($Row['DateLastArticleComment']);
                    $DateLastArticle = Gdn_Format::ToTimestamp($Row['DateLastArticle']);

                    $Set = array('LastArticleCommentID' => $ArticleCommentID);

                    if ($ArticleID) {
                        $LastArticleID = val('LastArticleID', $Category);

                        if ($DateLastArticleComment >= $DateLastArticle) {
                            // The most recent article is from this comment.
                            $Set['LastArticleID'] = $ArticleID;
                        } else {
                            // The most recent discussion has no comments.
                            $Set['LastArticleCommentID'] = null;
                        }
                    } else {
                        // Something went wrong.
                        $Set['LastArticleCommentID'] = null;
                        $Set['LastArticleID'] = null;
                    }

                    $this->SetField($ArticleCategoryID, $Set);
                }

                break;
            case 'LastDateInserted':
                $Categories = $this->SQL
                    ->Select('ca.ArticleCategoryID')
                    ->Select('a.DateInserted', '', 'DateLastArticle')
                    ->Select('ac.DateInserted', '', 'DateLastArticleComment')
                    ->From('ArticleCategory ca')
                    ->Join('Article a', 'a.ArticleID = ca.LastArticleID')
                    ->Join('ArticleComment ac', 'ac.ArticleCommentID = ca.LastArticleCommentID')
                    ->Get()->ResultArray();

                foreach ($Categories as $Category) {
                    $DateLastArticle = val('DateLastArticle', $Category);
                    $DateLastArticleComment = val('DateLastArticleComment', $Category);

                    $MaxDate = $DateLastArticleComment;
                    if (is_null($DateLastArticleComment) || $DateLastArticle > $MaxDate) {
                        $MaxDate = $DateLastArticle;
                    }

                    if (is_null($MaxDate)) {
                        continue;
                    }

                    $ArticleCategoryID = (int)$Category['ArticleCategoryID'];
                    $this->SetField($ArticleCategoryID, 'LastDateInserted', $MaxDate);
                }

                break;
        }

        return $Result;
    }

    /**
     * Gets the data for multiple articles based on given criteria.
     *
     * @param array $Wheres SQL conditions.
     *
     * @return Gdn_DataSet SQL result.
     */
    public function Get($Wheres = null) {
        // Set up selection query.
        $this->SQL->Select('ac.*')->From('ArticleCategory ac');

        // Handle SQL conditions for wheres.
        $this->EventArguments['Wheres'] = &$Wheres;
        $this->FireEvent('BeforeGet');

        if (is_array($Wheres)) {
            $this->SQL->Where($Wheres);
        }

        // Exclude root category
        $this->SQL->Where('ArticleCategoryID <>', '-1');

        // Set order of data.
        $this->SQL->OrderBy('ac.Name', 'asc');

        // Fetch data.
        $Categories = $this->SQL->Get();

        // Prepare and fire event.
        $this->EventArguments['Data'] = $Categories;
        $this->FireEvent('AfterGet');

        return $Categories;
    }

    /**
     * Delete a single category and assign its articles to another.
     *
     * @return void
     * @throws Exception on invalid category for deletion.
     * @param object $Category
     * @param int $ReplacementArticleCategoryID Unique ID of category all discussion are being move to.
     */
    public function Delete($Category, $ReplacementArticleCategoryID) {
        // Don't do anything if the required category object & properties are not defined.
        if (!is_object($Category)
            || !property_exists($Category, 'ArticleCategoryID')
            || !property_exists($Category, 'Name')
            || $Category->ArticleCategoryID <= 0
        ) {
            throw new Exception(T('Invalid category for deletion.'));
        } else {
            // If there is a replacement category...
            if ($ReplacementArticleCategoryID > 0) {
                // Update articles.
                $this->SQL
                    ->Update('Article')
                    ->Set('ArticleCategoryID', $ReplacementArticleCategoryID)
                    ->Where('ArticleCategoryID', $Category->ArticleCategoryID)
                    ->Put();

                // Update the article count.
                $Count = $this->SQL
                    ->Select('ArticleID', 'count', 'ArticleCount')
                    ->From('Article')
                    ->Where('ArticleCategoryID', $ReplacementArticleCategoryID)
                    ->Get()
                    ->FirstRow()
                    ->ArticleCount;

                if (!is_numeric($Count)) {
                    $Count = 0;
                }

                $this->SQL
                    ->Update('ArticleCategory')->Set('CountArticles', $Count)
                    ->Where('ArticleCategoryID', $ReplacementArticleCategoryID)
                    ->Put();
            } else {
                // Delete comments in this category.
                $this->SQL
                    ->From('ArticleComment ac')
                    ->Join('Article a', 'ac.ArticleID = a.ArticleID')
                    ->Where('a.ArticleID', $Category->ArticleCategoryID)
                    ->Delete();

                // Delete articles in this category.
                $this->SQL->Delete('Article', array('ArticleCategoryID' => $Category->ArticleCategoryID));
            }

            // Finally, delete the category.
            $this->SQL->Delete('ArticleCategory', array('ArticleCategoryID' => $Category->ArticleCategoryID));
        }
    }

    /**
     * Saves the category.
     *
     * @param array $FormPostValues The values being posted back from the form.
     * @return int ID of the saved category.
     */
    public function Save($FormPostValues) {
        // Define the primary key in this model's table.
        $this->defineSchema();

        // Get data from form
        $ArticleCategoryID = arrayValue('ArticleCategoryID', $FormPostValues);
        $NewName = arrayValue('Name', $FormPostValues, '');
        $UrlCode = arrayValue('UrlCode', $FormPostValues, '');
        $CustomPermissions = (bool)val('CustomPermissions', $FormPostValues);

        // Is this a new category?
        $Insert = $ArticleCategoryID > 0 ? false : true;
        if ($Insert) {
            $this->AddInsertFields($FormPostValues);
        }

        $this->AddUpdateFields($FormPostValues);
        $this->Validation->applyRule('UrlCode', 'Required');
        $this->Validation->applyRule('UrlCode', 'UrlStringRelaxed');

        // Make sure that the UrlCode is unique among categories.
        $this->SQL->select('ArticleCategoryID')
            ->from('ArticleCategory')
            ->where('UrlCode', $UrlCode);

        if ($ArticleCategoryID) {
            $this->SQL->where('ArticleCategoryID <>', $ArticleCategoryID);
        }

        if ($this->SQL->get()->numRows()) {
            $this->Validation->addValidationResult('UrlCode',
                'The specified URL code is already in use by another article category.');
        }

        //	Prep and fire event.
        $this->EventArguments['FormPostValues'] = &$FormPostValues;
        $this->EventArguments['ArticleCategoryID'] = $ArticleCategoryID;
        $this->fireEvent('BeforeSaveArticleCategory');

        // Validate the form posted values
        if ($this->validate($FormPostValues, $Insert)) {
            $Fields = $this->Validation->SchemaValidationFields();
            $Fields = RemoveKeyFromArray($Fields, 'ArticleCategoryID');

            if ($Insert === false) {
                $OldCategory = $this->getID($ArticleCategoryID, DATASET_TYPE_ARRAY);

                $this->update($Fields, array('ArticleCategoryID' => $ArticleCategoryID));
            } else {
                $ArticleCategoryID = $this->insert($Fields);

                if ($ArticleCategoryID) {
                    if ($CustomPermissions) {
                        $this->SQL->put('ArticleCategory', array('PermissionArticleCategoryID' => $ArticleCategoryID),
                            array('ArticleCategoryID' => $ArticleCategoryID));
                    }
                }
            }

            // Save the permissions
            if ($ArticleCategoryID) {
                // Check to see if this category uses custom permissions.
                if ($CustomPermissions) {
                    $PermissionModel = Gdn::permissionModel();
                    $Permissions = $PermissionModel->PivotPermissions(val('Permission', $FormPostValues, array()),
                        array('JunctionID' => $ArticleCategoryID));
                    $PermissionModel->SaveAll($Permissions,
                        array('JunctionID' => $ArticleCategoryID, 'JunctionTable' => 'ArticleCategory'));

                    if (!$Insert) {
                        // Figure out my last permission.
                        $Data = $this->SQL->select('PermissionArticleCategoryID')->from('ArticleCategory')
                            ->where('ArticleCategoryID', $ArticleCategoryID)->get()->firstRow(DATASET_TYPE_ARRAY);

                        // Update this category's permission.
                        $this->SQL->put('ArticleCategory', array('PermissionArticleCategoryID' => $ArticleCategoryID),
                            array('ArticleCategoryID' => $ArticleCategoryID));
                    }
                } elseif (!$Insert) {
                    // Delete my custom permissions.
                    $this->SQL->delete(
                        'Permission',
                        array('JunctionTable' => 'ArticleCategory', 'JunctionColumn' => 'PermissionArticleCategoryID', 'JunctionID' => $ArticleCategoryID)
                    );
                }
            }

            // Force the user permissions to refresh.
            Gdn::userModel()->ClearPermissions();
        } else {
            $ArticleCategoryID = false;
        }

        return $ArticleCategoryID;
    }

    /**
     * Get article category by ID.
     *
     * @param int $ArticleCategoryID
     * @return bool|object
     */
    public function GetByID($ArticleCategoryID) {
        // Set up the query.
        $this->SQL->Select('ac.*')
            ->From('ArticleCategory ac')
            ->Where('ac.ArticleCategoryID', $ArticleCategoryID);

        // Fetch data.
        $Category = $this->SQL->Get()->FirstRow();

        return $Category;
    }

    /**
     * Get article category by URL code.
     *
     * @param string $ArticleCategoryID
     * @return bool|object
     */
    public function GetByUrlCode($CategoryUrlCode) {
        // Set up the query.
        $this->SQL->Select('ac.*')
            ->From('ArticleCategory ac')
            ->Where('ac.UrlCode', $CategoryUrlCode);

        // Fetch data.
        $Category = $this->SQL->Get()->FirstRow();

        return $Category;
    }

    /**
     * Determines and sets the most recent post fields
     * for a specific article category ID.
     *
     * @param int $ArticleCategoryID
     */
    public function SetRecentPost($ArticleCategoryID) {
        $Row = $this->SQL
            ->GetWhere('Article', array('ArticleCategoryID' => $ArticleCategoryID), 'DateLastArticleComment', 'desc', 1)
            ->FirstRow(DATASET_TYPE_ARRAY);

        $Fields = array('LastArticleCommentID' => null, 'LastArticleID' => null);

        if ($Row) {
            $Fields['LastArticleCommentID'] = $Row['LastArticleCommentID'];
            $Fields['LastArticleID'] = $Row['ArticleID'];
        }

        $this->SetField($ArticleCategoryID, $Fields);
    }
}
