#!/usr/bin/php -q
<?php


function main() {
    $options = get_options();

    switch ($options['o']) {
            case 'lic':

                    if ( $options['P'] == 'nac' and $options['S'] == 'point' ) { // NAC
                           verifyLic("/opt/hsc/installer/bin/ts | grep -i 'Chave' ", ":", $options['w'], $options['c']);


                    }elseif ( $options['P'] == 'iss' and $options['S'] == 'point' ) { // ISS
                           verifyLic("/opt/hsc/installer/bin/ts | grep -i 'Chave' ", ":", $options['w'], $options['c']);
                    }elseif ( $options['P'] == 'iss' and $options['S'] == 'admsuite' ) { // ISS
                            verifyLic("cat /opt/hsc/iss/app/license.zl | grep 'Expires' ", "=", $options['w'], $options['c'], 1);


                    }elseif ( $options['P'] == 'mli' and $options['S'] == 'point' ) { // MLI
                            verifyLic("/opt/hsc/installer/bin/ts | grep -i 'Chave' ", ":", $options['w'], $options['c']);
                    }elseif ( $options['P'] == 'mli' and $options['S'] == 'admsuite' ) { // MLI
                            verifyLic("cat /opt/hsc/mailinspector/admsuite/license.zl | grep 'Expires' ", "=", $options['w'], $options['c'], 1);
                    }


                    else{
                        quit("UNKNOWN - Invalid Parameters. Check the syntax!", 3) ;
                    }
                    break;
            default:
                    echo "UNKNOWN - Parameter -o Invalid\n";
                    help();
            }
}

function verifyLic($cmd, $delimiter,  $warn=null, $crit=null, $format = null) {
    $resp_cmd = exec($cmd, $output , $return_code);
    $data = explode($delimiter,$resp_cmd);
    $data_lic = str_replace(" ","", $data[1]);

    if($data_lic == 'Never'){
                quit('OK - Licenca nunca expira: '. $data_lic, 0);
    }

    if($format == 1){
                $ano_lic = substr($data_lic, 8, -11);
    }else{
        $ano_lic = substr($data_lic, 0, 4);
    }

    if (substr($data_lic,2,1) != '-') {
        if (ereg('[^0-9]', $data_lic)) {
        quit('CRITICAL - Licenca inválida, favor verificar!', 2) ;
        }
    }

    if($ano_lic > '2037'){//BUG do ano 2038 em equipamentos de 32bits https://pt.wikipedia.org/wiki/Problema_do_ano_2038
        quit("UNKNOWN - Nao foi possivel calcular a data(BUG - Ano >= 2038 em 32bits ), licenca com data: ".$data_lic, 3);
    }

    $time_lic = strtotime($data_lic);
    $time_atual = strtotime(date('Ymd'));
    // Calcula a diferença de segundos entre as duas datas:
    $diferenca = $time_lic - $time_atual; // 19522800 segundos
    // Calcula a diferença de dias
    $dias = (int)floor( $diferenca / (60 * 60 * 24)); // 225 dias

    if ( $warn and $crit ) {
         $exit_code =  metric($dias, $warn,  $crit); //Ajustei a metrica para repassar o valor de dias e não data
    }else{
        $exit_code = 0;
    }

    // Tratamento do status {OK,WARNING,CRITICAL}
    if ( $exit_code == 0 ) {
        quit('OK - Licenca com data: '.$data_lic.' expira em: '. $dias.' dias | days='.$dias.";".$warn.";".$crit.";;", $exit_code);
    }
    if ( $exit_code == 1 ) {
        quit('WARNING - Licenca com data: '.$data_lic.' expira em: '. $dias.' dias | days='.$dias.";".$warn.";".$crit.";;", $exit_code);
    }
    if ( $exit_code == 2 ) {
        quit('CRITICAL - Licenca com data: '.$data_lic.' expira em: '. $dias.' dias | days='.$dias.";".$warn.";".$crit.";;", $exit_code);
    }
}


function get_options() {
    $shortopts  = "P:S:o:w:c:h:v";

    $options = getopt($shortopts);

    if(count($options) == 0 || isset($options['help']) || isset($options['h']))     help();
    return $options;

}

function metric($value, $warn, $crit) {




                if ($value > $warn) {
                        return 0 ;
                } else if ($value > $crit) {
                        return 1 ;
                } else if ($value <= $crit) {
                        return 2 ;
                } else {
                        $this->quit("UNKNOWN - Unable to check.",3);
                }

/*
                 if ($value = $crit) {
                        return 2;
                }else if ($value >= $warn) {
                        return 1;
                }else if ($value < $warn) {
                       return 0;
                }else {
                        $this->quit("UNKNOWN - Unable to check.",3);
                }
        }else if ($warn > $crit){
                if ($value <= $crit) {
                        return 2;
                }else if ($value <= $warn) {
                        return 1;
                }else if ($value > $warn) {
                        return 0;
                }else {
                        $this->quit("UNKNOWN - Unable to check.",3);
                }
        }
*/}
function help() {
    $basename = str_replace(".php", "", basename($_SERVER[PHP_SELF]));
    $texto = "Version 1.0
    ./$basename -P [nac|mli|iss] -S [point|admsuite] -o [lic] -w [days] -c [days]
    ";
    quit($texto, 3);
}

function quit($text, $code) {
    echo $text."\n";
    exit($code);
}

main();
?>

