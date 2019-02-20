function add_window_report() {
    add_email_window_width_closefunc_ex({'id':'mail_policy_report','funcName':'name_bind_check()'});
    $("#mail_policy_report").combo('hidePanel');
}

function add_report_window() {

    if ($('#wwreport').length <= 0) {
        $(document.body).append("<div id='wwreport' class='ngtos_window'></div>"); //创建空div
    }
    open_window('wwreport', 'waf_report ', 'add_report_window', jslang.common.btn_add, 'true', add_report); //打开弹出框
    //$("#report_name").next('span').children('input :first').focus()
}

function edit_report_window() {

    if ($('#wwreport').length <= 0) {
        $(document.body).append("<div id='wwreport' class='ngtos_window'></div>"); //创建空div
    }
    open_window('wwreport', 'waf_report ', 'add_report_window', jslang.common.btn_edit, 'true', edit_report); //打开弹出框
    $("#report_name").next('span').children('input :first').focus()
}
function init_module_attack_report() {
    $("#tr_attack_type").show();
    $('#module_attack_report').attr('checked', 'checked');
    $('#module_traffic_report').attr('checked', 'checked');
    $('#module_antitamper_report').attr('checked', 'checked');
    $('#module_ddos_report').attr('checked', 'checked');
    $('#type_eventtype_report').attr('checked', 'checked');
    $('#type_source_report').attr('checked', 'checked');
    $('#type_urlattack_report').attr('checked', 'checked');
    $('#type_ruleid_report').attr('checked', 'checked');
    $('#type_action_report').attr('checked', 'checked');
}

function check_attack_module(){
    if($('#module_attack_report').attr('checked')){
         $("#tr_attack_type").show();
         setenable_addRe();
         
    }else{
         $("#tr_attack_type").hide();
         setenable_addRe();
    }
}

function setenable_addRe(){
     if($('#module_attack_report').attr('checked') == 'checked' && $('#type_eventtype_report').attr('checked') == undefined && $('#type_source_report').attr('checked') == undefined && $('#type_ruleid_report').attr('checked') == undefined && $('#type_urlattack_report').attr('checked') == undefined && $('#type_action_report').attr('checked') == undefined){
            setItemEnable('addRe', false);
        }else{
            setItemEnable('addRe', true);
        }
}

function show_modle_attack_report(module,attack) {
    var moduleArray = module.split(',');
    var attackArray = attack.split(',');
    for(var i=0; i<moduleArray.length; i++) {
        if(moduleArray[i]=='attack') {
            $('#module_attack_report').attr('checked', 'checked');
            $("#tr_attack_type").show();
        } else if(moduleArray[i]=='traffic') {
            $('#module_traffic_report').attr('checked', 'checked');
        } else if(moduleArray[i]=='anti-tamper') {
            $('#module_antitamper_report').attr('checked', 'checked');
        } else if(moduleArray[i]=='ddos') {
            $('#module_ddos_report').attr('checked', 'checked');
        }
    }

    for(var j=0; j<attackArray.length; j++) {
        if(attackArray[j]=="event-type") {
            $('#type_eventtype_report').attr('checked', 'checked');
        } else if(attackArray[j]=="source") {
            $('#type_source_report').attr('checked', 'checked');
        } else if(attackArray[j]=="rule-id") {
            $('#type_ruleid_report').attr('checked', 'checked');
        } else if(attackArray[j]=="URL-attack") {
            $('#type_urlattack_report').attr('checked', 'checked');
        } else if(attackArray[j]=="action") {
            $('#type_action_report').attr('checked', 'checked');
        }
    }
}
function show_start_end_time_report(startTime, endTime) {
    $('#dt_start_time_report').datetimebox('setValue', startTime);
    $('#dt_end_time_report').datetimebox('setValue', endTime);
}
function add_report() {
    form_reset_report();
	$('#server_policy_tr_report').css('display','none');
	$('#server_policy_all_report').attr('checked',true);
    init_module_attack_report();
	show_email_title_body($("#report_lan input[name='report_lan']:checked").val());
    setIdFocus('report_name');
    setItemEnable('addRe', false);
    $('#report_type_pdf').attr('checked', 'checked');
    //$('#report_type_xml').attr('checked','checked');
    $('#addRe').attr('disabled', 'true');


    $("#addRe").click(function() {
		var $lan = $('#report_lan input[name="report_lan"]:checked').val()
		var $email = initial_email_title_body_selected($lan);
		var e = $email[1].textbox('getValue');
        e = encodeURIComponent(e);
        e = e.replace(/'/g, '&wafsqm');
        $email[1].textbox('setValue', '');
        var m = $email[0].textbox('getValue');
        m = encodeURIComponent(m);
        m = m.replace(/'/g, '&wafsqm')
        $email[0].textbox('setValue', '');
        $('#ffre').form({
            url: '?module=waf_report&action=add_report',
            queryParams: { email_body: e, email_title: m, report_lan: $lan },
            success: function(data) {
                var obj = jQuery.parseJSON(data);
                if (obj['type'] == 1) {
                    $('#wwreport').window('close')
                    $('#ffre').form('clear');
                    $('#ttre').datagrid('reload');
                } else if (obj['type'] == 2) {
                    e = decodeURIComponent(e);
                    e = e.replace(/&wafsqm/g, "'")
                    m = decodeURIComponent(m);
                    m = m.replace(/&wafsqm/g, "'")
                    $email[1].textbox('setValue', e);
                    $email[0].textbox('setValue', m);
                    ngtosPopMessager("error", jslang.common.login_timeout, "login");
                } else {
                    e = decodeURIComponent(e);
                    e = e.replace(/&wafsqm/g, "'")
                    m = decodeURIComponent(m);
                    m = m.replace(/&wafsqm/g, "'")
                    $email[1].textbox('setValue', e);
                    $email[0].textbox('setValue', m);
                    ngtosPopMessager("error", obj['info']);
                }
            }
        });
    })
   



}

function form_reset_report() {
    var combotoolbar_report = "<div id=\"combobox-toolbar\" style=\"border:#ABAFB8 1px solid;" +
        "background: #d3d3d3;padding:2px 0\"><a href=\"#\" class=\"easyui-linkbutton\" " +
        "data-options=\"iconCls:'icon-add',plain:true\"onClick=\"add_window_report();\"><span class=\"l-btn-left l-btn-icon-left\"><span class=\"l-btn-text\">" + jslang.common.btn_add +
        "</span><span class=\"l-btn-icon icon-add\"> </span></span></a></div>"
	
	$('#server_policy_report').combobox({
		url: '?module=waf_report&action=get_all_server_policy',
        valueField: 'text',
        textField: 'text',
		panelHeight: 'auto',
        panelMaxHeight: 198,
		editable:false
	});
    $("#monthday_value_report").combobox({
        url: '?module=waf_report&action=get_all_monthdays',
        multiple: true,
        multiline: true,
        valueField: 'value',
        textField: 'text',
		panelHeight:'auto',
        panelMaxHeight:198,
        editable: false
    })
    $("#report_top_n").combobox({
        url: '?module=waf_report&action=get_top_num',
        valueField: 'value',
        textField: 'text',
        panelHeight:'auto',
        panelMaxHeight:198,
        editable: false
    })
    $("#mail_policy_report").combobox({
        url: '?module=waf_email&action=get_all_email_name',
        valueField: 'text',
        textField: 'text',
        panelHeight: 'auto',
        panelMaxHeight: 198,
        editable: false
    })
     //$("#server_policy_report").combobox({
    //    url:'?module=waf_report&action=get_all_server_name',
    //    valueField:'text',
    //    textField:'text',
    //    panelHeight:'auto',
    //    panelMaxHeight:198,
    //    editable:false
    //});
    $("#mail_policy_report").combo('panel').after(combotoolbar_report);
    $('#weekday_report').hide()
    $('#attime_report').hide()
    $("#report_name").textbox('enable')
    $("#report_name").textbox('setValue', '')
    change_type_report('none');
	change_report_lan($.cookie("language"));
    $('#report_treport').find('input:checkbox').removeAttr('checked');
    $('#weekday_report').find('input:checkbox').removeAttr('checked');
    $("#server_policy_single_report").bind('click',function(){set_server_policy_combobox('single')});
	$("#server_policy_all_report").bind('click',function(){set_server_policy_combobox('all')});
	$("input[name='report_lan']").bind('click',function(){
		show_email_title_body($(this).val());
	});
	set_email_title_body();
	$("#mail_policy_report").combobox('setValue', jslang.alarm.mail_policy_alarm)
    $("#mail_policy_report").combobox('reload');
    $('#one_report').attr('checked', 'checked');

    $('#at_time_report').timespinner('setValue', '08:00:00');
    var curDate = new Date();
    var preDate = new Date(curDate.getTime() - 60*60*1000);  //前一天
    var vEnd = curDate.getMonth()+1+'/'+curDate.getDate()+'/'+curDate.getFullYear()+' '+curDate.getHours()+':00:00';
    var vStart = preDate.getMonth()+1+'/'+preDate.getDate()+'/'+preDate.getFullYear()+' '+preDate.getHours()+':00:00';
    $('#dt_end_time_report').datetimebox('setValue',vEnd);
    $('#dt_start_time_report').datetimebox('setValue', vStart);
    $('#monthday_value_report').combobox('setValues', '1');
    $('#report_top_n').combobox('setValue', '10');
    $("#addRe").unbind('click')
    $("#report_s").css('display', 'none');

    initValidateboxStyle(check_report);
	name_bind_check();
    search();
    $("#tr_attack_type").hide();
    getHelpInformation("policy_report", getHelpInfoReport, 1);
}
function name_bind_check()
{
	$('#report_name').next('span').children('input :first').bind('input', function() {
        check_report();
    })
}
function getHelpInfoReport(msg) {
    var helpInforTemp = "<img src=\"static/images/icons/help_info.png\" style=\"vertical-align:middle;cursor: pointer; margin-left: 20px\" ";
    var itemArray = new Array();
    itemArray.push($("#report_name").next('span'));
    itemArray.push($("#report_month"));
    itemArray.push($("#report_s"));
    itemArray.push($("#email_title").next('span'));
    itemArray.push($("#email_body").next('span'));
    itemArray.push($("#mail_policy_report").next('span'));
    for (var i = 0; i < itemArray.length; i++) {
        if (msg[i] != "") {
            var helpInfor = helpInforTemp + "title=" + msg[i] + ">";
            itemArray[i].after(helpInfor);
        }
    }
}

function edit_report() {
    form_reset_report();

    setItemEnable('addRe', true);
    var rows = $('#ttre').datagrid('getSelections');


    $('#report_name').textbox('setValue', rows[0].name);
    $('#report_name').textbox('disable');
    $('#report_top_n').combobox('setValues',  rows[0].topn.split(','));
    if (rows[0].schedule == 'none') {
        change_type_report('none');
        $('#schedule_none').attr('checked', 'checked');
        $('#at_time_report').timespinner('setValue', rows[0].attime);
        $('#start_time_report').css('display','table-row');
        $('#end_time_report').css('display','table-row');


    } else if (rows[0].schedule == 'daily') {
        change_type_report('daily');
        $('#schedule_daily').attr('checked', 'checked');
        $('#at_time_report').timespinner('setValue', rows[0].attime);
        $('#start_time_report').hide();
        $('#end_time_report').hide();


    } else if (rows[0].schedule == 'weekly') {
        change_type_report('weekly');
        $('#schedule_weekly').attr('checked', 'checked');

        $('#at_time_report').timespinner('setValue', rows[0].attime);
        show_weekday_report(rows[0].weekdays);
        $('#start_time_report').hide();
        $('#end_time_report').hide();

    } else if (rows[0].schedule == 'monthly') {
        change_type_report('monthly');
        $('#schedule_monthly').attr('checked', 'checked');

        $('#at_time_report').timespinner('setValue', rows[0].attime);

        $('#monthday_value_report').combobox('setValues', rows[0].monthdays.split(','));
        $('#start_time_report').hide();
        $('#end_time_report').hide();
    }
      $.post("?module=waf_report&action=show_single_report",{'name':rows[0].name},function(data){
        if(data.indexOf("parent.window.location")>=0){
            ngtosPopMessager("error", jslang.commmon.login_timeout,  "login")
        }else if(data.indexOf("error -")>=0){
            ngtosPopMessager("error",  jslang.commmon.get_info_failed);
        }else{
            var obj = jQuery.parseJSON(data);
            show_modle_attack_report(obj['rows'][0].module,obj['rows'][0].attack_rank);
            if(obj['rows'][0].schedule=='none') {
                show_start_end_time_report(obj['rows'][0]['start_time'],obj['rows'][0]['end_time'])
            }
			if(obj['rows'][0]['server'] == 'single')
			{
				$('#server_policy_single_report').attr('checked',true);
				set_server_policy_combobox('single');
				$('#server_policy_report').combobox('setValue',obj['rows'][0]['server_policy']);
			}else{
				$('#server_policy_all_report').attr('checked',true);
				set_server_policy_combobox('all');
			}
        }
    });
    if (rows[0].language == 'english') {
        change_report_lan('en');
    } else {
        change_report_lan('cn');
    }
	show_email_title_body(rows[0].language);
    //$('#mypanel').find('#report_type').val(formats);//html,xml
    var t_temp1 = rows[0].formats.split(',');

    for (var i = 0; i < t_temp1.length; i++) {
        if (t_temp1[i] == 'html') {
            $('#report_type_html').attr('checked', 'checked');
        } else if (t_temp1[i] == 'pdf') {
            $('#report_type_pdf').attr('checked', 'checked');
        }
        /* else if (t_temp1[i] == 'txt') {
                    $('#report_type_txt').attr('checked', 'checked');
                }else if (t_temp1[i] == 'xml') {
                    $('#report_type_xml').attr('checked', 'checked');
                }*/
    }
	var $email = initial_email_title_body_selected(rows[0].language);
    //给重定向url赋值
    var title = rows[0].title;
    title = title.replace(/&wafsqm/g, "'");
	$email[0].textbox('setValue', title);
    var body = rows[0].body;
    body = body.replace(/&wafsqm/g, "'");
    $email[1].textbox('setValue', body);
    var mail = rows[0].mail_policy == '' ? jslang.alarm.mail_policy_alarm : rows[0].mail_policy;
    $("#mail_policy_report").combobox('setValue', mail);
    $("#addRe").click(function() {
		var $email_final = initial_email_title_body_selected($("#report_lan input[name='report_lan']:checked").val());
        var e = $email_final[1].textbox('getValue');
        e = encodeURIComponent(e);
        e = e.replace(/'/g, '&wafsqm');
        $email_final[1].textbox('setValue', '');
		var m = $email_final[0].textbox('getValue');
        m = encodeURIComponent(m);
        m = m.replace(/'/g, '&wafsqm');
        $email_final[0].textbox('setValue', '');

        $('#ffre').form({
            url: '?module=waf_report&action=modify_report',
            queryParams: {
                name: rows[0].name,
                email_body: e,
                email_title: m
            },
            success: function(data) {
                var obj = jQuery.parseJSON(data);

                if (obj['type'] == 1) {
                    $('#wwreport').window('close')
                    $('#ffre').form('clear');
                    $('#ttre').datagrid('reload');
                } else if (obj['type'] == 2) {
                    e = decodeURIComponent(e);
                    e = e.replace(/&wafsqm/g, "'")
                    m = decodeURIComponent(m);
                    m = m.replace(/&wafsqm/g, "'")
                    $email_final[1].textbox('setValue', e);
                    $email_final[0].textbox('setValue', m);
                    ngtosPopMessager("error", jslang.common.login_timeout, "login");

                } else {
                    e = decodeURIComponent(e);
                    e = e.replace(/&wafsqm/g, "'")
                    m = decodeURIComponent(m);
                    m = m.replace(/&wafsqm/g, "'")
                    $email_final[1].textbox('setValue', e);
                    $email_final[0].textbox('setValue', m);
                    ngtosPopMessager("error", obj['info']);
                }
            }
        });
    })
}

function delete_btn_report() {
    var rows = $('#ttre').datagrid('getSelections');
    if (rows.length == 0)
        ngtosPopMessager('info', jslang.common.choose_row_delete)
    else {
        ngtosPopMessager("confirm", jslang.common.confirm_delete, function(r) {
            if (r) {
                delete_report()
            }
        })
    }
}

function delete_report() {
    //ajax 删除
    var ids1 = [];

    var rows = $('#ttre').datagrid('getSelections');
    for (var i = 0; i < rows.length; i++) {
        ids1.push(rows[i].name);
    }

    var name = ids1.join(';');

    $.ajax({
        url: "?module=waf_report&action=delete_report",
        type: 'POST',
        datatype: 'text',
        data: {
            name: name
        },
        success: function(data, textStatus) {
            var obj = jQuery.parseJSON(data);
            if (obj['type'] == 1) {
                // $('#ttre').datagrid('reload');
                datagridDeleteReload("ttre");
            } else if (obj['type'] == 2) {
                ngtosPopMessager("error", jslang.common.login_timeout, "login");
            } else {
                ngtosPopMessager("error", obj['info']);
            }
        }
    });
}


function clear_btn() {
    ngtosPopMessager("confirm", jslang.common.confirm_clean, function(r) {
        if (r) {

            $.ajax({
                url: "?module=waf_report&action=clear_report",
                type: 'POST',
                datatype: 'text',
                success: function(data, textStatus) {

                    if (data == 'ok') {
                        $('#ttre').datagrid('reload');
                    } else if (data == 2) {
                        ngtosPopMessager("error", jslang.common.login_timeout, "login");
                    } else {
                        ngtosPopMessager("error", data);
                        return false;
                    }
                }
            });

        }
    })
}

function change_type_report(type) {
    //alert(type);
    if (type == 'none') {
        $('#schedule_none').attr('checked', 'checked');
        //alert($('#schedule_none').val());
        $('#weekday_report').hide()
        $('#attime_report').hide()
        $("#monthday_report").hide()
         $('#start_time_report').css('display','table-row');
        $('#end_time_report').css('display','table-row');



    } else if (type == 'daily') {
        $('#schedule_daily').attr('checked', 'checked');
        $('#weekday_report').hide()

        $('#attime_report').show()
        $("#monthday_report").hide()
         $('#start_time_report').hide();
        $('#end_time_report').hide();

    } else if (type == 'weekly') {
        $('#schedule_weekly').attr('checked', 'checked');
        $('#weekday_report').show()
        $('#attime_report').show()
        $("#monthday_report").hide()
         $('#start_time_report').hide();
        $('#end_time_report').hide();

    } else if (type == 'monthly') {
        $('#schedule_monthly').attr('checked', 'checked');
        $('#weekday_report').hide()
        $('#attime_report').show()
        $("#monthday_report").show()
         $('#start_time_report').hide();
        $('#end_time_report').hide();

    }

}



//操作按钮
function set_toolbar_report() {
    var crows = $('#ttre').datagrid('getSelections');

    //编辑按钮逻辑
    if (crows.length == 1) {
        $('#report_edit').linkbutton('enable');
        $('#report_download').linkbutton('enable');

    } else {
        $('#report_edit').linkbutton('disable');
        $('#report_download').linkbutton('disable');
    }

    //删除按钮逻辑
    if (crows.length > 0) {
        $('#report_delete').linkbutton('enable');
    } else {
        $('#report_delete').linkbutton('disable');
    }


}

function Select() {

    var value1 = $('#mail_policy_report').combobox('getValue');

    if (value1 == jslang.alarm.mail_policy_alarm_new) {

        add_email_window_width_closefunc_ex({'id':'mail_policy_report','funcName':'name_bind_check()'});
        $('#mail_policy_report').combobox('setValue', '');
    }

}
//列表搜索框


function search_data_report() {

    if ($.trim($("#addName").val()) == '') {
        $("#ttre").datagrid({
            url: '?module=waf_report&action=showall_report'

        })
    } else {
        $("#ttre").datagrid({
            url: "?module=waf_report&action=show_search&name=" + $('#addName').val()

        })
        $("#ttre").datagrid('getPager').pagination('select', 1);
    }
}

function show_weekday_report(weekday) {
    var s_temp = weekday.split(',');
    //var s='';
    $('#one_report').attr('checked', false);

    for (var i = 0; i < s_temp.length; i++) {
        if (s_temp[i] == 'mon') {
            $('#one_report').attr('checked', 'checked');
        } else if (s_temp[i] == 'tue') {
            $('#two_report').attr('checked', 'checked');
        } else if (s_temp[i] == 'wed') {
            $('#three_report').attr('checked', 'checked');
        } else if (s_temp[i] == 'thu') {
            $('#four_report').attr('checked', 'checked');
        } else if (s_temp[i] == 'fri') {
            $('#five_report').attr('checked', 'checked');
        } else if (s_temp[i] == 'sat') {
            $('#six_report').attr('checked', 'checked');
        } else if (s_temp[i] == 'sun') {
            $('#seven_report').attr('checked', 'checked');
        }

    }
}

function check_report() {

    var no = $("#report_name").textbox('getText');
    /*
    var chkbs = document.getElementsByName("report_type[]");
    var chkNum= 0;

    $(chkbs).each(function(index){

        if ($(this).attr('checked')){
            chkNum++;
        }
    })
    */
    if ($("#report_name").textbox('isValid') /*&&chkNum!=0*/ ) {

        setItemEnable('addRe', true);
        //$("#report_s").css('display','none');

    } else {
        /*
        if(chkNum!=0){
            $("#report_s").css('display','none');
        }else{
            $("#report_s").css('display','inline');
        };
        */
        setItemEnable('addRe', false);

    }
};


function change_report_lan(lan) {
    if (lan == "cn") {
        $("#report_cn").attr("checked", "checked");
    } else {
        $("#report_en").attr("checked", "checked");
    }
}
function set_email_title_body(){
	$("#email_title").textbox('setValue', jslang.report.email_title_cn);
	$("#email_body").textbox('setValue', jslang.report.email_body_cn);
	$("#email_title_en").textbox('setValue', jslang.report.email_title_en);
	$("#email_body_en").textbox('setValue', jslang.report.email_body_en);
}
function show_email_title_body(lan){
	if(lan == 'cn' || lan == 'chinese')
	{
		$('.email_en').hide();
		$('.email').show();
    }else{
		$('.email_en').show();
		$('.email').hide();
	}
}

function initial_email_title_body_selected($lan){
	var $email_title_selected = '';
	var $email_body_selected = '';
	var $email_selected_array = new Array();
	if($lan == 'english')
	{
		$email_title_selected = $("#email_title_en");
		$email_body_selected = $("#email_body_en");
	}else{
		$email_title_selected = $("#email_title");
		$email_body_selected = $("#email_body");
	}
	$email_selected_array.push($email_title_selected);
	$email_selected_array.push($email_body_selected);
	return $email_selected_array;
}
function set_server_policy_combobox(show_type)
{
	if(show_type == 'single' && $('#server_policy_tr_report').css('display') == 'none')
	{
		$('#server_policy_tr_report').css('display','table-row')
	}
	if(show_type == 'all' && $('#server_policy_tr_report').css('display') == 'table-row')
	{
		$('#server_policy_tr_report').css('display','none')
	}
}