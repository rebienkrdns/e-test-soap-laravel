<?php

namespace Tests\Feature;

use App\Http\Soap\MainSoapServer;
use Tests\TestCase;

class WalletTest extends TestCase
{
    private $userId;
    private $walletId;
    private $documento;
    private $nombres;
    private $correo;
    private $celular;
    private $valor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documento = '123456789';
        $this->nombres = 'Lorem Ipsum';
        $this->correo = 'example@example.com';
        $this->celular = '987654321';
        $this->valor = 100000;
    }

    /**
     * Recharge wallet.
     */
    public function test_recharge_ok_wallet(): void
    {
        $response = $this->cast();

        $expected = [
            'success' => true,
            'cod_error' => '00',
            'message_error' => '',
            'data' => [
                'cliente' => [
                    'id' => $this->userId,
                    'documento' => $this->documento,
                    'nombres' => $this->nombres,
                    'correo' => $this->correo,
                    'celular' => $this->celular
                ],
                'billetera' => [
                    'id' => $this->walletId,
                    'valor' => 100000.0
                ]
            ]
        ];

        $this->assertEquals($expected, $response);
    }

    /**
     * Recharge wallet with zero.
     */
    public function test_recharge_with_zero_wallet(): void
    {
        $response = (new MainSoapServer)->recargaBilletera($this->documento, $this->celular, 0);

        $expected = [
            'success' => false,
            'cod_error' => '400',
            'message_error' => 'El valor debe ser mayor a cero',
            'data' => []
        ];

        $this->assertEquals($expected, $response);
    }

    /**
     * Recharge wallet with incomplete data.
     */
    public function test_recharge_with_incomplete_data_wallet(): void
    {
        $response = (new MainSoapServer)->recargaBilletera($this->documento, '', 0);

        $expected = [
            'success' => false,
            'cod_error' => '400',
            'message_error' => 'Todos los campos son requeridos y el valor debe ser numérico',
            'data' => []
        ];

        $this->assertEquals($expected, $response);
    }

    /**
     * Recharge wallet with wrong data.
     */
    public function test_recharge_with_wrong_data_wallet(): void
    {
        $response = (new MainSoapServer)->recargaBilletera($this->documento, '44', 0);

        $expected = [
            'success' => false,
            'cod_error' => '400',
            'message_error' => 'El cliente no está registrado',
            'data' => []
        ];

        $this->assertEquals($expected, $response);
    }

    private function cast()
    {
        $data = (new MainSoapServer)->recargaBilletera($this->documento, $this->celular, $this->valor);
        $this->userId = $data['data']['cliente']['id'] ?? null;
        $this->walletId = $data['data']['billetera']['id'] ?? null;
        return $data;
    }
}
