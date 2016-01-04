<?php defined('APPLICATION') or exit();

include $this->fetchViewLocation('helper_functions', 'article', 'articles');

if (!function_exists('writeComment')) {
    include $this->fetchViewLocation('helper_functions', 'discussion', 'vanilla');
}

$discussion = $this->Data('Discussion');

// Display article
echo "<article id=\"Discussion_$discussion->DiscussionID\" class=\"Article_$discussion->ArticleID Article\">";
echo '<header class="PageTitle">';
// Display options
echo '<div class="Options">';
$this->fireEvent('BeforeDiscussionOptions');
writeBookmarkLink();
writeDiscussionOptions();
writeAdminCheck();
echo '</div>';

echo Wrap($discussion->Name, 'h1');

writeArticleMeta($discussion);
echo '</header>';

$this->fireEvent('AfterDiscussionTitle');
$this->fireEvent('AfterPageTitle');

$text = formatBody($discussion);
$text = formatArticleBodyParagraphs($text);
echo wrap($text, 'div', array('class' => 'ArticleBody'));

$this->fireEvent('AfterDiscussionBody');

writeReactions($discussion);

if (val('Attachments', $discussion)) {
    writeAttachments($discussion->Attachments);
}
echo "</article>";

// Display author info
writeArticleAuthorInfo($discussion);

$this->fireEvent('AfterDiscussion');

// Display comments
echo '<div id="comments" class="CommentsWrap">';

$this->Pager->Wrapper = '<span %1$s>%2$s</span>';
echo '<span class="BeforeCommentHeading">';
$this->fireEvent('CommentHeading');
echo $this->Pager->toString('less');
echo '</span>';

echo '<div class="DataBox DataBox-Comments">';
if ($this->data('Comments')->numRows() > 0) {
    echo '<h2 class="CommentHeading">' . $this->data('_CommentsHeader', t('Comments')) . '</h2>';
}
?>
    <ul class="MessageList DataList Comments">
        <?php include $this->fetchViewLocation('comments', 'discussion', 'vanilla'); ?>
    </ul>
<?php
$this->fireEvent('AfterComments');
if ($this->Pager->LastPage()) {
    $LastCommentID = $this->addDefinition('LastCommentID');
    if (!$LastCommentID || $this->Data['Discussion']->LastCommentID > $LastCommentID) {
        $this->addDefinition('LastCommentID', (int)$this->Data['Discussion']->LastCommentID);
    }
    $this->addDefinition('Vanilla_Comments_AutoRefresh', Gdn::config('Vanilla.Comments.AutoRefresh', 0));
}
echo '</div>';

echo '<div class="P PagerWrap">';
$this->Pager->Wrapper = '<div %1$s>%2$s</div>';
echo $this->Pager->toString('more');
echo '</div>';

echo '</div>';

writeCommentForm();
