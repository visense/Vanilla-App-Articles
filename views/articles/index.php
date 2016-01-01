<?php defined('APPLICATION') or exit();

include $this->fetchViewLocation('helper_functions', 'article', 'articles');

if (!function_exists('formatBody')) {
    include $this->fetchViewLocation('helper_functions', 'discussion', 'vanilla');
}

if (!function_exists('optionsList')) {
    include $this->fetchViewLocation('helper_functions', 'discussions', 'vanilla');
}

$pagerOptions = array('Wrapper' => '<span class="PagerNub">&#160;</span><div %1$s>%2$s</div>', 'RecordCount' => $this->data('CountDiscussions'), 'CurrentRecords' => $this->data('Discussions')->numRows());
if ($this->data('_PagerUrl')) {
    $pagerOptions['Url'] = $this->data('_PagerUrl');
}

$discussions = $this->data('Discussions')->result();

echo '<div class="Articles">';
foreach ($discussions as $discussion) {
    echo "<article id=\"Article_$discussion->ArticleID\" class=\"Discussion_$discussion->DiscussionID Article\">";
    echo '<header>';
    // Display options
    echo '<span class="Options">';
    echo optionsList($discussion);
    echo bookmarkButton($discussion);
    echo '</span>';

    // Display article header
    echo wrap("<h2>" . Anchor($discussion->Name, articleUrl($discussion)) . "</h2>", 'header');

    // Display meta
    writeArticleMeta($discussion);
    echo '</header>';

    // Display excerpt or body
    $text = (strlen($discussion->ArticleExcerpt) > 0) ? $discussion->ArticleExcerpt : $discussion->Body;
    $formatObject = new stdClass();
    $formatObject->Body = $text;
    $text = formatBody($formatObject);
    echo wrap($text, 'div');

    echo '</article>';
}
echo '</div>';

echo '<div class="PageControls Bottom">';
PagerModule::write($pagerOptions);
echo '</div>';
