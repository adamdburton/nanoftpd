<?php

namespace AdamDBurton\NanoFTPd\NanoFTPd;

class Server
{
    /**
     * The Laravel application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Laravel Config instance.
     *
     * @var \Illuminate\Support\Facades\Config
     */
    protected $config;

    /**
     * The socket used for listening.
     *
     * @resource
     */
    protected $socket;

    /**
     * Array used for storing connected clients.
     *
     * @resource
     */
    protected $clients = array();

    /**
     * Array used for the passive pool.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Boolean used for whether to run the server loop.
     *
     * @var bool
     */
    public $shouldRun = true;

    public function __construct($app = null)
    {
        if(!$app)
        {
            $app = app(); // Fallback when $app is not given
        }

        $this->app = $app;
        $this->config = $this->app['config'];

        $this->pasv_pool = new Pool();
    }

    function run()
    {
        // assign listening socket
        if(!($this->socket = @socket_create(AF_INET, SOCK_STREAM, 0)))
            $this->socket_error();

        // reuse listening socket address
        if(!@socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1))
            $this->socket_error();

        // set socket to non-blocking
        if(!@socket_set_nonblock($this->socket))
            $this->socket_error();

        // bind listening socket to specific address/port
        if(!@socket_bind($this->socket, $this->config->get('nanoftpd::server.ip'), $this->config->get('nanoftpd::server.port')))
            $this->socket_error();

        // listen on listening socket
        if(!socket_listen($this->socket))
            $this->socket_error();

        $this->app['log']->info('[NanoFTPd] Server starting...');

        while($this->shouldRun)
        {
            // sockets we want to pay attention to
            $set_array = array_merge(array('server' => $this->socket), $this->get_client_connections());

            $set = $set_array;
            if (socket_select($set, $set_w, $set_e, 1, 0) > 0)
            {
                // loop through sockets
                foreach($set as $sock)
                {
                    $name = array_search($sock, $set_array);

                    if(!$name)
                    {
                        continue;
                    }
                    elseif($name == 'server')
                    {
                        if(!($conn = socket_accept($this->socket)))
                        {
                            $this->socket_error();
                        }
                        else
                        {
                            // add socket to client list and announce connection
                            $clientID = uniqid('client_');
                            $this->clients[$clientID] = new Client($this->app, $conn, $clientID);

                            // Check if throttling is enabled
                            if($this->config->get('nanoftpd::server.throttling.enabled') === true)
                            {
                                // if max_conn exceeded disconnect client
                                if(count($this->clients) > $this->config->get('nanoftpd::server.throttling.max_connections'))
                                {
                                    $this->clients[$clientID]->send("421 Maximum user count reached.");
                                    $this->clients[$clientID]->disconnect();

                                    $this->remove_client($clientID);

                                    continue;
                                }

                                // get a list of how many connections each IP has
                                $ip_pool = array();

                                foreach($this->clients as $client)
                                {
                                    $key = $client->addr;
                                    $ip_pool[$key] = (array_key_exists($key, $ip_pool)) ? $ip_pool[$key] + 1 : 1;
                                }

                                // disconnect when max_conn_per_ip is exceeded for this client
                                if($ip_pool[$key] > $this->config->get('nanoftpd::server.throttling.max_connections_per_ip'))
                                {
                                    $this->clients[$clientID]->send("421 Too many connections from this IP.");
                                    $this->clients[$clientID]->disconnect();

                                    $this->remove_client($clientID);

                                    continue;
                                }
                            }

                            // everything is ok, initialize client
                            $this->clients[$clientID]->init();
                        }
                    }
                    else
                    {
                        $clientID = $name;

                        // client socket has incoming data
                        if(($read = @socket_read($sock, 1024)) === false || $read == '')
                        {
                            if ($read != '')
                                $this->socket_error();

                            // remove client from array
                            $this->remove_client($clientID);
                        }
                        else
                        {
                            // only want data with a newline
                            if(strchr(strrev($read), "\n") === false)
                            {
                                $this->clients[$clientID]->buffer .= $read;
                            }
                            else
                            {
                                $this->clients[$clientID]->buffer .= str_replace("\n", "", $read);

                                if(!$this->clients[$clientID]->interact())
                                {
                                    $this->clients[$clientID]->disconnect();

                                    $this->remove_client($clientID);
                                }
                                else
                                {
                                    $this->clients[$clientID]->buffer = "";
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    function get_client_connections()
    {
        $conn = array();

        foreach($this->clients as $clientID => $client)
        {
            $conn[$clientID] = $client->connection;
        }

        return $conn;
    }

    function remove_client($clientID)
    {
        unset($this->clients[$clientID]);
    }

    function socket_error()
    {
        $this->app['log']->error("[NanoFTPd] socket error: " . socket_strerror(socket_last_error($this->socket)));

        if (is_resource($this->socket)) socket_close($this->socket);
    }
}