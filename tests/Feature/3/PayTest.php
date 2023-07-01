<?php

use App\Http\Soap\MainSoapServer;
use App\Models\Pay;
use Tests\TestCase;

class PayTest extends TestCase
{
    private $customerId;
    private $payId;
    private $documento;
    private $nombres;
    private $correo;
    private $celular;
    private $valorAAgregar;
    private $valorAPagar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documento = '123456789';
        $this->nombres = 'Lorem Ipsum';
        $this->correo = 'example@example.com';
        $this->celular = '987654321';
        $this->valorAAgregar = 100000;
        $this->valorAPagar = 23476.34;
    }

    /**
     * Register a pay.
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
                    'valor' => $this->valorAPagar
                ],
                'feedback' => 'Se ha enviado un correo más el id de sesión que debe ser usado en la confirmación de la compra'
            ]
        ];

        $this->assertEquals($expected, $response);

        $response = (new MainSoapServer)->confirmarPago($this->payId, Pay::find($this->payId)->token);

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
                'balance_billetera' => $this->valorAAgregar - $this->valorAPagar
            ]
        ];

        $this->assertEquals($expected, $response);
    }

    /**
     * Register a pay with incomplete data.
     */
    public function test_create_incomplete_data_pay(): void
    {
        $response = (new MainSoapServer)->pagar('', $this->celular, $this->valorAPagar);

        $expected = [
            'success' => false,
            'cod_error' => '400',
            'message_error' => 'Todos los campos son requeridos y el valor debe ser numérico',
            'data' => []
        ];

        $this->assertEquals($expected, $response);
    }

    private function cast()
    {
        $data = (new MainSoapServer)->pagar($this->documento, $this->celular, $this->valorAPagar);
        $this->customerId = $data['data']['cliente']['id'] ?? null;
        $this->payId = $data['data']['pago']['id'] ?? null;
        return $data;
    }
}
