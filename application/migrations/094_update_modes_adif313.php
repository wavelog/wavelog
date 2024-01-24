<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_update_modes_adif313 extends CI_Migration
{
	public function up()
	{
		// deactivate C4FM => Import only
		$this->db->set('active', 0);
		$this->db->where('mode', 'C4FM');
		$this->db->update('adif_modes');
		
		// deactivate DSTAR => Import only
		$this->db->set('active', 0);
		$this->db->where('mode', 'DSTAR');
		$this->db->update('adif_modes');
		
		// insert new C4FM
		$this->db->query("insert into adif_modes (mode,submode,qrgmode,active) values ('DIGITALVOICE','C4FM','DATA',1)
,('DIGITALVOICE','DMR','DATA',1)
,('DIGITALVOICE','DSTAR','DATA',1)
,('DYNAMIC',NULL,'DATA',1)
,('DYNAMIC','VARAHF','DATA',1)
,('DYNAMIC','VARASATELLITE','DATA',1)
,('DYNAMIC','VARAFM1200','DATA',1)
,('DYNAMIC','VARAFM9600','DATA',1)
,('DOMINO','DOM-M','DATA',1)
,('DOMINO','DOM4','DATA',1)
,('DOMINO','DOM5','DATA',1)
,('DOMINO','DOM8','DATA',1)
,('DOMINO','DOM11','DATA',1)
,('DOMINO','VARAFM9600','DATA',1)
,('DOMINO','DOM22','DATA',1)
,('DOMINO','DOM44','DATA',1)
,('DOMINO','DOM88','DATA',1)
,('HELL','HELLX5','DATA',1)
,('HELL','HELLX9','DATA',1)
,('HELL','SLOWHELL','DATA',1)
,('MFSK','FST4','DATA',1)
,('MFSK','FST4W','DATA',1)
,('MFSK','JTMS','DATA',1)
,('MFSK','Q65','DATA',1)
,('PSK','8PSK125','DATA',1)
,('PSK','8PSK125F','DATA',1)
,('PSK','8PSK125FL','DATA',1)
,('PSK','8PSK250','DATA',1)
,('PSK','8PSK250F','DATA',1)
,('PSK','8PSK250FL','DATA',1)
,('PSK','8PSK500','DATA',1)
,('PSK','8PSK500F','DATA',1)
,('PSK','8PSK1000','DATA',1)
,('PSK','8PSK1000F','DATA',1)
,('PSK','8PSK1200F','DATA',1)
,('PSK','PSK63F','DATA',1)
,('PSK','PSK63RC4','DATA',1)
,('PSK','PSK63RC5','DATA',1)
,('PSK','PSK63RC10','DATA',1)
,('PSK','PSK63RC20','DATA',1)
,('PSK','PSK63RC32','DATA',1)
,('PSK','PSK125C12','DATA',1)
,('PSK','PSK125R','DATA',1)
,('PSK','PSK125RC10','DATA',1)
,('PSK','PSK125RC12','DATA',1)
,('PSK','PSK125RC16','DATA',1)
,('PSK','PSK125RC4','DATA',1)
,('PSK','PSK125RC5','DATA',1)
,('PSK','PSK250C6','DATA',1)
,('PSK','PSK250R','DATA',1)
,('PSK','PSK250RC2','DATA',1)
,('PSK','PSK250RC3','DATA',1)
,('PSK','PSK250RC5','DATA',1)
,('PSK','PSK250RC6','DATA',1)
,('PSK','PSK250RC7','DATA',1)
,('PSK','PSK500C2','DATA',1)
,('PSK','PSK500C4','DATA',1)
,('PSK','PSK500R','DATA',1)
,('PSK','PSK500RC2','DATA',1)
,('PSK','PSK500RC3','DATA',1)
,('PSK','PSK500RC4','DATA',1)
,('PSK','PSK800C2','DATA',1)
,('PSK','PSK800RC2','DATA',1)
,('PSK','PSK1000C2','DATA',1)
,('PSK','PSK1000R','DATA',1)
,('PSK','PSK1000RC2','DATA',1)
,('THOR','THOR-M','DATA',1)
,('THOR','THOR4','DATA',1)
,('THOR','THOR5','DATA',1)
,('THOR','THOR8','DATA',1)
,('THOR','THOR11','DATA',1)
,('THOR','THOR16','DATA',1)
,('THOR','THOR22','DATA',1)
,('THOR','THOR25X4','DATA',1)
,('THOR','THOR50X1','DATA',1)
,('THOR','THOR50X2','DATA',1)
,('THOR','THOR100','DATA',1)
,('THRB','THRBX1','DATA',1)
,('THRB','THRBX2','DATA',1)
,('THRB','THRBX4','DATA',1)
,('THRB','THROB1','DATA',1)
,('THRB','THROB2','DATA',1)
,('THRB','THROB4','DATA',1)
,('TOR','NAVTEX','DATA',1)
,('TOR','SITORB','DATA',1)");
		
		
		
	}

	public function down()
	{
		// Not Possible
	}
}
