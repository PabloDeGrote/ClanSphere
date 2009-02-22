<?php
// ClanSphere 2008 - www.clansphere.net
// $Id: signout.php 1775 2009-02-17 20:59:11Z duRiel $

$cs_lang = cs_translate('events');

$eventguests_id = $_REQUEST['id'];
settype($eventguests_id,'integer');

$error = 0;
$errormsg = '';

$where = "eventguests_id = '" . $eventguests_id . "'";
$eventguests = cs_sql_select(__FILE__,'eventguests','*',$where);
$where2 = "events_id = '" . $eventguests['events_id'] . "'";
$events = cs_sql_select(__FILE__,'events','events_time',$where2);

if($eventguests['users_id'] != $account['users_id']) {
  $error++;
  $errormsg .= $cs_lang['userid_diff'] . cs_html_br(1);
}

if($events['events_time'] < cs_time()) {
  $error++;
  $errormsg .= $cs_lang['event_done'] . cs_html_br(1);
}

if(isset($_GET['agree']) AND empty($error)) {
  cs_sql_delete(__FILE__,'eventguests',$eventguests_id);

  cs_redirect($cs_lang['signout_true'],'events','center');
}
elseif(isset($_GET['cancel']) OR !empty($error)) {
  cs_redirect(empty($error) ? $cs_lang['signout_false'] : $errormsg,'events','center');
}
else {
  $data['lang']['remove'] = $cs_lang['head_signout'];

  $data['lang']['body'] = $cs_lang['signout_confirm'];

  $data['lang']['content'] = cs_link($cs_lang['confirm'],'events','signout','id=' . $eventguests_id . '&amp;agree');
  $data['lang']['content'] .= ' - ';
  $data['lang']['content'] .= cs_link($cs_lang['cancel'],'events','signout','id=' . $eventguests_id . '&amp;cancel');

  echo cs_subtemplate(__FILE__,$data,'events','remove');
}

?>