<?php


$fileName = $argv[3] ?? './api/sql.log.'.date('Ymd');
echo "listen $fileName\n";
$all = !empty($argv[1]);
$expr = $argv[2] ?? '/.*?(select.*?)cost.*/i';

$f = fopen($fileName, 'r');
$originSize = filesize($fileName);
while(true) {
    clearstatcache();
    $nowFileSize = filesize($fileName);
    $diffsize = $nowFileSize - $originSize;
    if ($diffsize <= 0) {
        continue;
    }
    fseek($f, $originSize);
    $line = fread($f, $diffsize);
    $lineArr = explode("\n", $line);
    foreach ($lineArr as $log) {
        if (empty($log) || strpos(strtolower($log), 'select') === false) {
            continue;
        }
        preg_match($expr, $log, $match);
        $sql = $match[1];
        //todo init database pdo instance
        $result = $database->query("desc $sql")[0];
        if (!empty($result) && ($result['type'] == 'ALL' || ($result['key'] === null && $result['rows'] !== null) || $all)) {
            foreach ($result as $key => $value) {
                $tmp = empty($value) ? 'NULL' : $value;
                $result[$key] = $tmp;
                $length = strlen($tmp) + 2;
                $keyLength = strlen($key) + 2;
                $tableLength[$key] = $length > $keyLength ? $length : $keyLength;
            }
            $totalLength = array_sum($tableLength);
            $sqlLength = strlen($sql);
            $titleLen = $totalLength > $sqlLength ? $totalLength : $sqlLength;
            $diff = $totalLength > $sqlLength ? 0 : $sqlLength - $totalLength;
            $keys = array_keys($result);
            $tableLength[end($keys)] += $diff;
            $str = '+';
            for ($i = 0; $i < ($titleLen + count($tableLength) - 1); $i ++) {
                $str .= '-';
            }
            $str .= "+\n|\033[31m$sql\033[0m";
            $sqlSpace = $titleLen + count($tableLength) - 1 - $sqlLength;
            if ($sqlSpace > 0) {
                for ($i = 0; $i < $sqlSpace; $i ++) {
                    $str .= " ";
                }
            }
            $str .= "|\n+";
            foreach ($keys as $key) {
                for ($i = 0; $i < $tableLength[$key]; $i ++) {
                    $str.= '-';
                }
                $str .= '+';
            }
            $str .= "\n|";
            foreach ($result as $key => $value) {
                $str .= $key;
                $spaceNum = $tableLength[$key] - strlen($key);
                for ($i = 0; $i < $spaceNum; $i ++) {
                    $str.= ' ';
                }
                $str .= '|';
            }
            $str .= "\n+";
            foreach ($keys as $key) {
                for ($i = 0; $i < $tableLength[$key]; $i ++) {
                    $str.= '-';
                }
                $str .= '+';
            }
            $str .= "\n|";
            foreach ($result as $key => $value) {
                $str .= $value;
                $spaceNum = $tableLength[$key] - strlen($value);
                for ($i = 0; $i < $spaceNum; $i ++) {
                    $str.= ' ';
                }
                $str .= '|';
            }
            $str .= "\n+";
            foreach ($keys as $key) {
                for ($i = 0; $i < $tableLength[$key]; $i ++) {
                    $str.= '-';
                }
                $str .= '+';
            }
            $str .= "\n\n\n";
            echo $str;
        }
    }
    $originSize = $nowFileSize;
}
