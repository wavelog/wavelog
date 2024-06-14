<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
___________________________________________________________________________________________
Station Logbooks
___________________________________________________________________________________________
*/

$lang['station_logbooks'] = "台站日志";
$lang['station_logbooks_description_header'] = "什么是台站日志";
$lang['station_logbooks_description_text'] = "台站日志可以让您对自己的台站位置进行分组，这对在同一 DXCC 或 VUCC 下的不同站点位置非常方便";
$lang['station_logbooks_create'] = "新建台站日志";
$lang['station_logbooks_status'] = "状态";
$lang['station_logbooks_link'] = "链接";
$lang['station_logbooks_public_search'] = "公开搜索";
$lang['station_logbooks_set_active'] = "设置为正在使用的日志";
$lang['station_logbooks_active_logbook'] = "正在使用的日志";
$lang['station_logbooks_edit_logbook'] = "编辑台站日志";    // Full sentence will be generated 'Edit Station Logbook: [Logbook Name]'
$lang['station_logbooks_confirm_delete'] = "确定删除此台站日志？你可能需要重新将台站位置链接到其他台站日志中: ";
$lang['station_logbooks_view_public'] = "浏览日志公开页: ";
$lang['station_logbooks_create_name'] = "台站日志名称";
$lang['station_logbooks_create_name_hint'] = "你可以随意称呼你的台站日志";
$lang['station_logbooks_edit_name_hint'] = "台站位置简称，例如：Home Log (HM54ip)";
$lang['station_logbooks_edit_name_update'] = "更新台站日志名称";
$lang['station_logbooks_public_slug'] = "自定义日志链接";
$lang['station_logbooks_public_slug_hint'] = "通过自定义日志链接，你可以通过此链接让别人访问你的日志";
$lang['station_logbooks_public_slug_format1'] = "他将会看起来像这样：";
$lang['station_logbooks_public_slug_format2'] = "[个性地址]";
$lang['station_logbooks_public_slug_input'] = "输入个性地址";
$lang['station_logbooks_public_slug_visit'] = "访问公开页面";
$lang['station_logbooks_public_search_hint'] = "启用可为日志本提供公开搜索功能，范围仅限此日志本。";
$lang['station_logbooks_public_search_enabled'] = "已启用公开搜索";
$lang['station_logbooks_select_avail_loc'] = "选择可用台站位置";
$lang['station_logbooks_link_loc'] = "链接的台站位置";
$lang['station_logbooks_linked_loc'] = "已链接的台站位置";
$lang['station_logbooks_no_linked_loc'] = "未链接的台站位置";
$lang['station_logbooks_unlink_station_location'] = "取消链接的台站位置";



/*
___________________________________________________________________________________________
Station Locations
___________________________________________________________________________________________
*/

$lang['station_location'] = '台站地址';
$lang['station_location_plural'] = "台站地址";
$lang['station_location_header_ln1'] = '台站地址为电台使用地址，如您或朋友的 QTH，或移动台的地址';
$lang['station_location_header_ln2'] = '和日志簿类似，一个台站地址为 QSO 集合的承载主体';
$lang['station_location_header_ln3'] = '同一时间只允许启用一个台站地址，详见如下表格中的 -启用- 标志';
$lang['station_location_create_header'] = '创建台站地址';
$lang['station_location_create'] = '创建一个台站地址';
$lang['station_location_edit'] = '修改台站地址：';
$lang['station_location_updated_suff'] = ' 已更新';
$lang['station_location_warning'] = '警告：应事先设置一个\'启用\'的台站地址，请在 \'呼号\'->\'台站地址\'中选择一个';
$lang['station_location_reassign_at'] = '重新分配 ';
$lang['station_location_warning_reassign'] = '由于 Wavelog 设置更改，您需要重新在电台设置中重新分配 QSO';
$lang['station_location_id'] = 'ID';
$lang['station_location_name'] = '名称';
$lang['station_location_name_hint'] = '自定义电台名称例如：家 (HM54ip)';
$lang['station_location_callsign'] = '电台呼号';
$lang['station_location_callsign_hint'] = '电台呼号，例如：4W7EST/P';
$lang['station_location_power'] = '电台功率 (W)';
$lang['station_location_power_hint'] = '默认电台功率 (W)，如有 CAT 信息会覆盖此设置';
$lang['station_location_emptylog'] = '日志为空';
$lang['station_location_confirm_active'] = '确认将如下台站设为启用状态：';
$lang['station_location_set_active'] = '设置启用';
$lang['station_location_active'] = '启用的台站';
$lang['station_location_claim_ownership'] = '宣称所有权';
$lang['station_location_confirm_del_qso'] = '确认删除台站下的所有 QSO？';
$lang['station_location_confirm_del_stationlocation'] = '确认删除该台站 ';
$lang['station_location_confirm_del_stationlocation_qso'] = '此操作会删除所有该台站的 QSO';
$lang['station_location_dxcc'] = '台站 DXCC';
$lang['station_location_dxcc_hint'] = '台站的 DXCC 实体，例如 China';
$lang['station_location_dxcc_warning'] = "请稍等，您选择的 DXCC 已经失效，请确认最新的 DXCC 实体，如果您十分确认，请忽略该警告。";
$lang['station_location_city'] = '台站城市';
$lang['station_location_city_hint'] = '台站城市，例如 Beijing';
$lang['station_location_state'] = '台站州/省';
$lang['station_location_state_hint'] = '台站的州或省，如国家不适用请留空';
$lang['station_location_county'] = '台站县';
$lang['station_location_county_hint'] = '台站的县 (仅用于 美国本土/阿拉斯加/夏威夷)';
$lang['station_location_gridsquare'] = '台站网格地址';
$lang['station_location_gridsquare_hint_ln1'] = "台站网格地址，例如 HM54ip，可在 <a href='https://zone-check.eu/?m=loc' target='_blank'>这里</a> 查询自己的网格";
$lang['station_location_gridsquare_hint_ln2'] = "如果处在网格线上，请输入逗号分隔的多个网格，例如：IO77,IO78,IO87,IO88";
$lang['station_location_iota_hint_ln1'] = "台站 IOTA 标识代码，例如 EU-005";
$lang['station_location_iota_hint_ln2'] = "查看 IOTA 名录：<a target='_blank' href='https://www.iota-world.org/iota-directory/annex-f-short-title-iota-reference-number-list.html'>IOTA World</a>";
$lang['station_location_sota_hint_ln1'] = "台站 SOTA 标识代码，查看全部 SOTA：<a target='_blank' href='https://www.sotamaps.org/'>SOTA Maps</a>";
$lang['station_location_wwff_hint_ln1'] = "台站 WWFF 标识代码，查看 <a target='_blank' href='https://www.cqgma.org/mvs/'>GMA Map</a>";
$lang['station_location_pota_hint_ln1'] = "台站 POTA 标识代码，允许多个逗号分隔值，POTA 地图：<a target='_blank' href='https://pota.app/#/map/'>POTA Map</a>";
$lang['station_location_signature'] = "签名";
$lang['station_location_signature_name'] = "签名名称";
$lang['station_location_signature_name_hint'] = "台站签名 (例如 TU 73)";
$lang['station_location_signature_info'] = "签名信息";
$lang['station_location_signature_info_hint'] = "签名信息 (例如 DA/NW-357).";
$lang['station_location_eqsl_hint'] = 'eQSL 中设置过的 QTH Nichname';
$lang['station_location_eqsl_defaultqslmsg'] = "默认 QSL 消息";
$lang['station_location_eqsl_defaultqslmsg_hint'] = "定义一个发送给对方的 QSO 默认消息（适用于 eQSL 等）";
$lang['station_location_qrz_subscription'] = '需要付费订阅';
$lang['station_location_qrz_hint'] = "查看 API Key：<a href='https://logbook.qrz.com/logbook' target='_blank'>the QRZ.com Logbook 设置页面";
$lang['station_location_qrz_realtime_upload'] = 'QRZ.com Logbook 上传';
$lang['station_location_hrdlog_username'] = "HRDLog.net 用户名";
$lang['station_location_hrdlog_username_hint'] = "HRDlog.net 注册用户名，通常为呼号";
$lang['station_location_hrdlog_code'] = "HRDLog.net API Key";
$lang['station_location_hrdlog_realtime_upload'] = "HRDLog.net Logbook 实时上传";
$lang['station_location_hrdlog_code_hint'] = "创建 API 代码：<a href='http://www.hrdlog.net/EditUser.aspx' target='_blank'>HRDLog.net 用户界面";
$lang['station_location_qo100_hint'] = "创建 API 代码：<a href='https://qo100dx.club' target='_blank'>your QO-100 Dx Club 用户界面";
$lang['station_location_qo100_realtime_upload'] = "QO-100 Dx Club 实时上传";
$lang['station_location_oqrs_enabled'] = "OQRS 已启用";
$lang['station_location_oqrs_email_alert'] = "OQRS 邮件提醒";
$lang['station_location_oqrs_email_hint'] = "确认邮件功能在站点设置中已配置";
$lang['station_location_oqrs_text'] = "OQRS 文本";
$lang['station_location_oqrs_text_hint'] = "QSL 信息";
$lang['station_location_ignore'] = "忽略 Clublog 上传";
$lang['station_location_ignore_hint'] = "If enabled, the QSOs made from this location will not be uploaded to Clublog. If this is deactivated on it's own please check if the Call is properly configured at Clublog";
$lang['station_location_clublog_realtime_upload']='ClubLog 实时上传';


