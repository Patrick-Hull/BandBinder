<?php

class UserType
{
    private DatabaseManager $db;
    private array $data;

    /**
     * @throws Exception
     */
    public function __construct($idUserType)
    {
        $this->db = new DatabaseManager();

        $sql = "SELECT * FROM `user_types` WHERE `idUserType` = ?";
        $rows = $this->db->query($sql, [$idUserType]);
        if (!isset($rows[0])) {
            throw new Exception("User Type not found");
        }

        $this->data = $rows[0];
    }

    public function getIdUserType(): string
    {
        return $this->data['idUserType'];
    }

    public function getUserTypeName(): string
    {
        return $this->data['userTypeName'];
    }

    /**
     * @return string[] Array of permissionTypeHtml values
     * @throws Exception
     */
    public function getPermissions(): array
    {
        $sql = "SELECT `permissionTypeHtml` FROM `user_types__permissions` WHERE `idUserType` = ?";
        $rows = $this->db->query($sql, [$this->getIdUserType()]);
        return array_column($rows, 'permissionTypeHtml');
    }

    /**
     * @throws Exception
     */
    public static function CreateUserType(string $userTypeName, array $permissionHtmls): UserType
    {
        $db = new DatabaseManager();
        $idUserType = Helper::UUIDv4();

        $sql = "INSERT INTO `user_types` (`idUserType`, `userTypeName`) VALUES (?, ?)";
        $db->query($sql, [$idUserType, $userTypeName]);

        foreach ($permissionHtmls as $permHtml) {
            $sql = "INSERT INTO `user_types__permissions` (`idUserTypePermission`, `idUserType`, `permissionTypeHtml`) VALUES (?, ?, ?)";
            $db->query($sql, [Helper::UUIDv4(), $idUserType, $permHtml]);
        }

        return new UserType($idUserType);
    }

    /**
     * @throws Exception
     */
    public function UpdateUserType(string $userTypeName, array $permissionHtmls): void
    {
        $this->db->query(
            "UPDATE `user_types` SET `userTypeName` = ? WHERE `idUserType` = ?",
            [$userTypeName, $this->getIdUserType()]
        );

        $this->db->query(
            "DELETE FROM `user_types__permissions` WHERE `idUserType` = ?",
            [$this->getIdUserType()]
        );

        foreach ($permissionHtmls as $permHtml) {
            $sql = "INSERT INTO `user_types__permissions` (`idUserTypePermission`, `idUserType`, `permissionTypeHtml`) VALUES (?, ?, ?)";
            $this->db->query($sql, [Helper::UUIDv4(), $this->getIdUserType(), $permHtml]);
        }

        $this->data['userTypeName'] = $userTypeName;
    }

    /**
     * @throws Exception
     */
    public function DeleteUserType(): void
    {
        // Remove user type assignment from all users who have it
        $this->db->query(
            "DELETE FROM `users__permissions` WHERE `permissionType` = 'userType' AND `permissionValueHtml` = ?",
            [$this->getIdUserType()]
        );

        // Delete the user type (cascade deletes user_types__permissions rows)
        $this->db->query(
            "DELETE FROM `user_types` WHERE `idUserType` = ?",
            [$this->getIdUserType()]
        );
    }

    /**
     * @return UserType[]
     * @throws Exception
     */
    public static function GetAll(): array
    {
        $db = new DatabaseManager();
        $sql = "SELECT * FROM `user_types` ORDER BY `userTypeName`";
        $rows = $db->query($sql);

        $result = [];
        foreach ($rows as $row) {
            $result[] = new UserType($row['idUserType']);
        }
        return $result;
    }
}
