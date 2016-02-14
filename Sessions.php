<?php
/**
 * Class for writing PHP sessions to the database.
 */
class Sessions
{
    /**
     * Determine whether the session is new.
     */
    private $new = false;
    
    /**
     * The lifespan of a given session in seconds.
     */
    private $lifespan = 1800;
    
    /**
     * The session id for a given session as assigned by PHP.
     */
    private $id;
    
    /**
     * The current instance of the Sessions() class.
     * @var Sessions
     */
    private static $handler = false;
    
    /**
     * Returns an instance of the Sessions class.
     * @var Sessions
     */
    public static function getHandler()
    {
        if(self::$handler === false)
        {
            self::$handler = new Sessions();
        }
        
        return self::$handler;
    }
    
    public function open($sessionPath, $sessionName)
    {
        
    }
    
    /**
     * Write changes to the session back to the database.
     */
    public function write($sessionId, $data)
    {
        if($this->new)
        {
            Db::query(
                sprintf(
                    "INSERT into system.sessions(id, data, expires, lifespan) VALUES('%s', '%s', %d, %d)",
                    $sessionId, 
                    Db::escape($data), 
                    time() + $this->lifespan, 
                    $this->lifespan
                ), 
                'main'
            );
        }
        else
        {
            if($_GET['no_extend']==true)
            {
                return true;
            }
            else{
            Db::query(
                sprintf(
                    "UPDATE system.sessions SET data = '%s', expires = %d WHERE id = '%s'",
                    db::escape($data), time() + $this->lifespan, $sessionId
                ),
                'main'
            );
            }
        }
        return true;
    }
    
    /**
     * Get the current session id.
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Bind a user to this session. This ends all other sessions the user is currently assigned to.
     */
    public static function bindUser($userId)
    {
        if(Application::$config['custom_sessions'])
        {
            Db::query(sprintf("DELETE FROM system.sessions WHERE user_id = %d", $userId), 'main');
            Db::query(sprintf("UPDATE system.sessions SET user_id = %d WHERE id = '%s'", $userId, self::getHandler()->getId()), 'main');
        }
    }
    
    /**
     * Read the session data from the database.
     * @return mixed
     */
    public function read($sessionId)
    {
        $this->id = $sessionId;
        $result = reset(
            Db::query(
                sprintf("SELECT data, lifespan, expires FROM system.sessions WHERE id = '%s'", $sessionId, time()),
                'main'
            )
        );
        if($result['expires'] <= time())
        {
            Db::query(sprintf("DELETE FROM system.sessions WHERE id = '%s'", $sessionId), 'main');
            $this->new = true;
            return '';
        }
        else if(count($result) == 0)
        {
            Db::query(sprintf("DELETE FROM system.sessions WHERE id = '%s'", $sessionId), 'main');
            $this->new = true;
            return '';
        }
        else
        {
            $this->lifeSpan = $result['lifespan'];
            return $result['data'];
        }
    }
    
    public function close()
    {
        return true;
    }
    
    /**
     * Destroys the session essentially logging the user out of the system.
     */
    public function destroy($sessionId)
    {
        Db::query(sprintf("DELETE FROM system.sessions WHERE id = '%s'", $sessionId), 'main');
        return true;        
    }
    
    /**
     * Garbage collect all expired sessions.
     */
    public function gc($lifetime)
    {
        Db::query(sprintf("DELETE FROM system.sessions WHERE expiry < %d", time()), 'main');
        return true;
    }
    
    /**
     * Returns true when the session is new and false otherwise.
     * @return bool
     */
    public function isNew()
    {
        return $this->new;
    }
}

