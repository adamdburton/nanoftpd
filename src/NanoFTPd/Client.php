<?php

namespace AdamDBurton\NanoFTPd\NanoFTPd;

class Client
{
    protected $app;
    protected $config;

    protected $id;
    public $connection;
    public $buffer;
    public $transfertype;
    public $loggedin;
    public $user;
    protected $io;

    protected $user_uid;
    protected $user_gid;

    public $addr;
    public $port;
    public $pasv;

    public $data_addr;
    public $data_port;

    // passive ftp data socket and connection
    protected $data_socket;
    protected $data_conn;

    // active ftp data socket pointer
    protected $data_fsp;

    protected $command;
    protected $parameter;
    protected $return;

    function __construct($app, $connection, $id)
    {
        $this->app = $app;
        $this->config = $app['config'];

        $this->id = $id;
        $this->connection = $connection;

        socket_getpeername($this->connection, $addr, $port);

        $this->addr = $addr;
        $this->port = $port;
        $this->pasv = false;
        $this->loggedin	= false;
    }

    function init()
    {
        $this->io = new IO\File($this->app);
        $this->auth = new IO\Auth();

        $this->buffer = '';

        $this->command		= "";
        $this->parameter	= "";
        $this->buffer		= "";
        $this->transfertype = "A";

        $this->send("220 " . $this->config->get('nanoftpd::server.name'));

        if(!is_resource($this->connection)) die;
    }

    function interact()
    {
        $this->return = true;

        if (strlen($this->buffer))
        {
            $this->command = trim(strtoupper(substr(trim($this->buffer), 0, 4)));
            $this->parameter = trim(substr(trim($this->buffer), 4));

            $command = $this->command;
            $this->io->parameter = $this->parameter;

            if($command == "QUIT")
            {
                $this->app['log']->info('[NanoFTPd] '."client: " . trim($this->buffer) . "\n");
                $this->cmd_quit();

                return $this->return;

            }
            elseif($command == "USER")
            {
                $this->app['log']->info('[NanoFTPd] '."client: " . trim($this->buffer) . "\n");
                $this->cmd_user();

                return $this->return;

            }
            elseif($command == "PASS")
            {
                $this->app['log']->info('[NanoFTPd] '."client: PASS xxxx\n");
                $this->cmd_pass();

                return $this->return;
            }

            $this->app['log']->info('[NanoFTPd] '.$this->user . ": ".trim($this->buffer));

            if(!$this->loggedin)
            {
                $this->send("530 Not logged in.");
            }
            elseif($command == "LIST" || $command == "NLST")
            {
                $this->cmd_list();
            }
            elseif($command == "PASV")
            {
                $this->cmd_pasv();
            }
            elseif($command == "PORT")
            {
                $this->cmd_port();
            }
            elseif($command == "SYST")
            {
                $this->cmd_syst();
            }
            elseif($command == "PWD")
            {
                $this->cmd_pwd();
            }
            elseif($command == "CWD")
            {
                $this->cmd_cwd();
            }
            elseif($command == "CDUP")
            {
                $this->cmd_cwd();
            }
            elseif($command == "TYPE")
            {
                $this->cmd_type();
            }
            elseif($command == "NOOP")
            {
                $this->cmd_noop();
            }
            elseif($command == "RETR")
            {
                $this->cmd_retr();
            }
            elseif($command == "SIZE")
            {
                $this->cmd_size();
            }
            elseif($command == "STOR")
            {
                $this->cmd_stor();
            }
            elseif($command == "DELE")
            {
                $this->cmd_dele();
            }
            elseif($command == "HELP")
            {
                $this->cmd_help();
            }
            elseif($command == "SITE")
            {
                $this->cmd_site();
            }
            elseif($command == "APPE")
            {
                $this->cmd_appe();
            }
            elseif($command == "MKD")
            {
                $this->cmd_mkd();
            }
            elseif ($command == "RMD")
            {
                $this->cmd_rmd();
            }
            elseif($command == "RNFR")
            {
                $this->cmd_rnfr();
            }
            elseif($command == "RNTO")
            {
                $this->cmd_rnto();
            }
            else
            {
                $this->send("502 Command not implemented.");
            }

            return $this->return;
        }
    }

    function disconnect()
    {
        if(is_resource($this->connection)) socket_close($this->connection);

        if($this->pasv)
        {
            if(is_resource($this->data_conn)) socket_close($this->data_conn);
            if(is_resource($this->data_socket)) socket_close($this->data_socket);
        }
    }

    /*
    NAME: help
    SYNTAX: help
    DESCRIPTION: shows the list of available commands...
    NOTE: -
    */
    function cmd_help()
    {
        $this->send(
            "214-" . $this->config->get('nanoftpd::server.name') . "\n"
            ."214-Commands available:\n"
            ."214-APPE\n"
            ."214-CDUP\n"
            ."214-CWD\n"
            ."214-DELE\n"
            ."214-HELP\n"
            ."214-LIST\n"
            ."214-MKD\n"
            ."214-NOOP\n"
            ."214-PASS\n"
            ."214-PASV\n"
            ."214-PORT\n"
            ."214-PWD\n"
            ."214-QUIT\n"
            ."214-RETR\n"
            ."214-RMD\n"
            ."214-RNFR\n"
            ."214-RNTO\n"
            ."214-SIZE\n"
            ."214-STOR\n"
            ."214-SYST\n"
            ."214-TYPE\n"
            ."214-USER\n"
            ."214 HELP command successful."
        );
    }

    /*
    NAME: quit
    SYNTAX: quit
    DESCRIPTION: closes the connection to the server...
    NOTE: -
    */
    function cmd_quit()
    {
        $this->send("221 Disconnected from " . $this->config->get('nanoftpd::server.name') . ". Have a nice day.");
        $this->disconnect();

        $this->return = false;
    }

    /*
    NAME: user
    SYNTAX: user <username>
    DESCRIPTION: logs <username> in...
    NOTE: -
    */
    function cmd_user()
    {
        $this->loggedin = false;
        $this->user = $this->parameter;

        $this->send("331 Password required for " . $this->user . ".");
    }

    /*
    NAME: pass
    SYNTAX: pass <password>
    DESCRIPTION: checks <password>, whether it's correct...
    NOTE: added authentication library support by Phanatic (26/12/2002)
    */
    function cmd_pass()
    {
        if(!$this->user)
        {
            $this->user = "";
            $this->loggedin = false;

            $this->send("530 Not logged in.");
        }

        //$pass = $this->app['hash']->make($this->parameter);
        $pass = $this->parameter;

        if($this->config->get('nanoftpd::users.driver') == 'eloquent')
        {
            $auth = new Auth\Eloquent($this->app);
        }
        elseif($this->config->get('nanoftpd::users.driver') == 'database')
        {
            $auth = new Auth\Database($this->app);
        }
        else
        {
            throw new \Exception('NanoFTPd Authentication driver ' . $this->config->get('nanoftpd::users.driver') . ' not available');
        }

        if(($user = $auth->authenticate($this->user, $pass)))
        {
            $this->send("230 User " . $this->user . " logged in from " . $this->addr . ".");
            $this->loggedin = true;

            $this->user_uid = $auth->getUID();
            $this->user_gid = $auth->getGID();
            $this->io->root = $auth->getHomeDirectory();
        }
        else
        {
            $this->send("530 Not logged in.");
            $this->loggedin = false;

            $this->cmd_quit();

            return;
        }

        if(!$this->auth->auth($this->user_uid, $this->user_gid))
        {
            $this->send("550 Root access is not allowed.");
            $this->cmd_quit();
        }

    }

    /*
    NAME: syst
    SYNTAX: syst
    DESCRIPTION: returns system type...
    NOTE: -
    */
    function cmd_syst()
    {
        $this->send("215 UNIX Type: L8");
    }

    /*
    NAME: cwd / cdup
    SYNTAX: cwd <directory> / cdup
    DESCRIPTION: changes current directory to <directory> / changes current directory to parent directory...
    NOTE: -
    */
    function cmd_cwd()
    {
        if($this->command == "CDUP")
        {
            $this->io->parameter = "..";
        }

        if($this->io->cwd() !== false)
        {
            $this->send("250 CWD command succesful.");
        }
        else
        {
            $this->send("450 Requested file action not taken.");
        }
    }

    /*
    NAME: pwd
    SYNTAX: pwd
    DESCRIPTION: returns current directory...
    NOTE: -
    */
    function cmd_pwd()
    {
        $dir = $this->io->pwd();
        $this->send("257 \"" . $dir . "\" is current directory.");
    }

    /*
    NAME: list
    SYNTAX: list
    DESCRIPTION: returns the filelist of the current directory...
    NOTE: should implement the <directory> parameter to be RFC-compilant...
    */
    function cmd_list()
    {
        $ret = $this->data_open();

        if(!$ret)
        {
            $this->send("425 Can't open data connection.");
            return;
        }

        $this->send("150 Opening  " . $this->transfer_text() . " data connection.");

        foreach($this->io->ls() as $info)
        {
            // formatted list output added by Phanatic
            $formatted_list = sprintf("%-11s%-2s%-15s%-15s%-10s%-13s".$info['name'], $info['perms'], "1", $info['owner'], $info['group'], $info['size'], $info['time']);

            $this->data_send($formatted_list);
            $this->data_eol();
        }

        $this->data_close();

        $this->send("226 Transfer complete.");
    }

    /*
    NAME: dele
    SYNTAX: dele <filename>
    DESCRIPTION: delete <filename>...
    NOTE: authentication check added by Phanatic (26/12/2002)
    */
    function cmd_dele()
    {
        if (strpos(trim($this->parameter), "..") !== false)
        {
            $this->send("550 Permission denied.");
            return;
        }

        if (substr($this->parameter, 0, 1) == "/")
        {
            $file = $this->io->root.$this->parameter;
        }
        else
        {
            $file = $this->io->root.$this->io->cwd.$this->parameter;
        }

        if(!is_file($file))
        {
            $this->send("550 Resource is not a file.");
        }
        else
        {
            if(!$this->auth->can_write($file))
            {
                $this->send("550 Permission denied.");
            }
            else
            {
                if(!$this->io->rm($this->parameter))
                {
                    $this->send("550 Couldn't delete file.");
                }
                else
                {
                    $this->send("250 Delete command successful.");
                }
            }
        }
    }

    /*
    NAME: mkd
    SYNTAX: mkd <directory>
    DESCRIPTION: creates the specified directory...
    NOTE: -
    */
    function cmd_mkd()
    {
        $dir = trim($this->parameter);

        if(strpos($dir, "..") !== false)
        {
            $this->send("550 Permission denied.");
            return;
        }

        if(!$this->io->md($dir))
        {
            $this->send("553 Requested action not taken.");
        }
        else
        {
            $this->send("250 MKD command successful.");
        }
    }

    /*
    NAME: rmd
    SYNTAX: rmd <directory>
    DESCRIPTION: removes the specified directory (must be empty)...
    NOTE: -
    */
    function cmd_rmd()
    {
        $dir = trim($this->parameter);

        if (strpos($dir, "..") !== false)
        {
            $this->send("550 Permission denied.");
            return;
        }

        if (!$this->io->rd($dir))
        {
            $this->send("553 Requested action not taken.");
        }
        else
        {
            $this->send("250 RMD command successful.");
        }
    }

    /*
    NAME: rnfr
    SYNTAX: rnfr <file>
    DESCRIPTION: sets the specified file for renaming...
    NOTE: -
    */
    function cmd_rnfr()
    {
        $file = trim($this->parameter);

        if(strpos($file, "..") !== false)
        {
            $this->send("550 Permission denied.");
            return;
        }

        if(!$this->io->exists($file))
        {
            $this->send("553 Requested action not taken.");
            return;
        }

        $this->rnfr = $file;
        $this->send("350 RNFR command successful.");
    }

    /*
    NAME: rnto
    SYNTAX: rnto <file>
    DESCRIPTION: sets the target of the renaming...
    NOTE: -
    */
    function cmd_rnto()
    {
        $file = trim($this->parameter);

        if(!isset($this->rnfr) || strlen($this->rnfr) == 0)
        {
            $this->send("550 Requested file action not taken (need an RNFR command).");
            return;
        }

        if(strpos($file, "..") !== false)
        {
            $this->send("550 Permission denied.");
            return;
        }

        if ($this->io->rn($this->rnfr, $file))
        {
            $this->send("250 RNTO command successful.");
        }
        else
        {
            $this->send("553 Requested action not taken.");
        }
    }

    /*
    NAME: stor
    SYNTAX: stor <file>
    DESCRIPTION: stores a local file on the server...
    NOTE: -
    */
    function cmd_stor()
    {
        $file = trim($this->parameter);

        if ($this->io->exists($file))
        {
            if($this->io->type($file) == "dir")
            {
                $this->send("553 Requested action not taken.");
                return;
            }
            elseif(!$this->io->rm($file))
            {
                $this->send("553 Requested action not taken.");
                return;
            }
        }

        $this->send("150 File status okay; opening " . $this->transfer_text() . " connection.");
        $this->data_open();

        $this->io->open($file, true);

        if($this->pasv)
        {
            while(($buf = socket_read($this->data_conn, 512)) !== false)
            {
                if (! strlen($buf)) break;
                $this->io->write($buf);
            }
        }
        else
        {
            while (!feof($this->data_fsp))
            {
                $buf = fgets($this->data_fsp, 16384);
                $this->io->write($buf);
            }
        }

        $this->io->close();

        $this->data_close();
        $this->send("226 transfer complete.");
    }

    /*
    NAME: appe
    SYNTAX: appe <file>
    DESCRIPTION: if <file> exists, the recieved data should be appended to that file...
    NOTE: -
    */
    function cmd_appe()
    {
        $file = trim($this->parameter);

        if (strpos($file, "..") !== false)
        {
            $this->send("550 Permission denied.");
            return;
        }

        $this->send("150 File status okay; openening " . $this->transfer_text() . " connection.");
        $this->data_open();

        if($this->io->exists($file))
        {
            if($this->io->type($file) == "dir")
            {
                $this->send("553 Requested action not taken.");
                return;
            }
            else
            {
                $this->io->open($file, false, true);
            }
        }
        else
        {
            $this->io->open($file, true);
        }

        if($this->pasv)
        {
            while(($buf = socket_read($this->data_conn, 512)) !== false)
            {
                if(!strlen($buf)) break;

                $this->io->write($buf);
            }
        }
        else
        {
            while(!feof($this->data_fsp))
            {
                $buf = fgets($this->data_fsp, 16384);
                $this->io->write($buf);
            }
        }

        $this->io->close();

        $this->data_close();
        $this->send("226 transfer complete.");
    }

    /*
    NAME: retr
    SYNTAX: retr <file>
    DESCRIPTION: retrieve a file from the server...
    NOTE: authentication check added by Phanatic (26/12/2002)
    */
    function cmd_retr()
    {
        $file = trim($this->parameter);

        if (strpos($file, "..") !== false)
        {
            $this->send("550 Permission denied.");
            return;
        }

        $filename = $this->io->root.$this->io->cwd.$file;

        if(!is_file($filename))
        {
            $this->send("550 Resource is not a file.");
            return;
        }
        else
        {
            if (!$this->io->exists($file))
            {
                $this->send("553 Requested action not taken.");
                return;
            }

            if(!$this->auth->can_read($filename))
            {
                $this->send("550 Permission denied.");
                return;
            }
            else
            {
                $size = $this->io->size($file);

                $this->io->open($file);
                $this->data_open();
                $this->send("150 " . $this->transfer_text() . " connection for " . $file . " (" . $size . " bytes).");

                if ($this->transfertype == "A")
                {
                    $file = str_replace("\n", "\r", $this->io->read($size));
                    $this->data_send($file);
                }
                else
                {
                    while ($data = $this->io->read(1024))
                    {
                        $this->data_send($data);
                    }
                }

                $this->io->close();

                $this->send("226 transfer complete.");
                $this->data_close();
            }
        }
    }

    function cmd_pasv()
    {
        $pool = $this->app['nanoftpd']->pasv_pool;

        if($this->pasv)
        {
            if (is_resource($this->data_conn)) socket_close($this->data_conn);
            if (is_resource($this->data_socket)) socket_close($this->data_socket);

            $this->data_conn = false;
            $this->data_socket = false;

            if ($this->data_port) $pool->remove($this->data_port);
        }

        $this->pasv = true;

        $low_port = $this->config['nanoftpd::server.passive.low'];
        $high_port = $this->config['nanoftpd::server.passive.high'];

        $try = 0;

        if(($socket = socket_create(AF_INET, SOCK_STREAM, 0)) < 0)
        {
            $this->send("425 Can't open data connection.");
            return;
        }

        // reuse listening socket address
        if(!@socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1))
        {
            $this->send("425 Can't open data connection.");
            return;
        }

        for ($port = $low_port; $port <= $high_port && $try < 4; $port++)
        {
            if(!$pool->exists($port))
            {
                $try++;

                $c = socket_bind($socket, $this->config->get('nanoftpd::server.ip'), $port);

                if($c >= 0)
                {
                    $pool->add($port);
                    break;
                }
            }
        }

        if(!$c)
        {
            $this->send("452 Can't open data connection.");
            return;
        }

        socket_listen($socket);

        $this->data_socket = $socket;
        $this->data_port = $port;

        $p1 = $port >> 8;
        $p2 = $port & 0xff;

        $tmp = str_replace(".", ",", $this->config->get('nanoftpd::server.ip'));
        $this->send("227 Entering Passive Mode ({$tmp},{$p1},{$p2}).");
    }

    function cmd_port()
    {
        $data = explode(",", $this->parameter);

        if(count($data) != 6)
        {
            $this->send("500 Wrong number of Parameters.");
            return;
        }

        $p2 = array_pop($data);
        $p1 = array_pop($data);

        $port = ($p1 << 8) + $p2;

        foreach($data as $ip_seg)
        {
            if(!is_numeric($ip_seg) || $ip_seg > 255 || $ip_seg < 0)
            {
                $this->send("500 Bad IP address " . implode(".", $data) . ".");
                return;
            }
        }

        $ip = implode(".", $data);

        if(!is_numeric($p1) || !is_numeric($p2) || ! $port)
        {
            $this->send("500 Bad Port number.");
            return;
        }

        $this->data_addr = $ip;
        $this->data_port = $port;

        $this->app['log']->info('[NanoFTPd] '.$this->user.": server: Client suggested: $ip:$port");
        $this->send("200 PORT command successful.");
    }

    function cmd_type()
    {
        $type = trim(strtoupper($this->parameter));

        if(strlen($type) != 1)
        {
            $this->send("501 Syntax error in parameters or arguments.");
        }
        elseif($type != "A" && $type != "I")
        {
            $this->send("501 Syntax error in parameters or arguments.");
        }
        else
        {
            $this->transfertype = $type;
            $this->send("200 type set.");
        }
    }

    function cmd_size()
    {
        $file = trim($this->parameter);

        if(strpos($file, "..") !== false)
        {
            $this->send("550 Permission denied.");
            return;
        }

        if(!$this->io->exists($file))
        {
            $this->send("553 Requested action not taken.");
            return;
        }

        $size = $this->io->size($file);

        if($size === false)
        {
            $this->send("553 Requested action not taken.");
            return;
        }

        $this->send("213 " . $size);
    }

    function cmd_noop()
    {
        $this->send("200 Nothing Done.");
    }

    /*
    NAME: site
    SYNTAX: site <command> <parameters>
    DESCRIPTION: server specific commands...
    NOTE: chmod feature built in by Phanatic (01/01/2003)
    */
    function cmd_site()
    {
        $p = explode(" ", $this->parameter);

        switch(strtolower($p[0]))
        {
            case "uid":
                $this->send("214 UserID: ".$this->user_uid);
                break;
            case "gid":
                $this->send("214 GroupID: ".$this->user_gid);
                break;
            case "chmod":
                if(!isset($p[1]) || !isset($p[2]))
                {
                    $this->send("214 Not enough parameters. Usage: SITE CHMOD <mod> <filename>.");
                }
                else
                {
                    if(strpos($p[2], "..") !== false)
                    {
                        $this->send("550 Permission denied.");
                        return;
                    }

                    if (substr($p[2], 0, 1) == "/")
                    {
                        $file = $this->io->root.$p[2];
                    }
                    else
                    {
                        $file = $this->io->root.$this->io->cwd.$p[2];
                    }

                    if (!$this->io->exists($p[2]))
                    {
                        $this->send("550 File or directory doesn't exist.");
                        return;
                    }

                    if(!$this->auth->can_write($file))
                    {
                        $this->send("550 Permission denied.");
                    }
                    else
                    {
                        $p[1] = escapeshellarg($p[1]);
                        $file = escapeshellarg($file);

                        exec("chmod ".$p[1]." ".$file, $output, $return);

                        if ($return != 0)
                        {
                            $this->send("550 Command failed.");
                        }
                        else
                        {
                            $this->send("200 SITE CHMOD command successful.");
                        }
                    }
                }
                break;

            default:
                $this->send("502 Command not implemented.");
        }
    }

    function data_open()
    {
        if($this->pasv)
        {
            if(!$conn = @socket_accept($this->data_socket))
            {
                $this->app['log']->info('[NanoFTPd] '.$this->user.": server: Client not connected\n");

                return false;
            }

            if(!socket_getpeername($conn, $peer_ip, $peer_port))
            {
                $this->app['log']->info('[NanoFTPd] '.$this->user.": server: Client not connected\n");
                $this->data_conn = false;

                return false;
            }
            else
            {
                $this->app['log']->info('[NanoFTPd] '.$this->user.": server: Client connected ($peer_ip:$peer_port)\n");
            }

            $this->data_conn = $conn;
        }
        else
        {
            $fsp = fsockopen($this->data_addr, $this->data_port, $errno, $errstr, 30);

            if(!$fsp)
            {
                $this->app['log']->info('[NanoFTPd] '.$this->user.": server: Could not connect to client\n");

                return false;
            }

            $this->data_fsp = $fsp;
        }

        return true;
    }

    function data_close()
    {
        if(!$this->pasv)
        {
            if (is_resource($this->data_fsp)) fclose($this->data_fsp);
            $this->data_fsp = false;
        }
        else
        {
            socket_close($this->data_conn);
            $this->data_conn = false;
        }
    }

    function data_send($str)
    {
        if ($this->pasv)
        {
            socket_write($this->data_conn, $str, strlen($str));
        }
        else
        {
            fputs($this->data_fsp, $str);
        }
    }

    function data_read()
    {
        if($this->pasv)
        {
            return socket_read($this->data_conn, 1024);
        }
        else
        {
            return fread($this->data_fsp, 1024);
        }
    }

    function data_eol()
    {
        $eol = ($this->transfertype == "A") ? "\r\n" : "\n";
        $this->data_send($eol);
    }


    function send($str)
    {
        socket_write($this->connection, $str . "\n");

        if(!$this->loggedin)
        {
            $this->app['log']->info('[NanoFTPd] '."server: $str\n");
        }
        else
        {
            $this->app['log']->info('[NanoFTPd] '.$this->user.": server: $str\n");
        }
    }

    function transfer_text()
    {
        return ($this->transfertype == "A") ? "ASCII mode" : "Binary mode";
    }
}