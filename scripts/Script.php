<?php

class Script 
{
    public function expandedMenus($data)
    {
        session_start();
        if (!$_SESSION['user_id']) {
            return null;
        }
        
        $file = "../../../../app/cache/menus/expandable_{$_SESSION['user_id']}.txt";
        $content = file_get_contents($file);
        
        if ($data['save'] == 'false' || !$data['item']) {
            return $content ? $content : '';
        } else if (!$data['save'] && $data['item']) {
            file_put_contents($file, $data['item']);
            User::log("Logged out");
            $_SESSION = array();
            
            return $data['item'];
        }
        
        $item = explode('=', $data['item']);
        
        if ($item[1] == 'block') {
            $content = $content ? $content . "$item[0];" : "$item[0];";
        } else {
            $content = str_replace("$item[0];", "", $content);
        }

        file_put_contents($file, $content);
        return $content;
    }
}
