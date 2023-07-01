<?php

namespace App\Http\Soap;

use App\Mail\ConfirmPay;
use App\Models\Pay;
use App\Models\User;
use App\Models\Wallet;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
                'data' => $data
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

    /**
     * Recargar billetera
     *
     * @param string $documento Documento del cliente
     * @param string $celular Celular del cliente
     * @param float $valor Valor a recargar
     *
     * @return array
     */
    public function recargaBilletera(string $documento, string $celular, float $valor): array
    {
        $success = false;
        $data = [];
        $cod_error = '00';
        $message_error = '';

        $validator = Validator::make(['documento' => $documento, 'celular' => $celular, 'valor' => $valor], [
            'documento' => 'required|filled|max:255',
            'celular' => 'required|filled|max:255',
            'valor' => 'required|numeric',
        ]);

        $user = User::where([
            ['documento', $documento],
            ['celular', $celular]
        ])->first();

        if ($validator->fails()) {
            return [
                'success' => $success,
                'cod_error' => '400',
                'message_error' => 'Todos los campos son requeridos y el valor debe ser numérico',
                'data' => $data
            ];
        } else if (is_null($user)) {
            return [
                'success' => $success,
                'cod_error' => '400',
                'message_error' => 'El cliente no está registrado',
                'data' => $data
            ];
        } else if ($valor === 0.0) {
            return [
                'success' => $success,
                'cod_error' => '400',
                'message_error' => 'El valor debe ser mayor a cero',
                'data' => $data
            ];
        }

        $data = Wallet::create([
            'userId' => $user->id,
            'valor' => $valor
        ]);
        $success = true;

        return [
            'success' => $success,
            'cod_error' => $cod_error,
            'message_error' => $message_error,
            'data' => [
                'cliente' => $user->only('id', 'documento', 'nombres', 'correo', 'celular'),
                'billetera' => $data->only('id', 'valor')
            ]
        ];
    }

    /**
     * Pagar
     *
     * @param string $documento Documento del cliente
     * @param string $celular Celular del cliente
     * @param float $valor Valor a descontar del saldo
     *
     * @return array
     */
    public function pagar(string $documento, string $celular, float $valor): array
    {
        $success = false;
        $data = [];
        $cod_error = '00';
        $message_error = '';

        $validator = Validator::make(['documento' => $documento, 'celular' => $celular, 'valor' => $valor], [
            'documento' => 'required|filled|max:255',
            'celular' => 'required|filled|max:255',
            'valor' => 'required|numeric',
        ]);

        $user = User::where([
            ['documento', $documento],
            ['celular', $celular]
        ])->first();

        if ($validator->fails()) {
            return [
                'success' => $success,
                'cod_error' => '400',
                'message_error' => 'Todos los campos son requeridos y el valor debe ser numérico',
                'data' => $data
            ];
        } else if (is_null($user)) {
            return [
                'success' => $success,
                'cod_error' => '400',
                'message_error' => 'El cliente no está registrado',
                'data' => $data
            ];
        } else if ($valor === 0.0) {
            return [
                'success' => $success,
                'cod_error' => '400',
                'message_error' => 'El valor debe ser mayor a cero',
                'data' => $data
            ];
        }

        $token = fake()->numberBetween(111111, 999999);
        $id = Str::uuid();

        $data = Pay::create([
            'id' => $id,
            'userId' => $user->id,
            'token' => $token,
            'valor' => $valor
        ]);

        Mail::to($user->correo)->send(new ConfirmPay($id, $data->token));

        $success = true;

        return [
            'success' => $success,
            'cod_error' => $cod_error,
            'message_error' => $message_error,
            'data' => [
                'cliente' => $user->only('id', 'documento', 'nombres', 'correo', 'celular'),
                'pago' => $data->only('id', 'valor'),
                'feedback' => 'Se ha enviado un correo más el id de sesión que debe ser usado en la confirmación de la compra'
            ]
        ];
    }
}
