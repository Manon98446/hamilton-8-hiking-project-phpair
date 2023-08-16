<?php

namespace controllers;

use Exception;
use models\Database;

class AuthController
{

    private $db;

    public function __construct(){
        $this->db = new Database();
        session_start();
    }

    public function register(){

        if (empty($_POST)) {

            include 'views/inc/header.view.php';
            include 'views/register.view.php';
            include 'views/inc/footer.view.php';

        }else {

            try {

                if (empty($_POST['firstname']) || empty($_POST['lastname']) || empty($_POST['nickname']) || empty($_POST['email']) || empty($_POST['password'])) {
                    throw new Exception('Formulaire non complet');
                }

                //$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
                $firstname = htmlspecialchars($_POST['firstname']);
                $lastname = htmlspecialchars($_POST['lastname']);
                $nickname = htmlspecialchars($_POST['nickname']);
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
                $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);

                $this->db->prepare("INSERT INTO Users (firstname, lastname, nickname, email,password) VALUES (?, ?, ?,?,?)", [$firstname,$lastname,$nickname, $email, $passwordHash]);

                $_SESSION['user'] = [
                    'id' => $this->db->lastInsertId(),
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'nickname' => $nickname,
                    'email' => $email
                ];

                header('location: /');


            } catch (Exception $e) {
                header('location: register?m=erreur%20dans%20la%20création%20du%20compte&color=red');
            }


        }

    }

    public function login(){

        if (empty($_POST)) {

            include 'views/inc/header.view.php';
            include 'views/login.view.php';
            include 'views/inc/footer.view.php';

        }else {

            try {

                if (empty($_POST['nickname'])  || empty($_POST['password'])) {
                    throw new Exception('Formulaire non complet');
                }

                $nickname = htmlspecialchars($_POST['nickname']);

                $user = $this->db->prepare("SELECT * FROM Users WHERE nickname = ?", [$nickname]);


                if (password_verify($_POST['password'], $user['password'])) {

                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'firstname' => $user['firstname'],
                        'lastname' => $user['lastname'],
                        'nickname' => $user['nickname'],
                        'email' => $user['email']
                    ];

                    header('location: /');

                } else {
                    // Gérer le cas où l'utilisateur n'est pas trouvé ou l'authentification échoue
                    header('location: login?m=le%20compte%20n%27existe%20pas&color=red');
                }


            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        }

    }

    public function profile(){

        if (!empty($_SESSION['user'])) {

            include 'views/inc/header.view.php';
            include 'views/profile.view.php';
            include 'views/inc/footer.view.php';

            if(!empty($_POST) && $_POST['action'] == 'Update') {
                if ( !empty($_POST['firstname']) && !empty($_POST['lastname']) && !empty($_POST['nickname']) && !empty($_POST['email']) && !empty($_POST['password'])) {

                    $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $id = $_SESSION['user']['id'];


                    $this->db->prepare("UPDATE Users SET firstname = ?, lastname = ?, nickname = ?, email = ?,password = ? WHERE id = ?;", [$_POST['firstname'], $_POST['lastname'], $_POST['nickname'], $_POST['email'], $passwordHash, $id]);

                    $_SESSION['user'] = [
                        'id' => $id,
                        'firstname' => $_POST['firstname'],
                        'lastname' => $_POST['lastname'],
                        'nickname' => $_POST['nickname'],
                        'email' => $_POST['email']
                    ];
                    header("location: /profile");


                } else {
                    throw new Exception("un ou plusieurs champs sont vides", 500);
                }
            }


            if(!empty($_POST)) {
                if ($_POST['action'] == 'Delete') {
                   $this->db->fetch('DELETE FROM Users WHERE id = '.$_SESSION['user']['id'].' ;');
                   $this->logout();
                    header("location: /");
                }
            }

        }else{
            header('Location: /login');
        }

    }

    public function logout(){
        session_destroy();
        header('Location: /');
    }

}