jQuery(document).ready(function ($) {
    // ArticleUrlCode: Map plain text category to url code
    $("#Form_Name").keyup(function (event) {
        if ($('#Form_ArticleUrlCodeIsDefined').val() == '0') {
            $('#ArticleUrlCode').show();
            var val = $(this).val().replace(/[ \/\\&.?;,<>'"]+/g, '-');
            val = val.replace(/\-+/g, '-').toLowerCase();
            $("#Form_ArticleUrlCode").val(val);
            $("#ArticleUrlCode").find("span").text(val);
        }
    });

    // UrlCode: Make sure not to override any values set by the user
    $('#ArticleUrlCode').find('span').text($('#ArticleUrlCode').find('input').val());
    $("#Form_ArticleUrlCode").focus(function () {
        $('#Form_ArticleUrlCodeIsDefined').val('1')
    });
    $('#ArticleUrlCode input, #ArticleUrlCode a.Save').hide();

    // UrlCode: Reveal input when "change" button is clicked
    $('#ArticleUrlCode a, #ArticleUrlCode span').click(function () {
        $('#ArticleUrlCode').find('input,span,a').toggle();
        $('#ArticleUrlCode').find('span').text($('#ArticleUrlCode').find('input').val());
        $('#ArticleUrlCode').find('input').focus();

        return false;
    });
});