<?php
require_once 'PHPMailer/class.phpmailer.php';
require_once 'PHPMailer/class.smtp.php';

class Mailer
{
    public static $HOST = 'smtp.qq.com'; 
    public static $PORT = 465; //
    public static $SMTP = 'ssl'; // 
    public static $CHARSET = 'UTF-8'; //

    private static $USERNAME = '123456789@qq.com'; //
    private static $PASSWORD = '****************'; // 
    private static $NICKNAME = 'woider'; //

    /**
     */
    public function __construct($debug = false)
    {
        $this->mailer = new PHPMailer();
        $this->mailer->SMTPDebug = $debug ? 1 : 0;
        $this->mailer->isSMTP(); 
    }

    /**
     * @return PHPMailer
     */
    public function getMailer()
    {
        return $this->mailer;
    }

    private function loadConfig()
    {
        /* Server Settings  */
        $this->mailer->SMTPAuth = true; //
        $this->mailer->Host = self::$HOST; // 
        $this->mailer->Port = self::$PORT; //
        $this->mailer->SMTPSecure = self::$SMTP; // 
        /* Account Settings */
        $this->mailer->Username = self::$USERNAME; // 
        $this->mailer->Password = self::$PASSWORD; // 
        $this->mailer->From = self::$USERNAME; //
        $this->mailer->FromName = self::$NICKNAME; //
        /* Content Setting  */
        $this->mailer->isHTML(true); // 
        $this->mailer->CharSet = self::$CHARSET; // 
    }

    public function addFile($path)
    {
        $this->mailer->addAttachment($path);
    }


    public function send($email, $title, $content)
    {
        $this->loadConfig();
        $this->mailer->addAddress($email); //
        $this->mailer->Subject = $title; //
        $this->mailer->Body = $content; //
        return (bool)$this->mailer->send(); //
    }
}
