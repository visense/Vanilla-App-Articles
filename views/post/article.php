<?php defined('APPLICATION') or exit();

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

    if ($this->ShowCategorySelector === true) {
        echo '<div class="P">';
        echo '<div class="Category">';
        echo $this->Form->label('Category', 'CategoryID'), ' ';
        echo $this->Form->categoryDropDown('CategoryID', array('Value' => val('CategoryID', $this->Category), 'PermFilter' => array('AllowedDiscussionTypes' => 'Article')));
        echo '</div>';
        echo '</div>';
    }

    echo '<div class="P">';
    echo $this->Form->label('Article Name', 'Name');
    echo wrap($this->Form->textBox('Name', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
    echo '</div>';

    echo '<div id="ArticleUrlCode">';
    echo wrap('URL Code', 'strong') . ': ';
    echo wrap(htmlspecialchars($this->Form->getValue('ArticleUrlCode')));
    echo $this->Form->textBox('ArticleUrlCode');
    echo anchor(T('edit'), '#', 'Edit');
    echo anchor(T('OK'), '#', 'Save SmallButton');
    echo '</div>';

    $this->fireEvent('BeforeBodyInput');
    echo '<div class="P">';
    echo $this->Form->bodyBox('Body', array('Table' => 'Discussion', 'FileUpload' => true));
    echo '</div>';

    echo '<div class="P">';
    echo $this->Form->label('Excerpt (Optional)', 'ArticleExcerpt');
    echo $this->Form->textBox('ArticleExcerpt', array('MultiLine' => true));
    echo '</div>';

    echo '<div class="P">';
    echo $this->Form->Label('Author', 'ArticleAuthorName');
    echo Wrap($this->Form->TextBox('ArticleAuthorName', array('class' => 'InputBox BigInput MultiComplete')),
        'div', array('class' => 'TextBoxWrapper'));
    echo '</div>';

    $this->fireEvent('AfterDiscussionFormOptions');

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