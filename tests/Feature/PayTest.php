<?php

namespace Tests\Feature;

use App\Http\Soap\MainSoapServer;
use Tests\TestCase;

class PayTest extends TestCase
{
    private $customerId;
    private $payId;
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
        $this->valor = 7500.3;
    }

    /**
     * A basic feature test example.
     */
    public function test_create_ok_pay(): void
    {
        $response = $this->cast();

        $expected = [
            'success' => true,
            'cod_error' => '00',
            'message_error' => '',
            'data' => [
                'cliente' => [
                    'id' => $this->customerId,
                    'documento' => $this->documento,
                    'nombres' => $this->nombres,
                    'correo' => $this->correo,
                    'celular' => $this->celular
                ],
                'pago' => [
                    'id' => $this->payId,
                    'valor' => $this->valor
                ],
                'feedback' => 'Se ha enviado un correo más el id de sesión que debe ser usado en la confirmación de la compra'
            ]
        ];

        $this->assertEquals($expected, $response);
    }

    private function cast()
    {
        $data = (new MainSoapServer)->pagar($this->documento, $this->celular, $this->valor);
        $this->customerId = $data['data']['cliente']['id'] ?? null;
        $this->payId = $data['data']['pago']['id'] ?? null;
        return $data;
    }
}
