<?php

use LibreNMS\Config;
global $plugin_name;
$plugin_name="VLANs";

$vlan_nr_to_exclude_from_report=array();
$default_vlans_to_exclude="1,1002-1005";


print '<style type="text/css">
.tg  {border-collapse:collapse;border-spacing:0;}
.tg td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
.tg th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
.tg .tg-9hbo{background-color:#c0c0c0;font-weight:bold;vertical-align:top}
.tg .tg-yw4l{vertical-align:top}
.tg .tg-q8xn{background-color:#dae8fc;vertical-align:top}
</style>
';

$device_groups=array();
$device_groups_members=array();
$device_members_groups=array();
$group_ids_to_exclude=array();
$query = 'SELECT
	device_group_device.device_id,
	device_group_device.device_group_id,
	device_groups.name
FROM
	device_groups,
	device_group_device
WHERE
	device_group_device.device_group_id=device_groups.id order by name
;';
if ($_POST){
	#print "DEBUG:<pre>";
	#var_dump($_POST);
	#print "</pre><br>\n";
	foreach ($_POST["group_selected"] as $arrayid => $gid){
		#print "DEBUG: POST data '$gid'<br>\n";
		$group_ids_to_exclude[$gid]=1;
	}
	if ($_POST["vlans_to_exclude"] ){
		$vlans_to_exclude=$_POST["vlans_to_exclude"];
	}
}else{
	$vlans_to_exclude=$default_vlans_to_exclude;
}
foreach (explode(",", $vlans_to_exclude) as $vlan){
	if (preg_match ( "/(\d+)-(\d+)/" , $vlan, $matches)){
		$first_vlan=$matches[1];
		$last_vlan=$matches[2];
		#print "DEBUG:<pre>";
		#var_dump($matches);
		#print "</pre><br>\n";
		#print "DEBUG: \$first_vlan=$first_vlan, \$last_vlan=$last_vlan<br>\n";
		if ($last_vlan > $first_vlan){
			for ($counter=$first_vlan; $counter <= $last_vlan; $counter++){
				$vlan_nr_to_exclude_from_report[$counter]=1;
			}
		}else{
			for ($counter=$first_vlan; $counter >= $last_vlan; $counter--){
				$vlan_nr_to_exclude_from_report[$counter]=1;
			}
			#print "ERROR in \"Exclude listed vlans\" $last_vlan need to be greater than $first_vlan.<br>\n";
		}
	}else{
		$vlan_nr_to_exclude_from_report[$vlan]=1;
	}
}
foreach( dbFetchRows($query) as $line){
	$device_id=$line[device_id];
	$device_group_id=$line[device_group_id];
	$device_group_name=$line[name];
	if (array_key_exists ($device_group_id, $device_groups)){
		# Do nothing
	}else{
		#print "DEBUG: add $device_group_name ($device_group_id)<br>\n";
		$device_groups[$device_group_id]=$device_group_name;
	}
	$device_groups_members[$device_group_id][$device_id]=1;
	$device_members_groups[$device_id][$device_group_id]=1;
}

$form_table="<form action='/plugin/p=$GLOBALS[plugin_name]' method='post'>";
$form_table.='<table class="tg"><tr><th colspan=2 class="tg-9hbo">Check group to exclude its members from report <input name="exclude" value="exclude selected" type="submit"></th></tr>';
foreach($device_groups as $gid => $gname){
	#print $format;
	$format= ( $n++ % 2 ) ? "tg-q8xn" : "tg-yw4l" ; # Set row format
	$checked="";
	if (array_key_exists ($gid, $group_ids_to_exclude)){
		$checked=" checked ";
	}
	$form_table.="<tr><td class=\"$format\"><input type=\"checkbox\" name=\"group_selected[]\" value=\"$gid\" $checked><td class=\"$format\">$gname</tr>\n";
}
$format= ( $n++ % 2 ) ? "tg-q8xn" : "tg-yw4l" ; # Set row format
$form_table.="<tr><td colspan=2 class=\"$format\">Exclude listed vlans:<input type=\"textbox\" size=50 name=\"vlans_to_exclude\" value=\"$vlans_to_exclude\" ></tr>\n";
$form_table.=csrf_field();
$form_table.="</table></form><br>\n";
print $form_table;


$vlans=array();
$data=array();
$query = "SELECT
	vlans.vlan_id,
	vlans.vlan_vlan,
	vlans.vlan_name,
	devices.sysName,
	vlans.device_id,
	vlans.vlan_type
FROM
	vlans,
	devices
WHERE
	devices.device_id = vlans.device_id
ORDER BY vlan_vlan,vlan_name";

foreach( dbFetchRows($query) as $line){
	$device_id=$line[device_id];
	$device_is_excluded=0;
	foreach($device_members_groups[$device_id] as $gid => $foo){
		if (array_key_exists($gid, $group_ids_to_exclude)){
			$device_is_excluded=1;
		}
	}
	if ($device_is_excluded){
		# Do nothing
	}else{

		if (array_key_exists ($line[vlan_vlan], $vlan_nr_to_exclude_from_report)){
			#Exclude from report
		}else{
			$data[$line[vlan_vlan]][$line[vlan_name]][" <A HREF='/device/device=$line[device_id]'>$line[sysName]</A>"]++;
			$key="$line[sysName].$line[vlan_vlan]";
			$vlans[$key][name]=$line[vlan_name];
			$vlans[$key][vlan]=$line[vlan_vlan];
			$vlans[$key][device]=$line[sysName];
			$vlans[$key][type]=$line[type];
		}
	}
}
#var_dump($data);
print "<table class=\"tg\"><tr><th class=\"tg-9hbo\">vlan</th><th class=\"tg-9hbo\">name</th><th class=\"tg-9hbo\">devices</th></tr>";
$n=0;
foreach($data as $vlan_nr => $vlan_name_array){
	$format= ( $n++ % 2 ) ? "tg-q8xn" : "tg-yw4l" ; # Set row format
	print "<tr><td class=\"$format\" rowspan='". count($vlan_name_array). "'>$vlan_nr</td>\n";
	foreach($vlan_name_array as $vlan_name => $device_array){
		print "<td class=\"$format\">$vlan_name</td><td  class=\"$format\"> ";
		$names=array();
		foreach($device_array as $device => $tmp){
			array_push($names, "$device");
		}
		natcasesort($names);
		foreach($names as $device){
			print "$device,";
		}
		print "</td></tr><tr>\n";
	}
	print "</tr>";
}
print "</table>";

# Now check for vlans that do not have active entrys in FDB
$fdb=array();
$query = "SELECT 
	DISTINCT ports_fdb.vlan_id,
	vlans.vlan_vlan,
	vlans.vlan_name,
	devices.sysName
FROM
	ports_fdb,
	vlans,
	devices
WHERE
	ports_fdb.vlan_id!=0
	AND ports_fdb.vlan_id=vlans.vlan_id
	AND devices.device_id=ports_fdb.device_id
ORDER BY vlans.vlan_vlan;";
foreach( dbFetchRows($query) as $line){
	$key="$line[sysName].$line[vlan_vlan]";
	$fdb[$key]=1;
}
foreach($fdb as $vlan_id => $vlaninfo){
	# If value is vlan_id was found, it had a active entry. Remove it from vlan list.
	unset ($vlans[$vlan_id]);
}
$device_no_fdb_vlan=array();
foreach ($vlans as $vlan_id => $vlaninfo){
	# Remaining vlans should not have any active FDB entrys
	# print "$vlaninfo[vlan], $vlaninfo[name], $vlaninfo[device], $vlaninfo[type]<br>\n";
	$device_no_fdb_vlan[$vlaninfo[device]][$vlan_id][vlan]=$vlaninfo[vlan];
	$device_no_fdb_vlan[$vlaninfo[device]][$vlan_id][name]=$vlaninfo[name];
	$device_no_fdb_vlan[$vlaninfo[device]][$vlan_id][type]=$vlaninfo[type];
}

#print "The vlans listed here do not have any active FDB entrys<br>\n";
print "<br>The vlans listed here do not have any active FDB entrys. It <b>may</b> be possible to remove them from the device.<br>\n";
print "<table class=\"tg\"><tr><th class=\"tg-9hbo\">device</th><th class=\"tg-9hbo\">vlans with no FDB entry</th></tr>";
$n=0;
foreach ($device_no_fdb_vlan as $device => $deviceinfo){
	$format= ( $n++ % 2 ) ? "tg-q8xn" : "tg-yw4l" ; # Set row format
	print "<tr><td class=\"$format\" >$device</td> <td class=\"$format\" >\n";
	foreach ($deviceinfo as $devicearray){
		print "$devicearray[vlan] $devicearray[name], ";
	}
	print "</td>\n";
}
print "</table>";
