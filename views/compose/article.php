<?php if (!defined('APPLICATION')) exit();

if (!function_exists('ShowArticlesDashboardMenu'))
    include($this->FetchViewLocation('helper_functions', 'compose', 'articles'));

ShowArticlesDashboardMenu($this->RequestMethod);

// Declare variables.
$Categories = $this->Data('Categories');

// Open the form.
echo $this->Form->Open();
echo $this->Form->Errors();
?>
    <div>
        <?php
        if ($Categories->NumRows() > 0) {
            echo '<div class="P">';
            echo $this->Form->Label('Category', 'CategoryID'), ' ';
            echo $this->Form->DropDown('CategoryID', $Categories, array(
                'IncludeNull' => TRUE,
                'ValueField' => 'CategoryID',
                'TextField' => 'Name',
                'Value' => GetValue('CategoryID', $this->Category)
            ));
            echo '</div>';
        }
        ?>
        <div class="P">
            <?php
            echo $this->Form->Label('Article Name', 'Name');
            echo Wrap($this->Form->TextBox('Name', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
            ?>
        </div>
        <div class="P">
            <?php
            echo $this->Form->Label('Body', 'Body');
            echo $this->Form->BodyBox('Body', array('Table' => 'Article'));
            ?>
        </div>
        <div class="P">
            <?php
            echo $this->Form->Label('Excerpt (Optional)', 'Excerpt');
            echo $this->Form->BodyBox('Excerpt', array('Table' => 'Article'));
            ?>
        </div>
        <?php if(Gdn::Session()->CheckPermission('Articles.Articles.Edit')): ?>
            <div class="P">
                <?php
                echo $this->Form->Label('Author', 'AuthorUserName');
                echo Wrap($this->Form->TextBox('AuthorUserName', array('class' => 'InputBox BigInput MultiComplete')), 'div', array('class' => 'TextBoxWrapper'));
                ?>
            </div>
        <?php endif; ?>
        <div class="P">
           <?php
           echo $this->Form->Label('Status', 'Status');
           echo $this->Form->RadioList('Status', $this->Data('StatusOptions'));
           ?>
        </div>
    </div>

    <div class="Buttons">
        <?php
        echo $this->Form->Button((property_exists($this, 'Article')) ? 'Save' : 'Post Article', array('class' => 'Button Primary ArticleButton'));
        ?>
    </div>
<?php
echo $this->Form->Close();
