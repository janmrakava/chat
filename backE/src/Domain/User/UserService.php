<?php


namespace App\Domain\User;


use PDO;

class UserService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function getAll(): array
    {
        $stmt = $this->pdo->prepare("select id_users,name,surname from users");
        $stmt->execute();
        $users = $stmt->fetchAll();
        return $users;
    }
    public function getById(int $id)
    {
        $stmt = $this->pdo->prepare("select * from users where id_users=:id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $user = $stmt->fetch();
        return $user;
    }


    public function getUserById(int $id)
    {
        $stmt = $this->pdo->prepare("select * from users where id_users=:id");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user;
    }

    public function createUser(array $data)
    {
        $stmt = $this->pdo->prepare("INSERT INTO users
    (login, email, password, name, surname, gender, registered, role)
    VALUES (:login, :email, :password, :name, :surname, :gender, :registered, :role)");
        $stmt->bindValue(':login', $data['login']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':password', password_hash($data['password'], PASSWORD_DEFAULT));
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':surname', $data['surname']);
        $stmt->bindValue(':gender', $data['gender']);
        $stmt->bindValue(':registered', time());
        $stmt->bindValue(':role', $data['role']);
        $stmt->execute();
        $id = $this->pdo->lastInsertId();
        return $this->getUserById($id);
    }

    public function verifyLogin(array $data)
    {
        $login = $data['login'];
        $password = $data['password'];

        $stmt = $this->pdo->prepare("select * from users where login=:login");
        $stmt->bindValue(':login', $login);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user === false) {
            return false;
        } else {
            $hash = $user["password"];
            if (password_verify($password, $hash)) {
                return $user;
            } else {
                return false;
            }
        }
    }

    public function updateUser(int $id, array $data)
    {
        $stmt = $this->pdo->prepare("UPDATE users SET login = :login,
                                                                email = :email,  
                                                                password = :password,                                                           
                                                                name = :name,
                                                                surname = :surname,
                                                                gender = :gender                                                                
                                                                WHERE id_users=:id_users");
        $stmt->bindValue(':login', $data['login']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':password', password_hash($data['password'], PASSWORD_DEFAULT));
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':surname', $data['surname']);
        $stmt->bindValue(':gender', $data['gender']);
        $stmt->bindValue(':id_users', $id);
        $stmt->execute();
        return $this->getById($id);

    }




}
