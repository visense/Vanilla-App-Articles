<?php defined('APPLICATION') or exit();

if (!function_exists('writeComment')) {
    include $this->fetchViewLocation('helper_functions', 'discussion', 'vanilla');
}

$discussion = $this->Data('Discussion');

// Display article
echo "<article id=\"Article_$discussion->ArticleID\" class=\"Discussion_$discussion->DiscussionID Article\">";

echo "<header class=\"PageTitle\">";
// Display options
echo '<div class="Options">';
$this->fireEvent('BeforeDiscussionOptions');
writeBookmarkLink();
writeDiscussionOptions();
writeAdminCheck();
echo '</div>';

echo Wrap($discussion->Name, 'h1');
echo "</header>";

$this->fireEvent('AfterDiscussionTitle');
$this->fireEvent('AfterPageTitle');

echo Wrap(formatBody($discussion), 'div');

$this->fireEvent('AfterDiscussionBody');
WriteReactions($discussion);
if (val('Attachments', $discussion)) {
    WriteAttachments($discussion->Attachments);
}
echo "</article>";

$this->fireEvent('AfterDiscussion');

// Display comments
echo '<div class="CommentsWrap">';

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
