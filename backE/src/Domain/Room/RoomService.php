<?php


namespace App\Domain\Room;


use PDO;

class RoomService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function getAllInRoom(int $id): array {
        $stmt = $this->pdo->prepare("SELECT * FROM in_room WHERE id_users = :id");
        $stmt->bindValue('id', $id);
        $stmt->execute();
        $info = $stmt->fetchAll();
        return $info;

    }
//Funkce vrátí všechny řádky v db
    public function getAll(): array
    {
        $stmt = $this->pdo->prepare("select * from rooms");
        $stmt->execute();
        $rooms = $stmt->fetchAll();
        return $rooms;
    }
//Funkce vrátí řádek odpovídající id
    public function getById(int $id)
    {
        $stmt = $this->pdo->prepare("select * from rooms where id_rooms=:id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $room = $stmt->fetch();
        return $room;
    }
//Funkce vrátí id místností, které nebyly použitý déle než 12 hodinek.

    public  function getIdOldRoom(){
        $stmt = $this->pdo->prepare("SELECT id_rooms from rooms WHERE(:nowtime - created)>43200");
        $stmt->bindValue(':nowtime', time());
        $stmt->execute();
        $oldroom = $stmt->fetch();
        return $oldroom;
    }
//funkce vytvoří místnost
    public function createRoom(array $data, int $userId)
    {
        $stmt = $this->pdo->prepare("insert into rooms (title,created,id_users_owner, lock) values (:title,:created,:id_users_owner, :lock)");
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':created', time());
        $stmt->bindValue(':id_users_owner', $userId);
        $stmt->bindValue(':lock', 'false');
        $stmt->execute();
        $id = $this->pdo->lastInsertId();
        return $this->getById($id);
    }
//funkce místnost smaže
    public function deleteRoom(int $idRoom, int $userId)
    {
        $user = $this->getCountAllUsersFroomRoom($idRoom);
        $countUser = count($user);
        $stmt = $this->pdo->prepare("SELECT id_users_owner FROM rooms WHERE id_rooms=:roomId");
        $stmt->bindValue(':roomId', $idRoom);
        $stmt->execute();
        $holder = $stmt->fetch();
        if ($holder[0] == $userId and $countUser == 1){
            $stmt=$this->pdo->prepare("DELETE FROM rooms WHERE id_rooms=:roomId");
            $stmt->bindValue(':roomId',  $idRoom);
            $stmt->execute();

            $stmt= $this->pdo->prepare("DELETE FROM messages WHERE id_rooms=:roomId");
            $stmt->bindValue(':roomId', $idRoom);
            $stmt->execute();

            $stmt = $this->pdo->prepare("DELETE FROM room_kick where id_rooms=:id_rooms");
            $stmt->bindValue(':id_rooms', $idRoom);
            $stmt->execute();
        return true;
        } else {
            return false;
        }


    }
//funkce upraví místnost s daným id
    public function updateRoom(int $userId,int $id, array $data)
    {
        $stmt = $this->pdo->prepare("SELECT id_users_owner FROM rooms WHERE id_rooms=:idRoom");
        $stmt->bindValue(':idRoom',$id);
        $stmt->execute();
        $holder = $stmt->fetch();
        if ($holder[0] == $userId) {
            $stmt = $this->pdo->prepare("UPDATE rooms SET title=:title WHERE id_rooms=:id_rooms");
            $stmt->bindValue(':title', $data['title']);
            $stmt->bindValue(':id_rooms', $id);
            $stmt->execute();
            return true;
        } else {
            return false;
        }
    }
//Funkce, která kontroluje, zda--li může user vstoupit do dané méístnosti
    public function checkniKick(int $idUser, int $idRoom)
    {
        $stmt = $this->pdo->prepare("SELECT id_users FROM room_kick WHERE id_users=:userId AND id_rooms = :roomId");
        $stmt->bindValue(':userId', $idUser);
        $stmt->bindValue(':roomId',$idRoom);
        $stmt->execute();
        $user = $stmt->fetch();
        $kickUser = (int)$user[0];
        if ($kickUser == $idUser){
            return true;
        } else {
            return false;
        }

    }
//Funkce prida zaznam do tabulky in room, když user vstoupí do nějaké room
    public function enterTheRoom(int $userId, int $idRoom)
    {
        if ($this->checkniKick($userId,$idRoom) == false){
            $stmt = $this->pdo->prepare("INSERT INTO in_room(id_users,id_rooms,last_message, entered) VALUES (:id_users,:id_rooms,:last_message,:entered)");
            $stmt->bindValue(':id_users',$userId);
            $stmt->bindValue(':id_rooms',$idRoom);
            $stmt->bindValue(':last_message',time());
            $stmt->bindValue(':entered',time());
            $stmt->execute();
            return true;
        } else {
            return false;
        }
    }
//Funkce smaze uzivatele, pri odchodu z roomky
    public function deleteUserFromRoom(int $idRoom, int $idUser)
    {
        $stmt = $this->pdo->prepare("SELECT id_users_owner FROM rooms WHERE id_rooms=:roomId");
        $stmt->bindValue(':roomId',$idRoom);
        $stmt->execute();
        $holder = $stmt->fetch();
        $users = $this->getAllUsersFrommRoom($idRoom);
        if ($idUser == $holder){
            $stmt = $this->pdo->prepare("UPDATE rooms SET id_users_owner= :holderId WHERE id_rooms = :roomId");
            $sum = count($users);
            $newHolder = rand(0,$sum-1);
            $stmt->bindValue(':holderId',$newHolder);
            $stmt->bindValue(':roomId', $idRoom);
            $stmt->execute();

            $stmt2= $this->pdo->prepare("DELETE FROM in_room WHERE id_rooms=:roomId AND id_users = :userId");
            $stmt2->bindValue(':roomId', $idRoom);
            $stmt2->bindValue(':userId', $idUser);
            $stmt2->execute();
        } else {
            $stmt2= $this->pdo->prepare("DELETE FROM in_room WHERE id_rooms=:roomId AND id_users = :userId");
            $stmt2->bindValue(':roomId', $idRoom);
            $stmt2->bindValue(':userId', $idUser);
            $stmt2->execute();
        }

    }
//Funkce vrátí všechny id useru, které jsou zrovna v dané místnosti
    public function getAllUsersFrommRoom(int $idRoom){
        $stmt = $this->pdo->prepare("SELECT id_users FROM in_room WHERE id_rooms=:roomId");
        $stmt->bindValue(':roomId',$idRoom);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $users;

    }
//Funkce, která vrátí jmeno, prijmeni a id usera v místnosti
    public function getAllUsersNameFrommRoom(int $idRoom){
        $stmt = $this->pdo->prepare("SELECT id_users FROM in_room WHERE id_rooms=:roomId");
        $stmt->bindValue(':roomId',$idRoom);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $users;

    }
//Funkce vrátí počet userů v dané roomce
    public function getCountAllUsersFroomRoom(int $idRoom){
        $stmt = $this->pdo->prepare("SELECT COUNT(id_users) FROM in_room WHERE id_rooms=:roomId");
        $stmt->bindValue(':roomId',$idRoom);
        $stmt->execute();
        $users = $stmt->fetchAll();
        return $users;
    }
//Funkce, která slouží pro vyhození uživatele pryč z dané místnosti
    public function kick(int $roomId, int $userId, int $idKick)
    {
        $stmt = $this->pdo->prepare("SELECT id_users_owner FROM rooms WHERE id_rooms=: roomId");
        $stmt->bindValue(':idRoom', $roomId);
        $stmt->execute();
        $holder = $stmt->fetch();

        if ($userId == $holder){
            $stmt2 = $this->pdo->prepare("INSERT INTO room_kick(id_users,id_rooms, created) VALUES(:userId, :roomId, :created)");
            $stmt2->bindValue(':userId', $idKick);
            $stmt2->bindValue(':roomId',$roomId);
            $stmt2->bindValue(':created',time());
            $stmt2->execute();
            $stmt2->fetch();
            return true;
        } else {
            return false;
        }


    }
//Funkce, která slouží pro uzamčení/odemčení místnosti //POTŘEBA UPRAVIT ASI
    public function lockUnlockRoom(int $roomId, int $userId)
    {
        $stmt = $this->pdo->prepare("SELECT id_users_owner, lock FROM rooms WHERE id_rooms = :roomId");
        $stmt->bindValue(':roomId', $roomId);
        $stmt->execute();
        $rooom = $stmt->fetch();
        if ($rooom[0] == $userId) {
            if ($rooom[1] == "false") {
                $stmt = $this->pdo->prepare("UPDATE rooms SET lock='true' WHERE id_rooms=:roomId");
                $stmt->bindValue(':roomId', $roomId);
                $stmt->execute();
            } elseif ($rooom[1] == "true") {
                $stmt = $this->pdo->prepare("UPDATE rooms SET lock='false' WHERE id_rooms=:roomId");
                $stmt->bindValue(':roomId', $roomId);
                $stmt->execute();
            }


        }

    }
//Funkce, která odhlásí uživatele, který je více jak 120 sekund neaktivní
    public function logOutAfterTime(int $userId, int $roomId){
        $stmt = $this->pdo->prepare("SELECT id_users FROM in_room WHERE (:now-last_message)>120");
        $stmt->bindValue(':now', time());
        $stmt->execute();
        $users = $stmt->fetch();
        if ($users > 0 ) {
            return true;
        } else {
            return false;
        }
    }




}

