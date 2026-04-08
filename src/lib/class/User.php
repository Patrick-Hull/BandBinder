<?php

use OTPHP\TOTP;

class User
{
    public DatabaseManager $db;
    private array $data;

    /**
     * @throws Exception
     */
    public function __construct($idUser)
    {
        $this->db = New DatabaseManager();

        $sql = "SELECT * FROM `users` WHERE id = ?";
        $args = [$idUser];
        $rows = $this->db->query($sql, $args);
        if(!isset($rows[0])){
            throw new Exception("User not found");
        }

        $this->data = $rows[0];

    }

    public function getIdUser()
    {
        return $this->data['id'];
    }
    public function getNameShort()
    {
        return $this->data['nameShort'];
    }
    public function getNameFirst()
    {
        return $this->data['nameFirst'];
    }
    public function getNameLast()
    {
        return $this->data['nameLast'];
    }
    public function getTotpEnabled()
    {
        return $this->data['totpEnabled'];
    }
    public function getTotpSecret()
    {
        return $this->data['totpSecret'];
    }

    public function getSitePermissions(): array
    {
        $permissionsArray = [];
        $sql = "SELECT * FROM `users__permissions` WHERE idUser = ?";
        $args = [$this->getIdUser()];
        $rows = $this->db->query($sql, $args);
        foreach ($rows as $row){
            if($row['permissionType'] == 'group'){
                $sql = "SELECT * FROM `site__permissions` WHERE permissionGroupHtml = ?";
                $args = [$row['permissionValueHtml']];
                $groupPermissions = $this->db->query($sql, $args);
                foreach ($groupPermissions as $groupPermission){
                    $permissionsArray[] = $groupPermission['permissionTypeHtml'];
                }
            } else {
                $permissionsArray[] = $row['permissionValueHtml'];
            }

        }

        return $permissionsArray;
    }


    public function enableTOTP(string $totpSecret): array
    {
        $sql = 'UPDATE `users` SET `totpEnabled` = 1, `totpSecret` = ? WHERE id = ?;';
        $args = [$totpSecret, $this->getIdUser()];
        return $this->db->query($sql, $args);
    }

    public function checkTotpCodeProvided(string $totpCode): bool
    {

        $otp = TOTP::createFromSecret($this->getTotpSecret());
        return $otp->verify($totpCode);
    }

}