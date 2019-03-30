String.prototype.escapeHTML = function () {
    return (
        this.replace(/>/g, '&gt;').replace(/</g, '&lt;').replace(/"/g, '&quot;')
    );
};


/* Global Vars */
var $version = '0.5.0';
var started = 0;

var cols = {},
    messageIsOpen = false;

$(function () {

    startTime(1);

    $('input').attr('autocomplete', 'off');

    // Bind the event.
    $(window).hashchange(function () {

        var hash = location.hash.replace(/^#/, '');
        if (hash === '') {
            $('.trigger-message-close').trigger('click');
        }

    });

    /* ------- Init Perfect Scrollbar ------- */
    var $ps = $('#perfectScroll');
    if ($ps.length) {
        new PerfectScrollbar('#perfectScroll');
    }
    var $psClass = $('.perfectScroll');
    if ($psClass.length) {
        new PerfectScrollbar('.perfectScroll');
    }
    new PerfectScrollbar('#pScrollerMenu');


    initModal();

    /* ------- Init tooltips ------- */
    var $tooltips = $('.tt');
    if ($tooltips.length) {
        initTooltips('.tt');
    }

    /* ------- Init FullCalendar ------- */
    var $calendar = $('.calendar');
    if ($calendar.length) {
        initCalendar('.calendar');
    }

    // Trigger the event (useful on page load).
    $(window).hashchange();

    var $curMenu = $('#' + GetNav);
    if (!$curMenu.is(':visible')) {
        $curMenu.parent().prev('a').trigger('click');
    }
    $curMenu.addClass('active');

    initOverlays();

    /* Init the Hashwatcher */
    detectHash();


    $('.search-box input').on('focus', function () {
        if ($(window).width() <= 1360) {
            cols.hideMessage();
        }
    });

});

/* Toggle Treview */
$(document).on('click', '.toggleTree', function (e) {
    e.preventDefault();

    var $items = $(this).next('.treeMenu');
    var $icon = $(this).children('i');
    if ($items.is(':visible')) {
        $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
    } else {
        $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
    }
    $items.toggle(200);

});

/* Click on any Listitem */
$(document).on('click', '.clickable', function (e) {

    e.preventDefault();
    if (!$(this).hasClass('innerMessage')) {
        window.location.hash = $(this).attr('data-hash');
    }

    if ($(this).hasClass('search-box')) {
        $('.js-search-message').trigger('click');
    }

    var $uId = Math.floor((Math.random() * 10000) + 1);

    $('body').append('<div class="messageFly" id="' + $uId + '"></div>');

    var $message = $('#' + $uId);
    $message.show();

    loadDetails($(this), $uId);

    if (messageIsOpen) {
        if (!$(this).hasClass('innerMessage')) {
            cols.hideMessage();
        }
        setTimeout(function () {
            cols.showMessage();
        }, 300);
    } else {
        cols.showMessage();
    }
    cols.showOverlay();

});

$(document).on('click', '#js-add-rate', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var $url = $btn.attr('data-url');
    var $table = $('#js-result-table');

    var html = 'Would you like to add another Ratetype?<br />\n<div class="row">\n    <div class="col-10">\n        <input placeholder="Rate Name (e.g. Best available Rate)" value="" type="text" id="rate-name"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-10">\n        <input placeholder="Rate Name Short (e.g. BAR)" value="" type="text" id="rate-name-short"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-8">\n        <input placeholder="Discount from BaseRate" value="" type="text" id="rate-dc-amount"/>\n    </div>\n    <div class="col-2">\n        <select id="rate-dc-type" style="margin-top:30%;">\n            <option value="p">&percnt;</option>\n            <option value="e">&euro;</option>\n        </select>\n    </div>\n</div>\n<div class="row">\n    <div class="col-10">\n        <input placeholder="Minimum Stay" value="" type="text" id="rate-min-stay"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-10">\n        <input placeholder="Days in Advance" value="" type="text" id="rate-pre-days"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-1">\n        <input style="position:relative;top:10px;" type="checkbox" id="rate-is-base">\n    </div>\n    <div class="col-9">\n        <span style="position:relative;top:10px;">is Baserate<br />(Discounts will be applied from this rate)</span>\n    </div>\n</div>';

    x0p({
        title: 'Add Ratetype',
        text: html,
        html: true,
        height:'70%',
        maxHeight: '550px',
        maxWidth: '500px',
        animationType: 'fadeIn',
        buttons: [
            {
                type: 'ok',
                text: 'Save',
                showLoading: true
            },
            {
                type: 'error',
                text: 'Cancel',
                showLoading: false
            }
        ]
    }).then(function (data) {
        if (data.button === 'ok') {

            var name = $.trim($('#rate-name').val());
            var nameShort = $.trim($('#rate-name-short').val());
            var isBase = $('#rate-is-base:checked').val() ? 'yes' : 'no';
            var dcAmount = $.trim($('#rate-dc-amount').val());
            var minStay = $.trim($('#rate-min-stay').val());
            var preDays = $.trim($('#rate-pre-days').val());
            var dcType = $.trim($('#rate-dc-type').val());

            /* Ajax Call */
            $.post($url, {
                name: name,
                nameShort: nameShort,
                isBase: isBase,
                dcAmount: dcAmount,
                dcType: dcType,
                minStay: minStay,
                preDays: preDays
            })
                .done(function (data) {
                    if (data.result === 'success') {

                        $table.append('' +
                            '<div class="row table-font" style="border-bottom:1px solid #ccc;" id="row_'+data.extra.type+'_'+data.extra.id+'">\n    ' +
                            '   <div class="col-2" id="nameShort_'+data.extra.type+'_'+data.extra.id+'">'+data.extra.nameShort+'</div>\n    ' +
                            '   <div class="col-4" id="name_'+data.extra.type+'_'+data.extra.id+'">'+data.extra.name+'</div>\n    ' +
                            '   <div class="col-1" id="minStay_'+data.extra.type+'_'+data.extra.id+'">'+data.extra.minStay+'</div>\n    ' +
                            '   <div class="col-1" id="daysAdvance_'+data.extra.type+'_'+data.extra.id+'">'+data.extra.preDays+'</div>\n    ' +
                            '   <div class="col-1">'+data.extra.isBase+'</div>\n    ' +
                            '   <div class="col-1"><span class="badge badge-success" id="status_'+data.extra.type+'_'+data.extra.id+'">active</span></div>\n    ' +
                            '   <div class="col-1">\n        ' +
                            '       <span id="dc_amount_'+data.extra.type+'_'+data.extra.id+'">'+data.extra.dcAmount+'</span><span id="dc_type_'+data.extra.type+'_'+data.extra.id+'">'+data.extra.dcType+'</span>\n    ' +
                            '   </div>\n    ' +
                            '   <div class="col-1">\n        ' +
                            '       <a href="#" class="btn btn-success btn-sm rounded-0 tt clickable" title="View Details"' +
                            '           data-url="'+data.extra.link+'"' +
                            '           data-hash="details-ratetype-'+data.extra.id+'"' +
                            '           data-trigger="'+data.extra.id+'"' +
                            '       >\n            ' +
                            '           <i class="fa fa-info-circle"></i> Details\n        ' +
                            '       </a>\n    ' +
                            '   </div>\n' +
                            '</div>');

                        x0p('Success',
                            data.extra.name + ' added',
                            'ok', false);
                    } else {
                        x0p('Error',
                            data.message,
                            'error', false);
                    }
                })
                .fail(function () {
                    openNoty('error', 'Ajax Error');
                })
            ;
        }
    });
});

$(document).on('click', '#js-add-room', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var $url = $btn.attr('data-url');
    var $table = $('#js-result-table');

    var html = 'Would you like to add another Roomtype?<br />\n<div class="row">\n    <div class="col-10">\n        <input placeholder="Room Name (e.g. Standard King)" value="" type="text" id="room-name"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-10">\n        <input placeholder="Room Name Short (e.g. STDK)" value="" type="text" id="room-name-short"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-10">\n        <input placeholder="Max Occupancy (e.g. 2)" value="" type="text" id="room-occ"/>\n    </div>\n</div>';

    x0p({
        title: 'Add Roomtype',
        text: html,
        html: true,
        maxHeight: '400px',
        maxWidth: '500px',
        animationType: 'fadeIn',
        buttons: [
            {
                type: 'ok',
                text: 'Save',
                showLoading: true
            },
            {
                type: 'error',
                text: 'Cancel',
                showLoading: false
            }
        ]
    }).then(function (data) {
        if (data.button === 'ok') {

            var name = $.trim($('#room-name').val());
            var nameShort = $.trim($('#room-name-short').val());
            var occ = $.trim($('#room-occ').val());


            /* Ajax Call */
            $.post($url, {
                name: name,
                nameShort: nameShort,
                occ: occ
            })
                .done(function (data) {
                    if (data.result === 'success') {

                        $table.append('' +
                            '<div class="row table-font" style="border-bottom:1px solid #ccc;" id="row_'+data.extra.type+'_'+data.extra.id+'">\n    ' +
                            '   <div class="col-2" id="nameShort_'+data.extra.type+'_'+data.extra.id+'">'+data.extra.nameShort+'</div>\n    ' +
                            '   <div class="col-6" id="name_'+data.extra.type+'_'+data.extra.id+'">'+data.extra.name+'</div>\n    ' +
                            '   <div class="col-1" id="maxOcc_'+data.extra.type+'_'+data.extra.id+'">'+data.extra.maxOcc+'</div>\n    ' +
                            '   <div class="col-1"><span class="badge badge-success" id="status_'+data.extra.type+'_'+data.extra.id+'">active</span></div>\n    ' +
                            '   <div class="col-1">\n        ' +
                            '       <a href="#" class="btn btn-success btn-sm rounded-0 tt clickable" title="View Details"' +
                            '           data-url="'+data.extra.link+'"' +
                            '           data-hash="details-roomtype-'+data.extra.id+'"' +
                            '           data-trigger="'+data.extra.id+'"' +
                            '       >\n            ' +
                            '           <i class="fa fa-info-circle"></i> Details\n        ' +
                            '       </a>\n    ' +
                            '   </div>\n' +
                            '</div>');

                        x0p('Success',
                            data.extra.name + ' added',
                            'ok', false);
                    } else {
                        x0p('Error',
                            data.message,
                            'error', false);
                    }
                })
                .fail(function () {
                    openNoty('error', 'Ajax Error');
                })
            ;
        }
    });
});

$(document).on('click', '#js-update-room', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var url = $btn.attr('data-url');
    var id = $btn.attr('data-id');

    var $name = $('#room-name');
    var $nameShort = $('#room-name-short');
    var $maxOcc = $('#room-occ');

    if($.trim($name.val()) === ''){
        openNoty('error','Name cannot be empty');
        return false;
    }

    if($.trim($nameShort.val()) === ''){
        openNoty('error','Name (Short) cannot be empty');
        return false;
    }

    if($.trim($maxOcc.val()) === ''){
        openNoty('error','Max. Occupancy cannot be empty');
        return false;
    }

    /* Ajax Call */
    $.post(url, {
        name: $name.val(),
        nameShort: $nameShort.val(),
        maxOcc: $maxOcc.val(),
        id: id
    })
        .done(function (data) {
            if (data.result === 'success') {

                closeDetails(function () {
                    var $nameRow = $('#name_' + data.extra.type + '_' + data.extra.id);
                    var $nameShortRow = $('#nameShort_' + data.extra.type + '_' + data.extra.id);
                    var $maxOcc = $('#maxOcc_' + data.extra.type + '_' + data.extra.id);

                    $nameRow.html(data.extra.name);
                    $nameShortRow.html(data.extra.nameShort);
                    $maxOcc.html(data.extra.maxOcc);

                });

                x0p('Success',
                    $nameShort.val() + ' updated',
                    'ok', false);
            } else {
                x0p('Error',
                    data.message,
                    'error', false);
            }
        })
        .fail(function () {
            openNoty('error', 'Ajax Error');
        })
    ;
});

$(document).on('click', '#js-add-competitor', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var $url = $btn.attr('data-url');
    var $table = $('#js-result-table');

    var html = 'Would you like to add another Competitor?<br />\n<div class="row">\n    <div class="col-10">\n        <input placeholder="Competitor Name" value="" type="text" id="competitor-name"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-10">\n        <input placeholder="Booking.com Subsite" value="" type="text" id="competitor-link"/>\n    </div>\n</div>';

    x0p({
        title: 'Add Competitor',
        text: html,
        html: true,
        maxHeight: '400px',
        maxWidth: '500px',
        animationType: 'fadeIn',
        buttons: [
            {
                type: 'ok',
                text: 'Save',
                showLoading: true
            },
            {
                type: 'error',
                text: 'Cancel',
                showLoading: false
            }
        ]
    }).then(function (data) {
        if (data.button === 'ok') {

            var name = $.trim($('#competitor-name').val());
            var link = $.trim($('#competitor-link').val());


            /* Ajax Call */
            $.post($url, {
                name: name,
                link: link
            })
                .done(function (data) {
                    if (data.result === 'success') {

                        $table.append('' +
                            '<div class="row table-font" style="border-bottom:1px solid #ccc;" id="row_'+data.extra.type+'_'+data.extra.id+'">\n    ' +
                            '   <div class="col-3" id="name_'+data.extra.type+'_'+data.extra.id+'">'+data.extra.name+'</div>\n    ' +
                            '   <div class="col-7" id="link_'+data.extra.type+'_'+data.extra.id+'">'+data.extra.sublink+'</div>\n    ' +
                            '   <div class="col-1"><span class="badge badge-success" id="status_'+data.extra.type+'_'+data.extra.id+'">active</span></div>\n    ' +
                            '   <div class="col-1">\n        ' +
                            '       <a href="#" class="btn btn-success btn-sm rounded-0 tt clickable" title="View Details"' +
                            '           data-url="'+data.extra.link+'"' +
                            '           data-hash="details-competitor-'+data.extra.id+'"' +
                            '           data-trigger="'+data.extra.id+'"' +
                            '       >\n            ' +
                            '           <i class="fa fa-info-circle"></i> Details\n        ' +
                            '       </a>\n    ' +
                            '   </div>\n' +
                            '</div>');

                        x0p('Success',
                            data.extra.name + ' added',
                            'ok', false);
                    } else {
                        x0p('Error',
                            data.message,
                            'error', false);
                    }
                })
                .fail(function () {
                    openNoty('error', 'Ajax Error');
                })
            ;
        }
    });
});

$(document).on('click', '#js-update-competitor', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var url = $btn.attr('data-url');
    var id = $btn.attr('data-id');

    var $name = $('#competitor-name');
    var $link = $('#competitor-link');

    if($.trim($name.val()) === ''){
        openNoty('error','Name cannot be empty');
        return false;
    }

    if($.trim($link.val()) === ''){
        openNoty('error','Link cannot be empty');
        return false;
    }

    /* Ajax Call */
    $.post(url, {
        name: $name.val(),
        link: $link.val(),
        id: id
    })
        .done(function (data) {
            if (data.result === 'success') {

                closeDetails(function () {
                    var $nameRow = $('#name_' + data.extra.type + '_' + data.extra.id);
                    var $linkRow = $('#link_' + data.extra.type + '_' + data.extra.id);

                    $nameRow.html(data.extra.name);
                    $linkRow.html(data.extra.link);

                });

                x0p('Success',
                    $name_.val() + ' updated',
                    'ok', false);
            } else {
                x0p('Error',
                    data.message,
                    'error', false);
            }
        })
        .fail(function () {
            openNoty('error', 'Ajax Error');
        })
    ;
});

$(document).on('click','#js-check-competitors',function (e) {
    e.preventDefault();

    var $btn = $(this);
    var date = $('#js-competitor-date');

    if($btn.hasClass('disabled')){
        return false;
    }

    if($.trim(date.val()) === '' || date.val() === null){
        openNoty('error', 'Date cannot be empty');
        return false;
    }

    $btn.addClass('disabled').html('<i class="fa fa-spin fa-spinner"></i>');

    /**
     * Gather all Competitor Rows
     */
    $('.competitor-row').each(function(i,v){

        var id = $(v).attr('data-id');
        var url = $(v).attr('data-url');
        started += 1;

        $('#room_'+id).html('<i class="fa fa-spin fa-spinner"></i> <span class="text-success">Started Query...</span>');

        $.post(url, {
            id: id,
            date: date.val()
        })
            .done(function (data) {
                if (data.result === 'success') {
                    $('#room_'+id).html(data.extra.room);
                    $('#incl_'+id).html(data.extra.incl);
                    $('#pax_'+id).html(data.extra.pax);
                    $('#price_'+id).html(data.extra.price);
                } else {
                    $('#room_'+id).html('<i class="text-danger">'+data.message+'</i>');
                }
                started -= 1;
                if(started === 0){
                    $btn.removeClass('disabled').html('<i class="fa fa-refresh"></i>')
                }
            })
            .fail(function () {
                started -= 1;
                if(started === 0){
                    $btn.removeClass('disabled').html('<i class="fa fa-refresh"></i>')
                }
                openNoty('error', 'Ajax Error');
            })
        ;

    });

});

$(document).on('click', '#js-update-rate', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var url = $btn.attr('data-url');
    var id = $btn.attr('data-id');

    var $name = $('#rate-name');
    var $nameShort = $('#rate-name-short');
    var $isBase = $('#rate-base');
    var $minStay = $('#rate-min-stay');
    var $daysAdvance = $('#rate-days-advance');
    var $dcAmount = $('#rate-dc-amount');
    var $dcType = $('#rate-dc-type');

    if($.trim($name.val()) === ''){
        openNoty('error','Name cannot be empty');
        return false;
    }

    if($.trim($nameShort.val()) === ''){
        openNoty('error','Name (Short) cannot be empty');
        return false;
    }

    /* Ajax Call */
    $.post(url, {
        name: $name.val(),
        nameShort: $nameShort.val(),
        minStay: $minStay.val(),
        daysAdvance: $daysAdvance.val(),
        isBase: $isBase.val(),
        id: id,
        dcAmount: $dcAmount.val(),
        dcType: $dcType.val()
    })
        .done(function (data) {
            if (data.result === 'success') {

                closeDetails(function () {
                    var $nameRow = $('#name_' + data.extra.type + '_' + data.extra.id);
                    var $nameShortRow = $('#nameShort_' + data.extra.type + '_' + data.extra.id);
                    var $minStayRow = $('#minStay_' + data.extra.type + '_' + data.extra.id);
                    var $daysAdvanceRow = $('#daysAdvance_' + data.extra.type + '_' + data.extra.id);
                    var $isBaseRow = $('#base_' + data.extra.type + '_' + data.extra.id).parent();
                    var $dcAmountRow = $('#dc_amount_' + data.extra.type + '_' + data.extra.id);
                    var $dcTypeRow = $('#dc_type_' + data.extra.type + '_' + data.extra.id);

                    $nameRow.html(data.extra.name);
                    $nameShortRow.html(data.extra.nameShort);
                    $minStayRow.html(data.extra.minStay);
                    $daysAdvanceRow.html(data.extra.daysAdvance);
                    $isBaseRow.html(data.extra.isBase);
                    $dcAmountRow.html(data.extra.dcAmount);
                    $dcTypeRow.html(data.extra.dcType);

                });

                x0p('Success',
                    $nameShort.val() + ' updated',
                    'ok', false);
            } else {
                x0p('Error',
                    data.message,
                    'error', false);
            }
        })
        .fail(function () {
            openNoty('error', 'Ajax Error');
        })
    ;
});

$(document).on('click', '#js-remove-entity', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var url = $btn.attr('data-url');
    var name = $btn.attr('data-name');
    var type = $btn.attr('data-type');
    var id = $btn.attr('data-id');

    x0p({
        title: '<span style="color:#dc1327;">Attention</span>',
        text: 'Would you like to remove <strong>' + name + '</strong>?<br /><br /><span style="color:#dc1327;">This action cannot be undone!</span>',
        html: true,
        maxHeight: '200px',
        maxWidth: '300px',
        animationType: 'fadeIn',
        buttons: [
            {
                type: 'ok',
                text: 'Yes, remove!',
                showLoading: true
            },
            {
                type: 'error',
                text: 'Cancel',
                showLoading: false
            }
        ]
    }).then(function (data) {
        if (data.button === 'ok') {

            /* Ajax Call */
            $.post(url, {
                type: type,
                id: id
            })
                .done(function (data) {
                    if (data.result === 'success') {

                        /**
                         * If there is a row with the id, remove it as well
                         */
                        closeDetails(function () {
                            var $row = $('#row_' + type + '_' + id);
                            if ($row.length) {
                                $row.remove();
                            }
                        });


                        x0p('Success',
                            name + ' removed',
                            'ok', false);
                    } else {
                        x0p('Error',
                            data.message,
                            'error', false);
                    }
                })
                .fail(function () {
                    openNoty('error', 'Ajax Error');
                })
            ;
        }
    });
});

$(document).on('click', '.js-toggle-active', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var $html = $btn.html();
    var url = $btn.attr('data-url');
    var to_set = $btn.attr('data-set');
    var type = $btn.attr('data-type');
    var id = $btn.attr('data-id');

    if($btn.hasClass('disabled')){
        return false;
    }

    $btn.addClass('disabled').html('<i class="fa fa-spin fa-spinner"></i>');

    $.post(url, {
        type: type,
        id: id,
        to_set: to_set
    })
        .done(function (data) {
            $btn.removeClass('disabled').html($html);
            if (data.result === 'success') {

                openNoty(data.result, data.message);

                /**
                 * If there is a badge with a label, update it
                 */
                var $status = $('#status_' + type + '_' + id);
                if($status.length){
                    $status.removeClass('badge-danger').removeClass('badge-success').addClass('badge-'+data.extra.b_class).html(data.extra.b_html);
                }

                /**
                 * Toggle the Button
                 */
                $btn.attr('data-set',data.extra.to_set_new).html(data.extra.html_new);

            } else {
                x0p('Error',
                    data.message,
                    'error', false);
            }
        })
        .fail(function () {
            openNoty('error', 'Ajax Error');
        })
    ;

});

$(document).on('change','.inputfile',function(e){

    var $btn = $(this);
    var $label = $('label[for="'+$btn.attr('id')+'"]');
    uploadReport($btn, $label.html());

});

function uploadReport($btn, html){

    var url = $btn.attr('data-url');
    var form = $('#form_'+$btn.attr('data-report-type'))[0];
    var formData = new FormData(form);
    var $label = $('label[for="'+$btn.attr('id')+'"]');

    if($label.hasClass('disabled')){
        return false;
    }

    $label.addClass('disabled').html('<i class="fa fa-spin fa-spinner"></i> <span>Uploading...</span>');

    $.ajax({
        url: url,
        type: "POST",
        data : formData,
        processData: false,
        contentType: false,
        beforeSend: function() {

        },
        success: function(data){
            $label.removeClass('disabled').html(html);
            openNoty('success', 'Upload succeeded');


        },
        error: function(xhr, ajaxOptions, thrownError) {
            openNoty('error', 'Upload failed');
            $label.removeClass('disabled').html(html);
            console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
    });



}

/* Noty Function
 * type = {alert, success, error, warning, information}
 */
function openNoty(type, text) {
    new Noty({
        layout: 'topRight',
        theme: 'mint',
        text: text,
        type: type,
        timeout: 3000,
        buttons: false
    }).show();
}

function loadDetails(trigger, id) {

    /* DIV */
    var $message = $('#' + id);
    $message.html('<i class="fa fa-spin fa-spinner fa-4x"></i>');

    /* Ajax Call */
    $.post(trigger.attr('data-url')).done(function (data) {
        $message.html(data);
        var sHash = window.location.hash.split('load-');
        if (sHash.length) {
            $('[data-device="' + sHash[1] + '"]').trigger('click');
        }

        $('#menuToggle > input').trigger('click');
        initOverlays();
        initModal();
        initScrollbar('.subScroll');
        initTooltips('.tt');

        $('.tooltipster-show').removeClass('tooltipster-show');
    });
}

function initOverlays() {
    cols.showOverlay = function () {
        $('body').addClass('show-main-overlay');
    };
    cols.hideOverlay = function () {
        $('body').removeClass('show-main-overlay');
    };


    cols.showMessage = function () {
        $('body').addClass('show-message');
        messageIsOpen = true;
    };
    cols.hideMessage = function () {
        $('body').removeClass('show-message');
        messageIsOpen = false;
    };


    cols.showSidebar = function () {
        $('body').addClass('show-sidebar');
    };
    cols.hideSidebar = function () {
        $('body').removeClass('show-sidebar');
    };


    // Show sidebar when trigger is clicked

    $('.trigger-toggle-sidebar').on('click', function () {
        cols.showSidebar();
        cols.showOverlay();
    });


    $('.trigger-message-close').on('click', function () {

        var $mb = $(this).closest('.messageFly');

        if ($('.messageFly').length <= 1) {
            cols.hideMessage();
            $mb.remove();
            history.pushState("", document.title, window.location.pathname);
        } else {
            $mb.animate({left: '9999px'}, function () {
                $mb.remove();
            });
        }

    });

    // When you click the overlay, close everything
    $('#main > .overlay').on('click', function () {
        cols.hideOverlay();
        cols.hideMessage();
        cols.hideSidebar();
    });
}

function closeDetails(callback) {
    if (typeof callback === "function") {
        $('.trigger-message-close').trigger('click', callback());
    } else {
        $('.trigger-message-close').trigger('click');
    }
}

function detectHash() {

    var hash = window.location.hash;
    if (!hash.length) {
        return false;
    }

    $("[data-hash=" + hash.replace('#', '') + "]").trigger('click');

}

/* Display Clock upon start */
function startTime(autostart) {
    var today = new Date();
    var h = today.getHours();
    var m = today.getMinutes();
    var s = today.getSeconds();
    m = checkTime(m);
    s = checkTime(s);
    $('#startClock').html("@" + h + ":" + m + ":" + s + " Uhr");
    var t = setTimeout(startTime, 1000);
    /* Update Totals and minutes every minute */
}

function checkTime(i) {
    if (i < 10) {
        i = "0" + i
    }
    return i;
}

function initModal() {

    [].slice.call($('.md-trigger')).forEach(function (el, i) {

        var $modal = $('#' + el.getAttribute('data-modal'));
        el.addEventListener('click', function (ev) {
            $modal.addClass('md-show');
        });
    });

}

function initTooltips(selector) {

    $(selector).tooltipster({
        theme: 'tooltipster-punk',
        contentAsHTML: true, debug: false
    });
}

function initScrollbar(selector) {
    if ($(selector).length) {
        new PerfectScrollbar(selector, {
            suppressScrollX: true
        });
    }

}