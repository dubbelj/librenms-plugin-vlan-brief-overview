<?php

use LibreNMS\Config;

#echo "Well done, the VLANs plugin system is up and running<br>\n";

$query = "SELECT
	vlans.vlan_vlan,
	vlans.vlan_name,
	devices.sysName,
	vlans.device_id
FROM
	vlans,
	devices
WHERE
	devices.device_id = vlans.device_id
ORDER BY vlan_vlan,vlan_name";

$data=array();
foreach( dbFetchRows($query) as $line){
	#echo "<br>$line[vlan_vlan],$line[vlan_name],<A HREF='/device/device=$line[device_id]'>$line[sysName]</A>\n";
	#array_push($data[$line[vlan_vlan]][$line[vlan_name]][" <A HREF='/device/device=$line[device_id]'>$line[sysName]</A>"]);
	#array_push($data[$line[vlan_vlan]][$line[vlan_name]]," <A HREF='/device/device=$line[device_id]'>$line[sysName]</A>");
	$data[$line[vlan_vlan]][$line[vlan_name]][" <A HREF='/device/device=$line[device_id]'>$line[sysName]</A>"]++;
}
#var_dump($data);
print "
<style type=\"text/css\">
.tg  {border-collapse:collapse;border-spacing:0;}
.tg td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
.tg th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
.tg .tg-9hbo{background-color:#c0c0c0;font-weight:bold;vertical-align:top}
.tg .tg-yw4l{vertical-align:top}
.tg .tg-q8xn{background-color:#dae8fc;vertical-align:top}
</style>
<table class=\"tg\"><tr><th class=\"tg-9hbo\">vlan</th><th class=\"tg-9hbo\">name</th><th class=\"tg-9hbo\">devices</th></tr>";
$n=0;
foreach($data as $vlan_nr => $vlan_name_array){
	$format= ( $n++ % 2 ) ? "tg-q8xn" : "tg-yw4l" ; # Set row format
	print "<tr><td class=\"$format\" rowspan='". count($vlan_name_array). "'>$vlan_nr</td>\n";
	foreach($vlan_name_array as $vlan_name => $device_array){
		print "<td class=\"$format\">$vlan_name</td><td  class=\"$format\"> ";
		#foreach($device_array as $device => $tmp){
		#	print "$device,";
		#}
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
