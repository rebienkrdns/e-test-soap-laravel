<?php

namespace App\Http\Soap;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MainSoapServer
{
    /**
     * Registrar un cliente
     *
     * @param string $documento Documento del cliente
     * @param string $nombres Nombres del cliente
     * @param string $correo Correo electrónico del cliente
     * @param string $celular Celular del cliente
     *
     * @return array
     */
    public function registroCliente(string $documento, string $nombres, string $correo, string $celular): array
    {
        $success = false;
        $data = [];
        $cod_error = '00';
        $message_error = '';

        $validator = Validator::make(['documento' => $documento, 'nombres' => $nombres, 'correo' => $correo, 'celular' => $celular], [
            'documento' => 'required|filled|max:255',
            'nombres' => 'required|filled|max:255',
            'correo' => 'required|filled|max:255',
            'celular' => 'required|filled|max:255'
        ]);

        if ($validator->fails()) {
            return [
                'success' => $success,
                'cod_error' => '400',
                'message_error' => 'Todos los campos son requeridos',
                'data' => []
            ];
        }

        try {
            $data = User::create([
                'documento' => $documento,
                'nombres' => $nombres,
                'correo' => $correo,
                'celular' => $celular,
                'password' => Hash::make('clave')
            ]);
            $success = true;
        } catch (Exception $e) {
            $cod_error = '409';
            $message_error = 'Ya existe un cliente registrado con ese correo electrónico';
        }

        return [
            'success' => $success,
            'cod_error' => $cod_error,
            'message_error' => $message_error,
            'data' => empty($data) ? $data : $data->only('id', 'documento', 'nombres', 'correo', 'celular')
        ];
    }
}
