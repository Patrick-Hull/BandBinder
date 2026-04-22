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
    public function getUsername()
    {
        return $this->data['username'];
    }
    public function getEmail()
    {
        return $this->data['email'];
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
            } elseif($row['permissionType'] == 'userType'){
                try {
                    $userType = new UserType($row['permissionValueHtml']);
                    $typePermissions = $userType->getPermissions();
                    foreach ($typePermissions as $perm) {
                        $permissionsArray[] = $perm;
                    }
                } catch (Exception $e) {
                    // User type no longer exists, skip
                }
            } else {
                $permissionsArray[] = $row['permissionValueHtml'];
            }

        }

        return array_unique($permissionsArray);
    }

    /**
     * @return User[]
     * @throws Exception
     */
    public static function GetAll(): array
    {
        $db = new DatabaseManager();
        $sql = "SELECT * FROM `users` ORDER BY `nameShort`, `username`";
        $rows = $db->query($sql);
        $result = [];
        foreach ($rows as $row) {
            $result[] = new User($row['id']);
        }
        return $result;
    }

    /**
     * @throws Exception
     */
    public static function CreateUser(string $username, string $password, string $email, ?string $nameShort, ?string $nameFirst, ?string $nameLast): User
    {
        $db = new DatabaseManager();
        $id = Helper::UUIDv4();
        $hashedPassword = null;
        
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        }

        $sql = "INSERT INTO `users` (`id`, `username`, `password`, `email`, `nameShort`, `nameFirst`, `nameLast`) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $db->query($sql, [$id, $username, $hashedPassword, $email, $nameShort, $nameFirst, $nameLast]);

        return new User($id);
    }

    /**
     * @throws Exception
     */
    public function UpdateUser(string $username, string $email, ?string $nameShort, ?string $nameFirst, ?string $nameLast): void
    {
        $sql = "UPDATE `users` SET `username` = ?, `email` = ?, `nameShort` = ?, `nameFirst` = ?, `nameLast` = ? WHERE `id` = ?";
        $this->db->query($sql, [$username, $email, $nameShort, $nameFirst, $nameLast, $this->getIdUser()]);
        $this->data['username'] = $username;
        $this->data['email'] = $email;
        $this->data['nameShort'] = $nameShort;
        $this->data['nameFirst'] = $nameFirst;
        $this->data['nameLast'] = $nameLast;
    }

    /**
     * @throws Exception
     */
    public function UpdatePassword(string $newPassword): void
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE `users` SET `password` = ? WHERE `id` = ?";
        $this->db->query($sql, [$hashedPassword, $this->getIdUser()]);
    }

    /**
     * @throws Exception
     */
    public function DeleteUser(): void
    {
        $this->db->query("DELETE FROM `link__user_instrument` WHERE `idUser` = ?", [$this->getIdUser()]);
        $this->db->query("DELETE FROM `users__permissions` WHERE `idUser` = ?", [$this->getIdUser()]);
        $this->db->query("DELETE FROM `users` WHERE `id` = ?", [$this->getIdUser()]);
    }

    /**
     * @return Instrument[]
     * @throws Exception
     */
    public function getInstruments(): array
    {
        $sql = "SELECT `idInstrument` FROM `link__user_instrument` WHERE `idUser` = ?";
        $rows = $this->db->query($sql, [$this->getIdUser()]);
        $instruments = [];
        foreach ($rows as $row) {
            try {
                $instruments[] = new Instrument($row['idInstrument']);
            } catch (Exception $e) {
                // Instrument no longer exists, skip
            }
        }
        return $instruments;
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getRawInstrumentIds(): array
    {
        $sql = "SELECT `idInstrument` FROM `link__user_instrument` WHERE `idUser` = ?";
        $rows = $this->db->query($sql, [$this->getIdUser()]);
        return array_column($rows, 'idInstrument');
    }

    /**
     * @throws Exception
     */
    public function setInstruments(array $instrumentIds): void
    {
        $this->db->query("DELETE FROM `link__user_instrument` WHERE `idUser` = ?", [$this->getIdUser()]);
        foreach ($instrumentIds as $idInstrument) {
            $sql = "INSERT INTO `link__user_instrument` (`idLink`, `idUser`, `idInstrument`) VALUES (?, ?, ?)";
            $this->db->query($sql, [Helper::UUIDv4(), $this->getIdUser(), $idInstrument]);
        }
    }

    /**
     * @return array Raw permission rows from users__permissions
     * @throws Exception
     */
    public function getUserPermissionRows(): array
    {
        $sql = "SELECT * FROM `users__permissions` WHERE `idUser` = ?";
        return $this->db->query($sql, [$this->getIdUser()]);
    }

    /**
     * @param array $rows Each element: ['permissionType' => '...', 'permissionValueHtml' => '...']
     * @throws Exception
     */
    public function setPermissions(array $rows): void
    {
        $this->db->query("DELETE FROM `users__permissions` WHERE `idUser` = ?", [$this->getIdUser()]);
        foreach ($rows as $row) {
            $sql = "INSERT INTO `users__permissions` (`idUserPermission`, `idUser`, `permissionType`, `permissionValueHtml`) VALUES (?, ?, ?, ?)";
            $this->db->query($sql, [Helper::UUIDv4(), $this->getIdUser(), $row['permissionType'], $row['permissionValueHtml']]);
        }
    }

    /**
     * @return UserType|null
     * @throws Exception
     */
    public function getUserType(): ?UserType
    {
        $sql = "SELECT `permissionValueHtml` FROM `users__permissions` WHERE `idUser` = ? AND `permissionType` = 'userType' LIMIT 1";
        $rows = $this->db->query($sql, [$this->getIdUser()]);
        if (!isset($rows[0])) {
            return null;
        }
        try {
            return new UserType($rows[0]['permissionValueHtml']);
        } catch (Exception $e) {
            return null;
        }
    }


    public function enableTOTP(string $totpSecret): array
    {
        $sql = 'UPDATE `users` SET `totpEnabled` = 1, `totpSecret` = ? WHERE id = ?;';
        $args = [$totpSecret, $this->getIdUser()];
        $this->data['totpEnabled'] = 1;
        $this->data['totpSecret'] = $totpSecret;
        return $this->db->query($sql, $args);
    }

    public function checkTotpCodeProvided(string $totpCode): bool
    {
        $issuer = 'BandBinder';
        $label = $this->getUsername();
        $otp = TOTP::createFromSecret($this->getTotpSecret());
        $otp = $otp->withIssuer($issuer)->withLabel($label);
        return $otp->verify($totpCode);
    }

    public function disableTOTP(): void
    {
        $sql = 'UPDATE `users` SET `totpEnabled` = 0, `totpSecret` = NULL WHERE id = ?;';
        $this->db->query($sql, [$this->getIdUser()]);
    }

    public function hasPassword(): bool
    {
        return !empty($this->data['password']);
    }

    public function sendWelcomeEmail(): bool
    {
        $db = new DatabaseManager();
        
        $result = $db->query("SELECT config_value FROM site_config WHERE config_key = 'mail_settings'");
        if (!$result || count($result) === 0) {
            return false;
        }
        
        $mailConfig = json_decode($result[0]['config_value'], true);
        if (!$mailConfig) {
            return false;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $tokenId = Helper::UUIDv4();
        $db->query(
            "INSERT INTO `password_reset_tokens` (`id`, `user_id`, `token`, `expires_at`) VALUES (?, ?, ?, ?)",
            [$tokenId, $this->getIdUser(), $token, $expiresAt]
        );

        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $resetUrl = $baseUrl . '/set-password.php?token=' . $token;

        $subject = 'Welcome to BandBinder - Set Your Password';
        $body = '
        <html>
        <head>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .card { background: #f8f9fa; border-radius: 8px; padding: 30px; margin-top: 20px; }
                .btn { display: inline-block; padding: 12px 24px; background: #0d6efd; color: #fff; text-decoration: none; border-radius: 6px; margin-top: 20px; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <h2>Welcome to BandBinder!</h2>
                <p>Hello ' . htmlspecialchars($this->getNameShort() ?: $this->getUsername()) . ',</p>
                <p>An account has been created for you on BandBinder. To get started, please set your password by clicking the button below:</p>
                <div class="card">
                    <a href="' . $resetUrl . '" class="btn">Set Your Password</a>
                </div>
                <p>This link will expire in 24 hours.</p>
                <div class="footer">
                    <p>If you did not expect this email, please ignore it.</p>
                </div>
            </div>
        </body>
        </html>
        ';

        return Mail::send($this->getEmail(), $subject, $body);
    }

}
