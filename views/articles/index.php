<?php defined('APPLICATION') or exit();

if (!function_exists('formatBody')) {
    include $this->fetchViewLocation('helper_functions', 'discussion', 'vanilla');
}

$pagerOptions = array('Wrapper' => '<span class="PagerNub">&#160;</span><div %1$s>%2$s</div>', 'RecordCount' => $this->data('CountDiscussions'), 'CurrentRecords' => $this->data('Discussions')->numRows());
if ($this->data('_PagerUrl')) {
    $pagerOptions['Url'] = $this->data('_PagerUrl');
}

$discussions = $this->data('Discussions')->result();

echo '<div class="Articles">';
foreach ($discussions as $discussion) {
    echo "<article id=\"Article_{$discussion->Article->ArticleID}\" class=\"Discussion_$discussion->DiscussionID\">";
    echo wrap("<h2>" . Anchor($discussion->Name, articleUrl($discussion)) . "</h2>", 'header');

    $text = (strlen($discussion->Article->Excerpt) > 0) ? $discussion->Article->Excerpt : $discussion->Body;

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
