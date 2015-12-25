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
    echo $this->Form->Label('Article Name', 'Name');
    echo Wrap($this->Form->TextBox('Name', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
    echo '</div>';

    $this->FireEvent('BeforeBodyInput');
    echo '<div class="P">';
    echo $this->Form->BodyBox('Body', array('Table' => 'Discussion', 'FileUpload' => true));
    echo '</div>';

    $this->FireEvent('AfterDiscussionFormOptions');

    echo '<div class="Buttons">';
    $this->FireEvent('BeforeFormButtons');
    echo $this->Form->Button((property_exists($this, 'Discussion')) ? 'Save' : 'Post Article', array('class' => 'Button Primary DiscussionButton'));
    if (!property_exists($this, 'Discussion') || !is_object($this->Discussion) || (property_exists($this, 'Draft') && is_object($this->Draft))) {
        echo ' ' . $this->Form->Button('Save Draft', array('class' => 'Button Warning DraftButton'));
    }
    echo ' ' . $this->Form->Button('Preview', array('class' => 'Button Warning PreviewButton'));
    echo ' ' . anchor(t('Edit'), '#', 'Button WriteButton Hidden') . "\n";
    $this->FireEvent('AfterFormButtons');
    echo ' ' . Anchor(T('Cancel'), $cancelUrl, 'Button Cancel');
    echo '</div>';
    echo $this->Form->Close();
    echo '</div>';
    ?>
</div>