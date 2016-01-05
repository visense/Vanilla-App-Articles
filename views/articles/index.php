<?php defined('APPLICATION') or exit();

include $this->fetchViewLocation('helper_functions', 'article', 'articles');

if (!function_exists('formatBody')) {
    include $this->fetchViewLocation('helper_functions', 'discussion', 'vanilla');
}

if (!function_exists('optionsList')) {
    include $this->fetchViewLocation('helper_functions', 'discussions', 'vanilla');
}

$pagerOptions = array('Wrapper' => '<span class="PagerNub">&#160;</span><div %1$s>%2$s</div>',
    'RecordCount' => $this->data('CountDiscussions'), 'CurrentRecords' => $this->data('Discussions')->numRows());
if ($this->data('_PagerUrl')) {
    $pagerOptions['Url'] = $this->data('_PagerUrl');
}

$discussions = $this->data('Discussions')->result();

echo '<div class="Articles">';
foreach ($discussions as $discussion) {
    // Get thumbnail
    $thumbnail = $this->ArticleThumbnailModel->getByArticleID($discussion->ArticleID);
    $showThumbnail = $thumbnail && strlen($discussion->ArticleExcerpt) > 0;
    $thumbnailClass = $showThumbnail ? ' HasThumbnail' : '';

    echo "<article id=\"Discussion_$discussion->DiscussionID\""
        . " class=\"Article_$discussion->ArticleID Article$thumbnailClass\">";
    // Display options
    echo '<span class="Options">';
    echo optionsList($discussion);
    echo bookmarkButton($discussion);
    echo '</span>';

    // Display thumbnail
    if ($showThumbnail) {
        $thumbnailPath = '/uploads' . $thumbnail->Path;

        echo wrap(anchor(img($thumbnailPath, array('title' => $discussion->Name)), articleUrl($discussion)), 'div',
            array('class' => 'ArticleThumbnail'));
    }

    echo '<div class="ArticleInner">';
    echo '<header>';
    // Display article header
    echo "<h2>" . anchor($discussion->Name, articleUrl($discussion)) . "</h2>";

    // Display meta
    writeArticleMeta($discussion);
    echo '</header>';

    // Display excerpt or body
    $text = (strlen($discussion->ArticleExcerpt) > 0) ? $discussion->ArticleExcerpt : $discussion->Body;
    $formatObject = new stdClass();
    $formatObject->Body = $text;
    $formatObject->Format = $discussion->Format;
    $text = formatBody($formatObject);
    $text = formatArticleBodyParagraphs($text);
    echo wrap($text, 'div', array('class' => 'ArticleBody'));

    echo '</div>'; // End ArticleInner
    echo '</article>';
}
echo '</div>';

echo '<div class="PageControls Bottom">';
PagerModule::write($pagerOptions);
echo '</div>';
