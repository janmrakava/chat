<?php
declare(strict_types=1);

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Exception\HttpNotFoundException;
use Slim\Http\ServerRequest;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });

    $app->add(function ($request, $handler) {
        $response = $handler->handle($request);
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
        $name = $args["name"];
        $response->getBody()->write('Hello ' . $name . '!');
        return $response;
    });
//USER ROUTE
//ROUTA, která vytvoří uživatele
    $app->post('/users', function (ServerRequest $request, Response $response, array $args) {
        $data = $request->getParsedBody();
        $userService = $this->get('userService');
        $user = $userService->createUser($data);
        unset($user['password']);       //potřeba vyřešit situaci, když se registruje uživatel se stejným loginem/emailem co už je v db
        return $response->withJson($user, 201);
    });
//Routa, která slouží pro login
    $app->post('/login', function (ServerRequest $request, Response $response, array $args) {
        $data = $request->getParsedBody();
        $userService = $this->get('userService');

        if ($user = $userService->verifyLogin($data)) {
            $tokenKey = getenv('TOKEN_KEY');
            $payload = [
                "userId" => $user['id_users'],
                "login" => $user['login'],
                "email" => $user['email'],
                "password" => $user['password'],
                "name" => $user['name'],
                "surname" =>$user['surname'],
                "gender" =>$user['gender'],
                "registered" =>$user['registered'],
                "role" =>$user['role']
            ];
            $token = JWT::encode($payload, $tokenKey, "HS256");
            return $response->withJson(['token' => $token], 201);
        } else {
            return $response->withStatus(401);
        }
    });
//AUTH ROUTY
    $app->group('/auth', function(RouteCollectorProxy $group) {
//USERS
//  Routa, vrátí všechny uživatele
        $group->get('/users', function (Request $request, Response $response) {
            $userService = $this->get('userService');
            $users = $userService->getAll();
            return $response->withJson($users);
        });
 //Routa, vrátí uživatele na základe id
        $group->get('/user/{id}', function (Request $request, Response $response, array $args) {
            $id = (int)$args["id"];
            $userService = $this->get('userService');
            $user = $userService->getUserById($id);
            return $response->withJson($user);
        });
//Routa upraví uživatele na základe id
        $group->put('/users/{id}', function (ServerRequest $request, Response $response, array $args) {
            $userService = $this->get('userService');
            $id = (int)$args["id"];
            $data = $request->getParsedBody();
            $user = $userService->updateUser($id, $data);
            return $response->withJson($user, 202);
        });
//Routa, vrátí uživatele na základe id
        $group->get('/users/{id}', function (Request $request, Response $response, array $args) {
            $id = (int)$args["id"];
            $userService = $this->get('userService');
            $user = $userService->getById($id);
            return $response->withJson($user);
        });
//Routa, která vrátí zprávyy pouze pro uživatele s daným id
        $group->get('/users/{id}/messages', function (Request $request, Response $response, array $args) {
            $id = (int)$args["id"];
            $messageService = $this->get('messageService');
            $messages = $messageService->getAllMessagesByUserId($id);
            return $response->withJson($messages);
        });
//Routa, která vrátí všechny uživatele, které jsou v mítnosti
        $group->get('/room/{id}/users', function (Request $request, Response $response, array $args) {
            $idRoom = (int)$args["id"];
            $roomService = $this->get('roomService');;
            $users = $roomService->getAllUsersNameFrommRoom($idRoom);
            return $response->withJson($users,204);

        });
//ROOMS ROUTY
//Routa, vrátí všechny rooms
        $group->get('/rooms', function (Request $request, Response $response) {
            $roomService = $this->get('roomService');
            $rooms = $roomService->getAll();
            return $response->withJson($rooms);
        });
//Routa, vrátí pouze místnost s daným id
        $group->get('/rooms/{id}', function (Request $request, Response $response, array $args) {
            $id = (int)$args["id"];
            $roomService = $this->get('roomService');
            $room = $roomService->getById($id);
            return $response->withJson($room);
        });
//Routa, která vytvoří místnost
        $group->post('/rooms', function (ServerRequest $request, Response $response, array $args) {
            $data = $request->getParsedBody();
            $roomService = $this->get('roomService');
            $tokenPayload = $request->getAttribute('token');
            $userId = (int)$tokenPayload['userId'];
            $room = $roomService->createRoom($data, $userId);
            return $response->withJson($room);
        });
//Routa, která smaže místnost s daným id
        $group->delete('/room/{id}', function (ServerRequest $request, Response $response, array $args) {
            $roomService = $this->get('roomService');
            $idRoom = (int)$args["id"];
            $tokenPayload = $request->getAttribute('token');
            $idUser = (int)$tokenPayload['userId'];
            if ($roomService->deleteRoom($idRoom, $idUser)){
                return $response->withStatus(204);
            } else {
                return $response->withStatus(403);
            }
        });
//Routa, která slouží pro vstup uživatele do dané místnosti
        $group->post('/room/{id}/enter', function (ServerRequest $request, Response $response, array $args) {
            $roomService = $this->get('roomService');
            $tokenPayload = $request->getAttribute('token');
            $idUser= (int)$tokenPayload['userId'];
            $idRoom = (int)$args["id"];
           ;
            if ( $roomService->enterTheRoom($idUser,$idRoom)){
                return $response->withStatus(204);
            } else {
                return $response->withStatus(404);
            }

        });

//Routa, která smaže uživatele při opuštění místnosti
        $group->delete('/room/{id}/leave', function (ServerRequest $request, Response $response, array $args) {
            $roomService = $this->get('roomService');
            $tokenPayload = $request->getAttribute('token');
            $userId= (int)$tokenPayload['userId'];
            $id = (int)$args["id"];
            $roomService->deleteUserFromRoom($id,$userId);
            return $response->withStatus(204);
        });
//Routa, která updatuje room s daným id
        $group->put('/room/{id}', function (ServerRequest $request, Response $response, array $args) {
            $roomService = $this->get('roomService');
            $id = (int)$args["id"];
            $data = $request->getParsedBody();
            $tokenPayload = $request->getAttribute('token');
            $idUser = (int)$tokenPayload['userId'];
            if ($roomService->updateRoom($idUser,$id, $data)){
                return $response->withStatus(204);
            } else {
                return $response->withStatus(403);
            }

        });
//Routa, která nastaví kick danému uživateli :)
        $group->post('/room/{id}/kick', function (ServerRequest $request, Response $response, array $args) {
            $idRoom = (int)$args["id"];
            $roomService = $this->get('roomService');
            $tokenPayload = $request->getAttribute('token');
            $idUser = (int)$tokenPayload['userId'];
            $idKick = $request->getParsedBody();
            $idKick=(int)$idKick['idKick'];
            if ($roomService->kick($idUser,$idRoom,$idKick)){
                return $response->withStatus(202);
            } else {
                return $response->withStatus(403);
            }
        });
//ROuta, která nastavuje lock/unlock místnosti -
        $group->put('/room/{id}/lock', function (ServerRequest $request, Response $response, array $args){
            $roomService = $this->get('roomService');
            $id = (int)$args["id"];
            $tokenPayload = $request->getAttribute('token');
            $userId= (int)$tokenPayload['userId'];
            $roomService->lockUnlockRoom($id,$userId);
            return $response->withStatus(201);
        });
        $group->get('/room/inRoom/{id}', function (ServerRequest $request, Response $response, array $args){
            $roomService = $this->get('roomService');
          //  $id = (int)$args["id"];
            $tokenPayload = $request->getAttribute('token');
            $userId= (int)$tokenPayload['userId'];
            $info = $roomService->getAllInRoom($userId);
            return $response->withJson($info);
        });
//MESSAGES ROUTY
//Routa, která vrátí zprávy pro danou místnost
        $group->get('/rooms/{id}/messages', function (Request $request, Response $response, array $args) {
            $id = (int)$args["id"];
            $messageService = $this->get('messageService');
            $messages = $messageService->getAllByRoomId($id);
            return $response->withJson($messages);
        });
//ROUta, která vytoří zprávu pro danou místnost
        $group->post('/rooms/{id}/messages', function (ServerRequest $request, Response $response, array $args) {
            $roomId = (int)$args["id"];
            $data = $request->getParsedBody();
            $messageService = $this->get('messageService');
            $message = $messageService->createMessage($roomId, $data);
            return $response->withJson($message, 201);
        });



    });

    /**
     * Catch-all route to serve a 404 Not Found page if none of the routes match
     * NOTE: make sure this route is defined last
     */
    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
        throw new HttpNotFoundException($request);
    });
};
