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
        try {
            loadSigningPlugin('et');
            var cert = new IdCardPluginHandler('et').getCertificate();
        } catch (e) {
            // TODO diplay errors to user
            console.log(e);
        }

        $.post(Config.prepareUrl, {
            certHex: cert.cert,
            certId: cert.id
        }, function (data) {
            console.log(data);
        })
    })
});

var Config = {
    prepareUrl: ""
};