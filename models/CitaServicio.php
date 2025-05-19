<?php

namespace Model;

class CitaServicio extends ActiveRecord{
   
        protected static $tabla = 'reservaservicios';
        protected static $columnasDB = ['id', 'reservaId', 'servicioId'];

        public $id;
        public $reservaId;
        public $servicioId;

        public function __construct($args = []){
                $this->id = $args['id'] ?? null;
                $this->reservaId = $args['reservaId'] ?? '';
                $this->servicioId = $args['servicioId'] ?? '';
        }
    
}