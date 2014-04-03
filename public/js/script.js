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

            $.post(Config.prepareUrl, {
                fileRealName: $('#fileRealName').val(),
                file: $('#fileInput').val(),
                certHex: cert.cert,
                certId: cert.id
            }, function (data) {
                if (data.success) {
                    try {
                        var signature = new IdCardPluginHandler('et').sign(cert.id, data.hash);

                        $.post(Config.finalizeUrl, {
                            signature: signature,
                            sessionId: data.sessionId,
                            signatureId: data.signatureId
                        }, function (data) {
                            if (data.success) {
                                Config.sessionId = data.sessionId;

                                $('#signPanelBody').html('<p>Well done! Now go and download you\'re file!</p>');
                                $('#downloadDiv').removeClass('hidden');
                                $('#signDiv').addClass('hidden');
                            } else {
                                Error.show(data.error);
                            }
                        });
                    } catch (e) {
                        console.log(e);
                        Error.show(e.message);
                    }
                } else {
                    Error.show(data.error);
                }
            });
        } catch (e) {
            console.log(e);
            Error.show(e.message)
        }
    });

    $('#download').click(function () {
        window.location.href = Config.downloadUrl
            + '?sessionId=' + Config.sessionId
            + '&file=' + $('#fileInput').val()
            + '&fileRealName=' + $('#fileRealName').val();

//        $.post(Config.downloadUrl, {
//            sessionId: Config.sessionId,
//            file: $('#fileInput').val(),
//            fileRealName: $('#fileRealName').val()
//        }, function (data, textStatus, jqXHR) {
//            console.log(jqXHR);
//        })
    });
});

var Config = {
    prepareUrl: "",
    finalizeUrl: "",
    downloadUrl: "",
    sessionId: ""
};

var Error = {
    show: function (message) {
        if (message === '') {
            return;
        }
        var container = $('#error');
        container.html("");
        container.html(message);
        container.removeClass('hidden');

        setTimeout(function () {
            container.slideUp(750, function () {
                container.addClass('hidden');
            });
        }, 5000);
    }
};