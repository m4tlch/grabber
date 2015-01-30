(function ($) {
    $.fancybox.helpers.media.defaults.remote = {
        matcher : /(http[s]?:.*)/i,
        params  : {
            autostart   : 1,
            wmode       : 'opaque'
        },
        type : 'iframe',
        url  : '/sites/all/libraries/jwplayer/jwplayer.swf?file=$1'
    };

});
