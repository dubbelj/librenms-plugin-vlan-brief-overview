<?php

namespace LibreNMS\Plugins;

class VLANs
{
    public static function menu()
    {
        echo '<li><a href="plugin/p=VLANs">VLANs</a></li>';
    }//end menu()


    public function device_overview_container($device) {
      $query="
SELECT
	stp.device_id,
	devices.sysName,
	stp.rootBridge,
	stp.bridgeAddress,
	stp.designatedRoot
FROM
	stp,
	devices
WHERE
	stp.designatedRoot = (SELECT designatedRoot FROM stp WHERE device_id=$device[device_id]) AND
	devices.device_id = stp.device_id
ORDER BY
	stp.designatedRoot,devices.sysName";
	$output=array();
	foreach( dbFetchRows($query) as $line){
	  if ($line[rootBridge]){
		  array_push ($output, "<b><A HREF='/device/device=$line[device_id]'>$line[sysName]</A></b> ");
	  }else{
		  array_push ($output, "<A HREF='/device/device=$line[device_id]'>$line[sysName]</A> ");
	  }
	}
	if (count ($output)){
          echo('<div class="container-fluid"><div class="row"> <div class="col-md-12"> <div class="panel panel-default panel-condensed"> <div class="panel-heading"><strong>'.get_class().' Plugin </strong> </div>');
          echo("Switches sharing spanning-tree domain <b>bold is designatedRoot</b>(<b>WARNING:</b> Very basic stp support, only vlan1  )<br>");
	  foreach ($output as $data){
            print $data;
	  }
          echo('</div></div></div></div>');
	}
    }

#    public function port_container($device, $port) {
#		echo('<div class="container-fluid"><div class="row"> <div class="col-md-12"> <div class="panel panel-default panel-condensed"> <div class="panel-heading"><strong>'.get_class().' plugin in "Port" tab</strong> </div>');
#	    echo ('Example display in Port tab</br>');
#	    echo('</div></div></div></div>');
#    }
}
