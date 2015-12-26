jQuery(document).ready(function ($) {
    // UrlCode: Map plain text category to url code
    $("#Form_Name").keyup(function (event) {
        if ($('#Form_UrlCodeIsDefined').val() == '0') {
            $('#UrlCode').show();
            var val = $(this).val().replace(/[ \/\\&.?;,<>'"]+/g, '-');
            val = val.replace(/\-+/g, '-').toLowerCase();
            $("#Form_UrlCode").val(val);
            $("#UrlCode").find("span").text(val);
        }
    });

    // UrlCode: Make sure not to override any values set by the user
    $('#UrlCode').find('span').text($('#UrlCode').find('input').val());
    $("#Form_UrlCode").focus(function () {
        $('#Form_UrlCodeIsDefined').val('1')
    });
    $('#UrlCode input, #UrlCode a.Save').hide();

    // UrlCode: Reveal input when "change" button is clicked
    $('#UrlCode a, #UrlCode span').click(function () {
        $('#UrlCode').find('input,span,a').toggle();
        $('#UrlCode').find('span').text($('#UrlCode').find('input').val());
        $('#UrlCode').find('input').focus();
        return false;
    });
});