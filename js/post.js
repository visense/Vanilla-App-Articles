jQuery(document).ready(function ($) {
    // ArticleUrlCode: Map plain text category to url code
    $("#Form_Name").keyup(function (event) {
        if ($('#Form_ArticleUrlCodeDefined').val() == '0') {
            $('#ArticleUrlCode').show();
            var val = $(this).val().replace(/[ \/\\&.?;,<>'"]+/g, '-');
            val = val.replace(/\-+/g, '-').toLowerCase();
            $("#Form_ArticleUrlCode").val(val);
            $("#ArticleUrlCode").find("span").text(val);
        }
    });

    // ArticleUrlCode: Make sure not to override any values set by the user
    $('#ArticleUrlCode').find('span').text($('#ArticleUrlCode').find('input').val());
    $("#Form_ArticleUrlCode").focus(function () {
        $('#Form_ArticleUrlCodeDefined').val('1')
    });
    $('#ArticleUrlCode input, #ArticleUrlCode a.Save').hide();

    // ArticleUrlCode: Reveal input when "change" button is clicked
    $('#ArticleUrlCode a, #ArticleUrlCode span').click(function () {
        $('#ArticleUrlCode').find('input,span,a').toggle();
        $('#ArticleUrlCode').find('span').text($('#ArticleUrlCode').find('input').val());
        $('#ArticleUrlCode').find('input').focus();

        return false;
    });

    // Enable multicomplete on article author name text box
    $('#Form_ArticleAuthorName').livequery(function () {
        $(this).autocomplete(
            gdn.url('/dashboard/user/autocomplete/'),
            {
                minChars: 1,
                multiple: false,
                scrollHeight: 220,
                selectFirst: true
            }
        );
    });

    // Thumbnail upload
    function createCustomElement(ElementType, SetOptions) {
        var element = document.createElement(ElementType);

        for (var prop in SetOptions) {
            var propval = SetOptions[prop];
            element.setAttribute(prop, propval);
        }

        return element;
    }

    // A thumbnail exists, so hide the upload form
    var articleThumbnailUploadLabel = $('#ArticleThumbnailUpload').find('label').text();

    if ($('#ArticleThumbnailImage').length) {
        $('#ArticleThumbnailUpload').find('label').text('Thumbnail');
        $('#Form_ArticleThumbnail_New').hide();
    }

    var currentArticleID = $('#Form_ArticleID').val();

    if ($('#Form_ArticleThumbnail_New').length) {
        $('#Form_ArticleThumbnail_New').ajaxfileupload({
            'action': gdn.url('/post/uploadarticlethumbnail?DeliveryMethod=JSON&DeliveryType=VIEW'),
            'params': {'ArticleID': currentArticleID},
            'onComplete': function (response) {
                console.log(response);
                // Reset the file upload field.
                $(this).wrap('<form>').closest('form').get(0).reset();
                $(this).unwrap();

                $(this).hide();

                var imagePath = gdn.definition('WebRoot') + '/uploads' + response.Path;

                // Show new image in form.
                $('#ArticleThumbnail')
                    .append('<div id="ArticleThumbnailImage"><img src="' + imagePath + '" alt="" /></div>' +
                        '<div id="ArticleThumbnailActions"><a id="DeleteArticleThumbnail" href="'
                        + gdn.url('/post/deletearticlethumbnail/' + response.ArticleThumbnailID)
                        + '?DeliveryMethod=JSON&DeliveryType=BOOL">Delete</a></div>');

                // Add new image to hidden form field to be passed to the controller.
                var ArticleThumbnailID = createCustomElement('input', {
                    'type': 'hidden',
                    'name': 'ArticleThumbnailID',
                    'value': response.ArticleThumbnailID
                });
                $('#DiscussionForm').find('form').append(ArticleThumbnailID);

                $('.TinyProgress').remove();
            },
            'onStart': function () {
                $(this).after('<span class="TinyProgress">&#160;</span>');
            },
            'onCancel': function () {
                //console.log('no file selected');
            }
        });
    }

    // Handle action of deleting article thumbnail
    $('#DeleteArticleThumbnail').popup({
        confirm: true,
        confirmHeading: gdn.definition('ConfirmDeleteImageHeading', 'Delete Thumbnail'),
        confirmText: gdn.definition('ConfirmDeleteImageText', 'Are you sure you want to delete this thumbnail?'),
        followConfirm: false,
        deliveryType: 'BOOL',
        afterConfirm: function (json, sender) {
            var linkUrl = jQuery(sender).attr('href').split('?')[0]; // Retrieve part of URL without query string.

            $('#ArticleThumbnail').empty();
            $('#ArticleThumbnailID').remove();
            $('#ArticleThumbnailUpload').find('label').text(articleThumbnailUploadLabel);
            $('#Form_ArticleThumbnail_New').show();
        }
    });
});