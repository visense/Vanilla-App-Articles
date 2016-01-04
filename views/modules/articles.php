<?php if (!defined('APPLICATION')) {
    exit();
}

if (!function_exists('BookmarkButton')) {
    require_once Gdn::controller()->fetchViewLocation('helper_functions', 'discussions', 'vanilla');
}

if (!isset($this->Prefix)) {
    $this->Prefix = 'Discussion';
}
?>
<div class="Box BoxDiscussions">
    <?php echo panelHeading(t('Recent Articles')); ?>
    <ul class="PanelInfo PanelDiscussions DataList">
        <?php
        foreach ($this->data('Discussions')->result() as $Discussion):
            ?>
            <li id="<?php echo "{$Px}_{$Discussion->DiscussionID}"; ?>" class="<?php echo CssClass($Discussion); ?>">
               <span class="Options">
                  <?php echo BookmarkButton($Discussion); ?>
               </span>
                <div class="Title"><?php
                    echo Anchor(Gdn_Format::Text($Discussion->Name, false), DiscussionUrl($Discussion) . ($Discussion->CountCommentWatch > 0 ? '#Item_' . $Discussion->CountCommentWatch : ''), 'DiscussionLink');
                    ?></div>
                <div class="Meta">
                    <?php
                    $First = new stdClass();
                    $First->UserID = $Discussion->FirstUserID;
                    $First->Name = $Discussion->FirstName;

                    echo NewComments($Discussion);

                    echo '<span class="MItem">' . Gdn_Format::Date($Discussion->FirstDate, 'html') . UserAnchor($First) . '</span>';
                    ?>
                </div>
            </li>
            <?php
        endforeach;
        if ($this->data('Discussions')->numRows() >= $this->Limit) {
            ?>
            <li class="ShowAll"><?php echo anchor(t('More…'), 'discussions'); ?></li>
        <?php } ?>
    </ul>
</div>
