<?php
class Log
{
    public static function write($array)
    {
        $args = [
            Helper::UUIDv4(),
            $array['idUser'],
            $array['idLogType'],
            $array['logTime'],
            $array['pageName'],
            $array['ipv4']
        ];
        $db = New DatabaseManager();
        $sql = "INSERT INTO `log` (`id`, `idUser`, `idLogType`, `logTime`, `pageName`, `ipv4`) VALUES (?, ?, ?, ?, ?, ?)";
        $db->query($sql, $args);
    }
}