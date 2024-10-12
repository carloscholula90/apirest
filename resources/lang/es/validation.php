<?php

return [
            'required' => 'El campo :attribute es obligatorio.',
            'email' => 'El campo :attribute debe ser una dirección de correo válida.',
            'max' => [
                'string' => 'El campo :attribute no puede exceder :max caracteres.',
            ],
            'min' => [
                'string' => 'El campo :attribute debe tener al menos :min caracteres.',
            ],
            'unique' => 'El :attribute ya ha sido tomado.',
            
            'attributes' => [
                'nombre' => 'Nombre completo',
                'email' => 'Dirección de correo electrónico',
                'password' => 'Contraseña',
                // Agrega más atributos según sea necesario
            ],
];
