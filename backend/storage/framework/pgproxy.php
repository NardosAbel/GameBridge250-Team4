<?php

$server = stream_socket_server('tcp://127.0.0.1:55432', $errno, $errstr);
if ($server === false) {
    fwrite(STDERR, $errstr.PHP_EOL);
    exit(1);
}

while ($client = @stream_socket_accept($server, -1)) {
    $remote = @stream_socket_client('tcp://[::1]:5433', $errno, $errstr, 3);
    if ($remote === false) {
        fclose($client);
        continue;
    }

    stream_set_blocking($client, false);
    stream_set_blocking($remote, false);
    $idle = 0;

    while ($idle < 8) {
        $read = [$client, $remote];
        $write = null;
        $except = null;
        $ready = stream_select($read, $write, $except, 1);
        if ($ready === false || $ready === 0) {
            $idle++;
            continue;
        }
        $idle = 0;
        foreach ($read as $socket) {
            $other = $socket === $client ? $remote : $client;
            $data = fread($socket, 65536);
            if ($data === false || $data === '') {
                $idle = 8;
                break;
            }
            fwrite($other, $data);
        }
    }

    fclose($client);
    fclose($remote);
}
