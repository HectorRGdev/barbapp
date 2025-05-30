<?php

namespace Controllers;

use Model\AdminCita;
use MVC\Router;

class AdminController{
    public static function index( Router $router) {
        
        session_start();

        isAdmin();

        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $fechas = explode('-', $fecha);

        if( !checkdate($fechas[1], $fechas[2], $fechas[0]) ){
            header('Location: /404');
        }

        

        


        // Consultar la base de datos
        $consulta = "SELECT reservas.id, reservas.hora, CONCAT( usuarios.nombre, ' ', usuarios.apellidos) as cliente, ";
        $consulta .= " usuarios.email, usuarios.telefono, servicios.nombre as servicio, servicios.precio  ";
        $consulta .= " FROM reservas  ";
        $consulta .= " LEFT OUTER JOIN usuarios ";
        $consulta .= " ON reservas.usuarioId=usuarios.id  ";
        $consulta .= " LEFT OUTER JOIN reservaservicios ";
        $consulta .= " ON reservaservicios.reservaId=reservas.id ";
        $consulta .= " LEFT OUTER JOIN servicios ";
        $consulta .= " ON servicios.id=reservaservicios.servicioId ";
        $consulta .= " WHERE fecha = '{$fecha}' ";

        $citas = AdminCita::SQL($consulta);

        
        
        $router-> render('admin/index', [
            'nombre' => $_SESSION['nombre'],
            'citas' => $citas,
            'fecha' => $fecha
        ]);
    }
}