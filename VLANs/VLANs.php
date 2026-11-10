<?php

namespace LibreNMS\Plugins;

class VLANs
{
    public static function menu()
    {
        echo '<li><a href="plugin/v1/VLANs">VLANs</a></li>';
    }//end menu()


    public function device_overview_container($device) {
      $query="
SELECT
	stp.device_id,
	devices.sysName,
	MAX(stp.rootBridge) AS rootBridge
FROM
	stp,
	devices
WHERE
	stp.designatedRoot IN (SELECT designatedRoot FROM stp WHERE device_id=?) AND
	devices.device_id = stp.device_id
GROUP BY
	stp.device_id, devices.sysName
ORDER BY
	devices.sysName";
	$output=array();
	foreach( array_map(fn ($r) => (array) $r, \Illuminate\Support\Facades\DB::select($query, [(int) $device['device_id']])) as $line){
	  if ($line['rootBridge']){
		  array_push ($output, "<b><A HREF='/device/device=$line[device_id]'>".htmlspecialchars($line['sysName'])."</A></b> ");
	  }else{
		  array_push ($output, "<A HREF='/device/device=$line[device_id]'>".htmlspecialchars($line['sysName'])."</A> ");
	  }
	}
	if (count ($output)){
          echo('<div class="container-fluid"><div class="row"> <div class="col-md-12"> <div class="panel panel-default panel-condensed"> <div class="panel-heading"><strong>VLANs Plugin </strong> </div>');
          echo("Switches sharing spanning-tree domain <b>bold is designatedRoot</b>(<b>WARNING:</b> very basic STP support, all STP instances merged)<br>");
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
