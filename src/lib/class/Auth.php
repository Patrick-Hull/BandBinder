<?php
class Auth
{


    /**
     * Check User Login. Returns CX_User object or throws Exception
     * @throws Exception
     */
    public static function UserLogin($username, $password): User
    {
        $db = New DatabaseManager();
        $sql = "SELECT * FROM `users` WHERE `username` = ?";
        $args = [$username];
        $rows = $db->query($sql, $args);
        if(count($rows) == 0){
            throw new Exception("Invalid Login. Error CX1");
        }
        $row = $rows[0];

        if(password_verify($password, $row['password'])){
            $user = New User($row['id']);
        } else {
            throw new Exception("Invalid Login. Error CX2");
        }

        return $user;

    }

    public static function SetSessionData(User $user)
    {
        // We are loged in
        session_regenerate_id();


        // If we're still here, add some things to the session
        $_SESSION['user']['me'] = $user->getIdUser();
        $_SESSION['user']['permissions'] = $user->getSitePermissions();
        $_SESSION['idPosition'] = 0;

        // In the tiny chance that a session id is hi-jacked before it has expired
        // we record as much /unique/ data as we on login and recheck that the same
        // data is being passed on every request
        $_SESSION['auth']['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['auth']['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];

        //  Mention the login in the log table
        $logArray = array
        (
            'idUser' => $_SESSION['user']['me'],
            'idLogType' => 1,
            'logTime' => $_SERVER['REQUEST_TIME'],
            'pageName' => $_SERVER['REQUEST_URI'],
            'ipv4' => $_SERVER['REMOTE_ADDR']
        );

        $log = new Log();
        Log::write($logArray);

    }
}