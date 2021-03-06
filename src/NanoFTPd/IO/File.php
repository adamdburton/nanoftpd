<?php

namespace AdamDBurton\NanoFTPd\NanoFTPd\IO;

class File
{
    public $parameter;
    public $root;
    public $cwd;

    protected $fp;

    function __construct($app)
    {
        $this->root = "/";
        $this->cwd = "/";
    }

    function cwd()
    {
        $dir = trim($this->parameter);
        $cwd_path = preg_split("/\//", $this->cwd, -1, PREG_SPLIT_NO_EMPTY);
        $new_cwd = "";

        switch(TRUE)
        {
            case (!strlen($dir)):
                return $this->cwd;

            case ($dir == ".."):
                if (count($cwd_path)) {
                    array_pop($cwd_path);
                    $terminate = (count($cwd_path) > 0) ? "/" : "";
                    $new_cwd = "/" . implode("/", $cwd_path) . $terminate;
                }
                else
                {
                    return false;
                }
                break;

            case (substr($dir, 0, 1) == "/"):
                if (strlen($dir) == 1)
                {
                    $new_cwd = "/";
                }
                else
                {
                    $new_cwd = rtrim($dir, "/") . "/";
                }
                break;

            default:
                $new_cwd = $this->cwd . rtrim($dir, "/") . "/";
                break;
        }

        if(strpos($new_cwd, "..") !== false) return false;

        if(file_exists($this->root . $new_cwd) && filetype($this->root . $new_cwd) == "dir")
        {
            $this->cwd = $new_cwd;

            return $this->cwd;
        }
        else
        {
            return false;
        }
    }

    function pwd()
    {
        return $this->cwd;
    }

    function ls()
    {
        $list = array();

        if ($handle = opendir($this->root . $this->cwd))
        {
            while(false !== ($file = readdir($handle)))
            {
                if($file == "." || $file == "..") continue;

                $filename = $this->root . $this->cwd . $file;
                $filetype = filetype($filename);

                if($filetype != "dir" && $filetype != "file") continue;

                $filesize = ($filetype == "file") ? filesize($filename) : 0;

                /* owner, group, last modification and access info added by Phanatic */
                $owner = posix_getpwuid(fileowner($filename));
                $fileowner = $owner['name'];

                $group = posix_getgrgid(filegroup($filename));
                $filegroup = $group['name'];

                $mtime = filemtime($filename);
                $filemod = date("M d H:i", $mtime);

                $fileperms = $this->perms(fileperms($filename));

                clearstatcache();

                $info = array(
                    "name" => $file,
                    "size" => $filesize,
                    "owner" => $fileowner,
                    "group" => $filegroup,
                    "time" => $filemod,
                    "perms" => $fileperms
                );

                $list[] = $info;
            }

            closedir($handle);

            return $list;
        }
        else
        {
            return false;
        }
    }

    function rm($filename)
    {
        if(substr($filename, 0, 1) == "/")
        {
            return unlink($this->root . $filename);
        }
        else
        {
            return unlink($this->root . $this->cwd . $filename);
        }
    }

    function size($filename)
    {
        if(substr($filename, 0, 1) == "/")
        {
            return filesize($this->root . $filename);
        }
        else
        {
            return filesize($this->root . $this->cwd . $filename);
        }
    }

    function exists($filename)
    {
        if(substr($filename, 0, 1) == "/")
        {
            return file_exists($this->root . $filename);
        }
        else
        {
            return file_exists($this->root . $this->cwd . $filename);
        }
    }

    function type($filename)
    {
        if(substr($filename, 0, 1) == "/")
        {
            return (filetype($this->root . $filename));
        }
        else
        {
            return (filetype($this->root . $this->cwd . $filename));
        }
    }

    function md($dir)
    {
        if(substr($dir, 0, 1) == "/")
        {
            return mkdir($this->root . $dir);
        }
        else
        {
            return mkdir($this->root . $this->cwd . $dir);
        }
    }

    function rd($dir)
    {
        if(substr($dir, 0, 1) == "/")
        {
            return rmdir($this->root . $dir);
        }
        else
        {
            return rmdir($this->root . $this->cwd . $dir);
        }
    }

    function rn($from, $to)
    {
        if(substr($from, 0, 1) == "/")
        {
            $ff = $this->root . $from;
        }
        else
        {
            $ff = $this->root . $this->cwd . $from;
        }

        if(substr($to, 0, 1) == "/")
        {
            $ft = $this->root . $to;
        }
        else
        {
            $ft = $this->root . $this->cwd . $to;
        }

        return (rename($ff, $ft));
    }

    function read($size)
    {
        return fread($this->fp, $size);
    }

    function write($str)
    {
        fwrite($this->fp, $str);
    }

    function open($filename, $create = false, $append = false)
    {
        clearstatcache();
        $type = ($create) ? "w" : "r";
        $type = ($append) ? "a" : $type;

        if(substr($filename, 0, 1) == "/")
        {
            return ($this->fp = fopen($this->root . $filename, $type));
        }
        else
        {
            return ($this->fp = fopen($this->root . $this->cwd . $filename, $type));
        }
    }

    function close()
    {
        fclose($this->fp);
    }

    /* permission output added by Phanatic */
    function perms($mode)
    {
        /* Determine Type */
        if($mode & 0x1000)
            $type='p'; /* FIFO pipe */
        elseif($mode & 0x2000)
            $type='c'; /* Character special */
        elseif($mode & 0x4000)
            $type='d'; /* Directory */
        elseif($mode & 0x6000)
            $type='b'; /* Block special */
        elseif($mode & 0x8000)
            $type='-'; /* Regular */
        elseif($mode & 0xA000)
            $type='l'; /* Symbolic Link */
        elseif($mode & 0xC000)
            $type='s'; /* Socket */
        else
            $type='u'; /* UNKNOWN */

        /* Determine permissions */
        $owner['read']    = ($mode & 00400) ? 'r' : '-';
        $owner['write']   = ($mode & 00200) ? 'w' : '-';
        $owner['execute'] = ($mode & 00100) ? 'x' : '-';
        $group['read']    = ($mode & 00040) ? 'r' : '-';
        $group['write']   = ($mode & 00020) ? 'w' : '-';
        $group['execute'] = ($mode & 00010) ? 'x' : '-';
        $world['read']    = ($mode & 00004) ? 'r' : '-';
        $world['write']   = ($mode & 00002) ? 'w' : '-';
        $world['execute'] = ($mode & 00001) ? 'x' : '-';

        /* Adjust for SUID, SGID and sticky bit */
        if($mode & 0x800)
            $owner['execute'] = ($owner['execute']=='x') ? 's' : 'S';
        if($mode & 0x400)
            $group['execute'] = ($group['execute']=='x') ? 's' : 'S';
        if($mode & 0x200)
            $world['execute'] = ($world['execute']=='x') ? 't' : 'T';

        $permstr = sprintf("%1s", $type);
        $permstr = $permstr . sprintf("%1s%1s%1s", $owner['read'], $owner['write'], $owner['execute']);
        $permstr = $permstr . sprintf("%1s%1s%1s", $group['read'], $group['write'], $group['execute']);
        $permstr = $permstr . sprintf("%1s%1s%1s", $world['read'], $world['write'], $world['execute']);

        return $permstr;
    }
}
