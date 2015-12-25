<?php defined('APPLICATION') or exit();

$pagerOptions = array('Wrapper' => '<span class="PagerNub">&#160;</span><div %1$s>%2$s</div>', 'RecordCount' => $this->data('CountArticles'), 'CurrentRecords' => $this->data('Articles')->numRows());
if ($this->data('_PagerUrl')) {
    $pagerOptions['Url'] = $this->data('_PagerUrl');
}

$articles = $this->data('Articles')->result();

echo '<div class="Articles">';
foreach ($articles as $article) {
    echo "<article id=\"Article_$article->DiscussionID\">";
    echo wrap("<h2>$article->Name</h2>", 'header');
    echo wrap($article->Body, 'div');
    echo '</article>';
}
echo '</div>';

echo '<div class="PageControls Bottom">';
PagerModule::write($pagerOptions);
echo '</div>';
