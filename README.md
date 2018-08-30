# librenms-plugin-vlan-brief-overview
A plugin for Librenms to list all device VLAN and some basic STP support (List devices in same STP domain, but it is very basic. Does not support multiple VLAN etc.)

INSTALL
1. Copy the VLANs directory to your librenms/html/plugins/ directory.
2. In Librenms go to Overview->Plugins->Plugin Admin
3. Click enable on "VLANs"

USAGE

You should have a VLAN list in Overview->Plugins->VLANs
And if the device is in STP table you should get a "LibreNMS\Plugins\VLANs Plugin" section with STP domain members in the device.
