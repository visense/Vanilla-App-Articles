<?php defined('APPLICATION') or exit(); ?>
<h1><?php echo $this->title(); ?></h1>

<?php echo $this->ConfigurationModule->toString(); ?>

<br />

<h3><?php echo t('Feedback'); ?></h3>

<div class="Info">
    <p><?php echo t('Articles.Settings.DonateInfo',
        'If you like this application and want to support the developer, click the donation button below. :)'); ?></p>

    <div class="Box" style="width: 150px; text-align: center; padding: 10px;"><a
            href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=72R6B2BUCMH46" target="_blank"><img
                src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" alt="" style="vertical-align: middle;"/></a>
    </div>
</div>