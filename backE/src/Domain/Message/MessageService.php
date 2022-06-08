<?php


namespace App\Domain\Message;


use PDO;

class MessageService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
//FUnkce vrátí zprávy pro danou místnost
    public function getAllByRoomId(int $id)
    {
        $stmt = $this->pdo->prepare("select * from messages where id_rooms=:id_rooms");
        $stmt->bindValue(':id_rooms', $id);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $messages;
    }
//Funkce vrátí všechny zprávy pro daného uživatele
    public function getAllMessagesByUserId(int $id){
        $stmt = $this->pdo->prepare("select message from messages where id_users_from=:id");
        $stmt->bindValue(':id',$id);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $messages;

    }
//Vrátí zprávu pro dané id zprávy
    public function getMessageById(int $id)
    {
        $stmt = $this->pdo->prepare("select * from messages where id_messages=:id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $message = $stmt->fetch();
        return $message;
    }
//Funkce která vytvoří zprávu
    public function createMessage(int $roomId, array $data)
    {
        $stmt = $this->pdo->prepare("INSERT INTO messages
    (id_rooms, id_users_from, id_users_to, created, message)
    VALUES (:id_rooms, :id_users_from, :id_users_to, :created, :message)");
        $stmt->bindValue(':id_rooms', $roomId);
        $stmt->bindValue(':id_users_from', $data['id_users_from']);
        $stmt->bindValue(':id_users_to', $data['id_users_to']);
        $stmt->bindValue(':created', time());
        $stmt->bindValue(':message', $data['message']);
        $stmt->execute();
        $id = $this->pdo->lastInsertId();
        return $this->getMessageById($id);
    }
//Funkce, která updatne čas v tabulce in_room, při zprávě
    public function updateLastMessage(int $idUser, int $idRoom)
    {
        $stmt=$this->pdo->prepare("UPDATE in room SET last_message= :last WHERE id_users=:idUser AND id_rooms=:roomId");
        $stmt->bindValue(':idUser', $idUser);
        $stmt->bindValue(':roomId', $idRoom);
        $stmt->bindValue(':last', time());
        $stmt->execute();



    }

}