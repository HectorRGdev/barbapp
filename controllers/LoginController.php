<?php

namespace Controllers;

use Model\Usuario;
use MVC\Router;
use Classes\Email;

class LoginController{
    public static function login(Router $router){

        $alertas = [];

        $auth = new Usuario;

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            $auth = new Usuario($_POST);

            $alertas = $auth->validarLogin();

            if(empty($alertas)){
                //Comprobar si el usuario existe
                $usuario = Usuario::where('email', $auth->email);

                if($usuario){
                    //Verificar password
                    if( $usuario->comprobarPasswordAndVerificado($auth->password)){
                        //Autenticar el usuario
                        session_start();
                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre . " " . $usuario->apellidos;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;

                        //Redireccionamiento
                            //Rolles de admin y cliente
                        if($usuario->admin === "1"){
                            $_SESSION['admin'] = $usuario->admin ?? null;

                            header('Location: /admin');
                        } else{
                            header('Location: /cita');
                        }

                    }
                   

                }else{
                    Usuario::setAlerta('error','Usuario no encontrado');
                }
            }

            
        }

        $alertas = Usuario::getAlertas();

        $router->render('auth/login', [
            'alertas' => $alertas,
            'auth' => $auth
        ]);
    }

    public static function logout(){
        session_start();
        $_SESSION = [];
        header('Location: /');
    }

    public static function olvide(Router $router){

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth = new Usuario($_POST);
            $alertas = $auth->validarEmail();

            if(empty($alertas)){
                $usuario = Usuario::where('email', $auth->email);

                if($usuario && $usuario->confirmado === "1"){
                    //Generar un token
                    $usuario->crearToken();
                    $usuario->guardar();

                    

                    //Enviar el email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarInstrucciones();

                    //Alerta de exito
                    Usuario::setAlerta('exito', 'Revisa tu email');
                    

                } else{
                    Usuario::setAlerta('error', 'El Usuario no existe o no esta confirmado');
                    
                }
            }
        }

        $alertas = Usuario::getAlertas();

        $router->render('auth/olvide-password', [
            'alertas' => $alertas
        ]);
    }

    public static function recuperar(Router $router){

        $alertas = [];
        $error = false;

        $token = s($_GET['token']);

        //Buscar usuario por su token
        $usuario = Usuario::where('token', $token);

        if(empty($usuario)){
            Usuario::setAlerta('error', 'Token no válido');
            $error = true;
        }

        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            //leer el nuevo password y guardarlo

            $password = new Usuario($_POST);
            $alertas = $password->validarPassword();

            if(empty($alertas)){
                $usuario->password = null;

                
                $usuario->password = $password->password;
                $usuario->hashPassword();
                $usuario->token = null;

                $resultado = $usuario->guardar();
                if($resultado){
                    header('Location: /');
                }
            }
        }

        $alertas = Usuario::getAlertas();
        $router->render('auth/recuperar-password', [
            'alertas' => $alertas,
            'error' => $error
        ]);
    } 
    

    public static function crear(Router $router){

        $usuario = new Usuario;

        //Alertas vacias
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST'){

           
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta();


            //Reviso que alertas este vacio
            if(empty($alertas)){
                
                //Verifico que el usuario no este registrado
               $resultado = $usuario->existeUsuario();

               if($resultado->num_rows){
                    $alertas = Usuario::getAlertas();
                }else{
                    //Hashear el Password
                    $usuario->hashPassword();

                    //Generar un Token
                    $usuario->crearToken();

                    //Enviar el email
                    $email = new Email($usuario->nombre, $usuario->email, $usuario->token);

                    $email->enviarConfirmacion();

                    //Crear el usuario
                    $resultado = $usuario->guardar();
                   

                  //  debuguear($usuario);

                  if($resultado){
                    header('Location: /mensaje');
                }

                }
            }
            

            
        }

        $router->render('auth/crear-cuenta',[
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function mensaje(Router $router){

        $router->render('auth/mensaje');

    }

    public static function confirmar(Router $router){

        $alertas = [];

        $token = s($_GET['token']);

        $usuario = Usuario::where('token', $token);

        if(empty($usuario)){
            //Mostrar mensaje de error
            Usuario::setAlerta('error', 'Token no válido');
        }else{
            //Modificar a usuario confirmado
            
            $usuario->confirmado = "1";
            $usuario->token = "";
            $usuario->guardar();
            Usuario::setAlerta('exito', 'Cuenta comprobada correctamente');
        }

        //Obtener alertas
        $alertas = Usuario::getAlertas();

        //Renderiza la vista
        $router->render('auth/confirmar-cuenta', [
            
            'alertas' => $alertas
    
        ]);
    }
}