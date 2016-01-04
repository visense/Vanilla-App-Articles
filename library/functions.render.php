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

        $result = '/article/' . date('Y', strtotime($discussion->DateInserted)) . '/' . $discussion->ArticleUrlCode;

        if ($page) {
            if ($page > 1 || Gdn::Session()->UserID) {
                $result .= '/p' . $page;
            }
        }

        return Url($result, $withDomain);
    }
}

if (!function_exists('FormatArticleBody')) {
    /**
     * Formats the body string of an article.
     *
     * @param string $text
     * @return string
     */
    function formatArticleBodyParagraphs($text) {
        // Format new lines
        $text = preg_replace("/(\015\012)|(\015)|(\012)/", "<br />", $text);
        $text = fixNl2Br($text);

        // Convert br to paragraphs
        $text = preg_replace('#(?:<br\s*/?>\s*?){2,}#', '</p><p>', $text);

        // Add p on first paragraph
        $text = '<p>' . $text . '</p>';

        return $text;
    }
}
