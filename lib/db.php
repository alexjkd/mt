<?php

class db {

    private static $instance = NULL;

    public static function getInstance() {
        if(!self::$instance) {
            self::$instance = new PDO('mysql:dbname=mt;host=localhost', 'mws', 'mws9lBl88G2uvVtcHw$'); 
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    return self::$instance;
    }
}

?>
