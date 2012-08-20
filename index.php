<?php
include '../config.php.inc';
//ini_set('display_errors', '0');

	$connection = mysql_connect($mysql_host,$mysql_user,$mysql_pass);
	if (!$connection) {
		die("Database connection failed: " . mysql_error());
	}
	
	$db_select = mysql_select_db($database,$connection);
	if (!$db_select) {
		die("Database selection failed: " . mysql_error());
	}

?>
<html>
<head>
<title>Freqin'</title>
<style type="text/css">
body {
    color: #000;
    font: 8pt Verdana;
	background-image:url('rd.png');
	background-repeat:no-repeat;
	background-attachment:fixed;
	background-position: 99% 99%;
}
td {
    color: #000;
    font: 8pt Verdana;
}

</style>
</head>
<body>

<?php
if ($_POST) {
    $radio_data = array();
	$peer_data = array();
    
    echo str_repeat(' ', 512);
    ob_flush();
    flush();
    
    $logins = array(
        array(
            'user' => $_POST['username'],
            'pass' => $_POST['password']
        )
    );
    if ($_POST['username2'] && $_POST['password2']) {
        $logins[] = array(
            'user' => $_POST['username2'],
            'pass' => $_POST['password2']
        );
    }

function iprange($ip,$mask=24,$return_array=FALSE) {
    $corr=(pow(2,32)-1)-(pow(2,32-$mask)-1);
    $first=ip2long($ip) & ($corr);
    $length=pow(2,32-$mask)-1;
    if (!$return_array) {
    return array(
        'first'=>$first,
        'size'=>$length+1,
        'last'=>$first+$length,
        'first_ip'=>long2ip($first),
        'last_ip'=>long2ip($first+$length)
        );
    }
    $ips=array();
    for ($i=0;$i<=$length;$i++) {
        $ips[]=long2ip($first+$i);
    }
    return $ips;
}


function ping($host, $port, $timeout) {
        $tB = microtime(true);
        $fP = fSockOpen($host, $port, $errno, $errstr, $timeout);
        if (!$fP) {
                return 0;
        }
        $tA = microtime(true);
        return round((($tA - $tB) * 1000), 0)." ms";
}


$test = iprange($_POST['ips'], $_POST['netmask'],TRUE);

for($i = 0; $i < count($test); $i++) {
        ob_flush();
        flush();
        if($rtadata = ping($test[$i], 80, 0.075)) {
                echo $test[$i] . ' - <span style="color:green"> Online. RTA: </span>' . $rtadata . ' Retrieving Data: ';
				if($data = get_ubnt_stats($test[$i], $logins)) {
					$radio_data[$test[$i]] = $data;
					echo ' <span style="color:green">Success! </span> ';
				} else {
					echo ' <span style="color:red">Failed! </span> ';
				}
				echo $test[$i] . ' - Retrieving Peer Data: ';
				if($pdata = get_peer_stats($test[$i], $logins)) {
					$peer_data[$test[$i]] = $pdata;
					echo ' <span style="color:green">Success! </span> ';
				} else {
					echo ' <span style="color:red">Failed!</a> </span> ';
				}
		        $online_stack[] = $test[$i];
        } else {
                echo $test[$i] . ' - <span style="color:red">' . 'Offline' . '.</span>';
                $offline_stack[] = $test[$i];
        }
        echo "<br />";
        ob_flush();
        flush();
}

/*
$ips = preg_split('/[\r\n\s]+/', $_POST['ips']);
foreach ($ips as $ip) {
if (!($ip = trim($ip))) continue;
if (strstr($ip, '/')) {
list($net,$mask) = explode('/', $ip);
$nmask = pow(2, 32-$mask);
for ($host = ip2long($net)+1; $host < ip2long($net)+$nmask-1; $host++) {
echo long2ip($host) . "...";
ob_flush();
flush();
if ($data = get_ubnt_stats(long2ip($host), $logins)) {
$radio_data[long2ip($host)] = $data;
echo '<span style="color:blue">success.</span>';
} else {
echo '<span style="color:red">' . ($data === false ? 'failed' : 'failed: invalid login') . '.</span>';
}
echo "<br />";
ob_flush();
flush();
}
} else {
echo "$ip...";
ob_flush();
flush();
if ($data = get_ubnt_stats($ip, $logins)) {
$radio_data[$ip] = $data;
echo '<span style="color:blue">success.</span>';
} else {
echo '<span style="color:red">' . ($data === false ? 'failed' : 'failed: invalid login') . '.</span>';
}
echo "<br />";
ob_flush();
flush();
}
}
*/
    echo '<script type="text/javascript">location.href=\'#freqin\';</script>';
}
?>

<a name="freqin"><h1>Freqin'</h1></a>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<table>
<tr><td colspan="4"><b>Ip Address / Netmask:</b><br /></td></tr>
<tr><td colspan="4"><textarea name="ips" cols="15" rows="1"><?php echo isset($_POST['ips']) ? $_POST['ips'] : ''; ?></textarea><textarea name="netmask" cols="4" rows="1"><?php echo isset($_POST['netmask']) ? $_POST['netmask'] : '32'; ?></textarea>
</td></tr>
<tr><td><b>Username:</b></td><td><input type="text" size="20" name="username" value="<?php echo isset($_POST['username']) ? $_POST['username'] : 'ubnt'; ?>" /></td><td><b>Password:</b></td><td><input type="password" name="password" value="<?php echo isset($_POST['password']) ? $_POST['password'] : ''; ?>" size="20" /></td></tr>
<tr><td><b>Secondary Username:</b></td><td><input type="text" size="20" name="username2" value="<?php echo isset($_POST['username2']) ? $_POST['username2'] : 'ubnt'; ?>" /></td><td><b>Password:</b></td><td><input type="password" name="password2" value="<?php echo isset($_POST['password2']) ? $_POST['password2'] : ''; ?>" size="20" /></td></tr>
</table><br />
<input type="submit" value="Go" />
<?php
if (isset($radio_data)) {
    echo '<br /><br />';
    echo '<table border="0" cellspacing="1" cellpadding="4" width="100%">';
    echo '<tr align="center" style="background-color:#ddd">';
    echo '<td><b>IP</b></td>';
	echo '<td><b>Peer MAC</b></td>';
	echo '<td><b>Model</b></td>';
    echo '<td><b>Name</b></td>';
    echo '<td><b>Mode</b></td>';
    echo '<td><b>FW</b></td>';
    echo '<td><b>Uptime</b></td>';
    echo '<td><b>LAN</b></td>';
	echo '<td><b>LAN MAC</b></td>';
	echo '<td><b><a href="javascript:alert(\'DFS Enabled?\')">?</b></td>';
    echo '<td><b>Freq</b></td>';
	echo '<td><b>Width</b></td>';
	echo '<td><b>Ch</b></td>';
	echo '<td><b>WLAN MAC</b></td>';
    echo '<td><b>Signal</b></td>';
    echo '<td><b>Noise</b></td>';
	echo '<td><b>SSID</b></td>';
	echo '<td><b>Security</b></td>';
	echo '<td><b>Dist</b></td>';
	echo '<td><b><a href="javascript:alert(\'Connections\')">?</b></td>';
	echo '<td><b>Speeds</b></td>';
	echo '<td><b>Chain</b></td>';
	echo '<td><b>Errors</b></td>';
	echo '<td><b>CCQ</b></td>';
	echo '<td><b><a href="javascript:alert(\'airMax Enabled?\')">AME</a></b></td>';
	echo '<td><b><a href="javascript:alert(\'airMax Quality\')">AMQ</a></b></td>';
	echo '<td><b><a href="javascript:alert(\'airMax Capacity\')">AMC</a></b></td>';
	echo '<td><b>GPS</b></td>';
	echo '<td><b><a href="javascript:alert(\'                                                 Add to Database\n If checkbox is grayed out, the device IP already exists in the database.\')">+</a></b></td>';
    echo '</tr>';
	
$i = 0;	
for ($bgcolor = 'transparent'; list($ip,$data) = each($radio_data); $bgcolor = $bgcolor == 'transparent' ? '#eee' : 'transparent') {
		echo '<tr align="center" style="background-color:' . $bgcolor . '">';
		
		echo '<td><a href="http://' . $ip . '" target="_blank">' . $ip . '</a></td>';
		        
		for ($j = 0; list($ip,$pdata) = each($peer_data); $j++) {
			if(isset($pdata['mac'])) {	
				echo '<td>' . $pdata['mac'] . '</td>';
				break;
			} else {
				echo '<td>NOT ASSOCIATED</td>';
				$pdata['mac'] = "NOT ASSOCIATED";
				break;
			}
		}
		
		echo '<td>' . $data['model'] . '</td>';
		echo '<td>' . $data['name'] . '</td>';
		if($data['wds'] == 1) {
			echo '<td>' . $data['mode'] . ' WDS</td>';
		} else {
			echo '<td>' . $data['mode'] . '</td>';
		}
        echo '<td>' . $data['fw'] . '</td>';
        echo '<td>' . $data['uptime'] . '</td>';
        echo '<td>' . $data['lan'] . '</td>';
		echo '<td>' . $data['lan_mac'] . '</td>';
		if($data['dfs'] == 1) {
			echo '<td>Y</td>';
		} elseif ($data['dfs'] == 0) {
			echo '<td>N</td>';
		} else {
			echo '<td>N/A</td>';
		}
        echo '<td>' . $data['freq'] . ' MHz</td>';
		echo '<td>' . $data['width'] . ' MHz</td>';
		echo '<td>' . $data['channel'] . '</td>';
        echo '<td>' . $data['wlan_mac'] . '</td>';
		echo '<td>' . $data['signal'] . ' dBm</td>';
        echo '<td>' . $data['noise'] . ' dBm</td>';
		echo '<td>' . $data['ssid'] . '</td>';
		echo '<td>' . $data['security'] . '</td>';
		echo '<td>' . $data['distance'] . '</td>';
		echo '<td>' . $data['connections'] . '</td>';
		echo '<td>' . $data['tx'] . "/" . $data['rx'] . '</td>';
		echo '<td>' . $data['chains'] . '</td>';
		echo '<td>' . ($data['retries'] + $data['err_other']) . '</td>';
		echo '<td>' . $data['ccq'] . '</td>';
		if($data['ame'] == 1) {	
			echo '<td>Y</td>';
			echo '<td>' . $data['amq'] . '</td>';
			echo '<td>' . $data['amc'] . '</td>';
		} else {
			echo '<td>N</td>';
			echo '<td>N/A</td>';
			echo '<td>N/A</td>';
		}
		if($data['gps'] == 1) {
			echo '<td>Y</td>';
		} else {
			echo '<td>N</td>';
		}
//		$nodup = "SELECT * FROM `$bh_table` WHERE `ip` LIKE '$ip'";
//			$execute = mysql_query($nodup);
//			$execute = mysql_fetch_array($execute);
//			if(!in_array($ip, $execute)) {
				echo '<td><input type="checkbox" name="addtodb[]" value="' . $ip . '" /></td>';
//			} else {
//				echo '<td><input type="checkbox" name="addtodb[]" value="' . $ip . '" disabled /></td>';
//			}
		echo '</tr>';
		
		?><pre><?php /*print_r($peer_data)*/;?></pre><?php
		
		if((isset($_POST['add'])) && (in_array($ip,$_POST['addtodb']))) {
			$tobeadded = $_POST['addtodb'];
			
			//foreach($tobeadded as $pos => $ip){
			//for($i = 0; $i < count($tobeadded); $i++) {
				$pmac = $peer_data[$ip]['mac'];
				$speeds = $radio_data[$ip]['tx'] . "/" . $radio_data[$ip]['rx'];
				$errors = ($radio_data[$ip]['retries'] + $radio_data[$ip]['err_other']);
				$model = $radio_data[$ip]['model'];
				$name = $radio_data[$ip]['name'];
				$freq = $radio_data[$ip]['freq'];
				$channel = $radio_data[$ip]['channel'];
				$mode = $radio_data[$ip]['mode'];
				$ssid = $radio_data[$ip]['ssid'];
				$security = $radio_data[$ip]['security'];
				$fw = $radio_data[$ip]['fw'];
				$width = $radio_data[$ip]['width'];
				$distance = $radio_data[$ip]['distance'];
				$wlan_mac = $radio_data[$ip]['wlan_mac'];
				$lan_mac = $radio_data[$ip]['lan_mac'];
				$lan = $radio_data[$ip]['lan'];
				$connections = $radio_data[$ip]['connections'];
				$noise = $radio_data[$ip]['noise'];
				$ccq = $radio_data[$ip]['ccq'];
				$ame = $radio_data[$ip]['ame'];
				$amq = $radio_data[$ip]['amq'];
				$amc = $radio_data[$ip]['amc'];
				$uptime = $radio_data[$ip]['uptime'];
				$dfs = $radio_data[$ip]['dfs'];
				$gps = $radio_data[$ip]['gps'];
				$wds = $radio_data[$ip]['wds'];
				$signal = $radio_data[$ip]['signal'];
				$chains = $radio_data[$ip]['chains'];
			
			//echo "<br>" . $chains . "<br>" . $ip . "<br>"/* . $pmac*/;
			//echo $peer_data[$ip]['mac'];
			//echo "<br>" . gettype($chains) . "<br>";
			
				$add_me_a_new_bh = "INSERT INTO `$database`.`$bh_table` (
					`tower_#`,
					`tower_name`,
					`ip`,
					`peer_ip`,
					`model`,
					`device_name`,
					`frequency`,
					`channel`,
					`wireless_mode`,
					`ssid`,
					`security`,
					`firmware`,
					`channel_width`,
					`distance`,
					`wlan_mac`,
					`lan_mac`,
					`lan_speed`,
					`connections`,
					`noise_floor`,
					`transmit_ccq`,
					`airmax_enabled`,
					`am_q`,
					`am_c`,
					`uptime`,
					`dfs`,
					`gps`,
					`wds`,
					`signal`,
					`speed`,
					`chains`,
					`errors`) VALUES (
					'tower_# placeholder'
					'tower_name placeholder',
					'$ip',
					'$pmac',
					'$model',
					'$name',
					'$freq',
					'$channel',
					'$mode',
					'$ssid',
					'$security',
					'$fw',
					'$width',
					'$distance',
					'$wlan_mac',
					'$lan_mac',
					'$lan',
					'$connections',
					'$noise',
					'$ccq',
					'$ame',
					'$amq',
					'$amc',
					'$uptime',
					'$dfs',
					'$gps',
					'$wds',
					'$signal',
					'$speeds',
					'$chains',
					'$errors')";				
				
				echo "<br>" . $add_me_a_new_bh;
				
			//}
		}
		
		
    }
	echo '</table>';
	if(isset($radio_data) && isset($peer_data)){
		echo "<input type=\"submit\" name=\"add\" value=\"Add Checked to Database\"></form>";
	} else {
		echo "<input type=\"submit\" name=\"add\" value=\"Add Checked to Database\" disabled>";
	}
}

?>
</form>
</body>
</html>