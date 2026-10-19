jQuery(function ($) {
    var frame;
    $('#flaneur-tutorial-select').on('click', function () {
        if (!frame) {
            frame = wp.media({
                title: 'Selecionar vídeo do tutorial',
                button: { text: 'Usar este vídeo' },
                library: { type: 'video' },
                multiple: false
            });
            frame.on('select', function () {
                var video = frame.state().get('selection').first().toJSON();
                $('#flaneur-tutorial-video-url').val(video.url).trigger('change');
            });
        }
        frame.open();
    });
    $('#flaneur-tutorial-clear').on('click', function () {
        $('#flaneur-tutorial-video-url').val('').trigger('change');
    });
});
