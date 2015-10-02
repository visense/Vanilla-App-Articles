<?php
/**
 * Render functions.
 *
 * @copyright 2015 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

if (!function_exists('articleUrl')) {
    /**
     * Return a URL for an article.
     *
     * @param object $discussion
     * @return string
     */
    function articleUrl($discussion, $page = '', $withDomain = true) {
        $discussion = (object)$discussion;

        $result = '/article/' . Gdn_Format::date($discussion->DateInserted, '%Y') . '/' . $discussion->DiscussionID;

        if ($page) {
            if ($page > 1 || Gdn::Session()->UserID) {
                $result .= '/p' . $page;
            }
        }

        return Url($result, $withDomain);
    }
}
