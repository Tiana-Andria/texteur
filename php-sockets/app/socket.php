<?php

namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Socket implements MessageComponentInterface {

    protected $clients = [];
    protected $elements = [];
    protected $textByUser = [];

    public function onOpen(ConnectionInterface $conn){

        $identifier = uniqid();
        echo "New connection! ({$conn->resourceId})\n";
        $this->clients[$conn->resourceId] = [
            'connection' => $conn,
            'identifier' => $identifier
        ];

        $numConnected = count($this->clients);
        echo "Nombre d'utilisateurs connectés : {$numConnected}\n";

    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        $identifier = $this->clients[$from->resourceId]['identifier'];
        
        if (isset($data['action'])) {
            if ($data['action'] === 'text_update') {
                $text = $data['text'];
                
                // Enregistrement du texte associé à l'utilisateur
                $this->textByUser[$identifier] = $text;
    
                // Diffusion du texte aux autres utilisateurs
                $this->broadcastTextUpdate($identifier, $text);
            } 
            elseif ($data['action'] === 'cursor_position_update') {
                $cursorPos = $data['cursorPos'];
                
                // Diffusion de la position du curseur aux autres utilisateurs
                $this->broadcastCursorUpdate($identifier, $cursorPos);
            }
        }
    }
    

    public function onClose(ConnectionInterface $conn)
    {

        $identifier = $this->clients[$conn->resourceId]["identifier"];

        $message = json_encode([
            'type' => 'deconnexion',
            'id' => $identifier,
        ]);

        foreach ($this->clients as $client) {
            if ($client['connection'] !== $conn) {
                $client['connection']->send($message);
            }
        }

        unset($this->clients[$conn->resourceId]);
        echo "User ({$identifier}) déconnecté \n";
        //echo($$this->elements['creator']);

        $numConnected = count($this->clients);
        echo "Nombre d'utilisateurs connectés : {$numConnected}\n";

        $conn->close();
    }


    public function onError(ConnectionInterface $conn, \Exception $e){

        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function broadcastTextUpdate($userId, $text) {
        foreach ($this->clients as $client) {
            if ($client['identifier'] !== $userId) {
                $client['connection']->send(json_encode([
                    'type' => 'text_update',
                    'userId' => $userId,
                    'text' => $text,
                ]));
            }
        }
    }
    

    protected function broadcastCursorUpdate($userId, $cursorPos) {
        foreach ($this->clients as $client) {
            if ($client['connection'] !== $from) {
                $client['connection']->send(json_encode([
                    'type' => 'cursor_position_update',
                    'userId' => $userId,
                    'cursorPos' => $cursorPos,
                ]));
            }
        }
    }

}
