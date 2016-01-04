<?php defined('APPLICATION') or exit();

if (!function_exists('writeArticleMeta')) {
    function writeArticleMeta($discussion) {
        $category = CategoryModel::categories($discussion->CategoryID);
        $author = userBuilder($discussion, isset($discussion->FirstName) ? 'First' : 'Insert');

        echo '<div class="Meta Meta-Discussion Meta-Article">';
        // Discussion tags for announce and closed
        if (strtolower(Gdn::controller()->ControllerName) === 'articlescontroller') {
            writeTags($discussion);

            echo NewComments($discussion);
        }

        // Category
        if (c('Vanilla.Categories.Use') && $category) {
            echo wrap(Anchor(htmlspecialchars($discussion->Category), categoryUrl($discussion->CategoryUrlCode)), 'span', array('class' => 'MItem Category ' . $category['CssClass']));
        }

        // Date
        echo ' <span class="MItem Date">' . Gdn_Format::date($discussion->DateInserted, '%e %B %Y - %l:%M %p') . '</span>';

        // Author
        // Get author display name
        $authorMeta = userModel::getMeta($author->UserID, 'Articles.%', 'Articles.');
        $authorOptions = array();
        if ($authorMeta['AuthorDisplayName'] != "") {
            $authorOptions['Text'] = $authorMeta['AuthorDisplayName'];
            $authorOptions['title'] = $author->Name;
        }

        echo ' <span class="MItem Author">' . userAnchor($author, null, $authorOptions) . '</span> ';

        // Comments
        echo '<span class="MItem MCount CommentCount">';
        $commentsText = ($discussion->CountComments == 0) ? t('Comments')
            : sprintf(
                pluralTranslate($discussion->CountComments, '%s comment html', '%s comments html', t('%s comment'), t('%s comments')),
                bigPlural($discussion->CountComments, '%s comment')
            );

        echo anchor($commentsText, articleUrl($discussion) . '#comments');
        echo '</span>';
        echo '</div>';
    }
}

if (!function_exists('writeArticleAuthorInfo')) {
    function writeArticleAuthorInfo($discussion) {
        $author = userBuilder($discussion, isset($discussion->FirstName) ? 'First' : 'Insert');
        $authorMeta = userModel::getMeta($author->UserID, 'Articles.%', 'Articles.');

        if (c('Articles.Articles.ShowAuthorInfo', false) && (count($authorMeta) > 0) && ($authorMeta['AuthorBio'] !== '')) :
            ?>
            <div id="AuthorInfo" class="FormWrapper FormWrapper-Condensed BoxAfterArticle">
                <div id="AuthorPhoto">
                    <?php echo userPhoto($author, array('Size' => 'Medium')); ?>
                </div>

                <div id="AboutTheAuthor">
                    <?php echo T('About the Author'); ?>
                </div>

                <h2 class="H"><?php
                    if ($authorMeta['AuthorDisplayName'] === '') {
                        echo userAnchor($author);
                    } else {
                        echo $authorMeta['AuthorDisplayName'] . ' (' . userAnchor($author) . ')';
                    }
                    ?></h2>

                <div id="AuthorBio">
                    <?php echo $authorMeta['AuthorBio']; ?>
                </div>
            </div>
            <?php
        endif;
    }
}
