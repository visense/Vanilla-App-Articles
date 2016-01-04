<?php defined('APPLICATION') or exit();

$session = Gdn::session();

$cancelUrl = '/articles';
if (C('Vanilla.Categories.Use') && is_object($this->Category)) {
    $cancelUrl = '/categories/' . urlencode($this->Category->UrlCode);
}
?>
<div id="DiscussionForm" class="FormTitleWrapper DiscussionForm">
    <?php
    if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
        echo Wrap($this->data('Title'), 'h1', array('class' => 'H'));
    }

    echo '<div class="FormWrapper">';
    echo $this->Form->open();
    echo $this->Form->errors();
    $this->fireEvent('BeforeFormInputs');

    // Category
    if ($this->ShowCategorySelector === true) {
        echo '<div class="P">';
        echo '<div class="Category">';
        echo $this->Form->label('Category', 'CategoryID'), ' ';
        echo $this->Form->categoryDropDown('CategoryID', array('Value' => val('CategoryID', $this->Category), 'PermFilter' => array('AllowedDiscussionTypes' => 'Article')));
        echo '</div>';
        echo '</div>';
    }

    // Name
    echo '<div class="P">';
    echo $this->Form->label('Article Name', 'Name');
    echo wrap($this->Form->textBox('Name', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
    echo '</div>';

    // URL code
    echo '<div id="ArticleUrlCode">';
    echo wrap('URL Code', 'strong') . ': ';
    echo wrap(htmlspecialchars($this->Form->getValue('ArticleUrlCode')));
    echo $this->Form->textBox('ArticleUrlCode');
    echo anchor(T('edit'), '#', 'Edit');
    echo anchor(T('OK'), '#', 'Save SmallButton');
    echo '</div>';

    // Body
    $this->fireEvent('BeforeBodyInput');
    echo '<div class="P">';
    echo $this->Form->label('Body', 'Body');
    echo $this->Form->bodyBox('Body', array('Table' => 'Discussion', 'FileUpload' => true));
    echo '</div>';

    // Excerpt
    echo '<div class="P">';
    echo $this->Form->label('Excerpt (Optional)', 'ArticleExcerpt');
    echo $this->Form->textBox('ArticleExcerpt', array('MultiLine' => true));
    echo '</div>';

    // Article thumbnail
    echo '<div id="ArticleThumbnailUpload" class="P">';
    echo $this->Form->label('Upload Thumbnail (Max dimensions: ' . c('Articles.Articles.ThumbnailWidth', 260)
        . 'x' . c('Articles.Articles.ThumbnailHeight', 146) . ')', 'ArticleThumbnail');
    echo $this->Form->imageUpload('ArticleThumbnail');

    echo '<div id="ArticleThumbnail">';
        $thumbnail = $this->data('ArticleThumbnail');
        if ($thumbnail) {
            $imagePath = Url('/uploads' . $thumbnail->Path);

            echo '<div id="ArticleThumbnailImage"><img src="' . $imagePath . '" alt="" /></div>' .
                '<div id="ArticleThumbnailActions"><a id="DeleteArticleThumbnail" href="' . Url('/post/deletearticlethumbnail/'
                    . $thumbnail->ArticleThumbnailID) . '?DeliveryMethod=JSON&DeliveryType=BOOL">Delete</a></div>';
        }
    echo '</div>';
    echo '</div>';

    // Author
    echo '<div class="P">';
    echo $this->Form->Label('Author', 'ArticleAuthorName');
    echo Wrap($this->Form->TextBox('ArticleAuthorName', array('class' => 'InputBox BigInput MultiComplete')),
        'div', array('class' => 'TextBoxWrapper'));
    echo '</div>';

    // Options
    $options = '';
    // If the user has any of the following permissions (regardless of junction), show the options
    // Note: I need to validate that they have permission in the specified category on the back-end
    // TODO: hide these boxes depending on which category is selected in the dropdown above.
    if ($session->checkPermission('Vanilla.Discussions.Announce')) {
        $options .= '<li>' . checkOrRadio('Announce', 'Announce', $this->data('_AnnounceOptions')) . '</li>';
    }

    $this->EventArguments['Options'] = &$options;
    $this->fireEvent('DiscussionFormOptions');

    if ($options != '') {
        echo '<div class="P">';
        echo '<ul class="List Inline PostOptions">' . $options . '</ul>';
        echo '</div>';
    }

    $this->fireEvent('AfterDiscussionFormOptions');

    // Buttons
    echo '<div class="Buttons">';
    $this->fireEvent('BeforeFormButtons');
    echo $this->Form->button((property_exists($this, 'Discussion')) ? 'Save' : 'Post Article', array('class' => 'Button Primary DiscussionButton'));
    if (!property_exists($this, 'Discussion') || !is_object($this->Discussion) || (property_exists($this, 'Draft') && is_object($this->Draft))) {
        echo ' ' . $this->Form->button('Save Draft', array('class' => 'Button Warning DraftButton'));
    }
    echo ' ' . $this->Form->button('Preview', array('class' => 'Button Warning PreviewButton'));
    echo ' ' . anchor(t('Edit'), '#', 'Button WriteButton Hidden') . "\n";
    $this->fireEvent('AfterFormButtons');
    echo ' ' . Anchor(T('Cancel'), $cancelUrl, 'Button Cancel');
    echo '</div>';
    echo $this->Form->close();
    echo '</div>';
    ?>
</div>