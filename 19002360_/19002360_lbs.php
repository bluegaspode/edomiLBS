###[DEF]###
[name=Kostal Plenticore ModbusTCP ]

[e#1 trigger = Trigger]
[e#2 option = IP]
[e#3 option = Log Level #init=0 ]

// ##### Basics 
[a#1 = Inverter Status]
[a#2 = Inverter Status Text]
[a#3 = Total DC power (W)]
[a#4 = Inverter Generation Power (W)]
[a#5 = Battery charge/discharge power (W)]
[a#10 = Home own consumption from battery (W)]
[a#11 = Home own consumption from grid (W) ]
[a#12 = Home own consumption from PV (W)]

[a#20 = --- DC --- ]
[a#21 = DC1 Voltage (V) ]
[a#22 = DC1 Current (A) ]
[a#23 = DC1 Power (W) ]
[a#24 = DC2 Voltage (V) ]
[a#25 = DC2 Current (A) ]
[a#26 = DC2 Power (W) ]
[a#27 = DC3 Voltage (V) ]
[a#28 = DC3 Current (A) ]
[a#29 = DC3 Power (W) ]

[a#35 = --- AC --- ]
[a#36 = AC L1 Voltage (V) ]
[a#37 = AC L1 Current (A) ]
[a#38 = AC L1 Power (W) ]
[a#39 = AC L2 Voltage (V) ]
[a#40 = AC L2 Current (A) ]
[a#41 = AC L2 Power (W) ]
[a#42 = AC L3 Voltage (V) ]
[a#43 = AC L3 Current (A) ]
[a#44 = AC L3 Power (W) ]
[a#45 = AC Total Active Power (W)]


[a#50 = --- Yields --- ]
[a#51 = Total yield (Wh) ]
[a#52 = Daily yield (Wh) ]
[a#53 = Yearly yield (Wh) ]
[a#54 = Monthly yield (Wh) ]

[a#60 = --- Battery --- ]
[a#61 = Act. state of charge (%) ]


[v#1		=	]

[v#100 = 0.1]  								//Version
[v#101 = 19002360]  						//LBS ID
[v#102 = Kostal Plenticore ModbusTCP]      //LBS Name
[v#103		= 0 ]							// Log level
[v#104		= 0 ]							// Log per instance
[v#105		= 0 ]							// log ID in each line
###[/DEF]###

###[HELP]###
Der Baustein dient zum Auslesen eines Kostal Plenticore Wechselrichters über eine Modbus TCP Verbindung.

Die Datei ModbusMaster.php muss in das Verzeichnis: /usr/local/edomi/main/include/php/ kopiert werden.

E1: Trigger, über diesen Eingang kann der Baustein getriggert werden.
E2: IP Wechselrichter
E3: Log-Level (0=none, 1=emergency, 2=alert, 3=critical, 4=error, 5=warning, 6=notice, 7=info, 8=debug)


###[/HELP]###

###[LBS]###
<?
function LB_LBSID_logging($id, $msg, $var = NULL, $priority = 8)
{
    $E = getLogicEingangDataAll($id);
    $logLevel = getLogicElementVar($id, 103);
    if (is_int($priority) && $priority <= $logLevel && $priority > 0) {
        $logLevelNames = array(
            'none',
            'emerg',
            'alert',
            'crit',
            'err',
            'warning',
            'notice',
            'info',
            'debug'
        );
        $version = getLogicElementVar($id, 100);
        $lbsNo = getLogicElementVar($id, 101);
        $logName = getLogicElementVar($id, 102) . "-LBS$lbsNo";
        $logName = preg_replace('/ /', '_', $logName);
        if (logic_getVar($id, 104) == 1)
            $logName .= "-$id";
            if (logic_getVar($id, 105) == 1)
                $msg .= " ($id)";
                strpos($_SERVER['SCRIPT_NAME'], $lbsNo) ? $scriptname = 'EXE' . $lbsNo : $scriptname = 'LBS' . $lbsNo;
                writeToCustomLog($logName, str_pad($logLevelNames[$logLevel], 7), $scriptname . " [v$version]:\t" . $msg);
                if (isset($var)) {
                    writeToCustomLog($logName, str_pad($logLevelNames[$logLevel], 7), $scriptname . " [v$version]:\t================ ARRAY/OBJECT START ================");
                    writeToCustomLog($logName, str_pad($logLevelNames[$logLevel], 7), $scriptname . " [v$version]:\t" . json_encode($var));
                    writeToCustomLog($logName, str_pad($logLevelNames[$logLevel], 7), $scriptname . " [v$version]:\t================ ARRAY/OBJECT  END  ================");
                }
    }
}


function LB_LBSID($id) {

	if ($E=logic_getInputs($id)) {
		setLogicElementVar($id, 103, $E[3]['value']); // set loglevel to #VAR 103
        	
		if ($E[1]['value']==1 && $E[1]['refresh']==1) {
			LB_LBSID_logging($id, 'LBS started');
			logic_setVar($id,1,1);

			if (logic_getVar($id,1)==1)  {				//setzt V1=1, um einen mehrfachen Start des EXEC-Scripts zu verhindern
				
				logic_callExec(LBSID,$id);				//EXEC-Script starten
				
        		}

			LB_LBSID_logging($id, 'LBS ended');
		}

        		}

}
?>
###[/LBS]###

###[EXEC]###
<?
require(dirname(__FILE__)."/../../../../main/include/php/incl_lbsexec.php");
require(dirname(__FILE__). "/../../../../main/include/php/ModbusMaster.php");
set_time_limit(25);
sql_connect();

//-------------------------------------------------------------------------------------
function logging($id, $msg, $var = NULL, $priority = 8)
{
    $E = getLogicEingangDataAll($id);
    $logLevel = getLogicElementVar($id, 103);
    if (is_int($priority) && $priority <= $logLevel && $priority > 0) {
        $logLevelNames = array(
            'none',
            'emerg',
            'alert',
            'crit',
            'err',
            'warning',
            'notice',
            'info',
            'debug'
        );
        $version = getLogicElementVar($id, 100);
        $lbsNo = getLogicElementVar($id, 101);
        $logName = getLogicElementVar($id, 102) . "-LBS$lbsNo";
        $logName = preg_replace('/ /', '_', $logName);
        if (logic_getVar($id, 104) == 1)
            $logName .= "-$id";
        if (logic_getVar($id, 105) == 1)
            $msg .= " ($id)";
        strpos($_SERVER['SCRIPT_NAME'], $lbsNo) ? $scriptname = 'EXE' . $lbsNo : $scriptname = 'LBS' . $lbsNo;
        writeToCustomLog($logName, str_pad($logLevelNames[$logLevel], 7), $scriptname . " [v$version]:\t" . $msg);
        if (isset($var)) {
            writeToCustomLog($logName, str_pad($logLevelNames[$logLevel], 7), $scriptname . " [v$version]:\t================ ARRAY/OBJECT START ================");
            writeToCustomLog($logName, str_pad($logLevelNames[$logLevel], 7), $scriptname . " [v$version]:\t" . json_encode($var));
            writeToCustomLog($logName, str_pad($logLevelNames[$logLevel], 7), $scriptname . " [v$version]:\t================ ARRAY/OBJECT  END  ================");
        }
    }
}

function readLogAndSaveFloat($id, $sourceRegister, $sourceRegisterName, $targetOutput, $valuesByteArray, $valuesOffset) {
	$value = PhpType::bytes2float(array_slice($valuesByteArray , ($sourceRegister-$valuesOffset)*2, 4));
	$value = round($value, 2);
	setLogicLinkAusgang($id,$targetOutput, $value);
	logging($id, $sourceRegisterName . ":" . strval($value));
}

function readLogAndSaveS16($id, $sourceRegister, $sourceRegisterName, $targetOutput, $valuesByteArray, $valuesOffset) {
	$value = PhpType::bytes2signedInt(array_slice($valuesByteArray , ($sourceRegister-$valuesOffset)*2, 2));
	setLogicLinkAusgang($id,$targetOutput, $value);
	logging($id, $sourceRegisterName . ":" . strval($value));
}

function readLogAndSaveU32($id, $sourceRegister, $sourceRegisterName, $targetOutput, $valuesByteArray, $valuesOffset) {
	$value = PhpType::bytes2unsignedInt(array_slice($valuesByteArray , ($sourceRegister-$valuesOffset)*2, 4));
	setLogicLinkAusgang($id,$targetOutput, $value);
	logging($id, $sourceRegisterName . ":" . strval($value));
}


$E = getLogicEingangDataAll($id);
$V=logic_getVars($id);
$ip = $E[2]['value'];

$modbus = new ModbusMaster($ip, "TCP");

$modbus->port = 1502;

try {
	logging($id, "--- starting Modbus Read ---");

	$recData56 = $modbus->readMultipleRegisters(71, 56, 1);
	$recData100 = $modbus->readMultipleRegisters(71, 100, 100);
	$recData200 = $modbus->readMultipleRegisters(71, 200, 100);
	$recData320 = $modbus->readMultipleRegisters(71, 320, 8);
	$recData575 = $modbus->readMultipleRegisters(71, 575, 8);

	/*
	// ##### Basics 
	[a#1 = Inverter Status]
	[a#2 = Inverter Status Text]
	[a#3 = Total DC power (W)]
	[a#4 = Inverter Generation Power (W)]
	[a#5 = Battery charge/discharge power (W)]
	[a#10 = Home own consumption from battery (W)]
	[a#11 = Home own consumption from grid (W) ]
	[a#12 = Home own consumption from PV (W)]
	*/

	readLogAndSaveU32($id, 56, "Inverter Status", 1, $recData56, 100);
	//readLogAndSaveFloat($id, 158, "Inverter Status Text", 2, $recData100, 100);
	readLogAndSaveFloat($id, 100, "Total DC power (W)", 3, $recData100, 100);
	readLogAndSaveS16($id, 575, "Inverter Generation Power (W)", 4, $recData575, 575);
	readLogAndSaveS16($id, 582, "Battery charge/discharge power (W)", 5, $recData575, 575);

	readLogAndSaveFloat($id, 106, "Home own consumption from battery (W)", 10, $recData100, 100);
	readLogAndSaveFloat($id, 108, "Home own consumption from grid (W)", 11, $recData100, 100);
	readLogAndSaveFloat($id, 116, "Home own consumption from PV (W)", 12, $recData100, 100);

	/*
	[a#35 = --- AC --- ]
	[a#36 = AC L1 Voltage (V) ]
	[a#37 = AC L1 Current (A) ]
	[a#38 = AC L1 Power (W) ]
	[a#39 = AC L2 Voltage (V) ]
	[a#40 = AC L2 Current (A) ]
	[a#41 = AC L2 Power (W) ]
	[a#42 = AC L3 Voltage (V) ]
	[a#43 = AC L3 Current (A) ]
	[a#44 = AC L3 Power (W) ]
	[a#45 = AC Total Active Power (W)]
	*/

	readLogAndSaveFloat($id, 158, "Voltage Phase 1", 36, $recData100, 100);
	readLogAndSaveFloat($id, 154, "Current Phase 1", 37, $recData100, 100);
	readLogAndSaveFloat($id, 156, "Active power Phase 1", 38, $recData100, 100);
	readLogAndSaveFloat($id, 164, "Voltage Phase 2", 39, $recData100, 100);
	readLogAndSaveFloat($id, 160, "Current Phase 2", 40, $recData100, 100);
	readLogAndSaveFloat($id, 162, "Active power Phase 2", 41, $recData100, 100);
	readLogAndSaveFloat($id, 170, "Voltage Phase 3", 42, $recData100, 100);
	readLogAndSaveFloat($id, 166, "Current Phase 3", 43, $recData100, 100);
	readLogAndSaveFloat($id, 168, "Active power Phase 3", 44, $recData100, 100);
	readLogAndSaveFloat($id, 172, "Total AC active power", 45, $recData100, 100);

	/*
	[a#20 = --- DC --- ]
	[a#21 = DC1 Voltage (V) ]
	[a#22 = DC1 Current (A) ]
	[a#23 = DC1 Power (W) ]
	[a#24 = DC2 Voltage (V) ]
	[a#25 = DC2 Current (A) ]
	[a#26 = DC2 Power (W) ]
	[a#27 = DC3 Voltage (V) ]
	[a#28 = DC3 Current (A) ]
	[a#29 = DC3 Power (W) ]
	*/

	readLogAndSaveFloat($id, 266, "Voltage DC1", 21, $recData200, 200);
	readLogAndSaveFloat($id, 258, "Current DC1", 22, $recData200, 200);
	readLogAndSaveFloat($id, 260, "Power DC1", 23, $recData200, 200);

	readLogAndSaveFloat($id, 276, "Voltage DC2", 24, $recData200, 200);
	readLogAndSaveFloat($id, 268, "Current DC2", 25, $recData200, 200);
	readLogAndSaveFloat($id, 270, "Power DC2", 26, $recData200, 200);

	readLogAndSaveFloat($id, 286, "Voltage DC3", 27, $recData200, 200);
	readLogAndSaveFloat($id, 278, "Current DC3", 28, $recData200, 200);
	readLogAndSaveFloat($id, 280, "Power DC3", 29, $recData200, 200);

	/*
	[a#50 = --- Yields --- ]
	[a#51 = Total yield (Wh) ]
	[a#52 = Daily yield (Wh) ]
	[a#53 = Yearly yield (Wh) ]
	[a#54 = Monthly yield (Wh) ]
	*/	

	readLogAndSaveFloat($id, 320, "Total yield", 51, $recData320, 320);
	readLogAndSaveFloat($id, 322, "Daily yield", 52, $recData320, 320);
	readLogAndSaveFloat($id, 324, "Yearly yield", 53, $recData320, 320);
	readLogAndSaveFloat($id, 326, "Monthly yield", 54, $recData320, 320);

	/*
	[a#60 = --- Battery --- ]
	[a#61 = Act. state of charge (%) ]*/

	readLogAndSaveFloat($id, 210, "Act. state of charge (%)", 61, $recData200, 320);

} catch (Exception $e) {
	logging($id, "Fehler:" , 4);
	logging($id, $modbus,4);
	logging($id, $e,4);
	exit;
}
unset($modbus);

//logic_setVar($id,1,0);											//setzt V1=0, um einen erneuten Start des EXEC-Scripts zu erm?glichen
logging($id, "EXEC End");
//-------------------------------------------------------------------------------------

sql_disconnect();
?>
###[/EXEC]###