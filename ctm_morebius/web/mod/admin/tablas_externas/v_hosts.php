<?php
ini_set('display_errors', '1');
$TABLE = 'more_v_hosts';
$usu = 1;
$mapa_columnas = [
    'NID' => 'nId',
    'TMLASTVISIBLE' => 'tmLastVisible',
    'TMLASTINFOUPDATE' => 'tmLastInfoUpdate',
    'NGROUP' => 'nGroup',
    'NNTDOMAIN' => 'nNtDomain',
    'STRWINHOSTNAME' => 'strWinHostName',
    'NDNSDOMAIN' => 'nDnsDomain',
    'NSTATUS' => 'nStatus',
    'TMLASTUPDATE' => 'tmLastUpdate',
    'STRDISPLAYNAME' => 'strDisplayName',
    'STRNAME' => 'strName',
    'STRDNSNAME' => 'strDnsName',
    'NCOMPUTERTYPE' => 'nComputerType',
    'NPLATFORMTYPE' => 'nPlatformType',
    'NOSVERSION' => 'nOsVersion',
    'NIPADDRESS' => 'nIpAddress',
    'STRCONNECTIONNAME' => 'strConnectionName',
    'STRADDRESS' => 'strAddress',
    'BADDEDBYFACT' => 'bAddedByFact',
    'BREMOVED' => 'bRemoved',
    'BKEEPCONNECTION' => 'bKeepConnection',
    'TMLASTNAGENTCONNECTED' => 'tmLastNagentConnected',
    'NPRESUMEDGROUPNAME' => 'nPresumedGroupName',
    'NLASTFULLSCAN' => 'nLastFullScan',
    'NVIRUSCOUNT' => 'nVirusCount',
    'NVIRUSCOUNTBASE' => 'nVirusCountBase',
    'NLASTRTPSTATE' => 'nLastRtpState',
    'NLASTRTPERRORCODE' => 'nLastRtpErrorCode',
    'BNEWINTEGRATIONREQUIRED' => 'bNewIntegrationRequired',
    'BKNOWNOFFLINE' => 'bKnownOffline',
    'TMMUSTBECLEARED' => 'tmMustBeCleared',
    'BDNCHANGED' => 'bDnChanged',
    'BPUBLISHEDINVISIBLE' => 'bPublishedInvisible',
    'NIPCON' => 'nIpCon',
    'WSTRCOMMENT' => 'wstrComment',
    'NNSAIP' => 'nNsaIp',
    'TMNSAIP' => 'tmNsaIp',
    'NIPPEER' => 'nIpPeer',
    'TMIPPEER' => 'tmIpPeer',
    'TMCREATED' => 'tmCreated',
    'NVERSIONMAJOR' => 'nVersionMajor',
    'NVERSIONMINOR' => 'nVersionMinor',
    'NEXTRAFLAGS' => 'nExtraFlags',
    'WSTRNAGENTINSTALLID' => 'wstrNagentInstallId',
    'TMLASTSTART' => 'tmLastStart',
    'NRECORDTYPE' => 'nRecordType',
    'TMSYSTEMLASTSTARTED' => 'tmSystemLastStarted',
    'BREBOOTREQUIRED' => 'bRebootRequired',
    'NREBOOTREQUESTREASON' => 'nRebootRequestReason',
    'TMREBOOTREQUESTTIME' => 'tmRebootRequestTime',
    'BISCLUSTER' => 'bIsCluster',
    'NPKGMGRTYPE' => 'nPkgMgrType',
    'NVSERVER' => 'nVServer',
    'NVMTYPE' => 'nVmType',
    'BVMVDI' => 'bVmVdi',
    'NVISIBILITYTIMEOUT' => 'nVisibilityTimeout',
    'TMLASTUASEARCHDATE' => 'tmLastUaSearchDate',
    'BLASTUASEARCHVALID' => 'bLastUaSearchValid',
    'NENCRYPTIONSTATUS' => 'nEncryptionStatus',
    'BRECOVERYDATAEXISTS' => 'bRecoveryDataExists',
    'BWUASWITCHED' => 'bWuaSwitched',
    'NSCREENPROTOCOLS' => 'nScreenProtocols',
    'BGOTWARNINGINCIDENTS' => 'bGotWarningIncidents',
    'BGOTCRITICALINCIDENTS' => 'bGotCriticalIncidents',
    'NSUFFIXINT' => 'nSuffixInt',
    'NSPMAJOR' => 'nSpMajor',
    'NSPMINOR' => 'nSpMinor',
    'LLWOLSUBNETMASK' => 'llWolSubnetMask',
    'LLWOLSUBNET' => 'llWolSubnet',
    'BINWOLMAC' => 'binWolMac',
    'NPROCTYPE' => 'nProcType',
    'TMMUSTFORCESYNC' => 'tmMustForceSync',
    'QWNAGENTBUILD' => 'qwNagentBuild',
    'NID_OLD' => 'nId_Old',
    'NNLALOCATIONNAME' => 'nNlaLocationName',
    'BINUSEROWNER' => 'binUserOwner',
    'BCUSTOMUSEROWNER' => 'bCustomUserOwner',
    'NWLISTSTATUS' => 'nWlistStatus',
    'BHASDPEDATA' => 'bHasDpeData',
    'NENCRYPTIONTYPE' => 'nEncryptionType',
    'NOSNAME' => 'nOSName',
    'NENCALGORITHM' => 'nEncAlgorithm',
    'TMRTPNOTRUNNINGFROM' => 'tmRtpNotRunningFrom',
    'BCHILDUNASSIGNED' => 'bChildUnassigned',
    'NUNCURED' => 'nUncured',
    'BHASUNCURED' => 'bHasUncured',
    'BERRODS' => 'bErrOoDS',
    'LLFREESPACE' => 'llFreeSpace',
    'BMGDBYOTHERSRV' => 'bMgdByOtherSrv',
    'NOSBUILDNUMBER' => 'nOsBuildNumber',
    'NOSRELEASEID' => 'nOsReleaseID',
    'NNAGENTVERSIONID' => 'nNagentVersionID',
    'TMUNUSABLESINCE' => 'tmUnusableSince',
    'BREBOOTACCEPTED' => 'bRebootAccepted',
    'NCEDR_STATUS' => 'nCEDR_Status',
    'TMCEDRLASTSEEN' => 'tmCEDRLastSeen',
    'BINADDRESSIPV6' => 'binAddressIPv6',
    'BINCONNADDRESSIPV6' => 'binConnAddressIPv6'
];

$importado = false;
$errores = [];
$datos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['csv']['tmp_name']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
        $errores[] = 'No se seleccionó un archivo CSV válido.';
    } else {
        $tmpName = $_FILES['csv']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $errores[] = 'Formato no soportado: .' . $ext;
        } else {
            try {
                $rows = procesarCSV($tmpName);
                if (count($rows) < 2) {
                    throw new Exception('El CSV no contiene suficientes filas.');
                }
                // Usar encabezados reales del CSV y mapearlos a los nombres de la tabla
                $headers = array_map('normalizarColumna', $rows[0]);
                foreach ($headers as $i => $col) {
                    if (isset($mapa_columnas[$col])) {
                        $headers[$i] = $mapa_columnas[$col];
                    }
                }
                $datosCsv = array_slice($rows, 1);

                limpiarTabla($TABLE, $usu);
                insertarRegistros($TABLE, $headers, $datosCsv, $usu);
                $importado = true;

                // Mostrar los registros recién insertados
                $sql_select = "SELECT * FROM {$TABLE} ORDER BY strDisplayName, nId;";
                $datos = [];
                $res = sql_do_query($sql_select, '1', $usu);
                while ($fila = sql_do_array($res)) {
                    $datos[] = $fila;
                }
            } catch (Exception $e) {
                $errores[] = $e->getMessage();
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Importar Hosts del Sistema (CSV)</title>
    <style>
        :root {
            --color-fondo: #f5f5f5;
            --color-contenedor: #fff;
            --color-texto: #333;
            --color-borde: #ccc;
            --color-primario: #3498db;
            --color-primario-hover: #2980b9;
            --color-subtexto: #555;
            --color-descripcion: #888;
            --color-input-focus: #66afe9;
        }
        body {
            margin: 0;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: var(--color-fondo);
            color: var(--color-texto);
            line-height: 1.6;
        }
        .container {
            display: inline-block;
            width: auto;
            max-width: 100vw;
            margin: 40px auto;
            background: var(--color-contenedor);
            border-radius: 8px;
            padding: 30px 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1.page-title {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8em;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--color-subtexto);
        }
        input[type="file"] {
            display: block;
            width: 100%;
            padding: 10px;
            border: 1px solid var(--color-borde);
            border-radius: 4px;
            font-size: 1em;
            box-sizing: border-box;
        }
        input[type="file"]:focus {
            border-color: var(--color-input-focus);
            outline: none;
        }
        .btn {
            display: block;
            width: 100%;
            background: var(--color-primario);
            color: #fff;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 1.1em;
            cursor: pointer;
            text-align: center;
            transition: background 0.3s ease;
            margin-top: 20px;
        }
        .btn:hover {
            background: var(--color-primario-hover);
        }
        .message-ok {
            color: green;
            font-weight: bold;
            margin-top: 15px;
        }
        .message-error {
            color: red;
            font-weight: bold;
            margin-top: 15px;
        }
        .message-error ul {
            margin-top: 10px;
            padding-left: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            font-size: 0.9em;
        }
        th, td {
            border: 1px solid var(--color-borde);
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

    </style>
</head>
<body>
<div class="container">
    <h1 class="page-title">Importar Hosts del Sistema desde CSV</h1>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="csv">Selecciona el archivo (.csv):</label>
            <input type="file" name="csv" id="csv" accept=".csv" required>
        </div>
        <button type="submit" class="btn">Cargar e importar</button>
    </form>

    <?php if (!empty($errores)): ?>
        <div class="message-error">
            <strong>Se produjeron errores:</strong>
            <ul>
                <?php foreach ($errores as $msg): ?>
                    <li><?= htmlspecialchars($msg) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($importado): ?>
        <p class="message-ok">Importación completada (<?= count($datos) ?> registros).</p>
    <?php endif; ?>

    <?php if (!empty($datos)): ?>
        <h2>Registros recién insertados</h2>
        <div style="overflow-x:auto;">
            <table style="min-width:400px;max-width:100%;">
                <thead>
                    <tr>
                        <?php foreach (array_keys($datos[0]) as $header): ?>
                            <th><?= htmlspecialchars($header) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos as $fila): ?>
                        <tr>
                            <?php foreach ($fila as $celda): ?>
                                <td><?= htmlspecialchars($celda ?? '') ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
<br />
