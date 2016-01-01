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
        echo ' <span class="MItem Author">' . userAnchor($author) . '</span> ';

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
