$(document).ready(function () {
    $('input:file').change(function () {
        if ($(this).val() != '') {
            $(this).parent().parent().parent().parent().removeClass('panel-default').addClass('panel-success');
        }
    });

    $('#upload').click(function () {
        if ($('input:file').val() != '') {
            $(this).parent().parent().find('form').submit();
        }
    });

    $('#sign').click(function() {
        loadSigningPlugin('et');
        var handler = digidocPluginHandler('et');
        handler.getCertificate();
    })
});