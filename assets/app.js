jQuery.fn.exists = function () {
    return this.length > 0;
};
var data_table;
var map;
var primary_map;
var marker;
var dashboard_task_handle;
var dashboard_agent_handle;
var map_location;
var dashboard_run_silent = 2;

var map_dropoff;
var map_dropoff_marker;
var map_dropoff_bounds = [];

var bounds = [];
var delivery_map_marker;
var delivery_map_bounds = [];

var map_contact;
var map_contact_marker;
var map_contact_bounds = [];

$(document).ready(function () {
    $("ul#tabs li").click(function (e) {
        if (!$(this).hasClass("active")) {
            var tabNum = $(this).index();
            var nthChild = tabNum + 1;
            /*$("ul#tabs li.active").removeClass("active");
            $(this).addClass("active");*/

            var parent = $(this).parent().parent();
            //dump(parent);

            parent.find("ul#tabs li.active").removeClass("active");
            $(this).addClass("active");

            parent.find("ul#tab li.active").removeClass("active");
            parent
                .find("ul#tab li:nth-child(" + nthChild + ")")
                .addClass("active");

            /*$("ul#tab li.active").removeClass("active");
            $("ul#tab li:nth-child("+nthChild+")").addClass("active");*/
        }
    });

    $(document).on("click", ".show-forgot-pass", function () {
        $("#frm").hide();
        $("#frm-forgotpass").show();
    });
    $(document).on("click", ".show-login", function () {
        $("#frm-forgotpass").hide();
        $("#frm").show();
    });

    $(document).on("click", ".menu-pop", function () {
        $(".popup_menu.nav").toggle();
        $(".popup_menu.notification").hide();
    });

    $(document).on("click", ".menu-notification", function () {
        $(".popup_menu.notification").toggle();
        $(".popup_menu.nav").hide();
    });

    $("body").click(function (e) {
        if (
            $(e.target).closest(".popup_menu,.menu-pop,.menu-notification")
                .length === 0
        ) {
            $(".popup_menu.nav").hide();
            $(".popup_menu.notification").hide();
        }
    });

    $(document).on("click", ".close-modal", function () {
        var id = $(this).data("id");
        $(id).modal("hide");
    });

    $(document).on("click", ".show-lang-list", function () {
        $(".lang-wrapper").slideToggle("fast");
    });

    if ($(".mobile_inputs").exists()) {
        try {
            $(".mobile_inputs").intlTelInput({
                autoPlaceholder: false,
                defaultCountry: default_country,
                autoHideDialCode: true,
                nationalMode: false,
                autoFormat: false,
                utilsScript:
                    site_url +
                    "/assets/intel/lib/libphonenumber/build/utils.js",
            });
        } catch (err) {
            dump(err.message);
        }
    }

    $.validate({
        language: jsLanguageValidator,
        form: "#frm",
        onError: function () {},
        onSuccess: function () {
            var params = $("#frm").serialize();
            var action = $("#frm #action").val();
            var button = $('#frm button[type="submit"]');
            dump(button);
            callAjax(action, params, button);
            return false;
        },
    });

    $.validate({
        language: jsLanguageValidator,
        form: "#frm-forgotpass",
        onError: function () {},
        onSuccess: function () {
            var params = $("#frm-forgotpass").serialize();
            var action = $("#frm-forgotpass #action").val();
            var button = $('#frm-forgotpass button[type="submit"]');
            dump(button);
            callAjax(action, params, button);
            return false;
        },
    });

    $.validate({
        language: jsLanguageValidator,
        form: "#frm_task",
        onError: function () {},
        onSuccess: function () {
            var params = $("#frm_task").serialize();
            var action = $("#frm_task #action").val();
            var button = $('#frm_task button[type="submit"]');
            dump(button);
            callAjax(action, params, button);
            return false;
        },
    });

    $.validate({
        language: jsLanguageValidator,
        form: "#frm_changes_status",
        onError: function () {},
        onSuccess: function () {
            var params = $("#frm_changes_status").serialize();
            var action = $("#frm_changes_status #action").val();
            var button = $('#frm_changes_status button[type="submit"]');
            dump(button);
            callAjax(action, params, button);
            return false;
        },
    });

    $.validate({
        language: jsLanguageValidator,
        form: "#frm_notification_tpl",
        onError: function () {},
        onSuccess: function () {
            var params = $("#frm_notification_tpl").serialize();
            var action = $("#frm_notification_tpl #action").val();
            var button = $('#frm_notification_tpl button[type="submit"]');
            dump(button);
            callAjax(action, params, button);
            return false;
        },
    });

    if ($(".chosen").exists()) {
        $(".chosen").chosen({
            allow_single_deselect: true,
            width: "100%",
        });
    }

    if ($(".frm_table").exists()) {
        initTable();
    }
}); /*end docu*/

function empty(data) {
    //if (typeof data === "undefined" || data==null || data=="" ) {
    if (
        typeof data === "undefined" ||
        data == null ||
        data == "" ||
        data == "null" ||
        data == "undefined"
    ) {
        return true;
    }
    return false;
}

function dump(data) {
    console.debug(data);
}

var ajax_request;

/*mycall*/
function callAjax(action, params, button) {
    dump(ajax_url + "/" + action + "?" + params);

    params += "&language=" + language;

    ajax_request = $.ajax({
        url: ajax_url + "/" + action,
        data: params,
        type: "post",
        //async: false,
        dataType: "json",
        timeout: 6000,
        beforeSend: function () {
            dump("before=>");
            dump(ajax_request);
            if (ajax_request != null) {
                ajax_request.abort();
                dump("ajax abort");
                busy(false, button);
            } else {
                busy(true, button);
            }
        },
        complete: function (data) {
            ajax_request = (function () {
                return;
            })();
            dump("Completed");
            dump(ajax_request);
            busy(false, button);
        },
        success: function (data) {
            dump(data);
            if (data.code == 1) {
                switch (action) {
                    case "retryAutoAssign":
                        break;

                    case "login":
                        window.location.href = data.details;
                        break;

                    case "createTeam":
                    case "addAgent":
                        $("." + data.details).modal("hide");
                        nAlert(data.msg, "success");
                        tableReload();
                        break;

                    case "getTeam":
                        $("#team_name").val(data.details.team_name);
                        $("#location_accuracy").val(
                            data.details.location_accuracy
                        );
                        $("#status").val(data.details.status);
                        dump(data.details.team_member);
                        $("#team_member").val(data.details.team_member);
                        $("#team_member").trigger("chosen:updated");
                        break;

                    case "deleteRecords":
                        tableReload();
                        break;

                    case "getDriverInfo":
                        $("#first_name").val(data.details.first_name);
                        $("#last_name").val(data.details.last_name);
                        $("#username").val(data.details.username);
                        $("#email").val(data.details.email);
                        $("#phone").val(data.details.phone);
                        $(".team_id_driver_new").val(data.details.team_id);

                        //$("input[name='team_id'][value='"+data.details.team_id+"']").prop('selected', true);

                        $("#transport_type_id").val(
                            data.details.transport_type_id
                        );
                        $("#transport_description").val(
                            data.details.transport_description
                        );
                        $("#licence_plate").val(data.details.licence_plate);
                        $("#color").val(data.details.color);
                        $("#status").val(data.details.status);
                        $("#password").removeAttr("required");

                        if (!empty(data.details.profile_photo_url)) {
                            var image =
                                '<img src="' +
                                data.details.profile_photo_url +
                                '" />';
                            $(".profile-photo").html(image);
                        } else {
                            $(".profile-photo").html(
                                "<p>" + jslang.profile_photo + "</p>"
                            );
                        }

                        break;

                    case "addTask":
                        nAlert(data.msg, "success");
                        $(".new-task").modal("hide");

                        if ($("body.dashboard").exists()) {
                            clearInterval(task_remaining_handle);
                            getRemainingTask();
                        } else {
                            window.location.href = home_url;
                            return;
                        }

                        loadDashboardTask();

                        break;

                    case "getDashboardTask":
                        /*$(".task_"+data.msg).html( data.details.html );
	 			$(".task-total-"+data.msg).html( data.details.total );*/

                        $.each(data.details, function (key, val) {
                            if (!empty(val)) {
                                $(".task_" + key).html(val.html);
                                $(".task-total-" + key).html(val.total);
                            } else {
                                $(".task_" + key).html("");
                                $(".task-total-" + key).html("0");
                            }
                        });

                        dump("coordinates=>" + data.msg.length);
                        plotMainMap(data.msg);

                        break;

                    case "changeStatus":
                    case "assignTask":
                        nAlert(data.msg, "success");
                        $("." + data.details).modal("hide");

                        loadDashboardTask();
                        break;

                    case "getTaskDetails":
                        if (data.details.status_raw == "successful") {
                            $(".action-1").hide();
                            $(".action-2").show();
                        } else {
                            $(".action-1").show();
                            $(".action-2").hide();
                        }

                        $(".task_status").html(
                            '<span class="rounded tag ' +
                                data.details.status_raw +
                                '" >' +
                                data.details.status +
                                "</spa>"
                        );
                        $(".delivery_date").html(data.details.delivery_date);
                        $(".customer_name").html(data.details.customer_name);
                        $(".contact_number").html(data.details.contact_number);
                        $(".email_address").html(data.details.email_address);
                        $(".delivery_address").html(
                            data.details.delivery_address
                        );
                        $(".team_name").html(data.details.team_name);
                        $(".driver_name").html(data.details.driver_name);
                        $(".task_description").html(
                            data.details.task_description
                        );
                        $(".transaction_type").html(data.details.trans_type);

                        $(".assign-agent").attr(
                            "data-id",
                            data.details.task_id
                        );
                        $(".edit-task").attr("data-id", data.details.task_id);
                        $(".change-status").attr(
                            "data-id",
                            data.details.task_id
                        );
                        $(".delete-task").attr("data-id", data.details.task_id);

                        if (data.details.history_data.length > 0) {
                            history_html = tplTaskHistory(
                                data.details.history_data
                            );
                            $("#task-history").html(history_html);
                        } else {
                            dump("no history");
                            $("#task-history").html(
                                '<p class="alert alert-danger">' +
                                    jslang.no_history +
                                    "</p>"
                            );
                        }

                        if (data.details.driver_id > 0) {
                            dump("has driver assign");
                            $(".re_assign_agent").html(jslang.re_assign_agent);
                        } else {
                            $(".re_assign_agent").html(jslang.assign_agent);
                        }

                        /*show and hide order no*/
                        if (data.details.order_id > 0) {
                            $("#order-id-wrap").show();
                            $(".order-id").html(data.details.order_id);
                            $(".merchant_name").html(
                                data.details.merchant_name
                            );
                        } else {
                            $("#order-id-wrap").hide();
                        }

                        /*show order details*/
                        if (!empty(data.details.order_details)) {
                            $("#order-details").html(
                                data.details.order_details
                            );
                        } else {
                            $("#order-details").html(
                                '<p class="alert alert-danger">' +
                                    jslang.not_available +
                                    "</p>"
                            );
                        }

                        /*fill dropoff details*/

                        if (!empty(data.details.drop_address)) {
                            $(".dropoff-wrap").show();
                            if (data.details.trans_type == "delivery") {
                                $(".dropoff-wrap h5.dropoff_pickup").show();
                                $(".dropoff-wrap h5.dropoff_drop").hide();
                            } else {
                                $(".dropoff-wrap h5.dropoff_pickup").hide();
                                $(".dropoff-wrap h5.dropoff_drop").show();
                            }

                            $(".dropoff_contact_name_details").html(
                                data.details.dropoff_contact_name
                            );
                            $(".dropoff_contact_number_details").html(
                                data.details.dropoff_contact_number
                            );
                            $(".drop_address_details").html(
                                data.details.drop_address
                            );
                        } else {
                            $(".dropoff-wrap").hide();
                        }

                        if (!empty(data.details.tracking_link)) {
                            $(".tracking_link_wrap").show();
                            $(".tracking_link").attr(
                                "href",
                                data.details.tracking_link
                            );
                            $(".tracking_link").html(
                                data.details.tracking_link
                            );
                        } else {
                            $(".tracking_link_wrap").hide();
                        }

                        /*ratings*/
                        if (data.details.ratings > 0) {
                            $(".ratings_wrap").show();
                            $(".ratings_raty").attr(
                                "data-score",
                                data.details.ratings
                            );
                            initRating2();
                            if (!empty(data.details.rating_comment)) {
                                $(".rating_comment").html(
                                    data.details.rating_comment
                                );
                            }
                        } else {
                            $(".ratings_wrap").hide();
                        }

                        break;

                    case "deleteTask":
                        $(".task-details-modal").modal("hide");
                        loadDashboardTask();
                        break;

                    case "getTaskInfo":
                        $("#task_description").val(
                            data.details.task_description
                        );

                        //$("input[name='trans_type'][value='"+data.details.trans_type+"']").attr("checked", true);
                        $(
                            "input[name='trans_type'][value='" +
                                data.details.trans_type +
                                "']"
                        ).prop("checked", true);

                        dump("dxxx");
                        switchTransactionType(data.details.trans_type);

                        $("#contact_number").val(data.details.contact_number);
                        $("#email_address").val(data.details.email_address);
                        $("#customer_name").val(data.details.customer_name);
                        $("#delivery_date").val(data.details.delivery_date);
                        $("#delivery_address").val(
                            data.details.delivery_address
                        );

                        $("#task_lat").val(data.details.task_lat);
                        $("#task_lng").val(data.details.task_lng);

                        if (data.details.team_id > 0) {
                            dump("has team id");
                            $("#team_id").val(data.details.team_id);
                            swicthDriver(
                                data.details.team_id,
                                data.details.driver_id
                            );
                        }

                        if (!empty(data.details.task_lat)) {
                            dump("has lat");
                            switch (map_provider) {
                                case "google":
                                    moveDeliveryMarkers(
                                        data.details.task_lat,
                                        data.details.task_lng
                                    );
                                    break;

                                case "mapbox":
                                    mapbox_plotMarkerDelivery(
                                        data.details.task_lat,
                                        data.details.task_lng
                                    );
                                    break;
                            }
                        } else {
                            dump("no lat");
                            //setMarkerByAddress( $(".geocomplete").val() );
                        }

                        if (!empty(data.details.drop_address)) {
                            $("#dropoff_contact_name").val(
                                data.details.dropoff_contact_name
                            );
                            $("#dropoff_contact_number").val(
                                data.details.dropoff_contact_number
                            );
                            $("#drop_address").val(data.details.drop_address);
                            $("#dropoff_task_lat").val(
                                data.details.dropoff_task_lat
                            );
                            $("#dropoff_task_lng").val(
                                data.details.dropoff_task_lng
                            );

                            switch (map_provider) {
                                case "google":
                                    movePickupMarkers(
                                        data.details.dropoff_task_lat,
                                        data.details.dropoff_task_lng
                                    );
                                    break;

                                case "mapbox":
                                    setTimeout(function () {
                                        mapbox_plotMarkerPickup(
                                            data.details.dropoff_task_lat,
                                            data.details.dropoff_task_lng
                                        );
                                    }, 500);
                                    break;
                            }
                        } else {
                            /*$(".dropoff_wrap").hide();
	 				$(".map2").hide();*/
                        }

                        break;

                    case "getDriverDetails":
                        $(".driver_name").html(data.details.info.name);
                        $(".phone").html(data.details.info.phone);
                        $(".email").html(data.details.info.email);
                        $(".team_name").html(data.details.info.team_name);
                        $(".transport_type_id").html(
                            data.details.info.transport_type_id
                        );
                        $(".licence_plate").html(
                            data.details.info.licence_plate
                        );

                        $(".device_platform").html(
                            data.details.info.device_platform
                        );
                        $(".app_version").html(data.details.info.app_version);

                        if (data.details.task.length > 0) {
                            var html_task = "";
                            $.each(data.details.task, function (tkey, tval) {
                                html_task += formatTableRow(tval);
                            });
                            dump(html_task);
                            $(".driver-task-list tbody").html(html_task);
                        } else {
                            $(".driver-task-list tbody tr").remove();
                        }

                        break;

                    case "GetNotificationTPL":
                        $.each(data.details, function (key, value) {
                            $("#" + key).val(value);
                        });
                        break;

                    case "SaveNotificationTemplate":
                        $(".notification-pop").modal("hide");
                        break;

                    case "chartReports":
                        $(".report_div").html(data.details.charts);
                        $(".table_charts").html(data.details.table);
                        break;

                    case "forgotPassword":
                        window.location.href = data.details;
                        break;

                    case "resetPassword":
                        nAlert(data.msg, "success");
                        window.location.href = data.details;
                        break;

                    case "sendPush":
                        $(".push-form-modal").modal("hide");
                        nAlert(data.msg, "success");
                        break;

                    case "addContact":
                        $(".new-contact").modal("hide");
                        nAlert(data.msg, "success");
                        tableReload();
                        break;

                    case "getContactInfo":
                        $("#fullname").val(data.details.fullname);
                        $("#email").val(data.details.email);
                        $("#phone").val(data.details.phone);
                        $("#address").val(data.details.address);
                        $("#addresss_lat").val(data.details.addresss_lat);
                        $("#addresss_lng").val(data.details.addresss_lng);
                        $("#status").val(data.details.status);

                        switch (map_provider) {
                            case "google":
                                setMarkerEdit(
                                    data.details.addresss_lat,
                                    data.details.addresss_lng,
                                    "addresss_lat",
                                    "addresss_lng"
                                );
                                break;

                            case "mapbox":
                                mapbox_plotMarkerContact(
                                    data.details.addresss_lat,
                                    data.details.addresss_lng
                                );
                                break;
                        }

                        break;

                    case "loadContactInfo":
                        $("#contact_number").val(data.details.phone);
                        $("#email_address").val(data.details.email);
                        $("#customer_name").val(data.details.fullname);
                        $("#delivery_address").val(data.details.address);
                        $("#task_lat").val(data.details.addresss_lat);
                        $("#task_lng").val(data.details.addresss_lng);

                        switch (map_provider) {
                            case "google":
                                moveDeliveryMarkers(
                                    data.details.addresss_lat,
                                    data.details.addresss_lng
                                );
                                break;

                            case "mapbox":
                                mapbox_plotMarkerDelivery(
                                    data.details.addresss_lat,
                                    data.details.addresss_lng
                                );
                                break;
                        }

                        break;

                    case "loadContactInfo2":
                        $("#dropoff_contact_number").val(data.details.phone);
                        $("#dropoff_contact_name").val(data.details.fullname);
                        $("#drop_address").val(data.details.address);
                        $("#dropoff_task_lat").val(data.details.addresss_lat);
                        $("#dropoff_task_lng").val(data.details.addresss_lng);

                        switch (map_provider) {
                            case "google":
                                movePickupMarkers(
                                    data.details.addresss_lat,
                                    data.details.addresss_lng
                                );
                                //setMarkeyDropOffMap( $("#drop_address").val() );
                                break;

                            case "mapbox":
                                mapbox_plotMarkerPickup(
                                    data.details.addresss_lat,
                                    data.details.addresss_lng
                                );
                                break;
                        }

                        break;

                    case "sendBulkPush":
                        $("#push_title").val("");
                        $("#push_message").val("");
                        nAlert(data.msg, "success");
                        break;

                    case "loadAgentTrackBack":
                        trackBackMarker(data.details);
                        break;

                    case "loadTrackDate":
                        $("#track_date").html(data.details);
                        $("#track_date").removeAttr("disabled");
                        $("#track_date").focus();
                        break;

                    case "loadFilterForm":
                        $(".map_filter_wrap").html(data.details);
                        break;

                    case "mapFilterSettings":
                        $(".modalMapFilter").modal("hide");
                        callAjax("getDashboardTask", getParamsMap());
                        break;

                    default:
                        nAlert(data.msg, "success");
                        break;
                }
            } else {
                // failed mycon
                switch (action) {
                    case "loadFilterForm":
                        $(".map_filter_wrap").html("");
                        break;

                    case "loadTrackDate":
                        nAlert(data.msg, "warning");
                        $("#track_date").html("");
                        $("#track_date").attr("disabled", true);
                        break;

                    case "getDashboardTask":
                        $(".task_" + data.details).html("");
                        $(".task-total-" + data.details).html("0");
                        break;

                    case "getTaskDetails":
                        //$(".task-details-modal").modal('hide');
                        setTimeout(
                            '$(".task-details-modal").modal("hide")',
                            100
                        );
                        nAlert(data.msg, "warning");
                        break;

                    //silent
                    case "loadAgentDashboard":
                    case "break;":
                        break;

                    case "getDriverDetails":
                        $(".driver-details-moda").modal("hide");
                        break;

                    default:
                        nAlert(data.msg, "warning");
                        break;
                }
            }
        },
        error: function (request, error) {},
    });
}

function busy(e, button) {
    if (e) {
        $("body").css("cursor", "wait");
    } else $("body").css("cursor", "auto");

    if (e) {
        dump("busy loading");
        /*NProgress.set(0.0);
        NProgress.inc(); */
        $(".main-preloader").show();
        if (!empty(button)) {
            button.css({ "pointer-events": "none" });
        }
    } else {
        dump("done loading");
        $(".main-preloader").hide();
        //NProgress.done();
        if (!empty(button)) {
            button.css({ "pointer-events": "auto" });
        }
    }
}

function nAlert(msg, alert_type) {
    var n = noty({
        text: msg,
        type: alert_type,
        theme: "relax",
        layout: "topCenter",
        timeout: 3000,
        animation: {
            open: "animated fadeInDown", // Animate.css class names
            close: "animated fadeOut", // Animate.css class names
        },
    });
}

function initTable() {
    var params = $("#frm_table").serialize();
    var action = $("#frm_table #action").val();

    params += "&language=" + language;

    data_table = $("#table_list").dataTable({
        iDisplayLength: 20,
        bProcessing: true,
        bServerSide: true,
        sAjaxSource:
            ajax_url + "/" + action + "/?currentController=admin&" + params,
        aaSorting: [[0, "DESC"]],
        sPaginationType: "full_numbers",
        //"bFilter":false,
        bLengthChange: false,
        oLanguage: {
            sProcessing:
                '<p>Processing.. <i class="fa fa-spinner fa-spin"></i></p>',
        },
        oLanguage: {
            sEmptyTable: js_lang.tablet_1,
            sInfo: js_lang.tablet_2,
            sInfoEmpty: js_lang.tablet_3,
            sInfoFiltered: js_lang.tablet_4,
            sInfoPostFix: "",
            sInfoThousands: ",",
            sLengthMenu: js_lang.tablet_5,
            sLoadingRecords: js_lang.tablet_6,
            sProcessing: js_lang.tablet_7,
            sSearch: js_lang.tablet_8,
            sZeroRecords: js_lang.tablet_9,
            oPaginate: {
                sFirst: js_lang.tablet_10,
                sLast: js_lang.tablet_11,
                sNext: js_lang.tablet_12,
                sPrevious: js_lang.tablet_13,
            },
            oAria: {
                sSortAscending: js_lang.tablet_14,
                sSortDescending: js_lang.tablet_15,
            },
        },
        fnInitComplete: function (oSettings, json) {
            if ($(".raty-stars").exists()) {
                initRating();
            }
        },
        fnDrawCallback: function (oSettings) {
            dump("fnDrawCallback");
            setTimeout(function () {
                dump("init rating");
                if ($(".raty-stars").exists()) {
                    dump("run rating");
                    initRating();
                }
            }, 100);
        },
    });
}

function tableReload() {
    data_table.fnReloadAjax();

    setTimeout(function () {
        if ($(".raty-stars").exists()) {
            initRating();
        }
    }, 2000);
}

function clearFormElements(ele) {
    $(ele)
        .find(":input")
        .each(function () {
            switch (this.type) {
                case "password":
                case "select-multiple":
                case "select-one":
                case "text":
                case "textarea":
                    $(this).val("");
                    break;
                case "checkbox":
                case "radio":
                    this.checked = false;
            }
        });
}

$(document).ready(function () {
    $(".create-team").on("show.bs.modal", function (e) {
        $(".modal-title").html(jslang.create_team);
        if (!empty($("#id").val())) {
            $(".modal-title").html(jslang.update_team);
            callAjax(
                "getTeam",
                "id=" + $("#id").val(),
                $("#frm .orange-button")
            );
        }
    });
    $(".create-team").on("hide.bs.modal", function (e) {
        $("#id").val("");
        clearFormElements("#frm");
    });
    $(document).on("click", ".table-edit", function () {
        var id = $(this).data("modal");
        dump($(this).data("id"));
        $("#id").val($(this).data("id"));
        $(id).modal("show");
    });

    $(document).on("click", ".table-delete", function () {
        dump(jslang);
        c = confirm(jslang.are_your_sure + "?");
        if (c) {
            callAjax("deleteRecords", $(this).data("data"));
        }
    });

    $(document).on("click", "#transport_type_id", function () {
        var selected = $(this).val();
        switchTransportType(selected);
    });
    $(".new-agent").on("hide.bs.modal", function (e) {
        $("#id").val("");
        $(".profile-photo").html("<p>" + jslang.profile_photo + "</p>");
        clearFormElements("#frm");
    });
    $(".new-agent").on("show.bs.modal", function (e) {
        $("#password").attr("required", 1);
        switchTransportType($("#transport_type_id").val());
        $(".modal-title").html(jslang.add_driver);
        if (!empty($("#id").val())) {
            $(".modal-title").html(jslang.update_driver);
            callAjax(
                "getDriverInfo",
                "id=" + $("#id").val(),
                $("#frm .orange-button")
            );
        }
    });

    $(document).on("click", ".refresh-table", function () {
        tableReload();
    });

    $(document).on("click", ".add-new-task", function () {
        if (account_status == "expired") {
            nAlert(jslang.account_expired, "warning");
        } else {
            $(".task_id").val("");
            $(".new-task").modal("show");
        }
    });

    /*task modal*/
    $(".new-task").on("show.bs.modal", function (e) {
        dump("show modal new task");
        switchTransactionType($(".trans_type:checked").val());
        swicthDriver($("#driver_id:selected").val());
        var task_id = $(".task_id").val();
        dump(task_id);
        if (!empty(task_id)) {
            dump("task_id=>" + task_id);
            callAjax("getTaskInfo", "id=" + task_id);
        }
    });
    $(".new-task").on("shown.bs.modal", function (e) {
        dump("modal totally loaded");
        setDefaultMapLocation();
    });
    $(".new-task").on("hide.bs.modal", function (e) {
        dump("hide modal");
        $(".task_id").val("");
        $(".order_id").val("");
        $(".task_lat").val("");
        $(".task_lng").val("");

        $("#dropoff_task_lat").val("");
        $("#dropoff_task_lng").val("");

        $("#task_lat").val("");
        $("#task_lng").val("");

        clearFormElements("#frm_task");
    });

    $(document).on("click", ".trans_type", function () {
        switchTransactionType($(".trans_type:checked").val());
    });

    /*missing translation*/
    var today_date = moment().format("YYYY/MM/DD");
    if ($(".datetimepicker").exists()) {
        dump("datetimepicker exists");
        dump(today_date);
        $(".datetimepicker").datetimepicker({
            /*format:'Y-m-d g:i A',
	    	formatTime:'g:i A', */
            //format:'d.m.Y H:i'
            formatTime: "g:i A",
            format: "Y-m-d H:i",
            minDate: today_date,
        });
    }

    if ($("#calendar").exists()) {
        $("#calendar").datetimepicker({
            timepicker: false,
            format: "d M Y",
            //onChangeDateTime:function(dp,$input){
            onSelectDate: function (dp, $input) {
                var date_formated = dp.format("YYYY-MM-DD");
                dump(date_formated);
                $(".calendar_formated").val(date_formated);
                loadDashboardTask();
            },
        });
    }

    if (!empty(calendar_language)) {
        jQuery.datetimepicker.setLocale(calendar_language);
        //http://xdsoft.net/jqplugins/datetimepicker/#lang*/
    }

    /*geocomplete*/
    if ($(".geocomplete").exists()) {
        switch (map_provider) {
            case "google":
                $(".geocomplete")
                    .geocomplete({
                        country: default_country,
                    })
                    .bind("geocode:result", function (event, result) {
                        dump(result.geometry.location.lat());
                        dump(result.geometry.location.lng());

                        $("#task_lat").val(result.geometry.location.lat());
                        $("#task_lng").val(result.geometry.location.lng());

                        moveDeliveryMarkers(
                            result.geometry.location.lat(),
                            result.geometry.location.lng()
                        );
                    });
                break;

            case "mapbox":
                break;
        }
    }

    if ($(".search_map").exists()) {
        switch (map_provider) {
            case "google":
                $("#search_map")
                    .geocomplete({
                        country: default_country,
                    })
                    .bind("geocode:result", function (event, result) {
                        var t_lat = result.geometry.location.lat();
                        var t_lng = result.geometry.location.lng();
                        dump(t_lat);
                        dump(t_lng);
                        if (!empty(t_lat)) {
                            map.setCenter(t_lat, t_lng);
                            map.setZoom(10);
                        } else {
                            nAlert(jslang.undefine_result, "warning");
                        }
                    });

                break;

            case "mapbox":
                mapbox_initGeocoderSearch("search_map_mapbox");
                break;
        }
    }

    $(document).on("change", ".task_team_id", function () {
        var team_id = $(this).val();
        swicthDriver(team_id);
    });

    /*$( document ).on( "keyup", ".delivery_address_task", function() {
    	 var address=$(this).val();
    	 setMarkerByAddress( $(".geocomplete").val() );
    });    */

    if ($(".dashboard-work-area").exists()) {
        loadDashboardTask();
        if (disabled_auto_refresh != 1) {
            //dashboard_task_handle = setInterval(function(){loadDashboardTaskSilent()}, 9000);
        }
    }

    /*assign task*/

    $(document).on("click", ".assign-agent", function () {
        var task_id = $(this).data("id");
        //var task_id=$(".task_id_details").val();

        var modalclose = $(this).data("modalclose");
        if (!empty(modalclose)) {
            $("." + modalclose).modal("hide");
        }

        dump(task_id);

        $(".assign-task").modal("show");
        $(".task-id").html(task_id);
        $(".task_id").val(task_id);
    });
    $(".assign-task").on("show.bs.modal", function (e) {
        dump("modal totally loaded");
        swicthDriver(0);
    });

    var task_id_details = "";
    /*task details*/

    $(document).on("click", ".task-details", function () {
        $(".driver-details-moda").modal("hide");
        var task_id = $(this).data("id");
        dump("modal show");
        $(".task-id").html(task_id);
        $(".task_id_details").val(task_id);
        $(".task-details-modal").modal("show");
    });
    $(".task-details-modal").on("show.bs.modal", function (e) {
        dump("modal totally loaded");
        dump($(".task_id_details").val());
        callAjax("getTaskDetails", "id=" + $(".task_id_details").val());
    });

    /*delete task*/
    $(document).on("click", ".delete-task", function () {
        var task_id = $(".task_id_details").val();
        c = confirm(jslang.are_your_sure + "?");
        if (c) {
            callAjax("deleteTask", "task_id=" + task_id);
        }
    });

    /*edit task*/
    $(document).on("click", ".edit-task", function () {
        var task_id = $(".task_id_details").val();
        dump(task_id);
        $(".task_id").val(task_id);
        $(".task-details-modal").modal("hide");
        $(".new-task").modal("show");
    });

    /*change status*/

    $(document).on("click", ".change-status", function () {
        var task_id = $(".task_id_details").val();
        dump(task_id);
        $(".task-id").html(task_id);
        $(".task_id").val(task_id);
        $("#reason").val("");
        $(".task-details-modal").modal("hide");
        $(".task-change-status-modal").modal("show");
    });
    $(".task-details-modal").on("show.bs.modal", function (e) {
        dump("modal totally loaded");
        dump($(".task_id").val());
        $(".status").val("");
        $(".reason_wrap").hide();
        $(".reason").val("");
    });

    /*show reason text area*/
    $(document).on("change", ".status_task_change", function () {
        var status = $(this).val();
        switchReason(status);
    });

    /*set focus on map*/
    $(document).on("click", ".task-map", function () {
        var t_lat = $(this).data("lat");
        var t_lng = $(this).data("lng");

        switch (map_provider) {
            case "google":
                map.setCenter(t_lat, t_lng);
                break;

            case "mapbox":
                mapbox_setMapCenter(t_lat, t_lng);
                break;
        }

        $(".task-map").removeClass("active");
        $(this).addClass("active");
    });

    /*load agent list*/
    if ($(".agent-active").exists()) {
        loadAgentDashboardSilent();
        //dashboard_agent_handle = setInterval(function(){loadAgentDashboardSilent()}, 8500);
    }

    //show agent details
    //$( document ).on( "click", ".show-agent-details", function() {
    $(document).on("click", ".view-driver-details", function () {
        var driver_id = $(this).data("id");
        $(".driver_id_details").val(driver_id);
        $(".driver-details-moda").modal("show");
    });

    $(".driver-details-moda").on("show.bs.modal", function (e) {
        callAjax(
            "getDriverDetails",
            "driver_id=" +
                $(".driver_id_details").val() +
                "&date=" +
                $(".calendar_formated").val()
        );
    });

    if ($(".sticky").exists()) {
        dump("sticky");
        $(".sticky").sticky({ topSpacing: 0 });
    }

    $(".switch-boostrap").bootstrapSwitch({
        size: "mini",
    });

    $(document).on("click", ".notification_tpl", function () {
        $("#option_name").val($(this).data("id"));
        $(".option-name").html($(this).data("id"));
        $(".notification-pop").modal("show");
    });
    $(".notification-pop").on("show.bs.modal", function (e) {
        callAjax(
            "GetNotificationTPL",
            "option_name=" + $("#option_name").val()
        );
    });

    if ($("#jplayer").exists()) {
        initJplayer();
    }

    $(document).on("change", "#team", function () {
        loadAgentDashboard();
        callAjax("getDashboardTask", getParamsMap());
    });

    if (empty(Cookies.get("drv_sound_on"))) {
        Cookies.set("drv_sound_on", "1", { expires: 500, path: "/" });
    } else {
        var drv_sound_on = Cookies.get("drv_sound_on");
        dump("drv_sound_on->" + Cookies.get("drv_sound_on"));
        if (drv_sound_on == 2) {
            $(".menu-sound i").addClass("ion-android-volume-off");
            $(".menu-sound i").removeClass("ion-volume-high");
        }
    }

    $(document).on("click", ".menu-sound", function () {
        var f = $(this).find("i");
        if (f.hasClass("ion-android-volume-off")) {
            f.removeClass("ion-android-volume-off");
            f.addClass("ion-volume-high");
            dump("on");
            Cookies.set("drv_sound_on", "1", { expires: 500, path: "/" });
        } else {
            f.addClass("ion-android-volume-off");
            f.removeClass("ion-volume-high");
            dump("off");
            Cookies.set("drv_sound_on", "2", { expires: 500, path: "/" });
        }
    });

    $(document).on("click", ".show-location-map", function () {
        var lat = $(this).data("lat");
        var lng = $(this).data("lng");
        $(".task-details-modal").modal("hide");
        $("#map_location_lat").val(lat);
        $("#map_location_lng").val(lng);
        $("#map_task_id_ref").val($(this).data("taskid"));
        $(".show-location-map-modal").modal("show");
    });
    $(".show-location-map-modal").on("shown.bs.modal", function (e) {
        var lat = $("#map_location_lat").val();
        var lng = $("#map_location_lng").val();

        switch (map_provider) {
            case "google":
                map_location = new GMaps({
                    div: ".map-location",
                    lat: lat,
                    lng: lng,
                    zoom: 5,
                    styles: map_style,
                });
                map_location.setCenter(lat, lng);
                map_location.setZoom(10);

                var location_marker = map_location.addMarker({
                    lat: lat,
                    lng: lng,
                });

                break;

            case "mapbox":
                mapbox_plotLocationMap("map_location", lat, lng);
                break;
        }
    });
}); /*end docu*/

function loadDashboardTask() {
    if ($(".dashboard-work-area").exists()) {
        //callAjax("getDashboardTask","status=unassigned&date="+ $(".calendar_formated").val() );
        callAjax("getDashboardTask", getParamsMap());
    }
    if ($(".task-list-area").exists()) {
        tableReload();
    }
}

function loadDashboardTaskSilent() {
    dump("loadDashboardTaskSilent");
    //callAjaxSilent("getDashboardTask","status=unassigned&date="+ $(".calendar_formated").val() );
    callAjaxSilent("getDashboardTask", getParamsMap());
}

function loadAgentDashboard() {
    callAjax2(
        "loadAgentDashboard",
        "date=" + $(".calendar_formated").val() + "&team_id=" + $("#team").val()
    );
}

function loadAgentDashboardSilent() {
    dump("loadAgentDashboardSilent");
    callAjaxSilent2(
        "loadAgentDashboard",
        "date=" + $(".calendar_formated").val() + "&team_id=" + $("#team").val()
    );
}

function switchReason(status) {
    dump(status);
    switch (status) {
        case "failed":
        case "canceled":
        case "cancelled":
            $(".reason_wrap").show();
            break;

        default:
            $(".reason_wrap").hide();
            break;
    }
}

function switchTransportType(selected) {
    switch (selected) {
        case "bike":
            $("#licence_plate").hide();
            break;

        case "walk":
            $("#licence_plate").hide();
            $("#transport_description").hide();
            $("#color").hide();
            $(".description").hide();
            break;

        default:
            $(".description").show();
            $("#licence_plate").show();
            $("#transport_description").show();
            $("#color").show();
            break;
    }
}

function switchTransactionType(transaction_type) {
    dump(transaction_type);

    $(".contact_list").val("-1");
    $(".contact_list2").val("-1");

    switch (transaction_type) {
        case "pickup":
            $(".delivery-info").show();
            $("#delivery_date").attr("placeholder", jslang.pickup_before);
            $("#delivery_address").attr("placeholder", jslang.pickup_address);

            $(".dropoff_wrap").show();
            $(".dropoff_action_1").hide();
            $(".dropoff_action_2").show();
            $(".map2").show();

            initMapDropOffMap();

            break;

        case "delivery":
            $(".delivery-info").show();
            $("#delivery_date").attr("placeholder", jslang.delivery_before);
            $("#delivery_address").attr("placeholder", jslang.delivery_address);

            $(".dropoff_wrap").show();
            $(".dropoff_action_2").hide();
            $(".dropoff_action_1").show();
            $(".map2").show();

            initMapDropOffMap();

            break;

        default:
            $(".delivery-info").hide();
            $(".dropoff_wrap").hide();
            $(".map2").hide();
            break;
    }
}

function swicthDriver(team_id, id_selected) {
    if (team_id > 0) {
        $(".assign-agent-wrap").show();
        $(".team_opion").hide();
        $(".option_" + team_id).show();
        $(".map1").css({ height: "250px" });

        if (!empty(id_selected)) {
            $("#driver_id").val(id_selected);
        } else {
            $("#driver_id").val("");
        }
    } else {
        $(".task_team_id").val(0);
        $(".driver_id").val("");
        $(".assign-agent-wrap").hide();
        $(".team_opion").hide();
        $(".map1").css({ height: "250px" });
    }
}

function setDefaultMapLocation() {
    dump("setDefaultMapLocation");

    var task_id = $(".task_id").val();

    switch (map_provider) {
        case "google":
            primary_map = new GMaps({
                div: ".map_task",
                lat: default_location_lat,
                lng: default_location_lng,
                zoom: 5,
                styles: map_style,
            });

            //marker_icon = getTaskIcon();

            delivery_map_marker = primary_map.addMarker({
                lat: default_location_lat,
                lng: default_location_lng,
                draggable: true,
                //icon : marker_icon
            });

            delivery_map_marker.addListener("dragend", function (event) {
                dump("==>delivery_map_marker");
                dump("lat=>" + event.latLng.lat());
                dump("long=>" + event.latLng.lng());

                $("#task_lat").val(event.latLng.lat());
                $("#task_lng").val(event.latLng.lng());

                dump("drag event");
                if ($(".new-task-submit").is(":visible")) {
                    convertLatLongToAddress(
                        event.latLng.lat(),
                        event.latLng.lng()
                    );
                }
            });

            break;

        case "mapbox":
            mapbox_PlotMapDelivery(
                "map_task",
                default_location_lat,
                default_location_lng
            );
            mapbox_initGeocoderDelivery("mapbox_delivery_address");
            break;
    }
}

function setMarkerByAddress(address) {
    dump("setMarkerByAddress");

    //primary_map.removeMarkers();

    map_marker_task = map_marker_delivery;
    if ($(".trans_type").exists()) {
        if ($(".trans_type:checked").val() == "pickup") {
            map_marker_task = map_pickup_icon;
        }
    }

    if ($(".new-task-submit").is(":visible")) {
        $(".new-task-submit").css({ "pointer-events": "none" });
    }

    dump(address);
    GMaps.geocode({
        address: address,
        callback: function (results, status) {
            if (status == "OK") {
                if ($(".new-task-submit").is(":visible")) {
                    $(".new-task-submit").css({ "pointer-events": "auto" });
                }

                var latlng = results[0].geometry.location;
                primary_map.setCenter(latlng.lat(), latlng.lng());
                primary_map.setZoom(15);

                $("#task_lat").val(latlng.lat());
                $("#task_lng").val(latlng.lng());

                moveDeliveryMarker(latlng.lat(), latlng.lng());

                /*delivery_map_marker = primary_map.addMarker({
	         lat: latlng.lat(),
	         lng: latlng.lng(),
	         draggable: true,
	         icon : map_marker_task
	      });*/

                /*marker.addListener('dragend',function(event) {
	         $("#task_lat").val( event.latLng.lat() );
	         $("#task_lng").val( event.latLng.lng() );

	         dump("drag event");
	         if($('.new-task-submit').is(':visible')) {
	         	convertLatLongToAddress( event.latLng.lat() , event.latLng.lng() );
	         }

	      });*/
            } else {
                if ($(".new-task-submit").is(":visible")) {
                    $(".new-task-submit").css({ "pointer-events": "auto" });
                }
            }
        },
    });
}

function SetMapPlot(lat, lng) {
    primary_map.removeMarkers();

    dump(lat);
    dump(lng);
    primary_map.setCenter(lat, lng);
    primary_map.setZoom(15);

    map_marker = map_marker_delivery;
    if ($(".trans_type").exists()) {
        if ($(".trans_type:checked").val() == "pickup") {
            map_marker = map_pickup_icon;
        }
    }

    var marker = primary_map.addMarker({
        lat: lat,
        lng: lng,
        draggable: true,
        icon: map_marker,
    });

    //marker.addListener('drag',function(event) {
    marker.addListener("dragend", function (event) {
        dump("latx=>" + event.latLng.lat());
        dump("longx=>" + event.latLng.lng());
        $("#task_lat").val(event.latLng.lat());
        $("#task_lng").val(event.latLng.lng());
        convertLatLongToAddress(event.latLng.lat(), event.latLng.lng());
    });
}

function tplTaskHistory(data) {
    if (data.length <= 0) {
        return;
    }
    var html = "";
    $.each(data, function (key, val) {
        dump(val);
        html += '<div class="grey-box top10">';
        html += '<div class="row">';
        html += '<div class="col-md-2">';
        html +=
            '<span class="tag rounded ' +
            val.status_raw +
            '">' +
            val.status +
            "</span>";
        html += "</div>";
        html += '<div class="col-md-6">';
        html += val.remarks;

        if (!empty(val.reason)) {
            html +=
                '<p class="text-muted">' +
                jslang.reason +
                ": " +
                val.reason +
                "</p>";
        }

        if (!empty(val.notes)) {
            html +=
                '<p class="text-muted">' +
                jslang.notes +
                ": " +
                val.notes +
                "</p>";
        }

        if (!empty(val.photo_name_url)) {
            html += "<p>";
            html += '<a href="' + val.photo_name_url + '" target="_blank">';
            html +=
                '<img class="customer-signature" src="' +
                val.photo_name_url +
                '">';
            html += "</a>";
            html += "</p>";
        }

        if (!empty(val.customer_signature)) {
            html += "<p>";
            html +=
                '<a href="' + val.customer_signature_url + '" target="_blank">';
            html +=
                '<img class="customer-signature" src="' +
                val.customer_signature_url +
                '">';
            html += "</a>";
            html += "</p>";
        }

        if (!empty(val.receive_by)) {
            html +=
                '<p class="text-muted">' +
                jslang.receive_by +
                ": " +
                val.receive_by +
                "</p>";
        }

        html += "</div>";
        html += '<div class="col-md-4">';
        html +=
            '<i class="ion-ios-clock-outline"></i> ' +
            val.date_created +
            " <br/>";
        if (!empty(val.driver_location_lat)) {
            html += '<i class="ion-ios-location"></i>';
            html +=
                '<a href="javascript:;" class="show-location-map" data-lat="' +
                val.driver_location_lat +
                '" data-lng="' +
                val.driver_location_lng +
                '" data-taskid="' +
                val.task_id +
                '"  >' +
                jslang.location_on_map +
                "</a>";
        }
        html += "</div>";
        html += "</div> ";
        html += "</div>";
    });
    return html;
}

function plotMainMap(data) {
    dump("plotMainMap");
    switch (map_provider) {
        case "google":
            map = new GMaps({
                div: ".primary_map",
                lat: default_location_lat,
                lng: default_location_lng,
                //scrollwheel: false ,
                zoom: 5,
                styles: map_style,
                markerClusterer: function (map) {
                    return new MarkerClusterer(map);
                },
            });

            plotTaskMap(data);

            break;

        case "mapbox":
            mapbox_PlotMap(
                "primary_map",
                default_location_lat,
                default_location_lng
            );
            setTimeout(function () {
                mapbox_plotTaskMap(data, true);
            }, 100);
            break;
    }
}

function plotTaskMap(data) {
    if (data.length > 0) {
        // remove all pin
        dump("remove all pin");
        map.removeMarkers();

        var last_lat = "";
        var last_lng = "";
        bounds = [];

        $.each(data, function (key, val) {
            if (!empty(val.lat)) {
                if (map_hide_delivery == 1) {
                    if (val.trans_type_raw == "delivery") {
                        return;
                    }
                }

                if (map_hide_pickup == 1) {
                    if (val.trans_type_raw == "pickup") {
                        return;
                    }
                }

                if (map_hide_success_task == 1) {
                    if (val.status_raw == "successful") {
                        return;
                    }
                }

                info_html = "";

                if (val.map_type == "restaurant") {
                    info_html += '<div class="map-info-window">';
                    info_html +=
                        "<h4>" + jslang.task_id + ": " + val.task_id + "</h4>";
                    info_html +=
                        "<h5>" +
                        jslang.name +
                        ": " +
                        val.customer_name +
                        "</h5>";
                    info_html += "<p>" + val.address + "</p>";
                    info_html +=
                        '<p class="inline green-button small rounded">' +
                        val.trans_type +
                        "</p>";
                    info_html +=
                        '<p class="inline orange-button-small rounded">' +
                        val.status +
                        "</p>";
                    info_html +=
                        '<a href="javascript:;"  class="top10 task-details" data-id="' +
                        val.task_id +
                        '"  >' +
                        jslang.details +
                        "</a>";
                    info_html += "</div>";
                } else {
                    info_html += val.first_name + " ";
                    info_html += val.last_name;
                }

                last_lat = val.lat;
                last_lng = val.lng;

                var latlng = new google.maps.LatLng(last_lat, last_lng);
                bounds.push(latlng);

                if (val.map_type == "restaurant") {
                    if (val.trans_type == "delivery") {
                        switch (val.status_raw) {
                            case "successful":
                                map_marker = delivery_icon_success;
                                break;

                            case "failed":
                            case "declined":
                            case "cancelled":
                                map_marker = delivery_icon_failed;
                                break;

                            default:
                                map_marker = map_marker_delivery;
                                break;
                        }
                    } else {
                        switch (val.status_raw) {
                            case "successful":
                                map_marker = pickup_icon_success;
                                break;

                            case "failed":
                            case "declined":
                            case "cancelled":
                                map_marker = pickup_icon_failed;
                                break;

                            default:
                                map_marker = map_pickup_icon;
                                break;
                        }
                    }

                    map.addMarker({
                        lat: val.lat,
                        lng: val.lng,
                        icon: map_marker,
                        infoWindow: {
                            content: info_html,
                        },
                    });
                } else {
                    if (val.is_online == 1) {
                        map_marker = driver_icon_online;
                    } else {
                        map_marker = driver_icon_offline;
                    }
                    plotDriverToMap(val.lat, val.lng, map_marker, info_html);
                }
            }
        }); /*end each*/

        if (dashboard_run_silent == 2) {
            map.fitLatLngBounds(bounds);
        }
    } else {
        dump("no task to map");
    }
}

function setDriverMarker(lat, lng, info_html) {
    if (empty(lat)) {
        return;
    }
    if (empty(lng)) {
        return;
    }

    dump("setDriverMarker");

    var marker = map.addMarker({
        lat: lat,
        lng: lng,
        icon: driver_icon,
        draggable: false,
        infoWindow: {
            content: info_html,
        },
    });
}

function scroll(id) {
    if ($(id)) {
        $(".content_main").animate(
            { scrollTop: $(id).offset().top - 100 },
            "slow"
        );
    }
}

var notification_handle = "";

$(document).ready(function () {
    if ($("#layout_1").exists()) {
        getInitialNotifications();
        setTimeout("getNotifications()", 1100);
    }

    setInterval(function () {
        // Refresh auto
        loadAgentDashboardSilent();
    }, 2000);
}); /*end docu*/
function getInitialNotifications() {
    action = "getInitialNotifications";
    params = "";

    params += "&language=" + language;

    var notification_handle2;

    notification_handle2 = $.ajax({
        url: ajax_url + "/" + action,
        data: params,
        type: "post",
        dataType: "json",
        timeout: 6000,
        beforeSend: function () {
            if (notification_handle2 != null) {
                notification_handle2.abort();
                dump("ajax abort");
            }
        },
        complete: function (data) {
            notification_handle2 = (function () {
                return;
            })();
        },
        success: function (data) {
            if (data.code == 1) {
                $.each(data.details, function (key, val) {
                    fillPopUpNotification(
                        val.message,
                        val.title,
                        val.task_id,
                        val.status
                    );
                });
            } else {
                $("#notification_list").prepend(
                    '<p class="no-noti text-info">' +
                        jslang.no_notification +
                        "</p>"
                );
            }
        },
        error: function (request, error) {},
    });
}

function getNotifications() {
    action = "GetNotifications";
    params = "";

    params += "&language=" + language;

    notification_handle = $.ajax({
        url: ajax_url + "/" + action,
        data: params,
        type: "post",
        dataType: "json",
        timeout: 6000,
        beforeSend: function () {
            window.clearInterval(notification_handle);
        },
        complete: function (data) {
            notification_handle = setInterval(function () {
                getNotifications();
            }, 10000);
        },
        success: function (data) {
            if (data.code == 1) {
                $(".no-noti").remove();
                playNotification();
                $.each(data.details, function (key, val) {
                    toastMessage(val.message, val.title);
                    fillPopUpNotification(
                        val.message,
                        val.title,
                        val.task_id,
                        val.status
                    );
                });
            } else {
                //playNotification();
            }
        },
        error: function (request, error) {
            window.clearInterval(notification_handle);
        },
    });
}

function fillPopUpNotification(message, title, task_id, status) {
    var link =
        '<a data-id="' +
        task_id +
        '" class="task-details" href="javascript:;">' +
        task_id +
        "</a>";
    var new_title = status + " " + jslang.task_id + ":" + link;
    var html = "";
    html += "<li>";
    html +=
        '<i class="ion-ios-circle-filled"></i> ' +
        message +
        " <br/>" +
        new_title;
    html += "</li>";
    $("#notification_list").prepend(html);
}

function toastMessage(message, title) {
    if (empty(title)) {
        title = "";
    }
    if (empty(message)) {
        return;
    }
    toastr.options = {
        positionClass: "toast-bottom-right",
        preventDuplicates: false,
        onclick: null,
        showDuration: "500",
        hideDuration: "1000",
        timeOut: "5000",
        extendedTimeOut: "1000",
        showEasing: "swing",
        hideEasing: "linear",
        showMethod: "fadeIn",
        hideMethod: "fadeOut",
    };
    toastr.info(message, title);
}

function initJplayer() {
    //alert(site_url+"/assets/audio/fb-alert.mp3");
    $("#jplayer").jPlayer({
        ready: function () {
            $(this).jPlayer("setMedia", {
                mp3: website_url + "/assets/audio/fb-alert.mp3",
            });
        },
        swfPath: site_url + "/assets/jplayer",
        loop: false,
    });
}

function playNotification() {
    var drv_sound_on = Cookies.get("drv_sound_on");
    if (drv_sound_on == 2) {
        // do nothing
        dump("sound is off");
    } else {
        dump("sound is on");
        $("#jplayer").jPlayer("play");
    }
}

$(document).ready(function () {
    $(document).on("keyup", ".numeric_only", function () {
        this.value = this.value.replace(/[^0-9\.]/g, "");
    });

    if ($("#driver_auto_assign_type").exists()) {
        switchAutoAssign();
    }
    $(document).on("click", "#driver_auto_assign_type", function () {
        switchAutoAssign();
    });

    $(document).on("click", ".locate-driver-onmap", function () {
        var t_lat = $(this).data("lat");
        var t_lng = $(this).data("lng");
        if (!empty(t_lat) && !empty(t_lng)) {
            switch (map_provider) {
                case "google":
                    map.setCenter(t_lat, t_lng);
                    map.setZoom(14);
                    break;

                case "mapbox":
                    mapbox_setMapCenter(t_lat, t_lng);
                    break;
            }
        }
    });

    if ($(".report_div").exists()) {
        loadChart();
    }

    $(document).on("change", "#team_selection", function () {
        team_id = $(this).val();
        $("#driver_selection").val("all");
        $("#driver_selection .team_opion").hide();
        $("#driver_selection .option_" + team_id).show();
    });

    $(document).on("change", "#time_selection", function () {
        if ($(this).val() == "custom") {
            $(".custom_selection").show();
        } else {
            $(".custom_selection").hide();
            loadChart();
        }
    });

    $(document).on("click", ".change_charts", function () {
        $("#chart_type_option").val($(this).data("id"));
        loadChart();
    });
    $(document).on("change", "#team_selection,#driver_selection", function () {
        loadChart();
    });
}); /*end docu*/

function switchAutoAssign() {
    var selected = $("#driver_auto_assign_type:checked").val();
    dump(selected);
    switch (selected) {
        case "one_by_one":
            $(".section_one_by_one").show();
            $(".section_send_to_all").hide();
            break;

        case "send_to_all":
            $(".section_one_by_one").hide();
            $(".section_send_to_all").show();
            break;

        default:
            $(".section_one_by_one").hide();
            $(".section_send_to_all").hide();
            break;
    }
}

function plotDriverToMap(lat, lng, map_marker, info_html) {
    dump("plotDriverToMap");

    if (empty(lat) && empty(lng)) {
        return;
    }

    map.addMarker({
        lat: lat,
        lng: lng,
        icon: map_marker,
        infoWindow: {
            content: info_html,
        },
    });
}

function convertLatLongToAddress(lat, lng) {
    dump("convertLatLongToAddress");

    if (auto_geo_address != 1) {
        return;
    }

    if (empty(lat) || empty(lng)) {
        return;
    }

    $(".map_task_loader").show();

    setTimeout(function () {
        var latlng = new google.maps.LatLng(lat, lng);
        var geocoder = (geocoder = new google.maps.Geocoder());
        geocoder.geocode({ latLng: latlng }, function (results, status) {
            dump("GEOCODE =>" + status);
            if (status == google.maps.GeocoderStatus.OK) {
                if (results[1]) {
                    $(".map_task_loader").hide();
                    dump(results[1].formatted_address);
                    $("#delivery_address").val(results[1].formatted_address);
                } else {
                    $(".map_task_loader").hide();
                }
            } else {
                $(".map_task_loader").hide();
            }
        });
    }, 300);
}

function retryAutoAssign(task_id) {
    $(".autoassign-col-1-" + task_id).html("");
    $(".autoassign-col-2-" + task_id).html(
        '<p class="small-font text-primary">' + jslang.autoassigning + "...</p>"
    );
    callAjax("retryAutoAssign", "task_id=" + task_id);
}

function loadChart() {
    params = "chart_type=" + $("#chart_type").val();
    params += "&chart_type_option=" + $("#chart_type_option").val();
    params += "&time_selection=" + $("#time_selection").val();
    params += "&team_selection=" + $("#team_selection").val();
    params += "&driver_selection=" + $("#driver_selection").val();
    params += "&start_date=" + $("#start_date").val();
    params += "&end_date=" + $("#end_date").val();
    $(".table_charts").html("");
    callAjax("chartReports", params);
}

$(document).ready(function () {
    $(document).on("click", ".back-task-details", function () {
        $(".show-location-map-modal").modal("hide");
        var task_id = $("#map_task_id_ref").val();
        $(".task-id").html(task_id);
        $(".task_id_details").val(task_id);
        $(".task-details-modal").modal("show");
    });

    $(document).on("click", ".open-modal-push", function () {
        $("#driver_id_push").val($(this).data("id"));
        $(".push-form-modal").modal("show");
    });

    $(".push-form-modal").on("shown.bs.modal", function (e) {
        $("#x_push_title").val("");
        $("#x_push_message").val("");
    });

    $.validate({
        language: jsLanguageValidator,
        form: "#frm-send-push",
        onError: function () {},
        onSuccess: function () {
            var params = $("#frm-send-push").serialize();
            var action = $("#frm-send-push #action").val();
            var button = $('#frm-send-push button[type="submit"]');
            dump(button);
            callAjax(action, params, button);
            return false;
        },
    });

    playNotification();
}); /*end doc*/

function initMapDropOffMap() {
    dump("initMapDropOffMap");

    var html = $(".map2_task").html();

    switch (map_provider) {
        case "google":
            map_dropoff = new GMaps({
                div: ".map2_task",
                lat: default_location_lat,
                lng: default_location_lng,
                zoom: 5,
                styles: map_style,
            });

            map_dropoff_marker = map_dropoff.addMarker({
                lat: default_location_lat,
                lng: default_location_lng,
                draggable: true,
            });

            map_dropoff_marker.addListener("dragend", function (event) {
                dump("==>map_dropoff_marker");
                dump("lat=>" + event.latLng.lat());
                dump("long=>" + event.latLng.lng());

                $("#dropoff_task_lat").val(event.latLng.lat());
                $("#dropoff_task_lng").val(event.latLng.lng());

                dump("drag event");
                if ($(".new-task-submit").is(":visible")) {
                    convertLatLongToAddressDropOff(
                        event.latLng.lat(),
                        event.latLng.lng()
                    );
                }
            });

            break;

        case "mapbox":
            mapbox_PlotMapPickup(
                "map2_task",
                default_location_lat,
                default_location_lng
            );
            mapbox_initGeocoderPickup("mapbox_dropoff_address");
            break;
    }
}

$(document).ready(function () {
    if ($(".contact_address").exists()) {
        $(".contact_address")
            .geocomplete({
                country: default_country,
            })
            .bind("geocode:result", function (event, result) {
                $("#addresss_lat").val(result.geometry.location.lat());
                $("#addresss_lng").val(result.geometry.location.lng());
                moveContactMarkers(
                    result.geometry.location.lat(),
                    result.geometry.location.lng()
                );
            });
    }

    /*$( document ).on( "keyup", ".drop_address", function() {
    	 var address=$(this).val();
    	 setMarkeyDropOffMap( $(".drop_address").val() );
    });    */

    if ($(".drop_address").exists()) {
        switch (map_provider) {
            case "google":
                $(".drop_address")
                    .geocomplete({
                        country: default_country,
                    })
                    .bind("geocode:result", function (event, result) {
                        dump(result.geometry.location.lat());
                        dump(result.geometry.location.lng());

                        $("#dropoff_task_lat").val(
                            result.geometry.location.lat()
                        );
                        $("#dropoff_task_lng").val(
                            result.geometry.location.lng()
                        );

                        movePickupMarkers(
                            result.geometry.location.lat(),
                            result.geometry.location.lng()
                        );
                    });

                break;

            case "mapbox":
                break;
        }
    }
}); /*end docu*/

function setMarkeyDropOffMap(address) {
    dump("setMarkeyDropOffMap");

    map_dropoff.removeMarkers();

    if ($(".new-task-submit").is(":visible")) {
        $(".new-task-submit").css({ "pointer-events": "none" });
    }

    GMaps.geocode({
        address: address,
        callback: function (results, status) {
            if (status == "OK") {
                if ($(".new-task-submit").is(":visible")) {
                    $(".new-task-submit").css({ "pointer-events": "auto" });
                }

                var latlng = results[0].geometry.location;
                map_dropoff.setCenter(latlng.lat(), latlng.lng());
                map_dropoff.setZoom(10);

                $("#dropoff_task_lat").val(latlng.lat());
                $("#dropoff_task_lng").val(latlng.lng());

                var map_dropoff_marker = map_dropoff.addMarker({
                    lat: latlng.lat(),
                    lng: latlng.lng(),
                    draggable: true,
                    //icon : map_marker_task
                });

                map_dropoff.setZoom(15);

                map_dropoff_marker.addListener("dragend", function (event) {
                    $("#dropoff_task_lat").val(event.latLng.lat());
                    $("#dropoff_task_lng").val(event.latLng.lng());

                    dump("drag event");
                    if ($(".new-task-submit").is(":visible")) {
                        convertLatLongToAddressDropOff(
                            event.latLng.lat(),
                            event.latLng.lng()
                        );
                    }
                });
            } else {
                if ($(".new-task-submit").is(":visible")) {
                    $(".new-task-submit").css({ "pointer-events": "auto" });
                }
            }
        },
    });
}

function convertLatLongToAddressDropOff(lat, lng) {
    dump("convertLatLongToAddressDropOff");

    if (auto_geo_address != 1) {
        return;
    }

    if (empty(lat) || empty(lng)) {
        return;
    }

    $(".map_task_loader2").show();

    setTimeout(function () {
        var latlng = new google.maps.LatLng(lat, lng);
        var geocoder = (geocoder = new google.maps.Geocoder());
        geocoder.geocode({ latLng: latlng }, function (results, status) {
            dump("GEOCODE =>" + status);
            if (status == google.maps.GeocoderStatus.OK) {
                if (results[1]) {
                    $(".map_task_loader2").hide();
                    dump(results[1].formatted_address);
                    $("#drop_address").val(results[1].formatted_address);
                } else {
                    $(".map_task_loader2").hide();
                }
            } else {
                $(".map_task_loader2").hide();
            }
        });
    }, 300);
}

$(document).ready(function () {
    $(".new-contact").on("hide.bs.modal", function (e) {
        $("#id").val("");
        clearFormElements("#frm");
    });

    //$('.new-contact').on('show.bs.modal', function (e) {
    $(".new-contact").on("shown.bs.modal", function (e) {
        dump("loaded");
        if (!empty($("#id").val())) {
            initContactMap();
            callAjax(
                "getContactInfo",
                "contact_id=" + $("#id").val(),
                $("#frm .orange-button")
            );
        } else {
            initContactMap();
        }
    });

    $(document).on("change", ".contact_list", function () {
        var contact_id = $(this).val();
        if (contact_id == "-1") {
            $("#contact_number").val("");
            $("#email_address").val("");
            $("#customer_name").val("");
            $("#delivery_address").val("");
        } else {
            callAjax("loadContactInfo", "contact_id=" + contact_id);
        }
    });

    $(document).on("change", ".contact_list2", function () {
        var contact_id = $(this).val();
        if (contact_id == "-1") {
            $("#dropoff_contact_name").val("");
            $("#dropoff_contact_number").val("");
            $("#drop_address").val("");
        } else {
            callAjax("loadContactInfo2", "contact_id=" + contact_id);
        }
    });

    if ($("#upload-driver-photo").exists()) {
        var uploader = new ss.SimpleUpload({
            button: "upload-driver-photo", // HTML element used as upload button
            url: ajax_url + "/uploadprofilephoto", // URL of server-side upload handler
            name: "uploadfile", // Parameter name of the uploaded file
            responseType: "json",
            allowedExtensions: ["jpeg", "png", "jpg", "gif"],
            maxSize: 11024, // kilobytes
            onExtError: function (filename, extension) {
                nAlert("Invalid File extennsion", "warning");
            },
            onSizeError: function (filename, fileSize) {
                nAlert("Invalid File size", "warning");
            },
            onSubmit: function (filename, extension) {
                busy(true);
            },
            onComplete: function (filename, response) {
                dump(response);
                busy(false);
                if (response.code == 1) {
                    nAlert(response.msg, "success");
                    $("#profile_photo").val(filename);
                    var image = '<img src="' + response.details + '" />';
                    $(".profile-photo").html(image);
                } else {
                    nAlert(response.msg, "warning");
                }
            },
        });
    }

    if ($("#company-logo").exists()) {
        var uploader = new ss.SimpleUpload({
            button: "company-logo", // HTML element used as upload button
            url: ajax_url + "/uploadCompanyLogo", // URL of server-side upload handler
            name: "uploadfile", // Parameter name of the uploaded file
            responseType: "json",
            allowedExtensions: ["jpeg", "png", "jpg", "gif"],
            maxSize: 11024, // kilobytes
            onExtError: function (filename, extension) {
                nAlert("Invalid File extennsion", "warning");
            },
            onSizeError: function (filename, fileSize) {
                nAlert("Invalid File size", "warning");
            },
            onSubmit: function (filename, extension) {
                busy(true);
            },
            onComplete: function (filename, response) {
                dump(response);
                busy(false);
                if (response.code == 1) {
                    nAlert(response.msg, "success");
                    $("#driver_company_logo").val(filename);
                    var image = '<img src="' + response.details + '" />';
                    $(".company-logo").html(image);
                } else {
                    nAlert(response.msg, "warning");
                }
            },
        });
    }

    if ($(".raty-stars").exists()) {
        initRating();
    }

    if ($(".task-remaining-wrap").exists()) {
        getRemainingTask();
    }

    $(document).on("click", ".dashboard-tab a.tab-a", function () {
        var cur_tab = $(this);
        var tab = $(this).data("id");
        switch (tab) {
            case "tab-task":
                $(".content_1").show();
                $(".content_2").hide();
                $(".content_3").hide();
                break;

            case "tab-map":
                $(".content_1").hide();
                $(".content_2").show();
                $(".content_3").hide();
                break;

            case "tab-agent":
                $(".content_1").hide();
                $(".content_2").hide();
                $(".content_3").show();
                break;
        }
    });
}); /*end doc*/

function initRating() {
    $(".raty-stars").raty({
        readOnly: true,
        noRatedMsg: jslang.no_rated,
        score: function () {
            return $(this).attr("data-score");
        },
        path: website_url + "/assets/raty/images",
        hints: "",
    });
}

function initRating2() {
    $(".ratings_raty").raty({
        readOnly: true,
        noRatedMsg: jslang.no_rated,
        score: function () {
            return $(this).attr("data-score");
        },
        path: website_url + "/assets/raty/images",
        hints: "",
    });
}

var task_remaining_ajax;
var task_remaining_handle;

function getRemainingTask() {
    action = "getRemainingTask";
    params = "";
    task_remaining_ajax = $.ajax({
        url: ajax_url + "/" + action,
        data: params,
        type: "post",
        //async: false,
        dataType: "json",
        timeout: 6000,
        beforeSend: function () {
            if (task_remaining_ajax != null) {
                task_remaining_ajax.abort();
            } else {
            }
        },
        complete: function (data) {
            task_remaining_ajax = (function () {
                return;
            })();
            dump("Completed");
        },
        success: function (data) {
            dump(data);

            clearInterval(task_remaining_handle);
            task_remaining_handle = setInterval(function () {
                getRemainingTask();
            }, 20000);

            if (data.code == 1) {
                $(".task-number-remaining").html(data.details);
                $(".task-remaining-wrap").show();
            } else {
                $(".task-remaining-wrap").show();
                if (data.details == "unlimited") {
                    $(".task-number-remaining").html(jslang.UNLI);
                }
                //$(".task-remaining-wrap").hide();
            }
        },
        error: function (request, error) {
            $(".task-remaining-wrap").hide();
            clearInterval(task_remaining_handle);
        },
    });
}

function initContactMap() {
    dump("initContactMap");

    switch (map_provider) {
        case "google":
            map_contact = new GMaps({
                div: ".map_contact",
                lat: default_location_lat,
                lng: default_location_lng,
                zoom: 5,
                styles: map_style,
            });

            map_contact_marker = map_contact.addMarker({
                lat: default_location_lat,
                lng: default_location_lng,
                draggable: true,
                dragend: function (event) {
                    var lat = event.latLng.lat();
                    var lng = event.latLng.lng();
                    dump(lat + "=>" + lng);
                    $("#addresss_lat").val(lat);
                    $("#addresss_lng").val(lng);
                },
            });

            break;

        case "mapbox":
            mapbox_PlotMap(
                "map_contact",
                default_location_lat,
                default_location_lng
            );
            mapbox_initGeocoderContact("mapbox_delivery_address");
            break;
    }
}

function setMarker(address, lat_id, lng_id) {
    dump(address);
    map_contact.removeMarkers();

    GMaps.geocode({
        address: address,
        callback: function (results, status) {
            if (status == "OK") {
                var latlng = results[0].geometry.location;
                map_contact.setCenter(latlng.lat(), latlng.lng());
                map_contact.setZoom(10);

                $("#" + lat_id).val(latlng.lat());
                $("#" + lng_id).val(latlng.lng());

                var map_contact_marker = map_contact.addMarker({
                    lat: latlng.lat(),
                    lng: latlng.lng(),
                    draggable: true,
                    //icon : map_marker_task
                });

                map_contact.setZoom(15);

                map_contact_marker.addListener("dragend", function (event) {
                    $("#" + lat_id).val(event.latLng.lat());
                    $("#" + lng_id).val(event.latLng.lng());

                    dump("drag event");
                    if ($(".contact-submit").is(":visible")) {
                        convertLatLongToAddressGlobal(
                            event.latLng.lat(),
                            event.latLng.lng(),
                            "contact_address"
                        );
                    }
                });
            } else {
                //
            }
        },
    });
}

function convertLatLongToAddressGlobal(lat, lng, address_field) {
    dump("convertLatLongToAddressGlobal");
    if (auto_geo_address != 1) {
        return;
    }

    if (empty(lat) || empty(lng)) {
        return;
    }

    $(".map_task_loader2").show();

    setTimeout(function () {
        var latlng = new google.maps.LatLng(lat, lng);
        var geocoder = (geocoder = new google.maps.Geocoder());
        geocoder.geocode({ latLng: latlng }, function (results, status) {
            dump("GEOCODE =>" + status);
            if (status == google.maps.GeocoderStatus.OK) {
                if (results[1]) {
                    $(".map_task_loader2").hide();
                    dump(results[1].formatted_address);
                    $("." + address_field).val(results[1].formatted_address);
                } else {
                    $(".map_task_loader2").hide();
                }
            } else {
                $(".map_task_loader2").hide();
            }
        });
    }, 300);
}

function setMarkerEdit(lat, lng, lat_id, lng_id) {
    dump("setMarkerEdit");
    var map_contact_marker = map_contact.addMarker({
        lat: lat,
        lng: lng,
        draggable: true,
    });

    map_contact.setCenter(lat, lng);
    map_contact.setZoom(15);

    map_contact_marker.addListener("dragend", function (event) {
        $("#" + lat_id).val(event.latLng.lat());
        $("#" + lng_id).val(event.latLng.lng());

        dump("drag event");
        convertLatLongToAddressGlobal(
            event.latLng.lat(),
            event.latLng.lng(),
            "contact_address"
        );
    });
}

$(document).ready(function () {
    if ($("#layout_1").exists()) {
        var params = "";
        setTimeout(function () {
            $.ajax({
                url: ajax_url + "/getSMSBalance",
                data: params,
                type: "post",
                dataType: "json",
                timeout: 6000,
                beforeSend: function () {},
                complete: function (data) {},
                success: function (data) {
                    if (data.code == 1) {
                        noty({
                            text: data.msg,
                            type: "error",
                            theme: "relax",
                            layout: "bottomRight",
                            timeout: 20000,
                            animation: {
                                open: "animated fadeInDown", // Animate.css class names
                                close: "animated fadeOut", // Animate.css class names
                            },
                        });
                    }
                },
                error: function (request, error) {},
            });
        }, 3000);
    }
}); /*end docu*/

var track_map;
var track_driver_marker;
var track_interval;
var track_interval_counter = 1;
var track_driver_bounds = [];

$(document).ready(function () {
    if ($(".track-map").exists()) {
        switch (map_provider) {
            case "google":
                track_map = new GMaps({
                    div: ".track-map",
                    lat: default_location_lat,
                    lng: default_location_lng,
                    zoom: 5,
                    styles: map_style,
                });
                break;

            case "mapbox":
                mapbox_PlotMap(
                    "track-map",
                    default_location_lat,
                    default_location_lng
                );
                break;
        }
    }

    $(document).on("change", "#track_driver_id", function () {
        $(".track_replay").hide();
        $(".track-details-wrap").html("");
        clearInterval(track_interval);

        clearTrackMap();

        var selected = $(this).val();
        if (selected >= 1) {
            /*$("#track_date").removeAttr("disabled");
			$(".tr_d").hide();
			$(".track_driver_"+selected).show();
			$("#track_date").val("-1");
			$("#track_date").focus();*/
            callAjax("loadTrackDate", "driver_id=" + selected);
        } else {
            $(".tr_d").hide();
            $("#track_date").attr("disabled", true);
        }
    });

    $(document).on("change", "#track_date", function () {
        /*track_map = new GMaps({
		  div: '.track-map',
		   lat: default_location_lat,
		   lng: default_location_lng	,
		   zoom: 5,
		   styles: map_style
		 }); */

        $(".track_replay").hide();
        $(".track-details-wrap").html("");
        clearInterval(track_interval);

        var selected = $(this).val();
        if (selected != "-1") {
            params = "track_driver_id=" + $("#track_driver_id").val();
            params += "&track_date=" + $("#track_date").val();
            callAjax("loadAgentTrackBack", params);
        } else {
            clearTrackMap();
        }
    });

    $(document).on("click", ".track_replay", function () {
        /*track_map = new GMaps({
		  div: '.track-map',
		   lat: default_location_lat,
		   lng: default_location_lng	,
		   zoom: 5,
		   styles: map_style
		 }); */

        params = "track_driver_id=" + $("#track_driver_id").val();
        params += "&track_date=" + $("#track_date").val();
        callAjax("loadAgentTrackBack", params);
    });

    $(document).on("change", "#change_language", function () {
        var selected = $("#change_language :selected").text();
        if (selected <= 0) {
            return;
        }
        url =
            home_url +
            "/setlang/?lang=" +
            selected +
            "&action=" +
            $("#action_name").val();
        window.location.href = url;
    });
}); /*end docu*/

function trackBackMarker(data) {
    $(".track_replay").hide();
    var total = parseInt(data.length);
    if (total <= 0) {
        return;
    }

    dump("total=>" + total);
    dump("track_interval_counter=>" + track_interval_counter);

    $(".track-details-wrap").html("");

    track_driver_bounds = [];

    track_interval = setInterval(function () {
        var current_data = data[track_interval_counter - 1];
        dump("current_data");
        dump(current_data);

        lat = current_data.latitude;
        lng = current_data.longitude;

        $(".track-details-wrap").append(
            "<p>" +
                jslang.lat +
                ":" +
                lat +
                " , " +
                jslang.lng +
                ":" +
                lng +
                "</p>"
        );

        switch (map_provider) {
            case "google":
                if (empty(track_driver_marker)) {
                    track_driver_marker = track_map.addMarker({
                        lat: lat,
                        lng: lng,
                        icon: icon_driver,
                    });
                    latlng = toLatLng(lat, lng);
                    track_driver_bounds.push(latlng);
                    centerTrackMap();
                } else {
                    moveTrackMarkers(lat, lng);

                    var prev_data = data[total - track_interval_counter];
                    track_map.drawRoute({
                        origin: [prev_data.latitude, prev_data.longitude],
                        destination: [lat, lng],
                        travelMode: "driving",
                        strokeColor: "#ccc",
                        strokeOpacity: 0.6,
                        strokeWeight: 6,
                    });
                }

                break;

            case "mapbox":
                mapbox_moveMapMarker(lat, lng);
                break;
        }

        if (track_interval_counter >= total) {
            track_interval_counter = 0;
            $(".track_replay").css({
                display: "block",
            });
            clearInterval(track_interval);
        }
        track_interval_counter++;
    }, 1000);
}

$(document).ready(function () {
    $(document).on("click", ".mobile-nav-menu", function () {
        $(".parent-wrapper .content_1.white").toggle("fast", function () {
            if (
                $(this).attr("style") == "display: block;" ||
                $(this).attr("style") == "display:block;"
            ) {
                $(".content_main").addClass("margin-left");
            } else {
                $(".content_main").removeClass("margin-left");
            }
        });
    });
});
/*end docu*/

/*VERSION 1.4*/
setMapCenter = function () {
    switch (map_provider) {
        case "google":
            map.fitLatLngBounds(bounds);
            break;

        case "mapbox":
            mapbox_fitMap();
            break;
    }
};

getTaskIcon = function () {
    map_marker_task = map_marker_delivery;
    if ($(".trans_type").exists()) {
        if ($(".trans_type:checked").val() == "pickup") {
            map_marker_task = map_pickup_icon;
        }
    }
    return map_marker_task;
};

moveDeliveryMarker = function (lat, lng) {
    delivery_map_marker.setPosition(new google.maps.LatLng(lat, lng));
};

moveDeliveryMarkers = function (lat, lng) {
    moveDeliveryMarker(lat, lng);
    delivery_map_bounds = [];
    var latlng = toLatLng(lat, lng);
    delivery_map_bounds.push(latlng);
    centerDeliveryMap();
};

movePickupMarker = function (lat, lng) {
    map_dropoff_marker.setPosition(new google.maps.LatLng(lat, lng));
};

movePickupMarkers = function (lat, lng) {
    movePickupMarker(lat, lng);
    map_dropoff_bounds = [];
    var latlng = toLatLng(lat, lng);
    map_dropoff_bounds.push(latlng);
    centerPickupMap();
};

centerDeliveryMap = function () {
    primary_map.fitLatLngBounds(delivery_map_bounds);
    primary_map.setZoom(16);
};

centerPickupMap = function () {
    map_dropoff.fitLatLngBounds(map_dropoff_bounds);
    map_dropoff.setZoom(16);
};

centerContactMap = function () {
    map_contact.fitLatLngBounds(map_contact_bounds);
    map_contact.setZoom(16);
};

toLatLng = function (lat, lng) {
    var latlng = new google.maps.LatLng(lat, lng);
    return latlng;
};

moveContactMarkers = function (lat, lng) {
    map_contact_marker.setPosition(new google.maps.LatLng(lat, lng));
    map_contact_bounds = [];
    var latlng = toLatLng(lat, lng);
    map_contact_bounds.push(latlng);
    centerContactMap();
};

var activity_tracking;
var ajax_activity;

$(document).ready(function () {
    if ($(".dashboard").exists()) {
        if (disabled_activity_tracking != 1) {
            activity_tracking = setInterval(function () {
                checkActivity();
            }, activity_tracking_interval);
        }
    }
}); /* END DOCU*/

checkActivity = function () {
    action = "checkActivity";
    params = "";

    ajax_activity = $.ajax({
        url: ajax_url + "/" + action,
        data: params,
        method: "post",
        dataType: "json",
        timeout: 30000,
        crossDomain: true,
        beforeSend: function (xhr) {},
    });

    ajax_activity.done(function (data) {
        //dump("done ajax");
        if (data.code == 1) {
            dump("THERE IS ACTIVITY");
            loadDashboardTaskSilent();
            loadAgentDashboardSilent();
        } else {
            // do nothing
            dump("NO ACTIVITY");
        }
    });

    ajax_activity.always(function () {
        //dump("ajax always");
        clearInterval(activity_tracking);
        activity_tracking = setInterval(function () {
            checkActivity();
        }, activity_tracking_interval);
    });

    ajax_activity.fail(function (jqXHR, textStatus) {
        dump("failed ajax " + textStatus);
    });
};

moveTrackMarkers = function (lat, lng) {
    track_driver_marker.setPosition(new google.maps.LatLng(lat, lng));
    var latlng = toLatLng(lat, lng);
    track_driver_bounds.push(latlng);
    centerTrackMap();
};

centerTrackMap = function () {
    track_map.fitLatLngBounds(track_driver_bounds);
};

clearTrackMap = function () {
    switch (map_provider) {
        case "google":
            track_map.removeMarkers();
            track_driver_marker = "";
            track_map.cleanRoute();
            break;

        case "mapbox":
            if (!empty(mapbox_marker)) {
                mapbox.removeLayer(mapbox_marker);
                mapbox_marker = "";
            }
            break;
    }
};

getParamsMap = function () {
    params = "status=unassigned&date=" + $(".calendar_formated").val();
    params += "&team_id=" + $("#team").val();
    return params;
};

$(document).ready(function () {
    $(".modalMapFilter").on("show.bs.modal", function (e) {
        dump("modalMapFilter show");
        callAjax("loadFilterForm", "");
    });

    $(".modalMapFilter").on("hide.bs.modal", function (e) {
        dump("modalMapFilter hide");
    });
}); /* END DOCU*/
