<?php

namespace App\Http\Soap;

use App\Mail\ConfirmPay;
use App\Models\Pay;
use App\Models\User;
use App\Models\Wallet;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

        $data = Pay::create([
            'userId' => $user->id,
            'token' => $token,
            'valor' => $valor
        ]);

        Mail::to($user->correo)->send(new ConfirmPay($data->id, $data->token));

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

    /**
     * Confirmar Pago
     *
     * @param int $id Identificador de sesión
     * @param int $token Código de confirmación
     *
     * @return array
     */
    public function confirmarPago(int $id, int $token): array
    {
        $success = false;
        $data = [];
        $cod_error = '00';
        $message_error = '';

        $validator = Validator::make(['id' => $id, 'token' => $token], [
            'id' => 'required|filled|max:36|exists:pays,id',
            'token' => 'required|max:6'
        ]);

        $pay = Pay::find($id)->where('token', $token);

        if ($validator->fails() || !$pay->exists()) {
            return [
                'success' => $success,
                'cod_error' => '400',
                'message_error' => 'El id de sesión o el token es requerido o no concuerda con los registros',
                'data' => $data
            ];
        }

        $pay = $pay->first();
        Wallet::create([
            'userId' => $pay->userId,
            'valor' => $pay->valor * -1
        ]);
        $user = User::find($pay->userId);
        $success = true;

        $balance = Wallet::select(DB::raw('SUM(valor) as balance'))->where('userId', $pay->userId)->first()->balance ?? 0;

        return [
            'success' => $success,
            'cod_error' => $cod_error,
            'message_error' => $message_error,
            'data' => [
                'cliente' => $user->only('id', 'documento', 'nombres', 'correo', 'celular'),
                'balance_billetera' => $balance
            ]
        ];
    }

    /**
     * Consultar Saldo
     *
     * @param string $documento Documento del cliente
     * @param string $celular Celular del cliente
     *
     * @return array
     */
    public function consultarSaldo(string $documento, string $celular): array
    {
        $success = false;
        $data = [];
        $cod_error = '00';
        $message_error = '';

        $validator = Validator::make(['documento' => $documento, 'celular' => $celular], [
            'documento' => 'required|filled|max:255',
            'celular' => 'required|filled|max:255',
        ]);

        $user = User::where([
            ['documento', $documento],
            ['celular', $celular]
        ])->first();

        if ($validator->fails()) {
            return [
                'success' => $success,
                'cod_error' => '400',
                'message_error' => 'Todos los campos son requeridos',
                'data' => $data
            ];
        } else if (is_null($user)) {
            return [
                'success' => $success,
                'cod_error' => '400',
                'message_error' => 'El cliente no está registrado',
                'data' => $data
            ];
        }

        $balance = Wallet::select(DB::raw('SUM(valor) as balance'))->where('userId', $user->id)->first()->balance ?? 0;
        $success = true;

        return [
            'success' => $success,
            'cod_error' => $cod_error,
            'message_error' => $message_error,
            'data' => [
                'cliente' => $user->only('id', 'documento', 'nombres', 'correo', 'celular'),
                'balance_billetera' => $balance
            ]
        ];
    }
}
