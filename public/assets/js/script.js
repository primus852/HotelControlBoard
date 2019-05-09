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

$(document).on('click', '#js-add-user', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var $url = $btn.attr('data-url');
    var $table = $('#js-result-table');

    var html = 'Would you like to add another User?<br />\n<div class="row">\n    <div class="col-12">\n        <input placeholder="Full Name" value="" type="text" id="user-name"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-12">\n        <input placeholder="Short Name" value="" type="text" id="user-username"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-12">\n        <input placeholder="Password" value="" type="password" id="user-password"/>\n    </div>\n</div>\n<br />\n<div class="row">\n    <div class="col-12">\n        <select id="user-department">\n            <option value="1">ADMIN</option>\n            <option value="2">TENTEMPIÉ</option>\n            <option value="3">FO</option>\n            <option value="4">HSK</option>\n        </select>\n    </div>\n</div>\n<div class="row">\n    <div class="col-12">\n        <input placeholder="Holidays" value="" type="text" id="user-holidays"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-2">\n        <input style="position:relative;top:10px;" type="checkbox" id="user-admin">\n    </div>\n    <div class="col-10">\n        <span style="position:relative;top:10px;">HCB Admin</span>\n    </div>\n</div>\n<br />\n<div class="row">\n    <div class="col-2">\n        <input style="position:relative;top:10px;" type="checkbox" id="user-manager">\n    </div>\n    <div class="col-10">\n        <span style="position:relative;top:10px;">HCB Manager</span>\n    </div>\n</div>';

    x0p({
        title: 'Add User',
        text: html,
        html: true,
        height: '100%',
        maxHeight: '450px',
        maxWidth: '400px',
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

            var name = $.trim($('#user-name').val());
            var username = $.trim($('#user-username').val());
            var password = $.trim($('#user-password').val());
            var department = $.trim($('#user-department').val());
            var admin = $('#user-admin:checked').val() ? 'yes' : 'no';
            var manager = $('#user-manager:checked').val() ? 'yes' : 'no';
            var holidays = $.trim($('#user-holidays').val());


            /* Ajax Call */
            $.post($url, {
                name: name,
                username: username,
                password: password,
                department: department,
                isAdmin: admin,
                isManager: manager,
                holidays: holidays
            })
                .done(function (data) {
                    if (data.result === 'success') {

                        $table.append('' +
                            '<div class="row table-font" style="border-bottom:1px solid #ccc;" id="row_' + data.extra.type + '_' + data.extra.id + '">\n    ' +
                            '   <div class="col-3" id="name_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.name + '</div>\n    ' +
                            '   <div class="col-2" id="username_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.username + '</div>\n    ' +
                            '   <div class="col-3" id="department_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.department + '</div>\n    ' +
                            '   <div class="col-1" id="holidays_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.holidays + '</div>\n    ' +
                            '   <div class="col-2"><span class="badge badge-success" id="status_' + data.extra.type + '_' + data.extra.id + '">active</span></div>\n    ' +
                            '   <div class="col-1">\n        ' +
                            '       <a href="#" class="btn btn-success btn-sm rounded-0 tt clickable" title="View Details"' +
                            '           data-url="' + data.extra.link + '"' +
                            '           data-hash="details-user-' + data.extra.id + '"' +
                            '           data-trigger="' + data.extra.id + '"' +
                            '       >\n            ' +
                            '           <i class="fa fa-info-circle"></i> Details\n        ' +
                            '       </a>\n    ' +
                            '   </div>\n' +
                            '</div>');

                        x0p('Success',
                            data.extra.username + ' added',
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

$(document).on('click', '#js-add-rate', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var $url = $btn.attr('data-url');
    var $table = $('#js-result-table');

    var html = 'Would you like to add another Ratetype?<br />\n<div class="row">\n    <div class="col-12">\n        <input placeholder="Rate Name (e.g. Best available Rate)" value="" type="text" id="rate-name"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-12">\n        <input placeholder="Rate Name Short (e.g. BAR)" value="" type="text" id="rate-name-short"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-12">\n        <input placeholder="Note (e.g. non-ref.)" value="" type="text" id="rate-note"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-9">\n        <input placeholder="Discount from BaseRate" value="" type="text" id="rate-dc-amount"/>\n    </div>\n    <div class="col-3">\n        <select id="rate-dc-type" style="margin-top:21%;height:24px;">\n            <option value="p">&percnt;</option>\n            <option value="e">&euro;</option>\n        </select>\n    </div>\n</div>\n<div class="row">\n    <div class="col-6">\n        <input placeholder="fixed Rate Single" value="" type="text" id="rate-fixed-single"/>\n    </div>\n    <div class="col-6">\n        <input placeholder="fixed Rate Double" value="" type="text" id="rate-fixed-double"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-12">\n        <input placeholder="Minimum Stay" value="" type="text" id="rate-min-stay"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-6">\n        <input placeholder="min. Days in advance" value="" type="text" id="rate-pre-days"/>\n    </div>\n    <div class="col-6">\n        <input placeholder="max. Days in advance" value="" type="text" id="rate-max-pre"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-2" style="position:relative;top:10px;">Bookable</div>\n    <div class="col-1">\n        <input style="position:relative;top:10px;" type="checkbox" checked="checked" id="rate-days-mon">\n    </div>\n    <div class="col-1">\n        <input style="position:relative;top:10px;" type="checkbox" checked="checked" id="rate-days-tue">\n    </div>\n    <div class="col-1">\n        <input style="position:relative;top:10px;" type="checkbox" checked="checked" id="rate-days-wed">\n    </div>\n    <div class="col-1">\n        <input style="position:relative;top:10px;" type="checkbox" checked="checked" id="rate-days-thu">\n    </div>\n    <div class="col-1">\n        <input style="position:relative;top:10px;" type="checkbox" checked="checked" id="rate-days-fri">\n    </div>\n    <div class="col-1">\n        <input style="position:relative;top:10px;" checked="checked" type="checkbox" id="rate-days-sat">\n    </div>\n    <div class="col-1">\n        <input style="position:relative;top:10px;" type="checkbox" checked="checked" id="rate-days-sun">\n    </div>\n</div>\n<br />\n<div class="row">\n    <div class="col-2">&nbsp;</div>\n    <div class="col-1">Mon</div>\n    <div class="col-1">Tue</div>\n    <div class="col-1">Wed</div>\n    <div class="col-1">Thu</div>\n    <div class="col-1">Fri</div>\n    <div class="col-1">Sat</div>\n    <div class="col-1">Sun</div>\n</div>\n<br />\n<div class="row">\n    <div class="col-2">\n        <input style="position:relative;top:10px;" type="checkbox" id="rate-fairs">\n    </div>\n    <div class="col-10">\n        <span style="position:relative;top:10px;">allowed during fairs</span>\n    </div>\n</div>\n<br />\n<div class="row">\n    <div class="col-2">\n        <input style="position:relative;top:10px;" type="checkbox" id="rate-is-base">\n    </div>\n    <div class="col-10">\n        <span style="position:relative;top:10px;">is Baserate<br />(Discounts will be applied from this rate)</span>\n    </div>\n</div>';

    x0p({
        title: 'Add Ratetype',
        text: html,
        html: true,
        height: '100%',
        maxHeight: '650px',
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
            var note = $.trim($('#rate-note').val());
            var fairs = $('#rate-fairs:checked').val() ? 'yes' : 'no';
            var isBase = $('#rate-is-base:checked').val() ? 'yes' : 'no';
            var dcAmount = $.trim($('#rate-dc-amount').val());
            var minStay = $.trim($('#rate-min-stay').val());
            var preDays = $.trim($('#rate-pre-days').val());
            var maxPreDays = $.trim($('#rate-max-pre').val());
            var fixedSingle = $.trim($('#rate-fixed-single').val());
            var fixedDouble = $.trim($('#rate-fixed-double').val());
            var dcType = $.trim($('#rate-dc-type').val());
            var allowMon = $('#rate-days-mon:checked').val() ? 'yes' : 'no';
            var allowTue = $('#rate-days-tue:checked').val() ? 'yes' : 'no';
            var allowWed = $('#rate-days-wed:checked').val() ? 'yes' : 'no';
            var allowThu = $('#rate-days-thu:checked').val() ? 'yes' : 'no';
            var allowFri = $('#rate-days-fri:checked').val() ? 'yes' : 'no';
            var allowSat = $('#rate-days-sat:checked').val() ? 'yes' : 'no';
            var allowSun = $('#rate-days-sun:checked').val() ? 'yes' : 'no';

            /* Ajax Call */
            $.post($url, {
                name: name,
                nameShort: nameShort,
                note: note,
                isBase: isBase,
                allowMon: allowMon,
                allowTue: allowTue,
                allowWed: allowWed,
                allowThu: allowThu,
                allowFri: allowFri,
                allowSat: allowSat,
                allowSun: allowSun,
                fixedSingle: fixedSingle,
                fixedDouble: fixedDouble,
                fairs: fairs,
                dcAmount: dcAmount,
                dcType: dcType,
                minStay: minStay,
                preDays: preDays,
                maxPreDays: maxPreDays
            })
                .done(function (data) {
                    if (data.result === 'success') {

                        $table.append('' +
                            '<div class="row table-font" style="border-bottom:1px solid #ccc;" id="row_' + data.extra.type + '_' + data.extra.id + '">\n    ' +
                            '   <div class="col-1" id="nameShort_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.nameShort + '</div>\n    ' +
                            '   <div class="col-2" id="name_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.name + '</div>\n    ' +
                            '   <div class="col-1" id="note_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.note + '</div>\n    ' +
                            '   <div class="col-1" id="maxAdvance_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.maxAdvance + '</div>\n    ' +
                            '   <div class="col-1">' + data.extra.fairs + '</div>\n    ' +
                            '   <div class="col-1" id="minStay_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.minStay + '</div>\n    ' +
                            '   <div class="col-1" id="daysAdvance_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.preDays + '</div>\n    ' +
                            '   <div class="col-1">' + data.extra.isBase + '</div>\n    ' +
                            '   <div class="col-1"><span class="badge badge-success" id="status_' + data.extra.type + '_' + data.extra.id + '">active</span></div>\n    ' +
                            '   <div class="col-1" id="discount_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.discount + '</div>\n    ' +
                            '   <div class="col-1">\n        ' +
                            '       <a href="#" class="btn btn-success btn-sm rounded-0 tt clickable" title="View Details"' +
                            '           data-url="' + data.extra.link + '"' +
                            '           data-hash="details-ratetype-' + data.extra.id + '"' +
                            '           data-trigger="' + data.extra.id + '"' +
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

$(document).on('click', '#js-add-budget', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var $url = $btn.attr('data-url');
    var $table = $('#js-result-table');

    var html = 'Would you like to add another Budget?<br />\n<div class="row">\n    <div class="col-5">\n        <select id="budget-month" style="height: 50px;">\n            <option value="1">January</option>\n            <option value="2">February</option>\n            <option value="3">March</option>\n            <option value="4">April</option>\n            <option value="5">May</option>\n            <option value="6">June</option>\n            <option value="7">July</option>\n            <option value="8">August</option>\n            <option value="9">September</option>\n            <option value="10">October</option>\n            <option value="11">November</option>\n            <option value="12">December</option>\n        </select>\n    </div>\n    <div class="col-5">\n        <input placeholder="Year" value="" type="text" id="budget-year"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-10">\n        <input placeholder="Accomodation &euro;" value="" type="text" id="budget-acc"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-10">\n        <input placeholder="Other Revenue &euro;" value="" type="text" id="budget-other"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-10">\n        <input placeholder="Roomnights" value="" type="text" id="budget-nights"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-10">\n        <input placeholder="Occupancy &percnt;" value="" type="text" id="budget-occ"/>\n    </div>\n</div>\n<div class="row">\n    <div class="col-10">\n        <input placeholder="Rate &euro;" value="" type="text" id="budget-rate"/>\n    </div>\n</div>';

    x0p({
        title: 'Add Budget',
        text: html,
        html: true,
        height: '70%',
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

            var month = $.trim($('#budget-month').val());
            var year = $.trim($('#budget-year').val());
            var acc = $.trim($('#budget-acc').val());
            var other = $.trim($('#budget-other').val());
            var nights = $.trim($('#budget-nights').val());
            var occ = $.trim($('#budget-occ').val());
            var rate = $.trim($('#budget-rate').val());


            /* Ajax Call */
            $.post($url, {
                month: month,
                year: year,
                acc: acc,
                other: other,
                nights: nights,
                occ: occ,
                rate: rate
            })
                .done(function (data) {
                    if (data.result === 'success') {

                        $table.append('' +
                            '<div class="row table-font" style="border-bottom:1px solid #ccc;" id="row_' + data.extra.type + '_' + data.extra.id + '">\n    ' +
                            '   <div class="col-1" id="date_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.date + '</div>\n    ' +
                            '   <div class="col-2" id="acc_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.acc + '</div>\n    ' +
                            '   <div class="col-2" id="other_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.other + '</div>\n    ' +
                            '   <div class="col-2" id="nights_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.nights + '</div>\n    ' +
                            '   <div class="col-2" id="occ_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.occ + '</div>\n    ' +
                            '   <div class="col-2" id="rate_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.rate + '</div>\n    ' +
                            '   <div class="col-1">\n        ' +
                            '       <a href="#" class="btn btn-success btn-sm rounded-0 tt clickable" title="View Details"' +
                            '           data-url="' + data.extra.link + '"' +
                            '           data-hash="details-budget-' + data.extra.id + '"' +
                            '           data-trigger="' + data.extra.id + '"' +
                            '       >\n            ' +
                            '           <i class="fa fa-info-circle"></i> Details\n        ' +
                            '       </a>\n    ' +
                            '   </div>\n' +
                            '</div>');

                        x0p('Success',
                            data.extra.date + ' added',
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
                            '<div class="row table-font" style="border-bottom:1px solid #ccc;" id="row_' + data.extra.type + '_' + data.extra.id + '">\n    ' +
                            '   <div class="col-2" id="nameShort_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.nameShort + '</div>\n    ' +
                            '   <div class="col-6" id="name_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.name + '</div>\n    ' +
                            '   <div class="col-1" id="maxOcc_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.maxOcc + '</div>\n    ' +
                            '   <div class="col-1"><span class="badge badge-success" id="status_' + data.extra.type + '_' + data.extra.id + '">active</span></div>\n    ' +
                            '   <div class="col-1">\n        ' +
                            '       <a href="#" class="btn btn-success btn-sm rounded-0 tt clickable" title="View Details"' +
                            '           data-url="' + data.extra.link + '"' +
                            '           data-hash="details-roomtype-' + data.extra.id + '"' +
                            '           data-trigger="' + data.extra.id + '"' +
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

    if ($.trim($name.val()) === '') {
        openNoty('error', 'Name cannot be empty');
        return false;
    }

    if ($.trim($nameShort.val()) === '') {
        openNoty('error', 'Name (Short) cannot be empty');
        return false;
    }

    if ($.trim($maxOcc.val()) === '') {
        openNoty('error', 'Max. Occupancy cannot be empty');
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
                            '<div class="row table-font" style="border-bottom:1px solid #ccc;" id="row_' + data.extra.type + '_' + data.extra.id + '">\n    ' +
                            '   <div class="col-3" id="name_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.name + '</div>\n    ' +
                            '   <div class="col-7" id="link_' + data.extra.type + '_' + data.extra.id + '">' + data.extra.sublink + '</div>\n    ' +
                            '   <div class="col-1"><span class="badge badge-success" id="status_' + data.extra.type + '_' + data.extra.id + '">active</span></div>\n    ' +
                            '   <div class="col-1">\n        ' +
                            '       <a href="#" class="btn btn-success btn-sm rounded-0 tt clickable" title="View Details"' +
                            '           data-url="' + data.extra.link + '"' +
                            '           data-hash="details-competitor-' + data.extra.id + '"' +
                            '           data-trigger="' + data.extra.id + '"' +
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

    if ($.trim($name.val()) === '') {
        openNoty('error', 'Name cannot be empty');
        return false;
    }

    if ($.trim($link.val()) === '') {
        openNoty('error', 'Link cannot be empty');
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

$(document).on('click', '#js-check-competitors', function (e) {
    e.preventDefault();

    var $btn = $(this);
    var date = $('#js-competitor-date');

    if ($btn.hasClass('disabled')) {
        return false;
    }

    if ($.trim(date.val()) === '' || date.val() === null) {
        openNoty('error', 'Date cannot be empty');
        return false;
    }

    $btn.addClass('disabled').html('<i class="fa fa-spin fa-spinner"></i>');

    $('.comp-result-div').show();

    /**
     * Gather all Competitor Rows
     */
    $('.competitor-row').each(function (i, v) {

        var id = $(v).attr('data-id');
        var url = $(v).attr('data-url');
        started += 1;

        $('#room_' + id).html('<i class="fa fa-spin fa-spinner"></i>');
        $('#incl_' + id).html('<i class="fa fa-spin fa-spinner"></i>');
        $('#pax_' + id).html('<i class="fa fa-spin fa-spinner"></i>');
        $('#price_' + id).html('<i class="fa fa-spin fa-spinner"></i>');

        $.post(url, {
            id: id,
            date: date.val()
        })
            .done(function (data) {
                initTooltips('.tt');
                if (data.result === 'success') {
                    $('#room_' + id).html('<i class="fa fa-info-circle tt" title="'+data.extra.room+'"></i>');
                    $('#incl_' + id).html(data.extra.incl);
                    $('#pax_' + id).html(data.extra.pax);
                    $('#price_' + id).html(data.extra.price);
                } else {
                    $('#room_' + id).html('<i class="fa fa-remove text-danger tt" title="'+data.extra.room+'"></i>');
                }
                started -= 1;
                if (started === 0) {
                    $btn.removeClass('disabled').html('<i class="fa fa-refresh"></i>')
                }
            })
            .fail(function () {
                started -= 1;
                if (started === 0) {
                    $btn.removeClass('disabled').html('<i class="fa fa-refresh"></i>')
                }
                openNoty('error', 'Ajax Error');
            })
        ;

    });

});

$(document).on('click', '#js-check-rate', function (e) {

    e.preventDefault();

    var $btn = $(this);
    var $html = $btn.html();
    var url = $btn.attr('data-url');

    if($btn.hasClass('disabled')){
        return false;
    }

    var $nights = $('#js-stay-nights');
    var $pax = $('#js-stay-pax');
    var $date = $('#js-checkin-date');

    var $result = $('#js-rate-result');

    $btn.addClass('disabled').html('<i class="fa fa-spin fa-spinner"></i>');
    $result.html('<i class="fa fa-spin fa-spinner fa-3x"></i>');

    /* Ajax Call */
    $.post(url, {
        nights: $nights.val(),
        pax: $pax.val(),
        date: $date.val()
    })
        .done(function (data) {
            $result.html('');
            $btn.removeClass('disabled').html($html).blur();
            if (data.result === 'success') {
                $result.html(data.extra.template);
            }else{
                openNoty(data.result, data.message);
            }
            initTooltips('.tt');
        })
        .fail(function () {
            openNoty('error', 'Ajax Error');
            $result.html('');
            $btn.removeClass('disabled').html($html).blur();
        })
    ;



});

$(document).on('click', '#js-update-user', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var url = $btn.attr('data-url');
    var id = $btn.attr('data-id');

    var $name = $('#user-name');
    var $username = $('#user-username');
    var $password = $('#user-password');
    var $holidays = $('#user-holidays');
    var $department = $('#user-department');
    var $admin = $('#user-admin');
    var $manager = $('#user-manager');

    if ($.trim($name.val()) === '') {
        openNoty('error', 'Name cannot be empty');
        return false;
    }

    if ($.trim($username.val()) === '') {
        openNoty('error', 'Username cannot be empty');
        return false;
    }

    /* Ajax Call */
    $.post(url, {
        name: $name.val(),
        username: $username.val(),
        password: $password.val(),
        holidays: $holidays.val(),
        department: $department.val(),
        isAdmin: $admin.val(),
        isManager: $manager.val(),
        id: id
    })
        .done(function (data) {
            if (data.result === 'success') {

                closeDetails(function () {
                    var $nameRow = $('#name_' + data.extra.type + '_' + data.extra.id);
                    var $usernameRow = $('#username_' + data.extra.type + '_' + data.extra.id);
                    var $departmentRow = $('#department_' + data.extra.type + '_' + data.extra.id);
                    var $holidaysRow = $('#holidays_' + data.extra.type + '_' + data.extra.id);

                    $nameRow.html(data.extra.name);
                    $usernameRow.html(data.extra.username);
                    $departmentRow.html(data.extra.department);
                    $holidaysRow.html(data.extra.holidays);

                });

                x0p('Success',
                    $username.val() + ' updated',
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

$(document).on('click', '#js-update-rate', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var url = $btn.attr('data-url');
    var id = $btn.attr('data-id');

    var $name = $('#rate-name');
    var $note = $('#rate-note');
    var $nameShort = $('#rate-name-short');
    var $isBase = $('#rate-base');
    var $minStay = $('#rate-min-stay');
    var $daysAdvance = $('#rate-days-advance');
    var $maxAdvance = $('#rate-max-advance');
    var $dcAmount = $('#rate-dc-amount');
    var $dcType = $('#rate-dc-type');
    var $fixedSingle = $('#rate-fixed-single');
    var $fixedDouble = $('#rate-fixed-double');
    var $allowMon = $('#rate-days-mon');
    var $allowTue = $('#rate-days-tue');
    var $allowWed = $('#rate-days-wed');
    var $allowThu = $('#rate-days-thu');
    var $allowFri = $('#rate-days-fri');
    var $allowSat = $('#rate-days-sat');
    var $allowSun = $('#rate-days-sun');
    var $fairsAllowed = $('#rate-fairs');

    if ($.trim($name.val()) === '') {
        openNoty('error', 'Name cannot be empty');
        return false;
    }

    if ($.trim($nameShort.val()) === '') {
        openNoty('error', 'Name (Short) cannot be empty');
        return false;
    }

    /* Ajax Call */
    $.post(url, {
        name: $name.val(),
        note: $note.val(),
        nameShort: $nameShort.val(),
        minStay: $minStay.val(),
        daysAdvance: $daysAdvance.val(),
        maxAdvance: $maxAdvance.val(),
        isBase: $isBase.val(),
        fairs: $fairsAllowed.val(),
        fixedSingle: $fixedSingle.val(),
        fixedDouble: $fixedDouble.val(),
        allowMon: $allowMon.val(),
        allowTue: $allowTue.val(),
        allowWed: $allowWed.val(),
        allowThu: $allowThu.val(),
        allowFri: $allowFri.val(),
        allowSat: $allowSat.val(),
        allowSun: $allowSun.val(),
        id: id,
        dcAmount: $dcAmount.val(),
        dcType: $dcType.val()
    })
        .done(function (data) {
            if (data.result === 'success') {

                closeDetails(function () {
                    var $noteRow = $('#note_' + data.extra.type + '_' + data.extra.id);
                    var $nameRow = $('#name_' + data.extra.type + '_' + data.extra.id);
                    var $nameShortRow = $('#nameShort_' + data.extra.type + '_' + data.extra.id);
                    var $minStayRow = $('#minStay_' + data.extra.type + '_' + data.extra.id);
                    var $daysAdvanceRow = $('#daysAdvance_' + data.extra.type + '_' + data.extra.id);
                    var $maxAdvanceRow = $('#maxAdvance_' + data.extra.type + '_' + data.extra.id);
                    var $isBaseRow = $('#base_' + data.extra.type + '_' + data.extra.id).parent();
                    var $fairsRow = $('#fairs_' + data.extra.type + '_' + data.extra.id).parent();
                    var $dcRow = $('#discount_' + data.extra.type + '_' + data.extra.id);

                    $noteRow.html(data.extra.note);
                    $nameRow.html(data.extra.name);
                    $nameShortRow.html(data.extra.nameShort);
                    $minStayRow.html(data.extra.minStay);
                    $daysAdvanceRow.html(data.extra.daysAdvance);
                    $maxAdvanceRow.html(data.extra.maxAdvance);
                    $isBaseRow.html(data.extra.isBase);
                    $fairsRow.html(data.extra.fairs);
                    $dcRow.html(data.extra.discount);

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

$(document).on('click', '#js-update-budget', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var url = $btn.attr('data-url');
    var id = $btn.attr('data-id');

    var $acc = $('#budget-acc');
    var $other = $('#budget-other');
    var $nights = $('#budget-nights');
    var $occ = $('#budget-occ');
    var $rate = $('#budget-rate');


    /* Ajax Call */
    $.post(url, {
        acc: $acc.val(),
        other: $other.val(),
        nights: $nights.val(),
        occ: $occ.val(),
        rate: $rate.val(),
        id: id
    })
        .done(function (data) {
            if (data.result === 'success') {

                closeDetails(function () {
                    var $accRow = $('#acc_' + data.extra.type + '_' + data.extra.id);
                    var $otherRow = $('#other_' + data.extra.type + '_' + data.extra.id);
                    var $nightsRow = $('#nights_' + data.extra.type + '_' + data.extra.id);
                    var $occRow = $('#occ_' + data.extra.type + '_' + data.extra.id);
                    var $rateRow = $('#rate_' + data.extra.type + '_' + data.extra.id);

                    $accRow.html(data.extra.acc);
                    $otherRow.html(data.extra.other);
                    $nightsRow.html(data.extra.nights);
                    $occRow.html(data.extra.occ);
                    $rateRow.html(data.extra.rate);

                });

                x0p('Success',
                    'Budget updated',
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

$(document).on('click', '#js-ct-gen-calc', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var $html = $btn.html();
    var url = $btn.attr('data-url');

    if ($btn.hasClass('disabled')) {
        return false;
    }

    var $pax = $('#ct-gen-pax');
    if ($.trim($pax.val()) === '') {
        openNoty('error', 'Invalid Pax');
        return false;
    }

    var $rate = $('#ct-gen-rate');
    if ($.trim($pax.val()) === '' || isNaN(parseFloat($.trim($rate.val().replace(',','.'))))) {
        openNoty('error', 'Invalid Rate');
        return false;
    }

    var $discount1 = $('#ct-exp-dc1');
    var dc1 = 'None';
    if ($.trim($discount1.val()) !== '') {
        if(isNaN(parseFloat($.trim($discount1.val().replace(',','.'))))){
            openNoty('error', 'Invalid Discount 2');
            return false;
        }
        dc1 = parseFloat($.trim($discount1.val().replace(',','.')));
    }

    var $discount2 = $('#ct-exp-dc2');
    var dc2 = 'None';
    if ($.trim($discount2.val()) !== '') {
        if(isNaN(parseFloat($.trim($discount2.val().replace(',','.'))))){
            openNoty('error', 'Invalid Discount 2');
            return false;
        }
        dc2 = parseFloat($.trim($discount2.val().replace(',','.')));
    }

    $btn.addClass('disabled').html('<i class="fa fa-spin fa-spinner"></i>');

    $.post(url, {
        pax: $pax.val(),
        rate: parseFloat($.trim($rate.val().replace(',','.'))),
        dc1: dc1,
        dc2: dc2
    })
        .done(function (data) {

            x0p('CityTax Calculator',
                'Rate Opera: '+data.extra.rate+'\n\nw/out CityTax: '+data.extra.rate_no_tax+'\n\nCityTax: '+data.extra.citytax,
                'ok', false).then(function (inner) {
                $btn.removeClass('disabled').html($html);
                $rate.val('');
                $discount1.val('');
                $discount2.val('');
                $pax.val('1');
            });

        })
        .fail(function () {
            openNoty('error', 'Ajax Error');
            $btn.removeClass('disabled').html($html);
        })
    ;




});

$(document).on('click', '#js-save-settings', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var $html = $btn.html();
    var url = $btn.attr('data-url');

    if ($btn.hasClass('disabled')) {
        return false;
    }

    var $add_triple = $('#add_triple');
    var triple_val = parseFloat($add_triple.val().replace(',', '.'));
    if (triple_val <= 0 || isNaN(triple_val)) {
        openNoty('error', 'Invalid Triple Room Modifier');
        return false;
    }

    var $add_double = $('#add_double');
    var double_val = parseFloat($add_double.val().replace(',', '.'));
    if (double_val <= 0 || isNaN(double_val)) {
        openNoty('error', 'Invalid Double Room Modifier');
        return false;
    }

    var $add_extra = $('#add_extra');
    var extra_val = parseFloat($add_extra.val().replace(',', '.'));
    if (extra_val <= 0 || isNaN(extra_val)) {
        openNoty('error', 'Invalid Extra Person Modifier');
        return false;
    }

    var $bf = $('#bf');
    var bf_val = parseFloat($bf.val().replace(',', '.'));
    if (bf_val <= 0 || isNaN(bf_val)) {
        openNoty('error', 'Invalid Breakfast Amount');
        return false;
    }

    $btn.addClass('disabled').html('<i class="fa fa-spin fa-spinner"></i>');

    $.post(url, {
        add_double: double_val,
        add_triple: triple_val,
        add_extra: extra_val,
        bf: bf_val
    })
        .done(function (data) {
            openNoty(data.result, data.message);
            $btn.removeClass('disabled').html($html);
        })
        .fail(function () {
            openNoty('error', 'Ajax Error');
            $btn.removeClass('disabled').html($html);
        })
    ;


});

$(document).on('click', '#js-generate-tax-forms', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var $html = $btn.html();
    var url = $btn.attr('data-url');
    var status = $btn.attr('data-status');

    if (status === 'Error') {
        x0p('Error',
            'No XML found, please Upload Report first',
            'error', false);
        return false;
    }

    if ($btn.hasClass('disabled')) {
        return false;
    }

    $btn.children().removeClass('fa-file-pdf-o').addClass('fa-spin').addClass('fa-spinner');
    $btn.addClass('disabled');

    $.post(url)
        .done(function (r) {
            $btn.removeClass('disabled').html($html);
            if (r.result === 'success') {
                x0p('Success',
                    'Forms generated',
                    'ok', false).then(function (inner) {
                    window.location = r.extra.link;
                });
            } else {
                x0p('Error',
                    r.message,
                    'error', false);
            }
            $btn.children().addClass('fa-file-pdf-o').removeClass('fa-spin').removeClass('fa-spinner').removeClass('disabled');
            $btn.removeClass('disabled');

        })
        .fail(function () {
            openNoty('error', 'Ajax Error');
            $btn.children().addClass('fa-file-pdf-o').removeClass('fa-spin').removeClass('fa-spinner').removeClass('disabled');
            $btn.removeClass('disabled');
        })
    ;

});

$(document).on('click', '.js-toggle-active', function (e) {

    e.preventDefault();
    var $btn = $(this);
    var $html = $btn.html();
    var url = $btn.attr('data-url');
    var to_set = $btn.attr('data-set');
    var type = $btn.attr('data-type');
    var id = $btn.attr('data-id');

    if ($btn.hasClass('disabled')) {
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
                if ($status.length) {
                    $status.removeClass('badge-danger').removeClass('badge-success').addClass('badge-' + data.extra.b_class).html(data.extra.b_html);
                }

                /**
                 * Toggle the Button
                 */
                $btn.attr('data-set', data.extra.to_set_new).html(data.extra.html_new);

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

$(document).on('change', '.inputfile', function (e) {

    var $btn = $(this);
    var $label = $('label[for="' + $btn.attr('id') + '"]');
    uploadReport($btn, $label.html());

});

function uploadReport($btn, html) {

    var url = $btn.attr('data-url');
    var form = $('#form_' + $btn.attr('data-report-type'))[0];
    var formData = new FormData(form);
    var $label = $('label[for="' + $btn.attr('id') + '"]');

    if ($label.hasClass('disabled')) {
        return false;
    }

    $label.addClass('disabled').html('<i class="fa fa-spin fa-spinner"></i> <span>Uploading...</span>');

    $.ajax({
        url: url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function () {

        },
        success: function (data) {
            $label.removeClass('disabled').html(html);
            openNoty(data.result, data.message);


        },
        error: function (xhr, ajaxOptions, thrownError) {
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