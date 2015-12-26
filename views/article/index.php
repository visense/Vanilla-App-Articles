<?php defined('APPLICATION') or exit();

if (!function_exists('formatBody')) {
    include $this->fetchViewLocation('helper_functions', 'discussion', 'vanilla');
}

$discussion = $this->Data('Discussion');

echo Wrap($discussion->Name, 'h1');
echo Wrap(formatBody($discussion), 'div');
