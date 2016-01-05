<?php defined('APPLICATION') or exit();

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
            <li id="<?php echo "{$this->Prefix}_{$Discussion->DiscussionID}"; ?>"
                class="<?php echo cssClass($Discussion); ?>">
               <span class="Options">
                  <?php echo bookmarkButton($Discussion); ?>
               </span>
                <div class="Title"><?php
                    echo anchor(Gdn_Format::text($Discussion->Name, false),
                        discussionUrl($Discussion) . ($Discussion->CountCommentWatch > 0
                            ? '#Item_' . $Discussion->CountCommentWatch : ''),
                        'DiscussionLink');
                    ?></div>
                <div class="Meta">
                    <?php
                    $First = new stdClass();
                    $First->UserID = $Discussion->FirstUserID;
                    $First->Name = $Discussion->FirstName;

                    echo newComments($Discussion);

                    echo '<span class="MItem">' . Gdn_Format::date($Discussion->FirstDate,
                            'html') . userAnchor($First) . '</span>';
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
